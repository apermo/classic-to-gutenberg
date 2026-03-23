<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;
use Apermo\ClassicToGutenberg\Converter\ParagraphConverter;
use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;
use Brain\Monkey;
use Brain\Monkey\Filters;
use Closure;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContentConverter.
 *
 * Uses a pass-through wpautop mock to test pipeline logic
 * without requiring WordPress.
 */
class ContentConverterTest extends TestCase {

	/**
	 * Set up Brain Monkey.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
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
	 * Empty input returns empty output.
	 *
	 * @return void
	 */
	public function test_empty_input_returns_empty(): void {
		$converter = $this->create_converter();

		$this->assertSame( '', $converter->convert( '' ) );
	}

	/**
	 * Pre-convert filter is applied.
	 *
	 * @return void
	 */
	public function test_pre_convert_filter_is_applied(): void {
		Filters\expectApplied( 'classic_to_gutenberg_pre_convert' )
			->once()
			->with( '<p>Hello</p>' );

		$converter = $this->create_converter();
		$converter->convert( '<p>Hello</p>' );
	}

	/**
	 * Post-convert filter is applied.
	 *
	 * @return void
	 */
	public function test_post_convert_filter_is_applied(): void {
		Filters\expectApplied( 'classic_to_gutenberg_post_convert' )
			->once();

		$converter = $this->create_converter();
		$converter->convert( '<p>Hello</p>' );
	}

	/**
	 * Special comments are extracted and restored.
	 *
	 * @return void
	 */
	public function test_more_comment_is_preserved(): void {
		$converter = $this->create_converter();

		$input  = '<p>Before</p><!--more--><p>After</p>';
		$result = $converter->convert( $input );

		$this->assertStringNotContainsString( '%%CTG_PLACEHOLDER', $result );
	}

	/**
	 * Nextpage comment is preserved through pipeline.
	 *
	 * @return void
	 */
	public function test_nextpage_comment_is_preserved(): void {
		$converter = $this->create_converter();

		$input  = '<p>Page 1</p><!--nextpage--><p>Page 2</p>';
		$result = $converter->convert( $input );

		$this->assertStringNotContainsString( '%%CTG_PLACEHOLDER', $result );
	}

	/**
	 * Double paragraph wrapping is unwrapped.
	 *
	 * @return void
	 */
	public function test_double_paragraph_is_unwrapped(): void {
		$converter = $this->create_converter(
			static fn( string $content ): string =>
				\str_replace(
					'<p style="text-align: center;">',
					'<p><p style="text-align: center;">',
					\str_replace( '</p>', '</p></p>', $content ),
				),
		);

		$input  = '<p style="text-align: center;">Centered</p>';
		$result = $converter->convert( $input );

		$this->assertStringNotContainsString( '<p><p', $result );
		$this->assertStringContainsString( '<p style="text-align: center;">', $result );
	}

	/**
	 * Shortcodes wrapped in paragraphs are re-tagged.
	 *
	 * @return void
	 */
	public function test_shortcodes_in_paragraphs_are_retagged(): void {
		$converter = $this->create_converter(
			static fn( string $content ): string =>
				'<p>' . $content . '</p>',
		);

		$input  = '[contact-form-7 id="123"]';
		$result = $converter->convert( $input );

		// Should not be wrapped in wp:paragraph.
		$this->assertStringNotContainsString( 'wp:paragraph', $result );
	}

	/**
	 * Create a ContentConverter with minimal setup.
	 *
	 * @param \Closure|null $wpautop Custom wpautop mock. Defaults to pass-through.
	 *
	 * @return ContentConverter
	 */
	private function create_converter( ?Closure $wpautop = null ): ContentConverter {
		$factory = new BlockConverterFactory( new HtmlBlockConverter() );
		$factory->register( new ParagraphConverter() );

		return new ContentConverter(
			$factory,
			new TopLevelSplitter(),
			$wpautop ?? static fn( string $content ): string => $content,
		);
	}
}
