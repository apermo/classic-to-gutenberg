<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Parser\TopLevelElement;
use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;
use Closure;

/**
 * Full content conversion pipeline: raw classic content to Gutenberg blocks.
 */
class ContentConverter {

	/**
	 * The block converter factory.
	 *
	 * @var BlockConverterFactory
	 */
	private BlockConverterFactory $factory;

	/**
	 * The top-level HTML splitter.
	 *
	 * @var TopLevelSplitter
	 */
	private TopLevelSplitter $splitter;

	/**
	 * Callable that applies wpautop-style formatting.
	 *
	 * @var Closure
	 */
	private Closure $wpautop;

	/**
	 * Create a new content converter.
	 *
	 * @param BlockConverterFactory $factory  The converter factory.
	 * @param TopLevelSplitter      $splitter The HTML splitter.
	 * @param Closure               $wpautop  Callable for wpautop formatting.
	 */
	public function __construct( BlockConverterFactory $factory, TopLevelSplitter $splitter, Closure $wpautop ) {
		$this->factory  = $factory;
		$this->splitter = $splitter;
		$this->wpautop  = $wpautop;
	}

	/**
	 * Convert classic editor content to Gutenberg block markup.
	 *
	 * @param string $content Raw classic editor content.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $content ): string {
		/**
		 * Filter content before the conversion pipeline.
		 *
		 * @since 0.1.0
		 *
		 * @param string $content The raw content.
		 *
		 * @return string
		 */
		$content = apply_filters( 'classic_to_gutenberg_pre_convert', $content );

		$placeholders = [];
		$content      = $this->extract_special_comments( $content, $placeholders );

		$content = ( $this->wpautop )( $content );

		$content = $this->unwrap_double_paragraphs( $content );

		$content = $this->restore_special_comments( $content, $placeholders );

		$elements = $this->splitter->split( $content );
		$elements = $this->retag_wrapped_elements( $elements );

		$blocks = [];
		foreach ( $elements as $element ) {
			$converter = $this->factory->get_converter( $element->tag_name, $element->html );
			$blocks[]  = $converter->convert( $element->html );
		}

		$result = \implode( "\n\n", $blocks );

		/**
		 * Filter content after the conversion pipeline.
		 *
		 * @since 0.1.0
		 *
		 * @param string $result The converted Gutenberg markup.
		 *
		 * @return string
		 */
		return apply_filters( 'classic_to_gutenberg_post_convert', $result );
	}

	/**
	 * Extract special comments before wpautop processing.
	 *
	 * @param string               $content      The content.
	 * @param array<string,string> $placeholders Map populated by reference.
	 *
	 * @return string Content with placeholders.
	 */
	private function extract_special_comments( string $content, array &$placeholders ): string {
		$index = 0;

		return (string) \preg_replace_callback(
			'/<!--(more|nextpage)-->/',
			static function ( array $matches ) use ( &$placeholders, &$index ): string {
				$key                  = '%%CTG_PLACEHOLDER_' . $index . '%%';
				$placeholders[ $key ] = $matches[0];
				$index++;
				return $key;
			},
			$content,
		);
	}

	/**
	 * Restore special comments after wpautop processing.
	 *
	 * @param string               $content      The wpautop'd content.
	 * @param array<string,string> $placeholders Map of placeholder to original.
	 *
	 * @return string Content with comments restored.
	 */
	private function restore_special_comments( string $content, array $placeholders ): string {
		foreach ( $placeholders as $key => $original ) {
			$content = \str_replace(
				[ '<p>' . $key . '</p>', $key ],
				[ $original, $original ],
				$content,
			);
		}

		return $content;
	}

	/**
	 * Unwrap double paragraph tags created by wpautop.
	 *
	 * WordPress wpautop wraps existing `<p style="...">` in another `<p>`, producing
	 * `<p><p style="...">content</p></p>`. This strips the outer wrapper.
	 *
	 * @param string $content The wpautop'd content.
	 *
	 * @return string
	 */
	private function unwrap_double_paragraphs( string $content ): string {
		$result = \preg_replace(
			'/<p>\s*(<p\s[^>]*>.*?<\/p>)\s*<\/p>/s',
			'$1',
			$content,
		);

		if ( $result === null ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional diagnostic logging.
			\error_log( 'classic-to-gutenberg: preg_replace failed in unwrap_double_paragraphs' );
			return $content;
		}

		return $result;
	}

	/**
	 * Re-tag elements that wpautop incorrectly wrapped in paragraphs.
	 *
	 * Wpautop wraps shortcodes and standalone images in <p> tags.
	 * This detects those cases and re-tags them for correct converter dispatch.
	 *
	 * @param TopLevelElement[] $elements Parsed elements.
	 *
	 * @return TopLevelElement[]
	 */
	private function retag_wrapped_elements( array $elements ): array {
		$result = [];

		foreach ( $elements as $element ) {
			$retagged = $this->try_retag_paragraph( $element );
			$result[] = $retagged ?? $element;
		}

		return $result;
	}

	/**
	 * Try to re-tag a paragraph element if it wraps a shortcode or image.
	 *
	 * @param TopLevelElement $element The element to check.
	 *
	 * @return TopLevelElement|null Re-tagged element, or null if no change needed.
	 */
	private function try_retag_paragraph( TopLevelElement $element ): ?TopLevelElement {
		if ( $element->tag_name !== 'p' ) {
			return null;
		}

		return $this->try_retag_shortcode( $element )
			?? $this->try_retag_image( $element );
	}

	/**
	 * Try to extract a shortcode from a paragraph-wrapped element.
	 *
	 * @param TopLevelElement $element The paragraph element.
	 *
	 * @return TopLevelElement|null Re-tagged shortcode element, or null.
	 */
	private function try_retag_shortcode( TopLevelElement $element ): ?TopLevelElement {
		$inner = \trim( \strip_tags( $element->html, '<img><a>' ) );

		if ( ! \preg_match( '/^\[(\w[\w-]*)/', $inner ) ) {
			return null;
		}

		if ( ! \preg_match( '/\[(\w[\w-]*)(?:\s[^\]]*)?\](?:.*?\[\/\1\])?/s', $element->html, $match ) ) {
			return null;
		}

		return new TopLevelElement( '__shortcode__', $match[0] );
	}

	/**
	 * Try to extract a standalone image from a paragraph-wrapped element.
	 *
	 * @param TopLevelElement $element The paragraph element.
	 *
	 * @return TopLevelElement|null Re-tagged image element, or null.
	 */
	private function try_retag_image( TopLevelElement $element ): ?TopLevelElement {
		if ( ! \preg_match( '/^<p[^>]*>\s*(?:<a\s[^>]*>)?\s*<img\s/', $element->html ) ) {
			return null;
		}

		$stripped = (string) \preg_replace( '/<\/a>/', '', $element->html );
		if ( \preg_match( '/[^<>]\s*<\/p>/', $stripped ) ) {
			return null;
		}

		if ( ! \preg_match( '/(?:<a\s[^>]*>)?\s*<img\s[^>]*\/?>\s*(?:<\/a>)?/i', $element->html, $match ) ) {
			return null;
		}

		return new TopLevelElement( 'img', \trim( $match[0] ) );
	}
}
