<?php

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	/**
	 * Provides the WP_HTML_Tag_Processor methods used by isolated converter tests.
	 */
	class WP_HTML_Tag_Processor {

		/**
		 * HTML being processed.
		 *
		 * @var string
		 */
		private string $html;

		/**
		 * Current opening tag.
		 *
		 * @var string
		 */
		private string $current_tag = '';

		/**
		 * Creates the processor test double.
		 *
		 * @param string $html HTML to process.
		 */
		public function __construct( string $html ) {
			$this->html = $html;
		}

		/**
		 * Finds the next opening tag by name.
		 *
		 * @param array<string, string> $query Tag query.
		 *
		 * @return bool
		 */
		public function next_tag( array $query ): bool {
			$tag_name = $query['tag_name'] ?? '';
			if ( $tag_name === '' ) {
				return false;
			}

			if ( ! preg_match( '/<' . preg_quote( $tag_name, '/' ) . '\b[^>]*>/i', $this->html, $match ) ) {
				return false;
			}

			$this->current_tag = $match[0];
			return true;
		}

		/**
		 * Returns an attribute value from the current opening tag.
		 *
		 * @param string $name Attribute name.
		 *
		 * @return string|null
		 */
		public function get_attribute( string $name ): ?string {
			if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '=["\']([^"\']*)["\']/', $this->current_tag, $match ) ) {
				return $match[1];
			}

			return null;
		}

		/**
		 * Sets an attribute on the current opening tag.
		 *
		 * @param string $name  Attribute name.
		 * @param string $value Attribute value.
		 *
		 * @return void
		 */
		public function set_attribute( string $name, string $value ): void {
			if ( $this->current_tag === '' ) {
				return;
			}

			if ( $this->get_attribute( $name ) !== null ) {
				$new_tag = preg_replace(
					'/\b' . preg_quote( $name, '/' ) . '=["\'][^"\']*["\']/',
					$name . '="' . $value . '"',
					$this->current_tag,
					1,
				);
			} else {
				$new_tag = preg_replace( '/\s*\/?>$/', ' ' . $name . '="' . $value . '">', $this->current_tag, 1 );
			}

			$new_tag = (string) $new_tag;
			$this->html = preg_replace(
				'/' . preg_quote( $this->current_tag, '/' ) . '/',
				$new_tag,
				$this->html,
				1,
			) ?? $this->html;
			$this->current_tag = $new_tag;
		}

		/**
		 * Returns the processed HTML.
		 *
		 * @return string
		 */
		public function __toString(): string {
			return $this->html;
		}
	}
}
