<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;

/**
 * Tests for ShortcodeConverter.
 */
class ShortcodeConverterTest extends ConverterTestCase {

	/**
	 * Verifies shortcode conversion dispatches handlers and falls back for unknown tags.
	 *
	 * @return void
	 */
	public function test_dispatches_registered_handler_and_falls_back(): void {
		$handler = new class() implements ShortcodeHandlerInterface {
			/**
			 * Returns the shortcode tag this handler processes.
			 *
			 * @return string
			 */
			public function get_shortcode_tag(): string {
				return 'demo';
			}

			/**
			 * Converts the shortcode into deterministic test output.
			 *
			 * @param string $shortcode Shortcode input.
			 *
			 * @return string
			 */
			public function convert( string $shortcode ): string {
				return 'handled:' . $shortcode;
			}
		};

		$converter = new ShortcodeConverter( [ $handler ] );

		$this->assertSame( 'handled:[demo id="1"]', $converter->convert( '[demo id="1"]' ) );
		$this->assertSame(
			"<!-- wp:shortcode -->\n[unknown]\n<!-- /wp:shortcode -->",
			$converter->convert( '[unknown]' ),
		);
	}
}
