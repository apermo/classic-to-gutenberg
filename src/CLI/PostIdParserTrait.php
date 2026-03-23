<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\CLI;

use Apermo\ClassicToGutenberg\Migration\ClassicPostFinder;

/**
 * Shared helpers for CLI commands that accept post IDs.
 */
trait PostIdParserTrait {

	/**
	 * Parse post IDs from positional arguments.
	 *
	 * Supports space-separated and comma-separated IDs.
	 *
	 * @param string[] $args Positional arguments.
	 *
	 * @return int[]
	 */
	private function parse_post_ids( array $args ): array {
		if ( $args === [] ) {
			return [];
		}

		$post_ids = [];
		foreach ( $args as $argument ) {
			foreach ( \explode( ',', $argument ) as $part ) {
				$part = \trim( $part );
				if ( $part !== '' && \ctype_digit( $part ) ) {
					$post_ids[] = (int) $part;
				}
			}
		}

		return $post_ids;
	}

	/**
	 * Find classic posts via query.
	 *
	 * @param array<string,string> $assoc_args Associative arguments.
	 *
	 * @return int[]
	 */
	private function find_classic_posts( array $assoc_args ): array {
		$finder     = new ClassicPostFinder();
		$query_args = [];

		if ( isset( $assoc_args['post-type'] ) ) {
			$query_args['post_type'] = \explode( ',', $assoc_args['post-type'] );
		}

		return $finder->find( $query_args );
	}
}
