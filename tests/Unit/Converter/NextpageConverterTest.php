<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\NextpageConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for NextpageConverter.
 */
class NextpageConverterTest extends TestCase {

	/**
	 * Supports the __nextpage__ pseudo tag.
	 *
	 * @return void
	 */
	public function test_supports_nextpage_pseudo_tag(): void {
		$converter = new NextpageConverter();

		$this->assertSame( [ '__nextpage__' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a nextpage comment.
	 *
	 * @return void
	 */
	public function test_can_convert_nextpage(): void {
		$converter = new NextpageConverter();

		$this->assertTrue( $converter->can_convert( '__nextpage__', '<!--nextpage-->' ) );
	}

	/**
	 * Cannot convert non-nextpage tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new NextpageConverter();

		$this->assertFalse( $converter->can_convert( 'p', '<p>text</p>' ) );
	}

	/**
	 * Produces a self-closing nextpage block.
	 *
	 * @return void
	 */
	public function test_convert_produces_self_closing_block(): void {
		$converter = new NextpageConverter();

		$result = $converter->convert( '<!--nextpage-->' );

		$this->assertSame( '<!-- wp:nextpage /-->', $result );
	}
}
