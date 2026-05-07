<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\QuoteConverter;

/**
 * Tests for QuoteConverter.
 */
class QuoteConverterTest extends ConverterTestCase {

	/**
	 * Verifies quote conversion wraps paragraphs and preserves citations.
	 *
	 * @return void
	 */
	public function test_wraps_paragraphs_and_unwraps_cite_paragraph(): void {
		$this->assertSame(
			implode(
				"\n",
				[
					'<!-- wp:quote -->',
					'<blockquote class="wp-block-quote"><!-- wp:paragraph -->',
					'<p>Quoted text.</p>',
					'<!-- /wp:paragraph --><cite>Author</cite></blockquote>',
					'<!-- /wp:quote -->',
				],
			),
			( new QuoteConverter() )->convert( '<blockquote><p>Quoted text.</p><p><cite>Author</cite></p></blockquote>' ),
		);
	}
}
