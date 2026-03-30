<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter\Shortcode;

use Apermo\ClassicToGutenberg\Converter\Shortcode\DefaultHandler;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DefaultHandler (fallback shortcode handler).
 */
class DefaultHandlerTest extends TestCase {

	/**
	 * Wraps a shortcode in a shortcode block.
	 *
	 * @return void
	 */
	public function test_wraps_in_shortcode_block(): void {
		$handler = new DefaultHandler();

		$result = $handler->convert( '[unknown_shortcode]' );

		$this->assertSame(
			"<!-- wp:shortcode -->\n[unknown_shortcode]\n<!-- /wp:shortcode -->",
			$result,
		);
	}

	/**
	 * Wraps a closing shortcode in a shortcode block.
	 *
	 * @return void
	 */
	public function test_wraps_closing_shortcode(): void {
		$handler = new DefaultHandler();

		$result = $handler->convert( '[embed]https://example.com[/embed]' );

		$this->assertStringContainsString( '<!-- wp:shortcode -->', $result );
		$this->assertStringContainsString( '[embed]https://example.com[/embed]', $result );
		$this->assertStringContainsString( '<!-- /wp:shortcode -->', $result );
	}
}
