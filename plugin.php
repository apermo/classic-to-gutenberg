<?php
/**
 * Plugin Name: Classic to Gutenberg
 * Description: Batch migration from classic editor content to Gutenberg blocks.
 * Version:     0.3.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: classic-to-gutenberg
 * Requires at least: 6.2
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

\defined( 'ABSPATH' ) || exit();

\define( 'CLASSIC_TO_GUTENBERG_VERSION', '0.3.0' );
\define( 'CLASSIC_TO_GUTENBERG_FILE', __FILE__ );
\define( 'CLASSIC_TO_GUTENBERG_DIR', plugin_dir_path( __FILE__ ) );

if ( ! \file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', 'Apermo\ClassicToGutenberg\ctg_missing_autoloader_notice' );
	return;
}

/**
 * Display admin notice when Composer autoloader is missing.
 *
 * @return void
 */
function ctg_missing_autoloader_notice(): void {
	$plugin_data = get_plugin_data( __FILE__ );
	\printf(
		'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
		esc_html( $plugin_data['Name'] ),
		\sprintf(
			/* translators: %s: composer install command */
			esc_html__( 'Please run %s to install the required dependencies.', 'classic-to-gutenberg' ),
			'<code>composer install</code>',
		),
	);
}

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init();
