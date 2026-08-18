<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter\Shortcode;

use Apermo\ClassicToGutenberg\Converter\Shortcode\CaptionHandler;
use Apermo\ClassicToGutenberg\Tests\Unit\Converter\ConverterTestCase;

/**
 * Tests for CaptionHandler.
 */
class CaptionHandlerTest extends ConverterTestCase {

	/**
	 * Verifies caption shortcodes convert to image blocks.
	 *
	 * @return void
	 */
	public function test_converts_caption_shortcode(): void {
		$this->assertSame(
			\implode(
				"\n",
				[
					'<!-- wp:image {"id":55,"align":"right","width":300,"height":200} -->',
					'<figure class="wp-block-image alignright">'
						. '<img src="/cat.jpg" class="wp-image-55" width="300" height="200"/>'
						. '<figcaption class="wp-element-caption">Cat</figcaption></figure>',
					'<!-- /wp:image -->',
				],
			),
			( new CaptionHandler() )->convert(
				'[caption id="attachment_55" align="alignright" width="300"]'
					. '<img src="/cat.jpg" class="wp-image-55" width="300" height="200"> Cat[/caption]',
			),
		);
	}
}
