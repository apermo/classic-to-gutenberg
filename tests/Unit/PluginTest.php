<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit;

use Apermo\ClassicToGutenberg\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Plugin class.
 */
class PluginTest extends TestCase {

	/**
	 * Verify the Plugin class exists and has an init method.
	 *
	 * @return void
	 */
	public function test_init_method_exists(): void {
		$this->assertTrue( \method_exists( Plugin::class, 'init' ) );
	}

	/**
	 * Verify the create_factory method exists.
	 *
	 * @return void
	 */
	public function test_create_factory_method_exists(): void {
		$this->assertTrue( \method_exists( Plugin::class, 'create_factory' ) );
	}
}
