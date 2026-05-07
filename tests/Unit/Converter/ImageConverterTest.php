<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\ImageConverter;

/**
 * Tests for ImageConverter.
 */
class ImageConverterTest extends ConverterTestCase {

	/**
	 * Verifies image conversion extracts attributes and strips image alignment classes.
	 *
	 * @return void
	 */
	public function test_extracts_attrs_from_standalone_image(): void {
		$this->assertSame(
			implode(
				"\n",
				[
					'<!-- wp:image {"id":42,"align":"center","width":640,"height":480} -->',
					'<figure class="wp-block-image aligncenter">'
						. '<img class="size-large wp-image-42" src="/photo.jpg" width="640" height="480"/></figure>',
					'<!-- /wp:image -->',
				],
			),
			( new ImageConverter() )->convert(
				'<img class="aligncenter size-large wp-image-42" src="/photo.jpg" width="640" height="480" />',
			),
		);
	}
}
