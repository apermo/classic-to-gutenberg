<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\SeparatorConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SeparatorConverter.
 */
class SeparatorConverterTest extends TestCase {

	/**
	 * Supports the hr tag.
	 *
	 * @return void
	 */
	public function test_supports_hr_tag(): void {
		$converter = new SeparatorConverter();

		$this->assertSame( [ 'hr' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert an hr tag.
	 *
	 * @return void
	 */
	public function test_can_convert_hr(): void {
		$converter = new SeparatorConverter();

		$this->assertTrue( $converter->can_convert( 'hr', '<hr>' ) );
	}

	/**
	 * Produces a separator block.
	 *
	 * @return void
	 */
	public function test_convert_produces_separator_block(): void {
		$converter = new SeparatorConverter();

		$result = $converter->convert( '<hr>' );

		$expected = "<!-- wp:separator -->\n"
			. '<hr class="wp-block-separator has-alpha-channel-opacity"/>' . "\n"
			. '<!-- /wp:separator -->';

		$this->assertSame( $expected, $result );
	}

	/**
	 * Ignores input HTML and produces consistent output.
	 *
	 * @return void
	 */
	public function test_convert_ignores_input_html(): void {
		$converter = new SeparatorConverter();

		$result1 = $converter->convert( '<hr>' );
		$result2 = $converter->convert( '<hr class="custom"/>' );

		$this->assertSame( $result1, $result2 );
	}
}
