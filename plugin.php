<?php
/**
 * Plugin Name: Classic to Gutenberg
 * Description: Batch migration from classic editor content to Gutenberg blocks.
 * Version:     0.1.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: classic-to-gutenberg
 * Requires at least: 6.2
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

defined( 'ABSPATH' ) || exit();

define( 'CLASSIC_TO_GUTENBERG_VERSION', '0.1.0' );
define( 'CLASSIC_TO_GUTENBERG_FILE', __FILE__ );
define( 'CLASSIC_TO_GUTENBERG_DIR', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init();
