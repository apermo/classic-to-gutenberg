<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Migration;

use Apermo\ClassicToGutenberg\ContentConverter;

/**
 * Runs the migration for individual posts or batches.
 */
class MigrationRunner {

	/**
	 * The content converter.
	 *
	 * @var ContentConverter
	 */
	private ContentConverter $converter;

	/**
	 * Create a new migration runner.
	 *
	 * @param ContentConverter $converter The content converter.
	 */
	public function __construct( ContentConverter $converter ) {
		$this->converter = $converter;
	}

	/**
	 * Convert a single post.
	 *
	 * Checks post lock, acquires lock during conversion, and releases after.
	 *
	 * @param int  $post_id The post ID.
	 * @param bool $dry_run If true, return the result without saving.
	 *
	 * @return MigrationResult
	 */
	public function convert_post( int $post_id, bool $dry_run = false ): MigrationResult {
		$post = get_post( $post_id );

		if ( $post === null ) {
			return new MigrationResult( $post_id, false, '', '', 'Post not found.' );
		}

		if ( ! $dry_run && wp_check_post_lock( $post_id ) ) {
			return new MigrationResult( $post_id, false, '', '', 'Post is locked by another user.' );
		}

		$original  = $post->post_content;
		$converted = $this->converter->convert( $original );

		if ( $dry_run ) {
			return new MigrationResult( $post_id, true, $original, $converted );
		}

		wp_set_post_lock( $post_id );

		$revision_id = wp_save_post_revision( $post_id );

		$updated = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $converted,
			],
			true,
		);

		// Unlock the post. There is no dedicated unlock function.
		update_post_meta( $post_id, '_edit_lock', false );

		if ( is_wp_error( $updated ) ) {
			return new MigrationResult( $post_id, false, $original, $converted, $updated->get_error_message() );
		}

		if ( \is_int( $revision_id ) && $revision_id > 0 ) {
			update_post_meta( $post_id, '_ctg_revision_id', $revision_id );
		}

		/**
		 * Fires after a post has been converted to blocks.
		 *
		 * @since 0.1.0
		 *
		 * @param int             $post_id The post ID.
		 * @param MigrationResult $result  The migration result.
		 */
		do_action( 'classic_to_gutenberg_post_converted', $post_id, new MigrationResult( $post_id, true, $original, $converted ) );

		return new MigrationResult( $post_id, true, $original, $converted );
	}

	/**
	 * Convert a batch of posts.
	 *
	 * @param int[] $post_ids Post IDs to convert.
	 * @param bool  $dry_run  If true, return results without saving.
	 *
	 * @return MigrationResult[]
	 */
	public function convert_batch( array $post_ids, bool $dry_run = false ): array {
		/**
		 * Fires before a batch conversion starts.
		 *
		 * @since 0.1.0
		 *
		 * @param int[] $post_ids The post IDs.
		 * @param bool  $dry_run  Whether this is a dry run.
		 */
		do_action( 'classic_to_gutenberg_batch_started', $post_ids, $dry_run );

		$results = [];
		foreach ( $post_ids as $post_id ) {
			$results[] = $this->convert_post( $post_id, $dry_run );
		}

		/**
		 * Fires after a batch conversion completes.
		 *
		 * @since 0.1.0
		 *
		 * @param MigrationResult[] $results The migration results.
		 * @param bool              $dry_run Whether this was a dry run.
		 */
		do_action( 'classic_to_gutenberg_batch_completed', $results, $dry_run );

		return $results;
	}
}
