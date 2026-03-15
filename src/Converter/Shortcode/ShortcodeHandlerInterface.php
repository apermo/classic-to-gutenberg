<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter\Shortcode;

/**
 * Interface for shortcode-specific conversion handlers.
 */
interface ShortcodeHandlerInterface {

	/**
	 * Get the shortcode tag this handler processes.
	 *
	 * @return string
	 */
	public function get_shortcode_tag(): string;

	/**
	 * Convert a shortcode string into Gutenberg block markup.
	 *
	 * @param string $shortcode The full shortcode string.
	 *
	 * @return string
	 */
	public function convert( string $shortcode ): string;
}
