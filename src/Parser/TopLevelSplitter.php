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
		$pos      = 0;

		while ( $pos < $length ) {
			// Skip whitespace between elements.
			if ( preg_match( '/\G\s+/', $html, $m, 0, $pos ) ) {
				$pos += strlen( $m[0] );
				if ( $pos >= $length ) {
					break;
				}
			}

			// Match <!--more--> / <!--nextpage-->.
			if ( preg_match( '/\G<!--more-->/i', $html, $m, 0, $pos ) ) {
				$elements[] = new TopLevelElement( '__more__', $m[0] );
				$pos       += strlen( $m[0] );
				continue;
			}

			if ( preg_match( '/\G<!--nextpage-->/i', $html, $m, 0, $pos ) ) {
				$elements[] = new TopLevelElement( '__nextpage__', $m[0] );
				$pos       += strlen( $m[0] );
				continue;
			}

			// Match shortcode pattern at top level.
			if ( preg_match( '/\G\[(\w[\w-]*)(?:\s[^\]]*)?\](?:.*?\[\/\1\])?/s', $html, $m, 0, $pos ) ) {
				$elements[] = new TopLevelElement( '__shortcode__', $m[0] );
				$pos       += strlen( $m[0] );
				continue;
			}

			// Match opening block-level tag.
			$block_tags_pattern = implode( '|', self::BLOCK_TAGS );
			if ( preg_match( '/\G<(' . $block_tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)/i', $html, $m, 0, $pos ) ) {
				$tag_name = strtolower( $m[1] );

				// Void tags: emit immediately.
				if ( in_array( $tag_name, self::VOID_TAGS, true ) ) {
					$elements[] = new TopLevelElement( $tag_name, $m[0] );
					$pos       += strlen( $m[0] );
					continue;
				}

				// Find matching close tag with nesting depth tracking.
				$element_html = $this->extract_element( $html, $pos, $tag_name );
				$elements[]   = new TopLevelElement( $tag_name, $element_html );
				$pos         += strlen( $element_html );
				continue;
			}

			// Anything else: accumulate as __text__ until next block-level element, comment, or shortcode.
			$text = $this->extract_text( $html, $pos );
			if ( $text !== '' ) {
				$elements[] = new TopLevelElement( '__text__', $text );
				$pos       += strlen( $text );
				continue;
			}

			// Safety: advance by one character to avoid infinite loop.
			$pos++;
		}

		return $elements;
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
	 * Extract a complete element from the HTML starting at $pos.
	 *
	 * Tracks nesting depth to find the correct closing tag.
	 * Handles implicit <p> closing (a new <p> at depth 0 closes the previous <p>).
	 *
	 * @param string $html     Full HTML string.
	 * @param int    $pos      Start position (at the opening tag).
	 * @param string $tag_name Lowercase tag name.
	 *
	 * @return string The complete element HTML.
	 */
	private function extract_element( string $html, int $pos, string $tag_name ): string {
		$length = strlen( $html );

		// Match the opening tag to advance past it.
		preg_match( '/\G<' . $tag_name . '(?:\s[^>]*)?>/', $html, $open_match, 0, $pos );
		$search_pos = $pos + strlen( $open_match[0] );

		$depth         = 1;
		$open_pattern  = '/<' . $tag_name . '(?:\s[^>]*)?>|<\/' . $tag_name . '\s*>/i';
		$block_pattern = '/\G<(?:' . implode( '|', self::BLOCK_TAGS ) . ')(?:\s[^>]*)?(?:\/>|>)/i';

		while ( $search_pos < $length && $depth > 0 ) {
			if ( ! preg_match( $open_pattern, $html, $m, PREG_OFFSET_CAPTURE, $search_pos ) ) {
				// No matching close tag found — broken markup.
				// Take content until next block-level element at depth 0.
				return $this->extract_until_next_block( $html, $pos );
			}

			$match_str    = $m[0][0];
			$match_pos    = (int) $m[0][1];
			$is_close_tag = str_starts_with( $match_str, '</' );

			if ( $is_close_tag ) {
				$depth--;
			} else {
				// For <p>, a new <p> at depth 1 implicitly closes the previous.
				if ( $tag_name === 'p' && $depth === 1 ) {
					// Implicit close: return content up to this new <p>.
					return substr( $html, $pos, $match_pos - $pos );
				}
				$depth++;
			}

			$search_pos = $match_pos + strlen( $match_str );
		}

		if ( $depth > 0 ) {
			// Unclosed element — take until next block-level element.
			return $this->extract_until_next_block( $html, $pos );
		}

		return substr( $html, $pos, $search_pos - $pos );
	}

	/**
	 * Extract content from $pos until the next block-level element starts.
	 *
	 * Used for broken/unclosed elements.
	 *
	 * @param string $html Full HTML string.
	 * @param int    $pos  Start position.
	 *
	 * @return string
	 */
	private function extract_until_next_block( string $html, int $pos ): string {
		$block_tags_pattern = implode( '|', self::BLOCK_TAGS );
		$pattern            = '/(?<=\n|\s)<(?:' . $block_tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)/i';

		// Skip past the current opening tag before searching.
		preg_match( '/\G<\w+(?:\s[^>]*)?>/', $html, $open_match, 0, $pos );
		$after_open = $pos + strlen( $open_match[0] );

		// Look for the next block-level element.
		if ( preg_match( $pattern, $html, $m, PREG_OFFSET_CAPTURE, $after_open ) ) {
			$next_block_pos = (int) $m[0][1];
			$result         = substr( $html, $pos, $next_block_pos - $pos );
			return rtrim( $result );
		}

		// No more block elements — take the rest.
		return rtrim( substr( $html, $pos ) );
	}

	/**
	 * Extract non-block-level text content starting at $pos.
	 *
	 * @param string $html Full HTML string.
	 * @param int    $pos  Start position.
	 *
	 * @return string
	 */
	private function extract_text( string $html, int $pos ): string {
		$block_tags_pattern = implode( '|', self::BLOCK_TAGS );
		$stop_pattern       = '/<(?:' . $block_tags_pattern . ')(?:\s[^>]*)?(?:\/>|>)|<!--(?:more|nextpage)-->|\[(\w[\w-]*)(?:\s[^\]]*)?]/i';

		if ( preg_match( $stop_pattern, $html, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
			$stop_pos = (int) $m[0][1];
			if ( $stop_pos === $pos ) {
				return '';
			}
			return rtrim( substr( $html, $pos, $stop_pos - $pos ) );
		}

		// No more block elements — take the rest.
		return rtrim( substr( $html, $pos ) );
	}
}
