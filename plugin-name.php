<?php
/**
 * Plugin Name: Plugin_Name
 * Description: A WordPress plugin.
 * Version:     0.1.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: plugin-name
 * Requires PHP: 8.1
 */

declare(strict_types=1);

namespace Plugin_Name;

defined( 'ABSPATH' ) || exit();

define( 'PLUGIN_NAME_VERSION', '0.1.0' );
define( 'PLUGIN_NAME_FILE', __FILE__ );
define( 'PLUGIN_NAME_DIR', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init();
