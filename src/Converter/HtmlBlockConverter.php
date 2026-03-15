<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Fallback converter that wraps unrecognized HTML in a core/html block.
 */
class HtmlBlockConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function convert( string $html ): string {
		return $this->wrap_block( 'html', $html );
	}
}
