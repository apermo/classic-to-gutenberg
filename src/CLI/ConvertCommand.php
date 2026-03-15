<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use Apermo\ClassicToGutenberg\Migration\MigrationResult;
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
	 * Conversion statistics.
	 *
	 * @var array{converted: int, failed: int, locked: int}
	 */
	private array $stats = [
		'converted' => 0,
		'failed'    => 0,
		'locked'    => 0,
	];

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
	 * ## EXAMPLES
	 *
	 *     wp classic-to-gutenberg convert --dry-run
	 *     wp classic-to-gutenberg convert 42
	 *     wp classic-to-gutenberg convert 42 43 44
	 *     wp classic-to-gutenberg convert 42,43,44 --dry-run
	 *     wp classic-to-gutenberg convert --post-type=post,page
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

		// Disable output buffering for responsive CLI output.
		while ( \ob_get_level() > 0 ) {
			\ob_end_flush();
		}

		WP_CLI::log( \sprintf( 'Running as: %s (#%d)', $user->user_login, $user->ID ) );

		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$post_ids = $this->parse_post_ids( $args );

		if ( $post_ids === [] ) {
			$post_ids = $this->find_classic_posts( $assoc_args );
		}

		if ( $post_ids === [] ) {
			WP_CLI::success( 'No classic posts without block markup found.' );
			return;
		}

		$this->run_conversion( $post_ids, $dry_run );

		$prefix = $dry_run ? 'Would convert' : 'Converted';
		WP_CLI::success(
			\sprintf(
				'%s %d, failed %d, locked %d.',
				$prefix,
				$this->stats['converted'],
				$this->stats['failed'],
				$this->stats['locked'],
			),
		);
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
	 * Find classic posts via query.
	 *
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return int[]
	 */
	private function find_classic_posts( array $assoc_args ): array {
		$finder     = new ClassicPostFinder();
		$query_args = [ 'limit' => 0 ];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = \explode( ',', $assoc_args['post-type'] );
		}

		return $finder->find( $query_args );
	}

	/**
	 * Run conversion on post IDs with progress bar.
	 *
	 * @param int[] $post_ids Post IDs to convert.
	 * @param bool  $dry_run  Whether to preview only.
	 *
	 * @return void
	 */
	private function run_conversion( array $post_ids, bool $dry_run ): void {
		$total    = \count( $post_ids );
		$runner   = new MigrationRunner( $this->converter );
		$progress = Utils\make_progress_bar( $this->get_progress_label( $dry_run ), $total );

		foreach ( $post_ids as $post_id ) {
			$result = $runner->convert_post( $post_id, $dry_run );
			$this->track_result( $result );
			$progress->tick( 1, $this->get_tick_message( $total ) );
		}

		$progress->finish();
	}

	/**
	 * Track a migration result in statistics.
	 *
	 * @param MigrationResult $result The result.
	 *
	 * @return void
	 */
	private function track_result( MigrationResult $result ): void {
		if ( $result->success ) {
			$this->stats['converted']++;
			return;
		}

		if ( \str_contains( $result->error, 'locked' ) ) {
			$this->stats['locked']++;
		} else {
			$this->stats['failed']++;
		}
	}

	/**
	 * Get progress bar label.
	 *
	 * @param bool $dry_run Whether this is a dry run.
	 *
	 * @return string
	 */
	private function get_progress_label( bool $dry_run ): string {
		return $dry_run ? 'Dry run' : 'Converting';
	}

	/**
	 * Get tick message with current statistics.
	 *
	 * @param int $total Total number of posts.
	 *
	 * @return string
	 */
	private function get_tick_message( int $total ): string {
		$processed = $this->stats['converted'] + $this->stats['failed'] + $this->stats['locked'];

		return \sprintf(
			'%d/%d — OK %d / Failed %d / Locked %d',
			$processed,
			$total,
			$this->stats['converted'],
			$this->stats['failed'],
			$this->stats['locked'],
		);
	}
}
