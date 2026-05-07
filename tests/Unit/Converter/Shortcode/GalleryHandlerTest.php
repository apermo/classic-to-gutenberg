<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter\Shortcode;

use Apermo\ClassicToGutenberg\Converter\Shortcode\GalleryHandler;
use Apermo\ClassicToGutenberg\Tests\Unit\Converter\ConverterTestCase;

/**
 * Tests for GalleryHandler.
 */
class GalleryHandlerTest extends ConverterTestCase {

	/**
	 * Verifies gallery shortcode IDs, columns, and link mode.
	 *
	 * @return void
	 */
	public function test_converts_ids_columns_and_link_mode(): void {
		$this->assertSame(
			implode(
				"\n",
				[
					'<!-- wp:gallery {"columns":2,"linkTo":"media"} -->',
					'<figure class="wp-block-gallery has-nested-images columns-2">',
					'<!-- wp:image {"id":11,"linkDestination":"media"} -->',
					'<figure class="wp-block-image"><img src="" alt="" class="wp-image-11"/></figure>',
					'<!-- /wp:image -->',
					'',
					'<!-- wp:image {"id":12,"linkDestination":"media"} -->',
					'<figure class="wp-block-image"><img src="" alt="" class="wp-image-12"/></figure>',
					'<!-- /wp:image --></figure>',
					'<!-- /wp:gallery -->',
				],
			),
			( new GalleryHandler() )->convert( '[gallery ids="11,12" columns="2" link="file"]' ),
		);
	}
}
