<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Provides the base class for isolated converter tests.
 */
abstract class ConverterTestCase extends TestCase {

	/**
	 * Sets up WordPress function stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! \function_exists( 'wp_json_encode' ) ) {
			Functions\stubs(
				[
					// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- test stub for wp_json_encode
					'wp_json_encode' => static fn( $data, $flags = 0 ): string => (string) \json_encode( $data, $flags ),
				],
			);
		}
	}

	/**
	 * Tears down WordPress function stubs.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
