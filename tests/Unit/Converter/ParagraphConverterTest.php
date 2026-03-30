<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\ParagraphConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ParagraphConverter.
 */
class ParagraphConverterTest extends TestCase {

	/**
	 * Supports the p tag.
	 *
	 * @return void
	 */
	public function test_supports_p_tag(): void {
		$converter = new ParagraphConverter();

		$this->assertSame( [ 'p' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a p tag.
	 *
	 * @return void
	 */
	public function test_can_convert_p_tag(): void {
		$converter = new ParagraphConverter();

		$this->assertTrue( $converter->can_convert( 'p', '<p>Hello</p>' ) );
	}

	/**
	 * Cannot convert non-paragraph tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new ParagraphConverter();

		$this->assertFalse( $converter->can_convert( 'div', '<div>Hello</div>' ) );
	}

	/**
	 * Wraps content in a paragraph block.
	 *
	 * @return void
	 */
	public function test_convert_wraps_in_paragraph_block(): void {
		$converter = new ParagraphConverter();

		$result = $converter->convert( '<p>Hello world</p>' );

		$this->assertSame(
			"<!-- wp:paragraph -->\n<p>Hello world</p>\n<!-- /wp:paragraph -->",
			$result,
		);
	}

	/**
	 * Preserves inline markup within a paragraph.
	 *
	 * @return void
	 */
	public function test_convert_preserves_inline_markup(): void {
		$converter = new ParagraphConverter();

		$result = $converter->convert( '<p>Hello <strong>bold</strong> and <em>italic</em></p>' );

		$this->assertStringContainsString( '<strong>bold</strong>', $result );
		$this->assertStringContainsString( '<em>italic</em>', $result );
	}
}
