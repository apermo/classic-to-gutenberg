<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\HeadingConverter;

/**
 * Tests for HeadingConverter.
 */
class HeadingConverterTest extends ConverterTestCase {

	/**
	 * Verifies heading conversion preserves classes and adds level attributes.
	 *
	 * @return void
	 */
	public function test_adds_heading_class_and_level(): void {
		$this->assertSame(
			"<!-- wp:heading {\"level\":3} -->\n<h3 class=\"intro wp-block-heading\">Hello</h3>\n<!-- /wp:heading -->",
			( new HeadingConverter() )->convert( '<h3 class="intro">Hello</h3>' ),
		);
	}
}
