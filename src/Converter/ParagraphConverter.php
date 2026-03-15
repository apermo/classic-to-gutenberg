<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <p> tags to core/paragraph blocks.
 */
class ParagraphConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'p' ];
	}

	/**
	 * Convert a paragraph element to a paragraph block.
	 *
	 * @param string $html The paragraph HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		return $this->wrap_block( 'paragraph', $html );
	}
}
