<?php

declare(strict_types=1);

namespace Plugin_Name;

/**
 * Main plugin class.
 */
class Plugin {

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		register_activation_hook( PLUGIN_NAME_FILE, [ self::class, 'activate' ] );
		register_deactivation_hook( PLUGIN_NAME_FILE, [ self::class, 'deactivate' ] );
		add_action( 'plugins_loaded', [ self::class, 'boot' ] );
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Activation logic.
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Deactivation logic.
	}

	/**
	 * Boot the plugin after all plugins are loaded.
	 *
	 * @return void
	 */
	public static function boot(): void {
		// Initialize plugin functionality.
	}
}
