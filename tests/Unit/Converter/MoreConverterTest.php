<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\MoreConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MoreConverter.
 */
class MoreConverterTest extends TestCase {

	/**
	 * Supports the __more__ pseudo tag.
	 *
	 * @return void
	 */
	public function test_supports_more_pseudo_tag(): void {
		$converter = new MoreConverter();

		$this->assertSame( [ '__more__' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a more comment.
	 *
	 * @return void
	 */
	public function test_can_convert_more(): void {
		$converter = new MoreConverter();

		$this->assertTrue( $converter->can_convert( '__more__', '<!--more-->' ) );
	}

	/**
	 * Cannot convert non-more tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new MoreConverter();

		$this->assertFalse( $converter->can_convert( 'p', '<p>text</p>' ) );
	}

	/**
	 * Produces a more block.
	 *
	 * @return void
	 */
	public function test_convert_produces_more_block(): void {
		$converter = new MoreConverter();

		$result = $converter->convert( '<!--more-->' );

		$this->assertSame(
			"<!-- wp:more -->\n<!--more-->\n<!-- /wp:more -->",
			$result,
		);
	}
}
