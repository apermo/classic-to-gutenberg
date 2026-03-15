<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Parser;

/**
 * Splits HTML (post-wpautop) into top-level elements for block conversion.
 *
 * Uses regex-based scanning because WP_HTML_Tag_Processor cannot match
 * opening/closing tag pairs needed to identify top-level element boundaries.
 */
class TopLevelSplitter {

	/**
	 * Block-level tags the splitter recognizes.
	 */
	private const BLOCK_TAGS = [
		'p',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'ul',
		'ol',
		'blockquote',
		'table',
		'figure',
		'div',
		'pre',
		'hr',
		'img',
	];

	/**
	 * Void (self-closing) tags that have no closing tag.
	 */
	private const VOID_TAGS = [ 'hr', 'img' ];

	/**
	 * Split HTML into top-level elements.
	 *
	 * @param string $html HTML content already processed by wpautop().
	 *
	 * @return TopLevelElement[]
	 */
	public function split( string $html ): array {
		$html = $this->normalize_br( $html );

		$elements = [];
		$length   = strlen( $html );
		$offset   = 0;

		while ( $offset < $length ) {
			$offset = $this->skip_whitespace( $html, $offset );
			if ( $offset >= $length ) {
				break;
			}

			$element = $this->match_comment( $html, $offset )
				?? $this->match_shortcode( $html, $offset )
				?? $this->match_block_tag( $html, $offset )
				?? $this->match_text( $html, $offset );

			if ( $element !== null ) {
				$elements[] = $element;
				$offset     += strlen( $element->html );
			} else {
				++$offset;
			}
		}

		return $elements;
	}

	/**
	 * Skip whitespace characters starting at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Current position.
	 *
	 * @return int New position after whitespace.
	 */
	private function skip_whitespace( string $html, int $offset ): int {
		if ( preg_match( '/\G\s+/', $html, $match, 0, $offset ) ) {
			return $offset + strlen( $match[0] );
		}
		return $offset;
	}

	/**
	 * Try to match <!--more--> or <!--nextpage--> at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Current position.
	 *
	 * @return TopLevelElement|null
	 */
	private function match_comment( string $html, int $offset ): ?TopLevelElement {
		if ( preg_match( '/\G<!--more-->/i', $html, $match, 0, $offset ) ) {
			return new TopLevelElement( '__more__', $match[0] );
		}

		if ( preg_match( '/\G<!--nextpage-->/i', $html, $match, 0, $offset ) ) {
			return new TopLevelElement( '__nextpage__', $match[0] );
		}

		return null;
	}

	/**
	 * Try to match a shortcode pattern at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Current position.
	 *
	 * @return TopLevelElement|null
	 */
	private function match_shortcode( string $html, int $offset ): ?TopLevelElement {
		if ( preg_match( '/\G\[(\w[\w-]*)(?:\s[^\]]*)?\](?:.*?\[\/\1\])?/s', $html, $match, 0, $offset ) ) {
			return new TopLevelElement( '__shortcode__', $match[0] );
		}

		return null;
	}

	/**
	 * Try to match an opening block-level tag at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Current position.
	 *
	 * @return TopLevelElement|null
	 */
	private function match_block_tag( string $html, int $offset ): ?TopLevelElement {
		$tags_pattern = implode( '|', self::BLOCK_TAGS );

		if ( ! preg_match( '/\G<(' . $tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)/i', $html, $match, 0, $offset ) ) {
			return null;
		}

		$tag_name = strtolower( $match[1] );

		if ( in_array( $tag_name, self::VOID_TAGS, true ) ) {
			return new TopLevelElement( $tag_name, $match[0] );
		}

		$element_html = $this->extract_element( $html, $offset, $tag_name );

		return new TopLevelElement( $tag_name, $element_html );
	}

	/**
	 * Try to match non-block text content at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Current position.
	 *
	 * @return TopLevelElement|null
	 */
	private function match_text( string $html, int $offset ): ?TopLevelElement {
		$text = $this->extract_text( $html, $offset );

		if ( $text === '' ) {
			return null;
		}

		return new TopLevelElement( '__text__', $text );
	}

	/**
	 * Normalize <br> to <br/>.
	 *
	 * @param string $html Input HTML.
	 *
	 * @return string
	 */
	private function normalize_br( string $html ): string {
		return (string) preg_replace( '/<br\s*\/?>/', '<br/>', $html );
	}

	/**
	 * Extract a complete element from the HTML starting at given offset.
	 *
	 * Tracks nesting depth to find the correct closing tag.
	 * Handles implicit <p> closing (a new <p> at depth 0 closes the previous <p>).
	 *
	 * @param string $html     Full HTML string.
	 * @param int    $offset   Start position (at the opening tag).
	 * @param string $tag_name Lowercase tag name.
	 *
	 * @return string The complete element HTML.
	 */
	private function extract_element( string $html, int $offset, string $tag_name ): string {
		$length = strlen( $html );

		preg_match( '/\G<' . $tag_name . '(?:\s[^>]*)?>/', $html, $open_match, 0, $offset );
		$search_pos   = $offset + strlen( $open_match[0] );
		$depth        = 1;
		$open_pattern = '/<' . $tag_name . '(?:\s[^>]*)?>|<\/' . $tag_name . '\s*>/i';

		while ( $search_pos < $length && $depth > 0 ) {
			if ( ! preg_match( $open_pattern, $html, $match, PREG_OFFSET_CAPTURE, $search_pos ) ) {
				return $this->extract_until_next_block( $html, $offset );
			}

			$match_str    = $match[0][0];
			$match_offset = $match[0][1];
			$is_closing   = str_starts_with( $match_str, '</' );

			if ( $is_closing ) {
				--$depth;
			} else {
				if ( $tag_name === 'p' && $depth === 1 ) {
					return substr( $html, $offset, $match_offset - $offset );
				}
				++$depth;
			}

			$search_pos = $match_offset + strlen( $match_str );
		}

		if ( $depth > 0 ) {
			return $this->extract_until_next_block( $html, $offset );
		}

		return substr( $html, $offset, $search_pos - $offset );
	}

	/**
	 * Extract content from offset until the next block-level element starts.
	 *
	 * Used for broken/unclosed elements.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Start position.
	 *
	 * @return string
	 */
	private function extract_until_next_block( string $html, int $offset ): string {
		$tags_pattern = implode( '|', self::BLOCK_TAGS );
		$pattern      = '/(?<=\n|\s)<(?:' . $tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)/i';

		preg_match( '/\G<\w+(?:\s[^>]*)?>/', $html, $open_match, 0, $offset );
		$after_open = $offset + strlen( $open_match[0] );

		if ( preg_match( $pattern, $html, $match, PREG_OFFSET_CAPTURE, $after_open ) ) {
			$result = substr( $html, $offset, $match[0][1] - $offset );
			return rtrim( $result );
		}

		return rtrim( substr( $html, $offset ) );
	}

	/**
	 * Extract non-block-level text content starting at offset.
	 *
	 * @param string $html   Full HTML string.
	 * @param int    $offset Start position.
	 *
	 * @return string
	 */
	private function extract_text( string $html, int $offset ): string {
		$tags_pattern = implode( '|', self::BLOCK_TAGS );
		$stop_pattern = '/<(?:' . $tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)|<!--(?:more|nextpage)-->|\[(\w[\w-]*)(?:\s[^\]]*)?]/i';

		if ( preg_match( $stop_pattern, $html, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			$stop_offset = $match[0][1];
			if ( $stop_offset === $offset ) {
				return '';
			}
			return rtrim( substr( $html, $offset, $stop_offset - $offset ) );
		}

		return rtrim( substr( $html, $offset ) );
	}
}
