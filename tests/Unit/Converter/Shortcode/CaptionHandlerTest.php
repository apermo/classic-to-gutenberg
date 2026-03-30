<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter\Shortcode;

use Apermo\ClassicToGutenberg\Converter\Shortcode\CaptionHandler;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CaptionHandler.
 */
class CaptionHandlerTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubs(
			[
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- test stub for wp_json_encode
				'wp_json_encode' => static fn( $data, $flags = 0 ): string => (string) \json_encode( $data, $flags ),
			],
		);
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Reports caption as shortcode tag.
	 *
	 * @return void
	 */
	public function test_shortcode_tag_is_caption(): void {
		$handler = new CaptionHandler();

		$this->assertSame( 'caption', $handler->get_shortcode_tag() );
	}

	/**
	 * Converts a caption shortcode to an image block.
	 *
	 * @return void
	 */
	public function test_convert_produces_image_block(): void {
		$handler   = new CaptionHandler();
		$shortcode = '[caption id="attachment_42" width="300"]'
			. '<img src="photo.jpg" class="wp-image-42" width="300" height="200"/> A caption'
			. '[/caption]';

		$result = $handler->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:image', $result );
		$this->assertStringContainsString( '<!-- /wp:image -->', $result );
		$this->assertStringContainsString( 'wp-block-image', $result );
		$this->assertStringContainsString( 'wp-element-caption', $result );
		$this->assertStringContainsString( 'A caption', $result );
	}

	/**
	 * Extracts the attachment ID from the shortcode.
	 *
	 * @return void
	 */
	public function test_extracts_attachment_id(): void {
		$handler = new CaptionHandler();

		$result = $handler->convert( '[caption id="attachment_42"]<img src="p.jpg"/> Caption[/caption]' );

		$this->assertStringContainsString( '"id":42', $result );
	}

	/**
	 * Extracts the attachment ID from the img class attribute.
	 *
	 * @return void
	 */
	public function test_extracts_id_from_img_class(): void {
		$handler = new CaptionHandler();

		$result = $handler->convert( '[caption]<img src="p.jpg" class="wp-image-99"/> Caption[/caption]' );

		$this->assertStringContainsString( '"id":99', $result );
	}

	/**
	 * Extracts alignment from the shortcode attributes.
	 *
	 * @return void
	 */
	public function test_extracts_alignment(): void {
		$handler = new CaptionHandler();

		$result = $handler->convert( '[caption align="aligncenter"]<img src="p.jpg"/> Caption[/caption]' );

		$this->assertStringContainsString( '"align":"center"', $result );
		$this->assertStringContainsString( 'aligncenter', $result );
	}

	/**
	 * Extracts width and height dimensions from the shortcode.
	 *
	 * @return void
	 */
	public function test_extracts_dimensions(): void {
		$handler = new CaptionHandler();

		$result = $handler->convert(
			'[caption width="300"]<img src="p.jpg" width="300" height="200"/> Caption[/caption]',
		);

		$this->assertStringContainsString( '"width":300', $result );
		$this->assertStringContainsString( '"height":200', $result );
	}

	/**
	 * Omits caption markup when no caption text is present.
	 *
	 * @return void
	 */
	public function test_no_caption_text(): void {
		$handler = new CaptionHandler();

		$result = $handler->convert( '[caption]<img src="p.jpg"/>[/caption]' );

		$this->assertStringNotContainsString( 'wp-element-caption', $result );
	}
}
