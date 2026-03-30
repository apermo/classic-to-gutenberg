<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\QuoteConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QuoteConverter.
 */
class QuoteConverterTest extends TestCase {

	/**
	 * Supports the blockquote tag.
	 *
	 * @return void
	 */
	public function test_supports_blockquote_tag(): void {
		$converter = new QuoteConverter();

		$this->assertSame( [ 'blockquote' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a blockquote element.
	 *
	 * @return void
	 */
	public function test_can_convert_blockquote(): void {
		$converter = new QuoteConverter();

		$this->assertTrue( $converter->can_convert( 'blockquote', '<blockquote><p>Quote</p></blockquote>' ) );
	}

	/**
	 * Cannot convert non-blockquote tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new QuoteConverter();

		$this->assertFalse( $converter->can_convert( 'p', '<p>Not a quote</p>' ) );
	}

	/**
	 * Converts a simple blockquote to a quote block.
	 *
	 * @return void
	 */
	public function test_convert_simple_blockquote(): void {
		$converter = new QuoteConverter();

		$result = $converter->convert( '<blockquote><p>A simple quote</p></blockquote>' );

		$this->assertStringContainsString( '<!-- wp:quote -->', $result );
		$this->assertStringContainsString( '<!-- /wp:quote -->', $result );
		$this->assertStringContainsString( 'wp-block-quote', $result );
		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringContainsString( '<p>A simple quote</p>', $result );
	}

	/**
	 * Converts a blockquote with a citation element.
	 *
	 * @return void
	 */
	public function test_convert_blockquote_with_citation(): void {
		$converter = new QuoteConverter();

		$result = $converter->convert( '<blockquote><p>A quote</p><cite>Author</cite></blockquote>' );

		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringContainsString( '<cite>Author</cite>', $result );
	}

	/**
	 * Strips paragraph wrapper around cite element in blockquote.
	 *
	 * @return void
	 */
	public function test_convert_blockquote_strips_paragraph_around_cite(): void {
		$converter = new QuoteConverter();

		$result = $converter->convert( '<blockquote><p>A quote</p><p><cite>Author</cite></p></blockquote>' );

		$this->assertStringContainsString( '<cite>Author</cite>', $result );
		$this->assertStringNotContainsString( '<p><cite>', $result );
	}

	/**
	 * Converts a blockquote with multiple paragraphs.
	 *
	 * @return void
	 */
	public function test_convert_blockquote_with_multiple_paragraphs(): void {
		$converter = new QuoteConverter();

		$result = $converter->convert( '<blockquote><p>First</p><p>Second</p></blockquote>' );

		$this->assertSame( 2, \substr_count( $result, '<!-- wp:paragraph -->' ) );
		$this->assertStringContainsString( 'First', $result );
		$this->assertStringContainsString( 'Second', $result );
	}

	/**
	 * Falls back to HTML block for invalid blockquote.
	 *
	 * @return void
	 */
	public function test_convert_invalid_blockquote_falls_back_to_html(): void {
		$converter = new QuoteConverter();

		$result = $converter->convert( '<div>Not a blockquote</div>' );

		$this->assertStringContainsString( '<!-- wp:html -->', $result );
	}
}
