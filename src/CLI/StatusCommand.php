<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;
use WP_CLI;

/**
 * WP-CLI command: classic-to-gutenberg status.
 */
class StatusCommand {

	/**
	 * Register the command with WP-CLI.
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( 'classic-to-gutenberg status', [ new self(), 'execute' ] );
	}

	/**
	 * Show the number of classic posts that can be converted.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types. Default: post,page.
	 *
	 * ## EXAMPLES
	 *
	 *     wp classic-to-gutenberg status
	 *     wp classic-to-gutenberg status --post-type=post,page,custom
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function execute( array $args, array $assoc_args ): void {
		$finder     = new ClassicPostFinder();
		$query_args = [];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = \explode( ',', $assoc_args['post-type'] );
		}

		$total = $finder->count( $query_args );

		if ( $total === 0 ) {
			WP_CLI::success( 'No classic posts found. All content uses blocks.' );
			return;
		}

		WP_CLI::log( \sprintf( 'Found %d classic post(s) without block markup.', $total ) );
	}
}
