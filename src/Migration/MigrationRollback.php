<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Migration;

/**
 * Handles rollback of converted posts to their pre-conversion state.
 */
class MigrationRollback {

	/**
	 * Rollback a post to its pre-conversion content.
	 *
	 * @param int $post_id The post ID to rollback.
	 *
	 * @return MigrationResult
	 */
	public function rollback( int $post_id ): MigrationResult {
		$revision_id = (int) get_post_meta( $post_id, '_ctg_revision_id', true );

		if ( $revision_id === 0 ) {
			return new MigrationResult( $post_id, false, '', '', 'No conversion revision found.' );
		}

		$revision = get_post( $revision_id );

		if ( $revision === null ) {
			return new MigrationResult( $post_id, false, '', '', 'Revision not found.' );
		}

		$post = get_post( $post_id );

		if ( $post === null ) {
			return new MigrationResult( $post_id, false, '', '', 'Post not found.' );
		}

		$current  = $post->post_content;
		$original = $revision->post_content;

		$updated = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $original,
			],
			true,
		);

		if ( is_wp_error( $updated ) ) {
			return new MigrationResult( $post_id, false, $current, '', $updated->get_error_message() );
		}

		delete_post_meta( $post_id, '_ctg_revision_id' );

		/**
		 * Fires after a post has been rolled back.
		 *
		 * @since 0.1.0
		 *
		 * @param int    $post_id  The post ID.
		 * @param string $original The restored content.
		 */
		do_action( 'classic_to_gutenberg_post_rolled_back', $post_id, $original );

		return new MigrationResult( $post_id, true, $current, $original );
	}
}
