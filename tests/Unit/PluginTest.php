<?php

declare(strict_types=1);

namespace Plugin_Name\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Plugin_Name\Plugin;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'PLUGIN_NAME_FILE' ) ) {
			define( 'PLUGIN_NAME_FILE', '/tmp/plugin.php' );
		}
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify init registers activation and deactivation hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_activation_hooks(): void {
		Functions\expect( 'register_activation_hook' )
			->once()
			->with( PLUGIN_NAME_FILE, [ Plugin::class, 'activate' ] );

		Functions\expect( 'register_deactivation_hook' )
			->once()
			->with( PLUGIN_NAME_FILE, [ Plugin::class, 'deactivate' ] );

		Functions\expect( 'add_action' )
			->once()
			->with( 'plugins_loaded', [ Plugin::class, 'boot' ] );

		Plugin::init();
	}

	/**
	 * Verify activate can be called without error.
	 *
	 * @return void
	 */
	public function test_activate(): void {
		Plugin::activate();
		$this->assertTrue( true );
	}

	/**
	 * Verify deactivate can be called without error.
	 *
	 * @return void
	 */
	public function test_deactivate(): void {
		Plugin::deactivate();
		$this->assertTrue( true );
	}

	/**
	 * Verify boot can be called without error.
	 *
	 * @return void
	 */
	public function test_boot(): void {
		Plugin::boot();
		$this->assertTrue( true );
	}
}
