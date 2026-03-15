<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter;

/**
 * Converts <img> and <figure> patterns to core/image blocks.
 *
 * Handles three patterns:
 * - Standalone <img> tag
 * - Linked <a><img></a>
 * - <figure> with <img> and optional <figcaption>
 */
class ImageConverter extends AbstractBlockConverter {

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_tags(): array {
		return [ 'img', 'figure' ];
	}

	/**
	 * Refine matching: figure must contain an img.
	 *
	 * @param string $tag_name The tag name.
	 * @param string $html     The element HTML.
	 *
	 * @return bool
	 */
	public function can_convert( string $tag_name, string $html ): bool {
		if ( ! parent::can_convert( $tag_name, $html ) ) {
			return false;
		}

		if ( $tag_name === 'figure' ) {
			return (bool) \preg_match( '/<img\s/i', $html );
		}

		return true;
	}

	/**
	 * Convert an image element to an image block.
	 *
	 * @param string $html The image HTML.
	 *
	 * @return string
	 */
	public function convert( string $html ): string {
		if ( \preg_match( '/^\s*<figure/i', $html ) ) {
			return $this->convert_figure( $html );
		}

		if ( \preg_match( '/^\s*<a\s/i', $html ) ) {
			return $this->convert_linked_image( $html );
		}

		return $this->convert_standalone_image( $html );
	}

	/**
	 * Convert a standalone <img> tag.
	 *
	 * @param string $html The img HTML.
	 *
	 * @return string
	 */
	private function convert_standalone_image( string $html ): string {
		$attrs     = $this->extract_image_attrs( $html );
		$clean_img = $this->build_clean_img( $html );

		$figure = '<figure class="wp-block-image' . $this->align_class( $attrs ) . '">' . $clean_img . '</figure>';

		return $this->wrap_block( 'image', $figure, $attrs );
	}

	/**
	 * Convert a linked image (<a><img></a>).
	 *
	 * @param string $html The linked image HTML.
	 *
	 * @return string
	 */
	private function convert_linked_image( string $html ): string {
		\preg_match( '/<img\s[^>]*\/?>/i', $html, $img_match );
		$attrs                    = $this->extract_image_attrs( $img_match[0] );
		$attrs['linkDestination'] = 'custom';

		$clean_img = $this->build_clean_img( $img_match[0] );

		\preg_match( '/<a\s[^>]*>/i', $html, $link_match );
		$link_open = $link_match[0];

		$figure = '<figure class="wp-block-image' . $this->align_class( $attrs ) . '">'
			. $link_open . $clean_img . '</a></figure>';

		return $this->wrap_block( 'image', $figure, $attrs );
	}

	/**
	 * Convert a <figure> with img and optional caption.
	 *
	 * @param string $html The figure HTML.
	 *
	 * @return string
	 */
	private function convert_figure( string $html ): string {
		\preg_match( '/<img\s[^>]*\/?>/i', $html, $img_match );
		$img_attrs = $this->extract_image_attrs( $img_match[0] );

		$align = '';
		if ( \preg_match( '/class="[^"]*align(left|right|center|none)[^"]*"/', $html, $align_match ) ) {
			$align = $align_match[1];
		}

		$attrs = $this->build_figure_attrs( $img_attrs, $align );

		$clean_img = $this->build_clean_img( $img_match[0] );

		$caption = '';
		if ( \preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/s', $html, $cap_match ) ) {
			$caption = '<figcaption class="wp-element-caption">' . $cap_match[1] . '</figcaption>';
		}

		$figure = '<figure class="wp-block-image' . $this->align_class( $attrs ) . '">'
			. $clean_img . $caption . '</figure>';

		return $this->wrap_block( 'image', $figure, $attrs );
	}

	/**
	 * Build ordered block attributes for a figure element.
	 *
	 * Ensures attribute order: id, align, width, height.
	 *
	 * @param array<string, mixed> $img_attrs Attributes extracted from <img>.
	 * @param string               $align     Alignment value (empty if none).
	 *
	 * @return array<string, mixed>
	 */
	private function build_figure_attrs( array $img_attrs, string $align ): array {
		$attrs = [];

		if ( isset( $img_attrs['id'] ) ) {
			$attrs['id'] = $img_attrs['id'];
		}

		if ( $align !== '' ) {
			$attrs['align'] = $align;
		}

		if ( isset( $img_attrs['width'] ) ) {
			$attrs['width'] = $img_attrs['width'];
		}

		if ( isset( $img_attrs['height'] ) ) {
			$attrs['height'] = $img_attrs['height'];
		}

		return $attrs;
	}

	/**
	 * Extract block attributes from an img tag.
	 *
	 * @param string $img_html The <img> tag HTML.
	 *
	 * @return array<string, mixed>
	 */
	private function extract_image_attrs( string $img_html ): array {
		$attrs = [];

		if ( \preg_match( '/wp-image-(\d+)/', $img_html, $match ) ) {
			$attrs['id'] = (int) $match[1];
		}

		if ( \preg_match( '/\balign(left|right|center|none)\b/', $img_html, $match ) ) {
			$attrs['align'] = $match[1];
		}

		if ( \preg_match( '/\bwidth=["\'](\d+)["\']/', $img_html, $match ) ) {
			$attrs['width'] = (int) $match[1];
		}
		if ( \preg_match( '/\bheight=["\'](\d+)["\']/', $img_html, $match ) ) {
			$attrs['height'] = (int) $match[1];
		}

		return $attrs;
	}

	/**
	 * Build a clean <img> tag, removing alignment classes.
	 *
	 * @param string $img_html The original <img> tag.
	 *
	 * @return string
	 */
	private function build_clean_img( string $img_html ): string {
		$clean = (string) \preg_replace( '/\s*\balign(?:left|right|center|none)\b/', '', $img_html );
		$clean = (string) \preg_replace( '/\s*\/?\s*>$/', '/>', $clean );
		$clean = (string) \preg_replace( '/class="(\s+)/', 'class="', $clean );
		$clean = (string) \preg_replace( '/\s+"/', '"', $clean );

		return $clean;
	}

	/**
	 * Generate the alignment class suffix for the figure.
	 *
	 * @param array<string, mixed> $attrs Block attributes.
	 *
	 * @return string
	 */
	private function align_class( array $attrs ): string {
		if ( isset( $attrs['align'] ) ) {
			return ' align' . $attrs['align'];
		}
		return '';
	}
}
