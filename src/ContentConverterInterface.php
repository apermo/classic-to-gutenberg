<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

/**
 * Interface for content converters.
 *
 * Implementations convert classic editor content into Gutenberg block markup.
 * Addon plugins can provide custom implementations (e.g. page builder converters)
 * that pre-process content before delegating to the core pipeline.
 *
 * @since 0.5.0
 */
interface ContentConverterInterface {

	/**
	 * Convert classic editor content to Gutenberg block markup.
	 *
	 * @param string $content Raw classic editor content.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $content ): string;
}
