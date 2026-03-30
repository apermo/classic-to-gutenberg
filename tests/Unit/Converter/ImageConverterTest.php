<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\ImageConverter;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ImageConverter.
 */
class ImageConverterTest extends TestCase {

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
	 * Supports img and figure tags.
	 *
	 * @return void
	 */
	public function test_supports_img_and_figure_tags(): void {
		$converter = new ImageConverter();

		$this->assertSame( [ 'img', 'figure' ], $converter->get_supported_tags() );
	}

	/**
	 * Can convert an img element.
	 *
	 * @return void
	 */
	public function test_can_convert_img(): void {
		$converter = new ImageConverter();

		$this->assertTrue( $converter->can_convert( 'img', '<img src="test.jpg"/>' ) );
	}

	/**
	 * Can convert a figure containing an img.
	 *
	 * @return void
	 */
	public function test_can_convert_figure_with_img(): void {
		$converter = new ImageConverter();

		$this->assertTrue( $converter->can_convert( 'figure', '<figure><img src="test.jpg"/></figure>' ) );
	}

	/**
	 * Cannot convert a figure without an img.
	 *
	 * @return void
	 */
	public function test_cannot_convert_figure_without_img(): void {
		$converter = new ImageConverter();

		$this->assertFalse( $converter->can_convert( 'figure', '<figure><video src="v.mp4"></video></figure>' ) );
	}

	/**
	 * Converts a standalone img to an image block.
	 *
	 * @return void
	 */
	public function test_convert_standalone_img(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<img src="photo.jpg" alt="Photo"/>' );

		$this->assertStringContainsString( '<!-- wp:image -->', $result );
		$this->assertStringContainsString( 'wp-block-image', $result );
		$this->assertStringContainsString( '<figure', $result );
		$this->assertStringContainsString( 'src="photo.jpg"', $result );
		$this->assertStringContainsString( '<!-- /wp:image -->', $result );
	}

	/**
	 * Extracts the wp-image ID from the img class.
	 *
	 * @return void
	 */
	public function test_convert_img_extracts_wp_image_id(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<img class="wp-image-42" src="photo.jpg"/>' );

		$this->assertStringContainsString( '"id":42', $result );
	}

	/**
	 * Extracts alignment from the img class.
	 *
	 * @return void
	 */
	public function test_convert_img_extracts_alignment(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<img class="aligncenter" src="photo.jpg"/>' );

		$this->assertStringContainsString( '"align":"center"', $result );
		$this->assertStringContainsString( 'aligncenter', $result );
	}

	/**
	 * Extracts width and height dimensions from the img.
	 *
	 * @return void
	 */
	public function test_convert_img_extracts_dimensions(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<img src="photo.jpg" width="300" height="200"/>' );

		$this->assertStringContainsString( '"width":300', $result );
		$this->assertStringContainsString( '"height":200', $result );
	}

	/**
	 * Converts a linked image to an image block.
	 *
	 * @return void
	 */
	public function test_convert_linked_image(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<a href="https://example.com"><img src="photo.jpg"/></a>' );

		$this->assertStringContainsString( '<!-- wp:image', $result );
		$this->assertStringContainsString( '"linkDestination":"custom"', $result );
		$this->assertStringContainsString( '<a href="https://example.com">', $result );
		$this->assertStringContainsString( '</a>', $result );
	}

	/**
	 * Converts a figure with an img to an image block.
	 *
	 * @return void
	 */
	public function test_convert_figure_with_img(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<figure><img src="photo.jpg"/></figure>' );

		$this->assertStringContainsString( '<!-- wp:image -->', $result );
		$this->assertStringContainsString( 'wp-block-image', $result );
	}

	/**
	 * Converts a figure with a figcaption to an image block with caption.
	 *
	 * @return void
	 */
	public function test_convert_figure_with_caption(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<figure><img src="photo.jpg"/><figcaption>My caption</figcaption></figure>' );

		$this->assertStringContainsString( 'wp-element-caption', $result );
		$this->assertStringContainsString( 'My caption', $result );
	}

	/**
	 * Converts a figure with alignment class to an aligned image block.
	 *
	 * @return void
	 */
	public function test_convert_figure_with_alignment(): void {
		$converter = new ImageConverter();

		$result = $converter->convert( '<figure class="aligncenter"><img src="photo.jpg"/></figure>' );

		$this->assertStringContainsString( '"align":"center"', $result );
	}
}
