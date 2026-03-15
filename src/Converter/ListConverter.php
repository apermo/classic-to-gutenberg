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
		$result = preg_replace_callback(
			'/<li(?:\s[^>]*)?>(.*?)<\/li>/s',
			[ $this, 'convert_list_item' ],
			$html,
		);

		return $result ?? $html;
	}

	/**
	 * Convert a single list item, recursively processing nested lists.
	 *
	 * @param string[] $matches Regex match groups.
	 *
	 * @return string
	 */
	private function convert_list_item( array $matches ): string {
		$content = preg_replace_callback(
			'/<(ul|ol)(?:\s[^>]*)?>.*?<\/\1>/s',
			[ $this, 'convert_nested_list' ],
			$matches[1],
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
		$inner      = $this->convert_list( $matches[0], $is_ordered );

		return $this->wrap_block( 'list', $inner, $attrs );
	}
}
