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
	 * Always reports it can convert (fallback converter).
	 *
	 * @param string $tag_name The lowercase tag name.
	 * @param string $html     The outer HTML of the element.
	 *
	 * @return bool
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		return true;
	}

	/**
	 * Wrap content in a core/html block.
	 *
	 * @param string $html The HTML content.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		return $this->wrap_block( 'html', $html );
	}
}
