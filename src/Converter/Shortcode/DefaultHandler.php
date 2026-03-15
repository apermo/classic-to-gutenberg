<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter\Shortcode;

/**
 * Default shortcode handler: wraps unknown shortcodes in core/shortcode blocks.
 */
class DefaultHandler {

	/**
	 * Convert an unknown shortcode to a core/shortcode block.
	 *
	 * @param string $shortcode The full shortcode string.
	 *
	 * @return string
	 */
	public function convert( string $shortcode ): string {
		return "<!-- wp:shortcode -->\n" . $shortcode . "\n<!-- /wp:shortcode -->";
	}
}
