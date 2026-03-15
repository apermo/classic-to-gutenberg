<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <hr> tags to core/separator blocks.
 */
class SeparatorConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'hr' ];
	}

	/**
	 * Convert an hr element to a separator block.
	 *
	 * @param string $html The hr HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		return $this->wrap_block(
			'separator',
			'<hr class="wp-block-separator has-alpha-channel-opacity"/>',
		);
	}
}
