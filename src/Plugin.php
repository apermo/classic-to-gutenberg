<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\HeadingConverter;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;
use Apermo\ClassicToGutenberg\Converter\ImageConverter;
use Apermo\ClassicToGutenberg\Converter\ListConverter;
use Apermo\ClassicToGutenberg\Converter\MoreConverter;
use Apermo\ClassicToGutenberg\Converter\NextpageConverter;
use Apermo\ClassicToGutenberg\Converter\ParagraphConverter;
use Apermo\ClassicToGutenberg\Converter\PreformattedConverter;
use Apermo\ClassicToGutenberg\Converter\QuoteConverter;
use Apermo\ClassicToGutenberg\Converter\SeparatorConverter;
use Apermo\ClassicToGutenberg\Converter\Shortcode\CaptionHandler;
use Apermo\ClassicToGutenberg\Converter\Shortcode\GalleryHandler;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;
use Apermo\ClassicToGutenberg\Converter\TableConverter;
use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;

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
		if ( \defined( 'WP_CLI' ) && \WP_CLI ) {
			add_action(
				'cli_init',
				static function (): void {
					$converter = self::create_content_converter();
					CLI\StatusCommand::register();
					CLI\ConvertCommand::register( $converter );
					CLI\RollbackCommand::register();
				},
			);
		}

		add_action(
			'admin_init',
			static function (): void {
				$converter = self::create_content_converter();
				new Admin\RowAction( $converter );
				new Admin\AdminNotice();
			},
		);
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

		$factory->register( new ParagraphConverter() );
		$factory->register( new HeadingConverter() );
		$factory->register( new SeparatorConverter() );
		$factory->register( new PreformattedConverter() );
		$factory->register( new MoreConverter() );
		$factory->register( new NextpageConverter() );
		$factory->register( new ListConverter() );
		$factory->register( new QuoteConverter() );
		$factory->register( new TableConverter() );
		$factory->register( new ImageConverter() );

		/**
		 * Filter to extend shortcode handler registry.
		 *
		 * @since 0.1.0
		 *
		 * @param \Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface[] $handlers Shortcode handlers.
		 *
		 * @return \Apermo\ClassicToGutenberg\Converter\Shortcode\ShortcodeHandlerInterface[]
		 */
		$shortcode_handlers = apply_filters(
			'classic_to_gutenberg_shortcode_handlers',
			[
				new CaptionHandler(),
				new GalleryHandler(),
			],
		);
		$factory->register( new ShortcodeConverter( $shortcode_handlers ) );

		/**
		 * Allows registration of additional block converters.
		 *
		 * @since 0.1.0
		 *
		 * @param BlockConverterFactory $factory The converter factory.
		 *
		 * @return BlockConverterFactory
		 */
		apply_filters( 'classic_to_gutenberg_converters', $factory );

		return $factory;
	}

	/**
	 * Create a fully configured ContentConverter.
	 *
	 * @return ContentConverter
	 */
	public static function create_content_converter(): ContentConverter {
		return new ContentConverter(
			self::create_factory(),
			new TopLevelSplitter(),
			static fn( string $content ): string => wpautop( $content ),
		);
	}
}
