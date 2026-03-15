<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

use WP_HTML_Tag_Processor;

/**
 * Converts <table> tags to core/table blocks.
 */
class TableConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'table' ];
	}

	/**
	 * Refine matching: require proper thead/tbody structure.
	 *
	 * @param string $tag_name The tag name.
	 * @param string $html     The table HTML.
	 *
	 * @return bool
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		if ( ! parent::can_convert( $tag_name, $html ) ) {
			return false;
		}

		return (bool) \preg_match( '/<thead/i', $html ) && (bool) \preg_match( '/<tbody/i', $html );
	}

	/**
	 * Convert a table element to a table block.
	 *
	 * @param string $html The table HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		$collapsed = $this->collapse_whitespace( $html );

		$processor = new WP_HTML_Tag_Processor( $collapsed );
		if ( $processor->next_tag( [ 'tag_name' => 'table' ] ) ) {
			$existing = $processor->get_attribute( 'class' );
			$classes  = $existing !== null ? $existing . ' has-fixed-layout' : 'has-fixed-layout';
			$processor->set_attribute( 'class', $classes );
		}
		$table_html = (string) $processor;

		$content = '<figure class="wp-block-table">' . $table_html . '</figure>';

		return $this->wrap_block( 'table', $content );
	}

	/**
	 * Collapse whitespace between table child elements.
	 *
	 * @param string $html Table HTML.
	 *
	 * @return string
	 */
	private function collapse_whitespace( string $html ): string {
		return (string) \preg_replace( '/>\s+</', '><', $html );
	}
}
