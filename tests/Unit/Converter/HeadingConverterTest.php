<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\HeadingConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HeadingConverter.
 *
 * Full conversion tests live in integration tests (requires WP_HTML_Tag_Processor).
 */
class HeadingConverterTest extends TestCase {

	/**
	 * Supports all heading levels h1 through h6.
	 *
	 * @return void
	 */
	public function test_supports_all_heading_levels(): void {
		$converter = new HeadingConverter();

		$this->assertSame(
			[ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
			$converter->get_supported_tags(),
		);
	}

	/**
	 * Can convert an h1 tag.
	 *
	 * @return void
	 */
	public function test_can_convert_h1(): void {
		$converter = new HeadingConverter();

		$this->assertTrue( $converter->can_convert( 'h1', '<h1>Title</h1>' ) );
	}

	/**
	 * Can convert an h6 tag.
	 *
	 * @return void
	 */
	public function test_can_convert_h6(): void {
		$converter = new HeadingConverter();

		$this->assertTrue( $converter->can_convert( 'h6', '<h6>Small</h6>' ) );
	}

	/**
	 * Cannot convert a p tag.
	 *
	 * @return void
	 */
	public function test_cannot_convert_p(): void {
		$converter = new HeadingConverter();

		$this->assertFalse( $converter->can_convert( 'p', '<p>Not heading</p>' ) );
	}
}
