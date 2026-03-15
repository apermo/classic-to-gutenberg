<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use Apermo\ClassicToGutenberg\Migration\MigrationRunner;
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
	 *     wp classic-to-gutenberg convert --post-type=post --batch-size=100
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function execute( array $args, array $assoc_args ): void {
		$dry_run    = Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$batch_size = (int) ( $assoc_args['batch-size'] ?? 50 );
		$finder     = new ClassicPostFinder();
		$runner     = new MigrationRunner( $this->converter );
		$query_args = [ 'limit' => $batch_size ];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = explode( ',', $assoc_args['post-type'] );
		}

		$total = $finder->count( $query_args );

		if ( $total === 0 ) {
			WP_CLI::success( 'No classic posts found.' );
			return;
		}

		$mode = $dry_run ? 'Dry run' : 'Converting';
		WP_CLI::log( sprintf( '%s %d classic post(s)...', $mode, $total ) );

		$offset    = 0;
		$converted = 0;
		$failed    = 0;

		while ( true ) {
			$query_args['offset'] = $offset;
			$post_ids             = $finder->find( $query_args );

			if ( $post_ids === [] ) {
				break;
			}

			$results = $runner->convert_batch( $post_ids, (bool) $dry_run );

			foreach ( $results as $result ) {
				if ( $result->success ) {
					++$converted;
					WP_CLI::log( sprintf( '  [OK] Post #%d', $result->post_id ) );
				} else {
					++$failed;
					WP_CLI::warning( sprintf( '  [FAIL] Post #%d: %s', $result->post_id, $result->error ) );
				}
			}

			$offset += $batch_size;
		}

		$prefix = $dry_run ? 'Would convert' : 'Converted';
		WP_CLI::success( sprintf( '%s %d post(s), %d failed.', $prefix, $converted, $failed ) );
	}
}
