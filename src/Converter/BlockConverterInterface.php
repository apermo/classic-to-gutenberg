<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Interface for block converters.
 *
 * Each converter is responsible for converting a specific HTML
 * pattern into its corresponding Gutenberg block markup.
 */
interface BlockConverterInterface {

	/**
	 * Get the HTML tag names this converter handles.
	 *
	 * @return string[] List of lowercase tag names (e.g. ['p'], ['h1', 'h2', 'h3']).
	 */
	public function get_supported_tags(): array;

	/**
	 * Check whether this converter can handle the given HTML fragment.
	 *
	 * Allows converters to refine matching beyond tag name alone.
	 *
	 * @param string $tag_name The lowercase tag name of the element.
	 * @param string $html     The outer HTML of the element.
	 *
	 * @return bool
	 */
	public function can_convert( string $tag_name, string $html ): bool;

	/**
	 * Convert an HTML fragment into Gutenberg block markup.
	 *
	 * @param string $html The outer HTML of the element to convert.
	 *
	 * @return string The serialized Gutenberg block markup.
	 */
	public function convert( string $html ): string;
}
