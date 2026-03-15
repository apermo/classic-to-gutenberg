<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Base class for block converters with shared serialization helpers.
 */
abstract class AbstractBlockConverter implements BlockConverterInterface {

	/**
	 * {@inheritDoc}
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		return in_array( $tag_name, $this->get_supported_tags(), true );
	}

	/**
	 * Wrap content in a Gutenberg block comment delimiter.
	 *
	 * @param string               $block_name Block name without core/ prefix (e.g. 'paragraph').
	 * @param string               $content    The inner HTML content.
	 * @param array<string, mixed> $attrs      Block attributes.
	 *
	 * @return string
	 */
	protected function wrap_block( string $block_name, string $content, array $attrs = [] ): string {
		$attrs_string = $attrs !== [] ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';

		return "<!-- wp:{$block_name}{$attrs_string} -->\n{$content}\n<!-- /wp:{$block_name} -->";
	}

	/**
	 * Create a self-closing Gutenberg block.
	 *
	 * @param string               $block_name Block name without core/ prefix.
	 * @param array<string, mixed> $attrs      Block attributes.
	 *
	 * @return string
	 */
	protected function self_closing_block( string $block_name, array $attrs = [] ): string {
		$attrs_string = $attrs !== [] ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) : '';

		return "<!-- wp:{$block_name}{$attrs_string} /-->";
	}
}
