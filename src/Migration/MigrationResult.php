<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg\Migration;

/**
 * Value object representing the result of a single post migration.
 */
readonly class MigrationResult {

	/**
	 * Error code: post is locked by another user.
	 */
	public const ERROR_LOCKED = 'locked';

	/**
	 * Error code: post not found.
	 */
	public const ERROR_NOT_FOUND = 'not_found';

	/**
	 * Error code: update failed.
	 */
	public const ERROR_UPDATE_FAILED = 'update_failed';

	/**
	 * Error code: no conversion revision found (rollback).
	 */
	public const ERROR_NO_REVISION = 'no_revision';

	/**
	 * Create a new migration result.
	 *
	 * @param int    $post_id    The post ID.
	 * @param bool   $success    Whether the migration succeeded.
	 * @param string $original   The original post content.
	 * @param string $converted  The converted content (empty on failure).
	 * @param string $error      Error message (empty on success).
	 * @param string $error_code Machine-readable error code (empty on success).
	 */
	public function __construct(
		public int $post_id,
		public bool $success,
		public string $original,
		public string $converted,
		public string $error = '',
		public string $error_code = '',
	) {
	}
}
