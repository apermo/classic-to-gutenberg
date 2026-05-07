<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\ListConverter;

/**
 * Tests for ListConverter.
 */
class ListConverterTest extends ConverterTestCase {

	/**
	 * Verifies list conversion requires closed lists and wraps items.
	 *
	 * @return void
	 */
	public function test_wraps_ordered_items(): void {
		$converter = new ListConverter();

		$this->assertFalse( $converter->can_convert( 'ol', '<ol><li>One</li>' ) );
		$this->assertSame(
			implode(
				"\n",
				[
					'<!-- wp:list {"ordered":true} -->',
					'<ol class="wp-block-list"><!-- wp:list-item -->',
					'<li>One</li>',
					'<!-- /wp:list-item -->',
					'',
					'<!-- wp:list-item -->',
					'<li>Two</li>',
					'<!-- /wp:list-item --></ol>',
					'<!-- /wp:list -->',
				],
			),
			$converter->convert( '<ol><li>One</li><li>Two</li></ol>' ),
		);
	}
}
