<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Tests\Unit\Parser;

use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TopLevelSplitter.
 */
class TopLevelSplitterTest extends TestCase {

	/**
	 * The splitter under test.
	 *
	 * @var TopLevelSplitter
	 */
	private TopLevelSplitter $splitter;

	/**
	 * Set up the test splitter.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->splitter = new TopLevelSplitter();
	}

	/**
	 * Splits simple paragraphs.
	 *
	 * @return void
	 */
	public function test_splits_paragraphs(): void {
		$html     = '<p>First</p>' . "\n\n" . '<p>Second</p>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 2, $elements );
		$this->assertSame( 'p', $elements[0]->tag_name );
		$this->assertSame( '<p>First</p>', $elements[0]->html );
		$this->assertSame( 'p', $elements[1]->tag_name );
		$this->assertSame( '<p>Second</p>', $elements[1]->html );
	}

	/**
	 * Recognizes heading tags.
	 *
	 * @return void
	 */
	public function test_splits_headings(): void {
		$html     = '<h1>Title</h1>' . "\n\n" . '<h3>Subtitle</h3>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 2, $elements );
		$this->assertSame( 'h1', $elements[0]->tag_name );
		$this->assertSame( 'h3', $elements[1]->tag_name );
	}

	/**
	 * Recognizes void elements like hr and img.
	 *
	 * @return void
	 */
	public function test_splits_void_elements(): void {
		$html     = '<p>Before</p>' . "\n\n" . '<hr />' . "\n\n" . '<p>After</p>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 3, $elements );
		$this->assertSame( 'hr', $elements[1]->tag_name );
	}

	/**
	 * Recognizes standalone img tags.
	 *
	 * @return void
	 */
	public function test_splits_img_tags(): void {
		$html     = '<img src="test.jpg" alt="test" />';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'img', $elements[0]->tag_name );
	}

	/**
	 * Handles nested elements correctly.
	 *
	 * @return void
	 */
	public function test_handles_nested_elements(): void {
		$html     = '<ul><li>One</li><li>Two</li></ul>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'ul', $elements[0]->tag_name );
		$this->assertSame( $html, $elements[0]->html );
	}

	/**
	 * Detects <!--more--> markers.
	 *
	 * @return void
	 */
	public function test_detects_more_marker(): void {
		$html     = '<p>Excerpt</p>' . "\n\n" . '<!--more-->' . "\n\n" . '<p>Rest</p>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 3, $elements );
		$this->assertSame( '__more__', $elements[1]->tag_name );
		$this->assertSame( '<!--more-->', $elements[1]->html );
	}

	/**
	 * Detects <!--nextpage--> markers.
	 *
	 * @return void
	 */
	public function test_detects_nextpage_marker(): void {
		$html     = '<p>Page one</p>' . "\n\n" . '<!--nextpage-->' . "\n\n" . '<p>Page two</p>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 3, $elements );
		$this->assertSame( '__nextpage__', $elements[1]->tag_name );
	}

	/**
	 * Detects shortcodes.
	 *
	 * @return void
	 */
	public function test_detects_shortcodes(): void {
		$html     = '[gallery ids="1,2,3"]';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( '__shortcode__', $elements[0]->tag_name );
	}

	/**
	 * Detects enclosing shortcodes.
	 *
	 * @return void
	 */
	public function test_detects_enclosing_shortcodes(): void {
		$html     = '[caption id="42"]<img src="test.jpg" /> Caption text[/caption]';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( '__shortcode__', $elements[0]->tag_name );
	}

	/**
	 * Normalizes br tags to self-closing.
	 *
	 * @return void
	 */
	public function test_normalizes_br_tags(): void {
		$html     = '<p>Line one<br>Line two<br />Line three</p>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertStringContainsString( '<br/>', $elements[0]->html );
		$this->assertStringNotContainsString( '<br>', $elements[0]->html );
	}

	/**
	 * Handles mixed content types.
	 *
	 * @return void
	 */
	public function test_handles_mixed_content(): void {
		$html = '<h2>Title</h2>' . "\n\n"
			. '<p>Text</p>' . "\n\n"
			. '<ul><li>Item</li></ul>' . "\n\n"
			. '<!--more-->' . "\n\n"
			. '[gallery ids="1"]';

		$elements = $this->splitter->split( $html );

		$this->assertCount( 5, $elements );
		$this->assertSame( 'h2', $elements[0]->tag_name );
		$this->assertSame( 'p', $elements[1]->tag_name );
		$this->assertSame( 'ul', $elements[2]->tag_name );
		$this->assertSame( '__more__', $elements[3]->tag_name );
		$this->assertSame( '__shortcode__', $elements[4]->tag_name );
	}

	/**
	 * Empty input returns empty array.
	 *
	 * @return void
	 */
	public function test_empty_input_returns_empty(): void {
		$this->assertSame( [], $this->splitter->split( '' ) );
		$this->assertSame( [], $this->splitter->split( '   ' ) );
	}

	/**
	 * Handles blockquote with nested paragraphs.
	 *
	 * @return void
	 */
	public function test_handles_blockquote_with_nested_content(): void {
		$html     = "<blockquote>\n<p>Quote text</p>\n</blockquote>";
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'blockquote', $elements[0]->tag_name );
		$this->assertSame( $html, $elements[0]->html );
	}

	/**
	 * Handles table elements.
	 *
	 * @return void
	 */
	public function test_handles_table(): void {
		$html     = '<table><thead><tr><th>H</th></tr></thead><tbody><tr><td>D</td></tr></tbody></table>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'table', $elements[0]->tag_name );
	}

	/**
	 * Handles figure elements.
	 *
	 * @return void
	 */
	public function test_handles_figure(): void {
		$html     = '<figure><img src="test.jpg" /><figcaption>Caption</figcaption></figure>';
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'figure', $elements[0]->tag_name );
	}

	/**
	 * Handles pre elements (preserves internal content).
	 *
	 * @return void
	 */
	public function test_handles_pre(): void {
		$html     = "<pre>function hello() {\n    return true;\n}</pre>";
		$elements = $this->splitter->split( $html );

		$this->assertCount( 1, $elements );
		$this->assertSame( 'pre', $elements[0]->tag_name );
		$this->assertSame( $html, $elements[0]->html );
	}

	/**
	 * Handles broken/unclosed elements by taking content until next block.
	 *
	 * @return void
	 */
	public function test_handles_unclosed_elements(): void {
		$html     = "<div><span>Unclosed div\n\n<p>Next paragraph</p>";
		$elements = $this->splitter->split( $html );

		$this->assertCount( 2, $elements );
		$this->assertSame( 'div', $elements[0]->tag_name );
		$this->assertSame( 'p', $elements[1]->tag_name );
	}
}
