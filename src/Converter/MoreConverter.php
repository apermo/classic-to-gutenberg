<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <!--more--> markers to core/more blocks.
 */
class MoreConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ '__more__' ];
	}

	/**
	 * Convert a more marker to a more block.
	 *
	 * @param string $html The more comment HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		return $this->wrap_block( 'more', '<!--more-->' );
	}
}
