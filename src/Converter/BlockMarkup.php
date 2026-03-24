<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Public utility for building Gutenberg block comment markup.
 *
 * Provides the same serialization logic used internally by AbstractBlockConverter,
 * but accessible to addon plugins that build blocks outside of a BlockConverterInterface.
 *
 * @since 0.5.0
 */
final class BlockMarkup {

	/**
	 * Wrap content in a Gutenberg block comment delimiter.
	 *
	 * @param string               $block_name Block name (e.g. 'paragraph' or 'my-plugin/my-block').
	 * @param string               $content    The inner HTML content.
	 * @param array<string, mixed> $attrs      Block attributes.
	 *
	 * @return string
	 */
	public static function wrap( string $block_name, string $content, array $attrs = [] ): string {
		$attrs_string = self::encode_attrs( $attrs );

		return "<!-- wp:{$block_name}{$attrs_string} -->\n{$content}\n<!-- /wp:{$block_name} -->";
	}

	/**
	 * Create a self-closing Gutenberg block.
	 *
	 * @param string               $block_name Block name (e.g. 'nextpage' or 'my-plugin/my-block').
	 * @param array<string, mixed> $attrs      Block attributes.
	 *
	 * @return string
	 */
	public static function self_closing( string $block_name, array $attrs = [] ): string {
		$attrs_string = self::encode_attrs( $attrs );

		return "<!-- wp:{$block_name}{$attrs_string} /-->";
	}

	/**
	 * Encode block attributes to a JSON string for the comment delimiter.
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string Empty string or space-prefixed JSON.
	 */
	private static function encode_attrs( array $attrs ): string {
		if ( $attrs === [] ) {
			return '';
		}

		return ' ' . wp_json_encode( $attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );
	}
}
