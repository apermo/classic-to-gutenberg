<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\ListConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ListConverter.
 *
 * Full conversion tests live in integration tests (requires WP_HTML_Tag_Processor).
 */
class ListConverterTest extends TestCase {

	/**
	 * Supports the ul and ol tags.
	 *
	 * @return void
	 */
	public function test_supports_ul_and_ol(): void {
		$converter = new ListConverter();

		$this->assertSame( [ 'ul', 'ol' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a properly closed unordered list.
	 *
	 * @return void
	 */
	public function test_can_convert_closed_ul(): void {
		$converter = new ListConverter();

		$this->assertTrue( $converter->can_convert( 'ul', '<ul><li>Item</li></ul>' ) );
	}

	/**
	 * Can convert a properly closed ordered list.
	 *
	 * @return void
	 */
	public function test_can_convert_closed_ol(): void {
		$converter = new ListConverter();

		$this->assertTrue( $converter->can_convert( 'ol', '<ol><li>Item</li></ol>' ) );
	}

	/**
	 * Cannot convert an unclosed unordered list.
	 *
	 * @return void
	 */
	public function test_cannot_convert_unclosed_ul(): void {
		$converter = new ListConverter();

		$this->assertFalse( $converter->can_convert( 'ul', '<ul><li>Item</li>' ) );
	}

	/**
	 * Cannot convert an unclosed ordered list.
	 *
	 * @return void
	 */
	public function test_cannot_convert_unclosed_ol(): void {
		$converter = new ListConverter();

		$this->assertFalse( $converter->can_convert( 'ol', '<ol><li>Item</li>' ) );
	}

	/**
	 * Cannot convert non-list tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new ListConverter();

		$this->assertFalse( $converter->can_convert( 'p', '<p>Text</p>' ) );
	}
}
