<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

use WP_HTML_Tag_Processor;

/**
 * Converts <pre> tags to core/preformatted blocks.
 */
class PreformattedConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'pre' ];
	}

	/**
	 * Convert a pre element to a preformatted block.
	 *
	 * @param string $html The pre HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		$processor = new WP_HTML_Tag_Processor( $html );
		if ( $processor->next_tag( [ 'tag_name' => 'pre' ] ) ) {
			$existing = $processor->get_attribute( 'class' );
			$classes  = $existing !== null ? $existing . ' wp-block-preformatted' : 'wp-block-preformatted';
			$processor->set_attribute( 'class', $classes );
		}

		return $this->wrap_block( 'preformatted', (string) $processor );
	}
}
