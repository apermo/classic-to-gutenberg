<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Admin;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\MigrationRunner;
use WP_Post;

/**
 * Adds row actions to the post list table for block conversion.
 */
class RowAction {

	/**
	 * The content converter.
	 *
	 * @var ContentConverter
	 */
	private ContentConverter $converter;

	/**
	 * Create a new row action handler.
	 *
	 * @param ContentConverter $converter The content converter.
	 */
	public function __construct( ContentConverter $converter ) {
		$this->converter = $converter;

		add_filter( 'post_row_actions', [ $this, 'add_row_actions' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_row_actions' ], 10, 2 );
		add_action( 'admin_post_ctg_convert', [ $this, 'handle_convert' ] );
		add_action( 'admin_post_ctg_preview', [ $this, 'handle_preview' ] );

		$this->register_bulk_actions();
	}

	/**
	 * Register the bulk action on all public post type list tables.
	 *
	 * @return void
	 */
	public function register_bulk_actions(): void {
		foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
			$screen = 'edit-' . $post_type;
			add_filter( 'bulk_actions-' . $screen, [ $this, 'add_bulk_action' ], 5 );
			add_filter( 'handle_bulk_actions-' . $screen, [ $this, 'handle_bulk_convert' ], 10, 3 );
		}

		add_action( 'admin_footer', [ $this, 'disable_bulk_header' ] );
	}

	/**
	 * Disable the bulk action header option via inline script.
	 *
	 * @return void
	 */
	public function disable_bulk_header(): void {
		$screen = get_current_screen();
		if ( $screen === null || $screen->base !== 'edit' ) {
			return;
		}
		?>
		<script>document.querySelectorAll('option[value="ctg_header"]').forEach(function(o){o.disabled=true});</script>
		<?php
	}

	/**
	 * Add conversion row actions to the post list.
	 *
	 * @param string[] $actions Existing row actions.
	 * @param WP_Post  $post    The post object.
	 *
	 * @return string[]
	 */
	public function add_row_actions( array $actions, WP_Post $post ): array {
		if ( $this->has_blocks( $post ) ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$convert_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=ctg_convert&post_id=' . $post->ID ),
			'ctg_convert_' . $post->ID,
		);

		$preview_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=ctg_preview&post_id=' . $post->ID ),
			'ctg_preview_' . $post->ID,
		);

		$actions['ctg_convert'] = \sprintf(
			'<a href="%s">%s</a>',
			esc_url( $convert_url ),
			esc_html__( 'Convert to Blocks', 'classic-to-gutenberg' ),
		);

		$actions['ctg_preview'] = \sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( $preview_url ),
			esc_html__( 'Preview Blocks', 'classic-to-gutenberg' ),
		);

		return $actions;
	}

	/**
	 * Handle the convert action.
	 *
	 * @return void
	 */
	public function handle_convert(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below

		check_admin_referer( 'ctg_convert_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to convert this post.', 'classic-to-gutenberg' ) );
		}

		$runner = new MigrationRunner( $this->converter );
		$result = $runner->convert_post( $post_id );

		$redirect = wp_get_referer();
		if ( $redirect === false ) {
			$post     = get_post( $post_id );
			$redirect = admin_url( 'edit.php?post_type=' . ( $post->post_type ?? 'post' ) );
		}

		$transient_key = 'ctg_notice_' . get_current_user_id();
		if ( $result->success ) {
			set_transient(
				$transient_key,
				[
					'type' => 'converted',
					'post_ids' => [ $post_id ],
				],
				30,
			);
		} else {
			set_transient(
				$transient_key,
				[
					'type' => 'error',
					'message' => $result->error,
				],
				30,
			);
		}

		wp_safe_redirect( $redirect );
		exit();
	}

	/**
	 * Add "Convert to Blocks" to the bulk actions dropdown.
	 *
	 * @param string[] $actions Existing bulk actions.
	 *
	 * @return string[]
	 */
	public function add_bulk_action( array $actions ): array {
		$actions['ctg_header']       = '↓ ' . __( 'Classic to Gutenberg', 'classic-to-gutenberg' );
		$actions['ctg_bulk_convert'] = __( 'Convert to Blocks', 'classic-to-gutenberg' );
		return $actions;
	}

	/**
	 * Handle the bulk convert action.
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $action       The bulk action name.
	 * @param int[]  $post_ids     Selected post IDs.
	 *
	 * @return string
	 */
	public function handle_bulk_convert( string $redirect_url, string $action, array $post_ids ): string {
		if ( $action !== 'ctg_bulk_convert' ) {
			return $redirect_url;
		}

		$runner        = new MigrationRunner( $this->converter );
		$converted_ids = [];
		$failed        = 0;

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$failed++;
				continue;
			}

			$result = $runner->convert_post( $post_id );
			if ( $result->success ) {
				$converted_ids[] = $post_id;
			} else {
				$failed++;
			}
		}

		$transient_key = 'ctg_notice_' . get_current_user_id();
		if ( $converted_ids !== [] ) {
			set_transient(
				$transient_key,
				[
					'type'     => 'converted',
					'post_ids' => $converted_ids,
				],
				30,
			);
		} elseif ( $failed > 0 ) {
			set_transient(
				$transient_key,
				[
					'type'    => 'error',
					'message' => \sprintf(
						/* translators: %d: number of failed posts */
						__( '%d post(s) failed to convert.', 'classic-to-gutenberg' ),
						$failed,
					),
				],
				30,
			);
		}

		return $redirect_url;
	}

	/**
	 * Handle the preview action.
	 *
	 * @return void
	 */
	public function handle_preview(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below

		check_admin_referer( 'ctg_preview_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to preview this post.', 'classic-to-gutenberg' ) );
		}

		$runner = new MigrationRunner( $this->converter );
		$result = $runner->convert_post( $post_id, true );

		if ( ! $result->success ) {
			wp_die( esc_html( $result->error ) );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup preview
		echo '<html><head><title>Block Preview — Post #' . esc_html( (string) $post_id ) . '</title></head><body>';
		echo '<h1>' . esc_html__( 'Block Preview', 'classic-to-gutenberg' ) . '</h1>';
		echo '<pre style="white-space: pre-wrap; word-wrap: break-word;">';
		echo esc_html( $result->converted );
		echo '</pre></body></html>';
		exit();
	}

	/**
	 * Check whether a post already contains block markup.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return bool
	 */
	private function has_blocks( WP_Post $post ): bool {
		return \str_contains( $post->post_content, '<!-- wp:' );
	}
}
