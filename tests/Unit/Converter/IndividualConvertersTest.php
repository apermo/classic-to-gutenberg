<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\HeadingConverter;
use Apermo\ClassicToGutenberg\Converter\ImageConverter;
use Apermo\ClassicToGutenberg\Converter\ListConverter;
use Apermo\ClassicToGutenberg\Converter\QuoteConverter;
use Apermo\ClassicToGutenberg\Converter\Shortcode\CaptionHandler;
use Apermo\ClassicToGutenberg\Converter\Shortcode\GalleryHandler;
use Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;
use Apermo\ClassicToGutenberg\Converter\TableConverter;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Covers individual converter behavior in isolation.
 */
class IndividualConvertersTest extends TestCase {

	/**
	 * Sets up WordPress function stubs.
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
	 * Tears down WordPress function stubs.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verifies heading conversion preserves classes and adds level attributes.
	 *
	 * @return void
	 */
	public function test_heading_converter_adds_heading_class_and_level(): void {
		$result = ( new HeadingConverter() )->convert( '<h3 class="intro">Hello</h3>' );

		$this->assertSame(
			"<!-- wp:heading {\"level\":3} -->\n<h3 class=\"intro wp-block-heading\">Hello</h3>\n<!-- /wp:heading -->",
			$result,
		);
	}

	/**
	 * Verifies list conversion requires closed lists and wraps items.
	 *
	 * @return void
	 */
	public function test_list_converter_wraps_ordered_items(): void {
		$converter = new ListConverter();

		$this->assertFalse( $converter->can_convert( 'ol', '<ol><li>One</li>' ) );

		$result = $converter->convert( '<ol><li>One</li><li>Two</li></ol>' );

		$this->assertStringContainsString( '<!-- wp:list {"ordered":true} -->', $result );
		$this->assertStringContainsString( '<ol class="wp-block-list">', $result );
		$this->assertStringContainsString( "<!-- wp:list-item -->\n<li>One</li>\n<!-- /wp:list-item -->", $result );
		$this->assertStringContainsString( "<!-- wp:list-item -->\n<li>Two</li>\n<!-- /wp:list-item -->", $result );
	}

	/**
	 * Verifies quote conversion wraps paragraphs and preserves citations.
	 *
	 * @return void
	 */
	public function test_quote_converter_wraps_paragraphs_and_unwraps_cite_paragraph(): void {
		$result = ( new QuoteConverter() )->convert(
			'<blockquote><p>Quoted text.</p><p><cite>Author</cite></p></blockquote>',
		);

		$this->assertStringContainsString( '<!-- wp:quote -->', $result );
		$this->assertStringContainsString( '<blockquote class="wp-block-quote">', $result );
		$this->assertStringContainsString( "<!-- wp:paragraph -->\n<p>Quoted text.</p>\n<!-- /wp:paragraph -->", $result );
		$this->assertStringContainsString( '<cite>Author</cite>', $result );
		$this->assertStringNotContainsString( '<p><cite>Author</cite></p>', $result );
	}

	/**
	 * Verifies table conversion requires sections and adds block wrappers.
	 *
	 * @return void
	 */
	public function test_table_converter_requires_sections_and_adds_fixed_layout(): void {
		$converter = new TableConverter();

		$this->assertFalse( $converter->can_convert( 'table', '<table><tr><td>A</td></tr></table>' ) );

		$result = $converter->convert(
			'<table><thead><tr><th>Name</th></tr></thead><tbody><tr><td>Ada</td></tr></tbody></table>',
		);

		$this->assertStringContainsString( '<!-- wp:table -->', $result );
		$this->assertStringContainsString( '<figure class="wp-block-table">', $result );
		$this->assertStringContainsString( '<table class="has-fixed-layout">', $result );
	}

	/**
	 * Verifies image conversion extracts attributes and strips image alignment classes.
	 *
	 * @return void
	 */
	public function test_image_converter_extracts_attrs_from_standalone_image(): void {
		$result = ( new ImageConverter() )->convert(
			'<img class="aligncenter size-large wp-image-42" src="/photo.jpg" width="640" height="480" />',
		);

		$this->assertStringContainsString(
			'<!-- wp:image {"id":42,"align":"center","width":640,"height":480} -->',
			$result,
		);
		$this->assertStringContainsString( '<figure class="wp-block-image aligncenter">', $result );
		$this->assertStringContainsString( 'class="size-large wp-image-42"', $result );
		$this->assertStringNotContainsString( 'class="aligncenter size-large', $result );
	}

	/**
	 * Verifies shortcode conversion dispatches handlers and falls back for unknown tags.
	 *
	 * @return void
	 */
	public function test_shortcode_converter_dispatches_registered_handler_and_falls_back(): void {
		$handler = new class() implements ShortcodeHandlerInterface {
			/**
			 * Returns the shortcode tag this handler processes.
			 *
			 * @return string
			 */
			public function get_shortcode_tag(): string {
				return 'demo';
			}

			/**
			 * Converts the shortcode into deterministic test output.
			 *
			 * @param string $shortcode Shortcode input.
			 *
			 * @return string
			 */
			public function convert( string $shortcode ): string {
				return 'handled:' . $shortcode;
			}
		};

		$converter = new ShortcodeConverter( [ $handler ] );

		$this->assertSame( 'handled:[demo id="1"]', $converter->convert( '[demo id="1"]' ) );
		$this->assertSame(
			"<!-- wp:shortcode -->\n[unknown]\n<!-- /wp:shortcode -->",
			$converter->convert( '[unknown]' ),
		);
	}

	/**
	 * Verifies caption shortcodes convert to image blocks.
	 *
	 * @return void
	 */
	public function test_caption_handler_converts_caption_shortcode(): void {
		$result = ( new CaptionHandler() )->convert(
			'[caption id="attachment_55" align="alignright" width="300"]<img src="/cat.jpg" class="wp-image-55" width="300" height="200"> Cat[/caption]',
		);

		$this->assertStringContainsString(
			'<!-- wp:image {"id":55,"align":"right","width":300,"height":200} -->',
			$result,
		);
		$this->assertStringContainsString( '<figure class="wp-block-image alignright">', $result );
		$this->assertStringContainsString( '<figcaption class="wp-element-caption">Cat</figcaption>', $result );
	}

	/**
	 * Verifies gallery shortcode IDs, columns, and link mode.
	 *
	 * @return void
	 */
	public function test_gallery_handler_converts_ids_columns_and_link_mode(): void {
		$result = ( new GalleryHandler() )->convert( '[gallery ids="11,12" columns="2" link="file"]' );

		$this->assertStringContainsString( '<!-- wp:gallery {"columns":2,"linkTo":"media"} -->', $result );
		$this->assertStringContainsString( '<figure class="wp-block-gallery has-nested-images columns-2">', $result );
		$this->assertStringContainsString( '<!-- wp:image {"id":11,"linkDestination":"media"} -->', $result );
		$this->assertStringContainsString( 'class="wp-image-12"', $result );
	}
}
