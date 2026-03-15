<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Converter\Shortcode;

/**
 * Converts [gallery] shortcodes to core/gallery blocks with inner image blocks.
 */
class GalleryHandler implements ShortcodeHandlerInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_shortcode_tag(): string {
		return 'gallery';
	}

	/**
	 * Convert a gallery shortcode to a gallery block.
	 *
	 * @param string $shortcode The full shortcode string.
	 *
	 * @return string
	 */
	public function convert( string $shortcode ): string {
		$attrs       = $this->parse_attrs( $shortcode );
		$image_ids   = $this->parse_ids( $attrs );
		$columns     = isset( $attrs['columns'] ) ? (int) $attrs['columns'] : 3;
		$link_to     = $this->resolve_link_to( $attrs['link'] ?? 'none' );
		$block_attrs = [
			'columns' => $columns,
			'linkTo'  => $link_to,
		];

		$inner_blocks = $this->build_inner_images( $image_ids, $link_to );

		$columns_class = 'columns-' . $columns;
		$figure        = '<figure class="wp-block-gallery has-nested-images ' . $columns_class . '">'
			. $inner_blocks . '</figure>';

		return '<!-- wp:gallery ' . wp_json_encode( $block_attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE ) . " -->\n"
			. $figure . "\n"
			. '<!-- /wp:gallery -->';
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
		if ( \preg_match( '/\[gallery([^\]]*)\]/', $shortcode, $match ) ) {
			\preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $match[1], $attr_matches, \PREG_SET_ORDER );
			foreach ( $attr_matches as $attr ) {
				$attrs[ $attr[1] ] = $attr[2];
			}
		}
		return $attrs;
	}

	/**
	 * Parse image IDs from attributes.
	 *
	 * @param array<string, string> $attrs Parsed shortcode attributes.
	 *
	 * @return int[]
	 */
	private function parse_ids( array $attrs ): array {
		if ( ! isset( $attrs['ids'] ) ) {
			return [];
		}

		return \array_map( 'intval', \explode( ',', $attrs['ids'] ) );
	}

	/**
	 * Resolve the link attribute to Gutenberg's linkTo value.
	 *
	 * @param string $link Classic gallery link attribute.
	 *
	 * @return string
	 */
	private function resolve_link_to( string $link ): string {
		return match ( $link ) {
			'file'  => 'media',
			'post'  => 'attachment',
			default => 'none',
		};
	}

	/**
	 * Build inner image blocks for gallery images.
	 *
	 * @param int[]  $image_ids     Image attachment IDs.
	 * @param string $link_to The linkTo value for the gallery.
	 *
	 * @return string
	 */
	private function build_inner_images( array $image_ids, string $link_to ): string {
		$blocks = [];

		foreach ( $image_ids as $image_id ) {
			$img_attrs = [ 'id' => $image_id ];
			if ( $link_to !== 'none' ) {
				$img_attrs['linkDestination'] = $link_to;
			}

			$attrs_json = wp_json_encode( $img_attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );

			$blocks[] = "<!-- wp:image {$attrs_json} -->\n"
				. '<figure class="wp-block-image"><img src="" alt="" class="wp-image-' . $image_id . '"/></figure>' . "\n"
				. '<!-- /wp:image -->';
		}

		return \implode( "\n\n", $blocks );
	}
}
