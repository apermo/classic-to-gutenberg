<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Migration;

/**
 * Finds classic editor posts that have not been converted to blocks.
 */
class ClassicPostFinder {

	/**
	 * Find posts without Gutenberg block markup.
	 *
	 * @param array<string, mixed> $args Optional query arguments.
	 *
	 * @return int[] Post IDs.
	 */
	public function find( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'post_type'   => [ 'post', 'page' ],
			'post_status' => [ 'publish', 'draft', 'pending', 'future', 'private' ],
			'limit'       => 100,
			'offset'      => 0,
		];

		/**
		 * Filter the finder query arguments.
		 *
		 * @since 0.1.0
		 *
		 * @param array<string, mixed> $args     The query arguments.
		 * @param array<string, mixed> $defaults The default arguments.
		 *
		 * @return array<string, mixed>
		 */
		$filtered_args = apply_filters( 'classic_to_gutenberg_finder_args', $args, $defaults );
		$args          = wp_parse_args( $filtered_args, $defaults );

		$post_types = (array) $args['post_type'];
		$statuses   = (array) $args['post_status'];
		$limit      = (int) $args['limit'];
		$offset     = (int) $args['offset'];
		$type_in    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$status_in  = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- dynamic IN clause
		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type IN ({$type_in})
			AND post_status IN ({$status_in})
			AND post_content NOT LIKE %s
			AND post_content != ''
			ORDER BY ID ASC
			LIMIT %d OFFSET %d",
			...array_merge( $post_types, $statuses, [ '%<!-- wp:%', $limit, $offset ] ),
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- prepared above
		$results = $wpdb->get_col( $query );

		return array_map( 'intval', $results );
	}

	/**
	 * Count total classic posts matching the criteria.
	 *
	 * @param array<string, mixed> $args Optional query arguments.
	 *
	 * @return int
	 */
	public function count( array $args = [] ): int {
		global $wpdb;

		$defaults = [
			'post_type'   => [ 'post', 'page' ],
			'post_status' => [ 'publish', 'draft', 'pending', 'future', 'private' ],
		];

		$args = wp_parse_args( $args, $defaults );

		$post_types = (array) $args['post_type'];
		$statuses   = (array) $args['post_status'];
		$type_in    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$status_in  = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- dynamic IN clause
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type IN ({$type_in})
			AND post_status IN ({$status_in})
			AND post_content NOT LIKE %s
			AND post_content != ''",
			...array_merge( $post_types, $statuses, [ '%<!-- wp:%' ] ),
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- prepared above
		return (int) $wpdb->get_var( $query );
	}
}
