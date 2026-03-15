<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <blockquote> tags to core/quote blocks with inner paragraph blocks.
 */
class QuoteConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'blockquote' ];
	}

	/**
	 * Convert a blockquote element to a quote block.
	 *
	 * @param string $html The blockquote HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		if ( ! \preg_match( '/<blockquote(?:\s[^>]*)?>(.*)<\/blockquote>/si', $html, $match ) ) {
			return $this->wrap_block( 'html', $html );
		}
		$inner = \trim( $match[1] );

		$converted_inner = $this->convert_inner_content( $inner );
		$content         = '<blockquote class="wp-block-quote">' . $converted_inner . '</blockquote>';

		return $this->wrap_block( 'quote', $content );
	}

	/**
	 * Convert inner blockquote content: wrap <p> in paragraph blocks, keep <cite> as-is.
	 *
	 * Handles wpautop wrapping <cite> in <p> tags by detecting paragraphs
	 * that contain only a <cite> element and stripping the <p> wrapper.
	 *
	 * @param string $inner Inner HTML of the blockquote.
	 *
	 * @return string
	 */
	private function convert_inner_content( string $inner ): string {
		$parts = \preg_split(
			'/(<p(?:\s[^>]*)?>.*?<\/p>|<cite>.*?<\/cite>)/s',
			$inner,
			-1,
			\PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY,
		);

		if ( $parts === false ) {
			return $inner;
		}

		$result = '';
		$prev_was_paragraph = false;

		foreach ( $parts as $part ) {
			$part = \trim( $part );
			if ( $part === '' ) {
				continue;
			}

			if ( $this->is_cite_paragraph( $part ) ) {
				$result .= $this->extract_cite( $part );
				$prev_was_paragraph = false;
			} elseif ( \preg_match( '/^<p(?:\s[^>]*)?>.*<\/p>$/s', $part ) ) {
				if ( $prev_was_paragraph ) {
					$result .= "\n\n";
				}
				$result .= "<!-- wp:paragraph -->\n" . $part . "\n<!-- /wp:paragraph -->";
				$prev_was_paragraph = true;
			} else {
				$result .= $part;
				$prev_was_paragraph = false;
			}
		}

		return $result;
	}

	/**
	 * Check if a paragraph contains only a <cite> element.
	 *
	 * WordPress wpautop wraps standalone <cite> tags in <p> tags.
	 *
	 * @param string $part The HTML part to check.
	 *
	 * @return bool
	 */
	private function is_cite_paragraph( string $part ): bool {
		return (bool) \preg_match( '/^<p(?:\s[^>]*)?>\s*<cite>.*?<\/cite>\s*<\/p>$/s', $part );
	}

	/**
	 * Extract <cite> element from a paragraph wrapper.
	 *
	 * @param string $part The <p><cite>...</cite></p> HTML.
	 *
	 * @return string The bare <cite>...</cite> element.
	 */
	private function extract_cite( string $part ): string {
		if ( \preg_match( '/<cite>.*?<\/cite>/s', $part, $match ) ) {
			return $match[0];
		}
		return $part;
	}
}
