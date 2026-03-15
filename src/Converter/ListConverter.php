<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <ul>/<ol> tags to core/list blocks with nested list-item inner blocks.
 */
class ListConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'ul', 'ol' ];
	}

	/**
	 * Refine matching: require properly closed list.
	 *
	 * @param string $tag_name The tag name.
	 * @param string $html     The list HTML.
	 *
	 * @return bool
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		if ( ! parent::can_convert( $tag_name, $html ) ) {
			return false;
		}

		$close_tag = '</' . $tag_name . '>';
		return stripos( $html, $close_tag ) !== false;
	}

	/**
	 * Convert a list element to a list block.
	 *
	 * @param string $html The list HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		$is_ordered = (bool) preg_match( '/^\s*<ol/i', $html );
		$attrs      = $is_ordered ? [ 'ordered' => true ] : [];

		$inner = $this->convert_list( $html, $is_ordered );

		return $this->wrap_block( 'list', $inner, $attrs );
	}

	/**
	 * Convert a list element (ul/ol) into Gutenberg markup.
	 *
	 * @param string $html       The full list HTML.
	 * @param bool   $is_ordered Whether this is an ordered list.
	 *
	 * @return string
	 */
	private function convert_list( string $html, bool $is_ordered ): string {
		$tag_name = $is_ordered ? 'ol' : 'ul';

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( $processor->next_tag( [ 'tag_name' => $tag_name ] ) ) {
			$existing = $processor->get_attribute( 'class' );
			$classes  = $existing !== null ? $existing . ' wp-block-list' : 'wp-block-list';
			$processor->set_attribute( 'class', $classes );
		}
		$html = (string) $processor;

		return $this->wrap_list_items( $html );
	}

	/**
	 * Wrap each <li> in a list-item block, handling nested lists recursively.
	 *
	 * @param string $html The list HTML with wp-block-list class added.
	 *
	 * @return string
	 */
	private function wrap_list_items( string $html ): string {
		preg_match( '/^(<(?:ul|ol)(?:\s[^>]*)?>)\s*/i', $html, $open_match );
		preg_match( '/\s*(<\/(?:ul|ol)\s*>)\s*$/i', $html, $close_match );

		$open_tag  = $open_match[1];
		$close_tag = $close_match[1];

		$item_contents = $this->extract_list_items( $html );

		$items = [];
		foreach ( $item_contents as $content ) {
			$items[] = $this->convert_list_item_content( $content );
		}

		return $open_tag . implode( "\n\n", $items ) . $close_tag;
	}

	/**
	 * Extract inner content of each top-level <li> in a list, respecting nesting.
	 *
	 * @param string $html The list HTML.
	 *
	 * @return string[] Array of inner content strings for each <li>.
	 */
	private function extract_list_items( string $html ): array {
		$items  = [];
		$offset = 0;
		$length = strlen( $html );

		while ( $offset < $length ) {
			if ( ! preg_match( '/<li(?:\s[^>]*)?>/', $html, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
				break;
			}

			$li_start      = $match[0][1];
			$content_start = $li_start + strlen( $match[0][0] );
			$content_end   = $this->find_closing_li( $html, $content_start );

			$items[] = substr( $html, $content_start, $content_end - $content_start );
			$offset  = $content_end + strlen( '</li>' );
		}

		return $items;
	}

	/**
	 * Find the position of the closing </li> tag that matches an opening <li>.
	 *
	 * Tracks nesting depth to handle nested lists with their own <li> tags.
	 *
	 * @param string $html   The HTML to search in.
	 * @param int    $offset Position after the opening <li> tag.
	 *
	 * @return int Position of the matching </li>.
	 */
	private function find_closing_li( string $html, int $offset ): int {
		$depth  = 1;
		$length = strlen( $html );

		while ( $offset < $length && $depth > 0 ) {
			if ( ! preg_match( '/<li(?:\s[^>]*)?>|<\/li\s*>/i', $html, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
				return $length;
			}

			$match_str    = $match[0][0];
			$match_offset = $match[0][1];

			if ( stripos( $match_str, '</li' ) === 0 ) {
				--$depth;
				if ( $depth === 0 ) {
					return $match_offset;
				}
			} else {
				++$depth;
			}

			$offset = $match_offset + strlen( $match_str );
		}

		return $length;
	}

	/**
	 * Convert a single list item's content, recursively processing nested lists.
	 *
	 * @param string $content The inner content of the <li>.
	 *
	 * @return string
	 */
	private function convert_list_item_content( string $content ): string {
		$content = (string) preg_replace_callback(
			'/\s*<(ul|ol)(?:\s[^>]*)?>[\s\S]*?<\/\1\s*>\s*/i',
			[ $this, 'convert_nested_list' ],
			$content,
		);

		return "<!-- wp:list-item -->\n<li>" . $content . "</li>\n<!-- /wp:list-item -->";
	}

	/**
	 * Convert a nested list found inside a list item.
	 *
	 * @param string[] $matches Regex match groups.
	 *
	 * @return string
	 */
	private function convert_nested_list( array $matches ): string {
		$nested_tag = strtolower( $matches[1] );
		$is_ordered = $nested_tag === 'ol';
		$attrs      = $is_ordered ? [ 'ordered' => true ] : [];
		$inner      = $this->convert_list( trim( $matches[0] ), $is_ordered );

		return $this->wrap_block( 'list', $inner, $attrs );
	}
}
