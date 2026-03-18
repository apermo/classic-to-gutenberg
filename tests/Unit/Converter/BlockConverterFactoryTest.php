<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\BlockConverterInterface;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BlockConverterFactory.
 */
class BlockConverterFactoryTest extends TestCase {

	/**
	 * Returns the fallback converter when no converters are registered.
	 *
	 * @return void
	 */
	public function test_returns_fallback_for_unknown_tag(): void {
		$fallback = new HtmlBlockConverter();
		$factory  = new BlockConverterFactory( $fallback );

		$result = $factory->get_converter( 'div', '<div>content</div>' );

		$this->assertSame( $fallback, $result );
	}

	/**
	 * Returns the registered converter for a known tag.
	 *
	 * @return void
	 */
	public function test_returns_registered_converter(): void {
		$fallback  = new HtmlBlockConverter();
		$factory   = new BlockConverterFactory( $fallback );
		$converter = $this->createMock( BlockConverterInterface::class );

		$converter->method( 'get_supported_tags' )->willReturn( [ 'p' ] );
		$converter->method( 'can_convert' )->willReturn( true );

		$factory->register( $converter );

		$result = $factory->get_converter( 'p', '<p>text</p>' );

		$this->assertSame( $converter, $result );
	}

	/**
	 * Falls back when can_convert returns false.
	 *
	 * @return void
	 */
	public function test_falls_back_when_can_convert_is_false(): void {
		$fallback  = new HtmlBlockConverter();
		$factory   = new BlockConverterFactory( $fallback );
		$converter = $this->createMock( BlockConverterInterface::class );

		$converter->method( 'get_supported_tags' )->willReturn( [ 'p' ] );
		$converter->method( 'can_convert' )->willReturn( false );

		$factory->register( $converter );

		$result = $factory->get_converter( 'p', '<p>text</p>' );

		$this->assertSame( $fallback, $result );
	}

	/**
	 * Later registrations override earlier ones for the same tag.
	 *
	 * @return void
	 */
	public function test_later_registration_overrides_earlier(): void {
		$fallback = new HtmlBlockConverter();
		$factory  = new BlockConverterFactory( $fallback );

		$first = $this->createMock( BlockConverterInterface::class );
		$first->method( 'get_supported_tags' )->willReturn( [ 'p' ] );

		$second = $this->createMock( BlockConverterInterface::class );
		$second->method( 'get_supported_tags' )->willReturn( [ 'p' ] );
		$second->method( 'can_convert' )->willReturn( true );

		$factory->register( $first );
		$factory->register( $second );

		$result = $factory->get_converter( 'p', '<p>text</p>' );

		$this->assertSame( $second, $result );
	}

	/**
	 * Tag matching is case-insensitive.
	 *
	 * @return void
	 */
	public function test_tag_matching_is_case_insensitive(): void {
		$fallback  = new HtmlBlockConverter();
		$factory   = new BlockConverterFactory( $fallback );
		$converter = $this->createMock( BlockConverterInterface::class );

		$converter->method( 'get_supported_tags' )->willReturn( [ 'P' ] );
		$converter->method( 'can_convert' )->willReturn( true );

		$factory->register( $converter );

		$result = $factory->get_converter( 'p', '<p>text</p>' );

		$this->assertSame( $converter, $result );
	}

	/**
	 * Falls through to earlier converter when later one rejects.
	 *
	 * @return void
	 */
	public function test_falls_through_to_earlier_converter(): void {
		$fallback = new HtmlBlockConverter();
		$factory  = new BlockConverterFactory( $fallback );

		$general = $this->createMock( BlockConverterInterface::class );
		$general->method( 'get_supported_tags' )->willReturn( [ 'img' ] );
		$general->method( 'can_convert' )->willReturn( true );

		$specific = $this->createMock( BlockConverterInterface::class );
		$specific->method( 'get_supported_tags' )->willReturn( [ 'img' ] );
		$specific->method( 'can_convert' )->willReturn( false );

		$factory->register( $general );
		$factory->register( $specific );

		$result = $factory->get_converter( 'img', '<img src="test.jpg" />' );

		$this->assertSame( $general, $result );
	}

	/**
	 * Falls back to fallback when all converters in stack reject.
	 *
	 * @return void
	 */
	public function test_falls_back_when_all_reject(): void {
		$fallback = new HtmlBlockConverter();
		$factory  = new BlockConverterFactory( $fallback );

		$first = $this->createMock( BlockConverterInterface::class );
		$first->method( 'get_supported_tags' )->willReturn( [ 'div' ] );
		$first->method( 'can_convert' )->willReturn( false );

		$second = $this->createMock( BlockConverterInterface::class );
		$second->method( 'get_supported_tags' )->willReturn( [ 'div' ] );
		$second->method( 'can_convert' )->willReturn( false );

		$factory->register( $first );
		$factory->register( $second );

		$result = $factory->get_converter( 'div', '<div>content</div>' );

		$this->assertSame( $fallback, $result );
	}

	/**
	 * Returns all registered converters via get_converters.
	 *
	 * @return void
	 */
	public function test_get_converters_returns_all(): void {
		$fallback = new HtmlBlockConverter();
		$factory  = new BlockConverterFactory( $fallback );

		$this->assertSame( [], $factory->get_converters() );

		$converter = $this->createMock( BlockConverterInterface::class );
		$converter->method( 'get_supported_tags' )->willReturn( [ 'p', 'span' ] );

		$factory->register( $converter );

		$this->assertCount( 2, $factory->get_converters() );
	}
}
