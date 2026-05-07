<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\TableConverter;

/**
 * Tests for TableConverter.
 */
class TableConverterTest extends ConverterTestCase {

	/**
	 * Verifies table conversion requires sections and adds block wrappers.
	 *
	 * @return void
	 */
	public function test_requires_sections_and_adds_fixed_layout(): void {
		$converter = new TableConverter();

		$this->assertFalse( $converter->can_convert( 'table', '<table><tr><td>A</td></tr></table>' ) );
		$this->assertSame(
			implode(
				"\n",
				[
					'<!-- wp:table -->',
					'<figure class="wp-block-table">'
						. '<table class="has-fixed-layout"><thead><tr><th>Name</th></tr></thead>'
						. '<tbody><tr><td>Ada</td></tr></tbody></table></figure>',
					'<!-- /wp:table -->',
				],
			),
			$converter->convert( '<table><thead><tr><th>Name</th></tr></thead><tbody><tr><td>Ada</td></tr></tbody></table>' ),
		);
	}
}
