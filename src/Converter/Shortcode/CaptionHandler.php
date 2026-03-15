<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter\Shortcode;

/**
 * Converts [caption] shortcodes to core/image blocks.
 */
class CaptionHandler implements ShortcodeHandlerInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_shortcode_tag(): string {
		return 'caption';
	}

	/**
	 * {@inheritDoc}
	 */
	/**
	 * Convert a caption shortcode to an image block.
	 *
	 * @param string $shortcode The full shortcode string.
	 *
	 * @return string
	 */
	public function convert( string $shortcode ): string {
		$attrs   = $this->parse_attrs( $shortcode );
		$img_tag = $this->extract_img( $shortcode );

		// Extract caption text (content after <img> tag, before [/caption]).
		$caption_text = $this->extract_caption_text( $shortcode );

		$block_attrs = $this->build_block_attrs( $attrs, $img_tag );

		$clean_img = $this->build_clean_img( $img_tag );

		$caption_html = $caption_text !== '' ? '<figcaption class="wp-element-caption">' . $caption_text . '</figcaption>' : '';

		$align       = $this->extract_align( $attrs );
		$align_class = $align !== '' ? ' align' . $align : '';

		$figure = '<figure class="wp-block-image' . $align_class . '">'
			. $clean_img . $caption_html . '</figure>';

		return '<!-- wp:image ' . wp_json_encode( $block_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . " -->\n"
			. $figure . "\n"
			. '<!-- /wp:image -->';
	}

	/**
	 * Parse shortcode attributes.
	 *
	 * @param string $shortcode Full shortcode string.
	 *
	 * @return array<string, string>
	 */
	private function parse_attrs( string $shortcode ): array {
		$attrs = [];
		if ( preg_match( '/\[caption([^\]]*)\]/', $shortcode, $match ) ) {
			preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $match[1], $attr_matches, PREG_SET_ORDER );
			foreach ( $attr_matches as $attr ) {
				$attrs[ $attr[1] ] = $attr[2];
			}
		}
		return $attrs;
	}

	/**
	 * Extract the <img> tag from the shortcode.
	 *
	 * @param string $shortcode Full shortcode string.
	 *
	 * @return string
	 */
	private function extract_img( string $shortcode ): string {
		preg_match( '/<img\s[^>]*\/?>/i', $shortcode, $match );
		return $match[0] ?? '';
	}

	/**
	 * Extract caption text from after the img tag.
	 *
	 * @param string $shortcode Full shortcode string.
	 *
	 * @return string
	 */
	private function extract_caption_text( string $shortcode ): string {
		if ( preg_match( '/<img\s[^>]*\/?>\s*(.*?)\s*\[\/caption\]/s', $shortcode, $match ) ) {
			return trim( $match[1] );
		}
		return '';
	}

	/**
	 * Build block attributes from shortcode attrs and img tag.
	 *
	 * @param array<string, string> $attrs Parsed shortcode attributes.
	 * @param string                $img_tag   The <img> tag.
	 *
	 * @return array<string, mixed>
	 */
	private function build_block_attrs( array $attrs, string $img_tag ): array {
		$block_attrs = [];

		// ID from shortcode (attachment_N format) or img class.
		if ( isset( $attrs['id'] ) && preg_match( '/attachment_(\d+)/', $attrs['id'], $match ) ) {
			$block_attrs['id'] = (int) $match[1];
		} elseif ( preg_match( '/wp-image-(\d+)/', $img_tag, $match ) ) {
			$block_attrs['id'] = (int) $match[1];
		}

		$align = $this->extract_align( $attrs );
		if ( $align !== '' ) {
			$block_attrs['align'] = $align;
		}

		// Width and height from shortcode attrs or img tag.
		if ( isset( $attrs['width'] ) ) {
			$block_attrs['width'] = (int) $attrs['width'];
		} elseif ( preg_match( '/\bwidth=["\'](\d+)["\']/', $img_tag, $match ) ) {
			$block_attrs['width'] = (int) $match[1];
		}

		if ( preg_match( '/\bheight=["\'](\d+)["\']/', $img_tag, $match ) ) {
			$block_attrs['height'] = (int) $match[1];
		}

		return $block_attrs;
	}

	/**
	 * Extract alignment value from shortcode attributes.
	 *
	 * @param array<string, string> $attrs Parsed shortcode attributes.
	 *
	 * @return string Alignment value (left, right, center, none) or empty.
	 */
	private function extract_align( array $attrs ): string {
		if ( isset( $attrs['align'] ) ) {
			return str_replace( 'align', '', $attrs['align'] );
		}
		return '';
	}

	/**
	 * Build a clean <img> tag with self-closing format.
	 *
	 * @param string $img_tag The original img tag.
	 *
	 * @return string
	 */
	private function build_clean_img( string $img_tag ): string {
		return (string) preg_replace( '/\s*\/?\s*>$/', '/>', $img_tag );
	}
}
