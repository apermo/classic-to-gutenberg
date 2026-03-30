<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter\Shortcode;

use Apermo\ClassicToGutenberg\Converter\Shortcode\GalleryHandler;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for GalleryHandler.
 */
class GalleryHandlerTest extends TestCase {

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
	 * Reports gallery as shortcode tag.
	 *
	 * @return void
	 */
	public function test_shortcode_tag_is_gallery(): void {
		$handler = new GalleryHandler();

		$this->assertSame( 'gallery', $handler->get_shortcode_tag() );
	}

	/**
	 * Converts a gallery shortcode to a gallery block.
	 *
	 * @return void
	 */
	public function test_convert_produces_gallery_block(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1,2,3"]' );

		$this->assertStringContainsString( '<!-- wp:gallery', $result );
		$this->assertStringContainsString( '<!-- /wp:gallery -->', $result );
		$this->assertStringContainsString( 'wp-block-gallery', $result );
		$this->assertStringContainsString( 'has-nested-images', $result );
	}

	/**
	 * Defaults to three columns when none specified.
	 *
	 * @return void
	 */
	public function test_default_columns(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1,2"]' );

		$this->assertStringContainsString( '"columns":3', $result );
		$this->assertStringContainsString( 'columns-3', $result );
	}

	/**
	 * Applies a custom column count from shortcode attributes.
	 *
	 * @return void
	 */
	public function test_custom_columns(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1,2" columns="2"]' );

		$this->assertStringContainsString( '"columns":2', $result );
		$this->assertStringContainsString( 'columns-2', $result );
	}

	/**
	 * Creates nested image blocks for each gallery ID.
	 *
	 * @return void
	 */
	public function test_creates_inner_image_blocks(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="10,20,30"]' );

		$this->assertSame( 3, \substr_count( $result, '<!-- wp:image' ) );
		$this->assertStringContainsString( 'wp-image-10', $result );
		$this->assertStringContainsString( 'wp-image-20', $result );
		$this->assertStringContainsString( 'wp-image-30', $result );
	}

	/**
	 * Maps link=file to media linkTo.
	 *
	 * @return void
	 */
	public function test_link_to_file(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1" link="file"]' );

		$this->assertStringContainsString( '"linkTo":"media"', $result );
		$this->assertStringContainsString( '"linkDestination":"media"', $result );
	}

	/**
	 * Maps link=post to attachment linkTo.
	 *
	 * @return void
	 */
	public function test_link_to_post(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1" link="post"]' );

		$this->assertStringContainsString( '"linkTo":"attachment"', $result );
		$this->assertStringContainsString( '"linkDestination":"attachment"', $result );
	}

	/**
	 * Omits linkDestination for link=none.
	 *
	 * @return void
	 */
	public function test_link_to_none_omits_link_destination(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery ids="1" link="none"]' );

		$this->assertStringContainsString( '"linkTo":"none"', $result );
		$this->assertStringNotContainsString( 'linkDestination', $result );
	}

	/**
	 * Produces an empty gallery block when no IDs are given.
	 *
	 * @return void
	 */
	public function test_no_ids(): void {
		$handler = new GalleryHandler();

		$result = $handler->convert( '[gallery]' );

		$this->assertStringContainsString( '<!-- wp:gallery', $result );
		$this->assertStringNotContainsString( '<!-- wp:image', $result );
	}
}
