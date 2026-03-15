<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

use Apermo\ClassicToGutenberg\Converter\Shortcode\DefaultHandler;
use Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface;

/**
 * Converts shortcode patterns to their corresponding Gutenberg blocks.
 *
 * Dispatches to registered shortcode handlers, falling back to core/shortcode.
 */
class ShortcodeConverter extends AbstractBlockConverter {

	/**
	 * Registered shortcode handlers indexed by tag.
	 *
	 * @var array<string, ShortcodeHandlerInterface>
	 */
	private array $handlers = [];

	/**
	 * Default handler for unknown shortcodes.
	 *
	 * @var DefaultHandler
	 */
	private DefaultHandler $default_handler;

	/**
	 * Create a new shortcode converter with optional handlers.
	 *
	 * @param ShortcodeHandlerInterface[] $handlers Shortcode handlers.
	 */
	public function __construct( array $handlers = [] ) {
		$this->default_handler = new DefaultHandler();

		foreach ( $handlers as $handler ) {
			$this->handlers[ $handler->get_shortcode_tag() ] = $handler;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ '__shortcode__' ];
	}

	/**
	 * Convert a shortcode to its corresponding block.
	 *
	 * @param string $html The shortcode string.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		$tag = $this->extract_shortcode_tag( $html );

		if ( $tag !== '' && isset( $this->handlers[ $tag ] ) ) {
			return $this->handlers[ $tag ]->convert( $html );
		}

		return $this->default_handler->convert( $html );
	}

	/**
	 * Extract the shortcode tag name from the shortcode string.
	 *
	 * @param string $shortcode The full shortcode string.
	 *
	 * @return string The tag name, or empty string if not found.
	 */
	private function extract_shortcode_tag( string $shortcode ): string {
		if ( preg_match( '/\[(\w[\w-]*)/', $shortcode, $match ) ) {
			return $match[1];
		}
		return '';
	}
}
