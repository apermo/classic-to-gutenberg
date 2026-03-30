<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ShortcodeConverter.
 */
class ShortcodeConverterTest extends TestCase {

	/**
	 * Supports the __shortcode__ pseudo tag.
	 *
	 * @return void
	 */
	public function test_supports_shortcode_pseudo_tag(): void {
		$converter = new ShortcodeConverter();

		$this->assertSame( [ '__shortcode__' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert a shortcode element.
	 *
	 * @return void
	 */
	public function test_can_convert_shortcode(): void {
		$converter = new ShortcodeConverter();

		$this->assertTrue( $converter->can_convert( '__shortcode__', '[test]' ) );
	}

	/**
	 * Dispatches conversion to a registered handler.
	 *
	 * @return void
	 */
	public function test_dispatches_to_registered_handler(): void {
		$handler = $this->createMock( ShortcodeHandlerInterface::class );
		$handler->method( 'get_shortcode_tag' )->willReturn( 'test' );
		$handler->method( 'convert' )->willReturn( 'converted' );

		$converter = new ShortcodeConverter( [ $handler ] );
		$result    = $converter->convert( '[test attr="value"]content[/test]' );

		$this->assertSame( 'converted', $result );
	}

	/**
	 * Falls back to the default handler for unknown shortcodes.
	 *
	 * @return void
	 */
	public function test_falls_back_to_default_handler(): void {
		$converter = new ShortcodeConverter();

		$result = $converter->convert( '[unknown_shortcode]' );

		$this->assertStringContainsString( '<!-- wp:shortcode -->', $result );
		$this->assertStringContainsString( '[unknown_shortcode]', $result );
	}

	/**
	 * Dispatches to the correct handler based on shortcode tag.
	 *
	 * @return void
	 */
	public function test_dispatches_to_correct_handler(): void {
		$handler = $this->createMock( ShortcodeHandlerInterface::class );
		$handler->method( 'get_shortcode_tag' )->willReturn( 'gallery' );
		$handler->expects( $this->once() )->method( 'convert' );

		$converter = new ShortcodeConverter( [ $handler ] );
		$converter->convert( '[gallery ids="1,2,3"]' );
	}

	/**
	 * Uses the default handler for an unrecognized shortcode tag.
	 *
	 * @return void
	 */
	public function test_unrecognized_tag_uses_default(): void {
		$handler = $this->createMock( ShortcodeHandlerInterface::class );
		$handler->method( 'get_shortcode_tag' )->willReturn( 'gallery' );
		$handler->expects( $this->never() )->method( 'convert' );

		$converter = new ShortcodeConverter( [ $handler ] );
		$result    = $converter->convert( '[video src="clip.mp4"]' );

		$this->assertStringContainsString( '<!-- wp:shortcode -->', $result );
	}

	/**
	 * Uses the default handler for invalid shortcode syntax.
	 *
	 * @return void
	 */
	public function test_invalid_shortcode_uses_default(): void {
		$converter = new ShortcodeConverter();

		$result = $converter->convert( 'not a shortcode' );

		$this->assertStringContainsString( '<!-- wp:shortcode -->', $result );
	}
}
