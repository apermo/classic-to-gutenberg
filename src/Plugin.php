<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;

/**
 * Main plugin class.
 */
class Plugin {

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Plugin initialization will be added as features are implemented.
	}

	/**
	 * Create and configure the block converter factory with default converters.
	 *
	 * Third-party plugins can register additional converters via the
	 * 'classic_to_gutenberg_converters' filter.
	 *
	 * @return BlockConverterFactory
	 */
	public static function create_factory(): BlockConverterFactory {
		$factory = new BlockConverterFactory( new HtmlBlockConverter() );

		/**
		 * Allows registration of additional block converters.
		 *
		 * @param BlockConverterFactory $factory The converter factory.
		 *
		 * @return BlockConverterFactory
		 */
		apply_filters( 'classic_to_gutenberg_converters', $factory );

		return $factory;
	}
}
