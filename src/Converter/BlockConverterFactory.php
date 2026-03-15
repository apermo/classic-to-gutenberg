<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Factory that manages converter registration and lookup.
 *
 * Maintains a registry of BlockConverterInterface implementations
 * and resolves the appropriate converter for a given HTML element.
 * Falls back to HtmlBlockConverter for unrecognized content.
 */
class BlockConverterFactory {

	/**
	 * Registered converters indexed by tag name.
	 *
	 * @var array<string, BlockConverterInterface>
	 */
	private array $converters = [];

	/**
	 * Fallback converter for unmatched content.
	 *
	 * @var BlockConverterInterface
	 */
	private BlockConverterInterface $fallback;

	/**
	 * @param BlockConverterInterface $fallback Converter used when no match is found.
	 */
	public function __construct( BlockConverterInterface $fallback ) {
		$this->fallback = $fallback;
	}

	/**
	 * Register a converter for its supported tags.
	 *
	 * Later registrations for the same tag override earlier ones.
	 *
	 * @param BlockConverterInterface $converter The converter to register.
	 *
	 * @return void
	 */
	public function register( BlockConverterInterface $converter ): void {
		foreach ( $converter->get_supported_tags() as $tag ) {
			$this->converters[ strtolower( $tag ) ] = $converter;
		}
	}

	/**
	 * Get the appropriate converter for an HTML element.
	 *
	 * @param string $tag_name The lowercase tag name.
	 * @param string $html     The outer HTML of the element.
	 *
	 * @return BlockConverterInterface
	 */
	public function get_converter( string $tag_name, string $html ): BlockConverterInterface {
		$tag = strtolower( $tag_name );

		if ( isset( $this->converters[ $tag ] ) && $this->converters[ $tag ]->can_convert( $tag, $html ) ) {
			return $this->converters[ $tag ];
		}

		return $this->fallback;
	}

	/**
	 * Get all registered converters.
	 *
	 * @return array<string, BlockConverterInterface>
	 */
	public function get_converters(): array {
		return $this->converters;
	}
}
