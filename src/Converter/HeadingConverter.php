<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <h1>-<h6> tags to core/heading blocks.
 */
class HeadingConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
	}

	/**
	 * Convert a heading element to a heading block.
	 *
	 * @param string $html The heading HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		preg_match( '/<(h([1-6]))/i', $html, $match );
		$tag_name = strtolower( $match[1] );
		$level    = (int) $match[2];

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( $processor->next_tag( [ 'tag_name' => $tag_name ] ) ) {
			$existing = $processor->get_attribute( 'class' );
			$classes  = $existing !== null ? $existing . ' wp-block-heading' : 'wp-block-heading';
			$processor->set_attribute( 'class', $classes );
		}
		$content = (string) $processor;

		// h2 is the default level — omit from attributes.
		$attrs = $level !== 2 ? [ 'level' => $level ] : [];

		return $this->wrap_block( 'heading', $content, $attrs );
	}
}
