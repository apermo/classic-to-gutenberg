<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use Apermo\ClassicToGutenberg\Migration\MigrationRunner;
use Apermo\ClassicToGutenberg\Permission;
use WP_CLI;
use WP_CLI\Utils;
/**
 * WP-CLI command: classic-to-gutenberg convert.
 */
class ConvertCommand {

	/**
	 * The content converter.
	 *
	 * @var ContentConverter
	 */
	private ContentConverter $converter;

	/**
	 * Create a new convert command.
	 *
	 * @param ContentConverter $converter The content converter.
	 */
	public function __construct( ContentConverter $converter ) {
		$this->converter = $converter;
	}

	/**
	 * Register the command with WP-CLI.
	 *
	 * @param ContentConverter $converter The content converter.
	 *
	 * @return void
	 */
	public static function register( ContentConverter $converter ): void {
		WP_CLI::add_command( 'classic-to-gutenberg convert', [ new self( $converter ), 'execute' ] );
	}

	/**
	 * Convert classic posts to Gutenberg blocks.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more post IDs to convert. Comma-separated or space-separated.
	 *
	 * [--dry-run]
	 * : Preview conversion without saving changes.
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types. Default: post,page.
	 *
	 * [--batch-size=<number>]
	 * : Number of posts per batch. Default: 50.
	 *
	 * ## EXAMPLES
	 *
	 *     wp classic-to-gutenberg convert --dry-run
	 *     wp classic-to-gutenberg convert 42
	 *     wp classic-to-gutenberg convert 42 43 44
	 *     wp classic-to-gutenberg convert 42,43,44 --dry-run
	 *     wp classic-to-gutenberg convert --post-type=post --batch-size=100
	 *
	 * @param string[]             $args       Positional arguments (post IDs).
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function execute( array $args, array $assoc_args ): void {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			WP_CLI::error( 'No user set. Use --user=<id|login|email> to specify which user runs the conversion.' );
		}

		if ( ! Permission::user_can_convert( $user ) ) {
			WP_CLI::error( \sprintf( 'User "%s" does not have sufficient permissions.', $user->user_login ) );
		}

		WP_CLI::log( \sprintf( 'Running as: %s (#%d)', $user->user_login, $user->ID ) );

		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$post_ids = $this->parse_post_ids( $args );

		if ( $post_ids !== [] ) {
			$this->convert_by_ids( $post_ids, $dry_run );
			return;
		}

		$this->convert_by_query( $assoc_args, $dry_run );
	}

	/**
	 * Parse post IDs from positional arguments.
	 *
	 * Supports space-separated and comma-separated IDs.
	 *
	 * @param string[] $args Positional arguments.
	 *
	 * @return int[]
	 */
	private function parse_post_ids( array $args ): array {
		if ( $args === [] ) {
			return [];
		}

		$post_ids = [];
		foreach ( $args as $argument ) {
			foreach ( \explode( ',', $argument ) as $part ) {
				$part = \trim( $part );
				if ( $part !== '' && \ctype_digit( $part ) ) {
					$post_ids[] = (int) $part;
				}
			}
		}

		return $post_ids;
	}

	/**
	 * Convert specific posts by their IDs.
	 *
	 * @param int[] $post_ids Post IDs to convert.
	 * @param bool  $dry_run  Whether to preview only.
	 *
	 * @return void
	 */
	private function convert_by_ids( array $post_ids, bool $dry_run ): void {
		$runner = new MigrationRunner( $this->converter );
		$mode   = $dry_run ? 'Dry run' : 'Converting';

		WP_CLI::log( \sprintf( '%s %d post(s)...', $mode, \count( $post_ids ) ) );

		$results   = $runner->convert_batch( $post_ids, $dry_run );
		$converted = 0;
		$failed    = 0;

		foreach ( $results as $result ) {
			if ( $result->success ) {
				$converted++;
				WP_CLI::log( \sprintf( '  [OK] Post #%d', $result->post_id ) );
			} else {
				$failed++;
				WP_CLI::warning( \sprintf( '  [FAIL] Post #%d: %s', $result->post_id, $result->error ) );
			}
		}

		$prefix = $dry_run ? 'Would convert' : 'Converted';
		WP_CLI::success( \sprintf( '%s %d post(s), %d failed.', $prefix, $converted, $failed ) );
	}

	/**
	 * Convert posts found by query.
	 *
	 * @param array<string,string> $assoc_args Associative arguments.
	 * @param bool                 $dry_run    Whether to preview only.
	 *
	 * @return void
	 */
	private function convert_by_query( array $assoc_args, bool $dry_run ): void {
		$batch_size = (int) ( $assoc_args['batch-size'] ?? 50 );
		$finder     = new ClassicPostFinder();
		$runner     = new MigrationRunner( $this->converter );
		$query_args = [ 'limit' => $batch_size ];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = \explode( ',', $assoc_args['post-type'] );
		}

		$total = $finder->count( $query_args );

		if ( $total === 0 ) {
			WP_CLI::success( 'No classic posts found.' );
			return;
		}

		$mode = $dry_run ? 'Dry run' : 'Converting';
		WP_CLI::log( \sprintf( '%s %d classic post(s)...', $mode, $total ) );

		$offset    = 0;
		$converted = 0;
		$failed    = 0;

		while ( true ) {
			$query_args['offset'] = $offset;
			$post_ids             = $finder->find( $query_args );

			if ( $post_ids === [] ) {
				break;
			}

			$results = $runner->convert_batch( $post_ids, $dry_run );

			foreach ( $results as $result ) {
				if ( $result->success ) {
					$converted++;
					WP_CLI::log( \sprintf( '  [OK] Post #%d', $result->post_id ) );
				} else {
					$failed++;
					WP_CLI::warning( \sprintf( '  [FAIL] Post #%d: %s', $result->post_id, $result->error ) );
				}
			}

			$offset += $batch_size;
		}

		$prefix = $dry_run ? 'Would convert' : 'Converted';
		WP_CLI::success( \sprintf( '%s %d post(s), %d failed.', $prefix, $converted, $failed ) );
	}
}
