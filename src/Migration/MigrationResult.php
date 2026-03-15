<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Migration;

/**
 * Value object representing the result of a single post migration.
 */
readonly class MigrationResult {

	/**
	 * Create a new migration result.
	 *
	 * @param int    $post_id   The post ID.
	 * @param bool   $success   Whether the migration succeeded.
	 * @param string $original  The original post content.
	 * @param string $converted The converted content (empty on failure).
	 * @param string $error     Error message (empty on success).
	 */
	public function __construct(
		public int $post_id,
		public bool $success,
		public string $original,
		public string $converted,
		public string $error = '',
	) {
	}
}
