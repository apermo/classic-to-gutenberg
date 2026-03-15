<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Parser;

/**
 * Value object representing a single top-level HTML element.
 */
readonly class TopLevelElement {

	/**
	 * @param string $tag_name Lowercase tag name or pseudo-tag (__more__, __nextpage__, __shortcode__, __text__).
	 * @param string $html     The outer HTML of this element.
	 */
	public function __construct(
		public string $tag_name,
		public string $html,
	) {
	}
}
