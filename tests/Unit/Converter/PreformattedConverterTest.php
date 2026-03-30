<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\PreformattedConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PreformattedConverter.
 *
 * Full conversion tests live in integration tests (requires WP_HTML_Tag_Processor).
 */
class PreformattedConverterTest extends TestCase {

	/**
	 * Supports the pre tag.
	 *
	 * @return void
	 */
	public function test_supports_pre_tag(): void {
		$converter = new PreformattedConverter();

		$this->assertSame( [ 'pre' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a pre element.
	 *
	 * @return void
	 */
	public function test_can_convert_pre(): void {
		$converter = new PreformattedConverter();

		$this->assertTrue( $converter->can_convert( 'pre', '<pre>Code</pre>' ) );
	}

	/**
	 * Cannot convert non-pre tags.
	 *
	 * @return void
	 */
	public function test_cannot_convert_other_tags(): void {
		$converter = new PreformattedConverter();

		$this->assertFalse( $converter->can_convert( 'code', '<code>Code</code>' ) );
	}
}
