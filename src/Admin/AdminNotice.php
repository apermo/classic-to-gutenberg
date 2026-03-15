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
		\add_action( 'admin_notices', [ $this, 'display_notices' ] );
	}

	/**
	 * Display admin notices from conversion actions.
	 *
	 * @return void
	 */
	public function display_notices(): void {
		$user_id = \get_current_user_id();
		$notice  = \get_transient( 'ctg_notice_' . $user_id );

		if ( $notice === false ) {
			return;
		}

		\delete_transient( 'ctg_notice_' . $user_id );

		if ( $notice === 'converted' ) {
			\printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				\esc_html__( 'Post successfully converted to blocks.', 'classic-to-gutenberg' ),
			);
			return;
		}

		if ( \str_starts_with( (string) $notice, 'error:' ) ) {
			$error_message = \substr( (string) $notice, 6 );
			\printf(
				'<div class="notice notice-error is-dismissible"><p>%s %s</p></div>',
				\esc_html__( 'Conversion failed:', 'classic-to-gutenberg' ),
				\esc_html( $error_message ),
			);
		}
	}
}
