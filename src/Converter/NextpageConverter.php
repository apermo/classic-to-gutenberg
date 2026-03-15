<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <!--nextpage--> markers to core/nextpage blocks.
 */
class NextpageConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ '__nextpage__' ];
	}

	/**
	 * Convert a nextpage marker to a nextpage block.
	 *
	 * @param string $html The nextpage comment HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		return $this->self_closing_block( 'nextpage' );
	}
}
