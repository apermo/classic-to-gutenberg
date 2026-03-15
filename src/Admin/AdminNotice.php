<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Admin;

/**
 * Displays admin notices for conversion actions.
 */
class AdminNotice {

	/**
	 * Create a new admin notice handler.
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'display_notices' ] );
	}

	/**
	 * Display admin notices from conversion actions.
	 *
	 * @return void
	 */
	public function display_notices(): void {
		$user_id = get_current_user_id();
		$notice  = get_transient( 'ctg_notice_' . $user_id );

		if ( ! \is_array( $notice ) || ! isset( $notice['type'] ) ) {
			return;
		}

		delete_transient( 'ctg_notice_' . $user_id );

		if ( $notice['type'] === 'converted' ) {
			$this->display_success_notice( (array) ( $notice['post_ids'] ?? [] ) );
			return;
		}

		if ( $notice['type'] === 'error' ) {
			$this->display_error_notice( (string) ( $notice['message'] ?? '' ) );
		}
	}

	/**
	 * Display success notice with post links.
	 *
	 * @param int[] $post_ids Converted post IDs.
	 *
	 * @return void
	 */
	private function display_success_notice( array $post_ids ): void {
		echo '<div class="notice notice-success is-dismissible">';
		echo '<p>' . esc_html__( 'Post successfully converted to blocks.', 'classic-to-gutenberg' ) . '</p>';

		if ( \count( $post_ids ) === 1 ) {
			$this->render_single_post_link( $post_ids[0] );
		} elseif ( \count( $post_ids ) > 1 ) {
			$this->render_post_list( $post_ids );
		}

		echo '</div>';
	}

	/**
	 * Render a single post link.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	private function render_single_post_link( int $post_id ): void {
		$post = get_post( $post_id );
		if ( $post === null ) {
			return;
		}

		\printf(
			'<p><a href="%s">%s</a></p>',
			esc_url( get_edit_post_link( $post_id ) ?? '' ),
			\sprintf(
				/* translators: %s: post title */
				esc_html__( 'Open "%s"', 'classic-to-gutenberg' ),
				esc_html( get_the_title( $post ) ),
			),
		);
	}

	/**
	 * Render a list of post links.
	 *
	 * @param int[] $post_ids The post IDs.
	 *
	 * @return void
	 */
	private function render_post_list( array $post_ids ): void {
		echo '<ul>';
		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post === null ) {
				continue;
			}

			\printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( get_edit_post_link( $post_id ) ?? '' ),
				esc_html( get_the_title( $post ) ),
			);
		}
		echo '</ul>';
	}

	/**
	 * Display error notice.
	 *
	 * @param string $message The error message.
	 *
	 * @return void
	 */
	private function display_error_notice( string $message ): void {
		\printf(
			'<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
			esc_html__( 'Conversion failed:', 'classic-to-gutenberg' ),
			esc_html( $message ),
		);
	}
}
