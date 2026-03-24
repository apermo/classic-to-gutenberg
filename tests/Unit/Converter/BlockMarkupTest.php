<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Converter;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BlockMarkup utility class.
 */
class BlockMarkupTest extends TestCase {

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
	 * Wrap produces correct block markup without attributes.
	 *
	 * @return void
	 */
	public function test_wrap_without_attrs(): void {
		$result = BlockMarkup::wrap( 'paragraph', '<p>Hello</p>' );

		$this->assertSame(
			"<!-- wp:paragraph -->\n<p>Hello</p>\n<!-- /wp:paragraph -->",
			$result,
		);
	}

	/**
	 * Wrap produces correct block markup with attributes.
	 *
	 * @return void
	 */
	public function test_wrap_with_attrs(): void {
		$result = BlockMarkup::wrap( 'column', '<div>Inner</div>', [ 'width' => '50%' ] );

		$this->assertSame(
			"<!-- wp:column {\"width\":\"50%\"} -->\n<div>Inner</div>\n<!-- /wp:column -->",
			$result,
		);
	}

	/**
	 * Self-closing produces correct block markup without attributes.
	 *
	 * @return void
	 */
	public function test_self_closing_without_attrs(): void {
		$result = BlockMarkup::self_closing( 'nextpage' );

		$this->assertSame( '<!-- wp:nextpage /-->', $result );
	}

	/**
	 * Self-closing produces correct block markup with attributes.
	 *
	 * @return void
	 */
	public function test_self_closing_with_attrs(): void {
		$result = BlockMarkup::self_closing( 'spacer', [ 'height' => '32px' ] );

		$this->assertSame( '<!-- wp:spacer {"height":"32px"} /-->', $result );
	}

	/**
	 * Attributes with slashes and unicode are not escaped.
	 *
	 * @return void
	 */
	public function test_attrs_preserve_slashes_and_unicode(): void {
		$result = BlockMarkup::wrap(
			'image',
			'<figure></figure>',
			[
				'url' => 'https://example.com/img.jpg',
				'alt' => 'Ünïcödé',
			],
		);

		$this->assertStringContainsString( 'https://example.com/img.jpg', $result );
		$this->assertStringContainsString( 'Ünïcödé', $result );
	}
}
