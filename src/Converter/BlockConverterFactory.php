<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Factory that manages converter registration and lookup.
 *
 * Maintains a registry of BlockConverterInterface implementations
 * and resolves the appropriate converter for a given HTML element.
 * Multiple converters can be registered for the same tag — the last
 * registered converter is checked first (LIFO). If its can_convert()
 * returns false, the next converter in the stack is tried.
 * Falls back to HtmlBlockConverter for unrecognized content.
 */
class BlockConverterFactory {

	/**
	 * Registered converter stacks indexed by tag name.
	 *
	 * @var array<string, BlockConverterInterface[]>
	 */
	private array $converters = [];

	/**
	 * Fallback converter for unmatched content.
	 *
	 * @var BlockConverterInterface
	 */
	private BlockConverterInterface $fallback;

	/**
	 * Create a new factory with a fallback converter.
	 *
	 * @param BlockConverterInterface $fallback Converter used when no match is found.
	 */
	public function __construct( BlockConverterInterface $fallback ) {
		$this->fallback = $fallback;
	}

	/**
	 * Register a converter for its supported tags.
	 *
	 * Later registrations for the same tag take priority (LIFO).
	 * If the higher-priority converter's can_convert() returns false,
	 * the next converter in the stack is tried.
	 *
	 * @param BlockConverterInterface $converter The converter to register.
	 *
	 * @return void
	 */
	public function register( BlockConverterInterface $converter ): void {
		foreach ( $converter->get_supported_tags() as $tag ) {
			$key = \strtolower( $tag );
			if ( ! isset( $this->converters[ $key ] ) ) {
				$this->converters[ $key ] = [];
			}
			$this->converters[ $key ][] = $converter;
		}
	}

	/**
	 * Get the appropriate converter for an HTML element.
	 *
	 * Iterates the converter stack in reverse (LIFO) order.
	 * Returns the first converter whose can_convert() returns true,
	 * or the fallback if none match.
	 *
	 * @param string $tag_name The lowercase tag name.
	 * @param string $html     The outer HTML of the element.
	 *
	 * @return BlockConverterInterface
	 */
	public function get_converter( string $tag_name, string $html ): BlockConverterInterface {
		$tag = \strtolower( $tag_name );

		if ( isset( $this->converters[ $tag ] ) ) {
			// TODO: Iterate from end without copying when priority support is added (#20).
			$stack = \array_reverse( $this->converters[ $tag ] );
			foreach ( $stack as $converter ) {
				if ( $converter->can_convert( $tag, $html ) ) {
					return $converter;
				}
			}
		}

		return $this->fallback;
	}

	/**
	 * Get all registered converters (flattened, last registration per tag).
	 *
	 * @return array<string, BlockConverterInterface>
	 */
	public function get_converters(): array {
		$result = [];
		foreach ( $this->converters as $tag => $stack ) {
			$result[ $tag ] = \end( $stack );
		}
		return $result;
	}
}
