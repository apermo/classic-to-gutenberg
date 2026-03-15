<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use PHPUnit\Framework\TestCase;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;

/**
 * Tests for HtmlBlockConverter (fallback converter).
 */
class HtmlBlockConverterTest extends TestCase {

	/**
	 * Supports no tags (it's the fallback, matched explicitly).
	 *
	 * @return void
	 */
	public function test_supports_no_tags(): void {
		$converter = new HtmlBlockConverter();

		$this->assertSame( [], $converter->get_supported_tags() );
	}

	/**
	 * Always reports it can convert.
	 *
	 * @return void
	 */
	public function test_can_convert_always_true(): void {
		$converter = new HtmlBlockConverter();

		$this->assertTrue( $converter->can_convert( 'anything', '<anything>content</anything>' ) );
	}

	/**
	 * Wraps content in a core/html block.
	 *
	 * @return void
	 */
	public function test_convert_wraps_in_html_block(): void {
		$converter = new HtmlBlockConverter();
		$html      = '<div class="custom">Some content</div>';

		$result = $converter->convert( $html );

		$this->assertStringContainsString( '<!-- wp:html -->', $result );
		$this->assertStringContainsString( $html, $result );
		$this->assertStringContainsString( '<!-- /wp:html -->', $result );
	}
}
