<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Admin;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Migration\MigrationRunner;
use Apermo\ClassicToGutenberg\Permission;
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

		if ( ! $this->current_user_can_convert() ) {
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

		if ( ! $this->current_user_can_convert() ) {
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
		if ( $action !== 'ctg_bulk_convert' || ! $this->current_user_can_convert() ) {
			return $redirect_url;
		}

		$runner        = new MigrationRunner( $this->converter );
		$converted_ids = [];
		$failed        = 0;

		foreach ( $post_ids as $post_id ) {
			$result = $runner->convert_post( $post_id );
			if ( $result->success ) {
				$converted_ids[] = $post_id;
			} else {
				$failed++;
			}
		}

		$transient_key = 'ctg_notice_' . get_current_user_id();
		set_transient(
			$transient_key,
			[
				'type'     => 'converted',
				'post_ids' => $converted_ids,
				'failed'   => $failed,
			],
			30,
		);

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

		if ( ! $this->current_user_can_convert() ) {
			wp_die( esc_html__( 'You do not have permission to preview this post.', 'classic-to-gutenberg' ) );
		}

		$runner = new MigrationRunner( $this->converter );
		$result = $runner->convert_post( $post_id, true );

		if ( ! $result->success ) {
			wp_die( esc_html( $result->error ) );
		}

		$post  = get_post( $post_id );
		$title = $post !== null ? get_the_title( $post ) : '#' . $post_id;

		$this->render_preview_page( $title, $result->original, $result->converted );
		exit();
	}

	/**
	 * Render the side-by-side preview page.
	 *
	 * @param string $title     The post title.
	 * @param string $original  The original classic content.
	 * @param string $converted The converted block markup.
	 *
	 * @return void
	 *
	 * phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength -- HTML template, splitting hurts readability
	 */
	private function render_preview_page( string $title, string $original, string $converted ): void {
		$label_original  = esc_html__( 'Original (Classic)', 'classic-to-gutenberg' );
		$label_converted = esc_html__( 'Converted (Blocks)', 'classic-to-gutenberg' );
		$label_rendered  = esc_html__( 'Rendered', 'classic-to-gutenberg' );
		$label_markup    = esc_html__( 'Markup', 'classic-to-gutenberg' );

		// Apply wpautop to original for fair rendered comparison.
		$original_rendered = wpautop( $original );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- controlled preview output
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $title ); ?> — <?php esc_html_e( 'Block Preview', 'classic-to-gutenberg' ); ?></title>
			<style>
				* { box-sizing: border-box; margin: 0; padding: 0; }
				body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif; padding: 20px; background: #f0f0f1; }
				h1 { margin-bottom: 20px; font-size: 1.4em; color: #1d2327; }
				.ctg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
				.ctg-panel { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; overflow: hidden; }
				.ctg-panel-header { background: #f6f7f7; border-bottom: 1px solid #c3c4c7; padding: 10px 16px; display: flex; justify-content: space-between; }
				.ctg-panel-header strong { color: #1d2327; }
				.ctg-panel-header span { color: #646970; font-size: 0.9em; }
				.ctg-panel-body { padding: 16px; }
				.ctg-panel-body.ctg-rendered { font-size: 16px; line-height: 1.6; color: #1d2327; }
				.ctg-panel-body.ctg-rendered img { max-width: 100%; height: auto; }
				.ctg-panel-body.ctg-markup { background: #f6f7f7; }
				.ctg-panel-body pre { white-space: pre-wrap; word-wrap: break-word; font-family: Menlo, Consolas, Monaco, monospace; font-size: 12px; line-height: 1.5; color: #2c3338; }
				.ctg-section { margin-bottom: 24px; }
				.ctg-section-title { font-size: 1.1em; color: #646970; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
			</style>
		</head>
		<body>
			<h1><?php echo esc_html( $title ); ?></h1>

			<div class="ctg-section">
				<div class="ctg-section-title"><?php echo esc_html( $label_rendered ); ?></div>
				<div class="ctg-grid">
					<div class="ctg-panel">
						<div class="ctg-panel-header"><strong><?php echo esc_html( $label_original ); ?></strong></div>
						<div class="ctg-panel-body ctg-rendered"><?php echo $original_rendered; ?></div>
					</div>
					<div class="ctg-panel">
						<div class="ctg-panel-header"><strong><?php echo esc_html( $label_converted ); ?></strong></div>
						<div class="ctg-panel-body ctg-rendered"><?php echo $converted; ?></div>
					</div>
				</div>
			</div>

			<div class="ctg-section">
				<div class="ctg-section-title"><?php echo esc_html( $label_markup ); ?></div>
				<div class="ctg-grid">
					<div class="ctg-panel">
						<div class="ctg-panel-header"><strong><?php echo esc_html( $label_original ); ?></strong></div>
						<div class="ctg-panel-body ctg-markup"><pre><?php echo esc_html( $original ); ?></pre></div>
					</div>
					<div class="ctg-panel">
						<div class="ctg-panel-header"><strong><?php echo esc_html( $label_converted ); ?></strong></div>
						<div class="ctg-panel-body ctg-markup"><pre><?php echo esc_html( $converted ); ?></pre></div>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Check whether the current user has permission to convert posts.
	 *
	 * @return bool
	 */
	private function current_user_can_convert(): bool {
		return Permission::user_can_convert();
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
