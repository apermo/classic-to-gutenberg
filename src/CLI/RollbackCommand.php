<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\Migration\MigrationRollback;
use WP_CLI;

/**
 * WP-CLI command: classic-to-gutenberg rollback.
 */
class RollbackCommand {

	/**
	 * Register the command with WP-CLI.
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( 'classic-to-gutenberg rollback', [ new self(), 'execute' ] );
	}

	/**
	 * Rollback a converted post to its pre-conversion content.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The post ID to rollback.
	 *
	 * ## EXAMPLES
	 *
	 *     wp classic-to-gutenberg rollback 42
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return void
	 */
	public function execute( array $args, array $assoc_args ): void {
		unset( $assoc_args );
		$post_id  = (int) $args[0];
		$rollback = new MigrationRollback();
		$result   = $rollback->rollback( $post_id );

		if ( $result->success ) {
			WP_CLI::success( sprintf( 'Post #%d rolled back successfully.', $post_id ) );
		} else {
			WP_CLI::error( sprintf( 'Rollback failed for post #%d: %s', $post_id, $result->error ) );
		}
	}
}
