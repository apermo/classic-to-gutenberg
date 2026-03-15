<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenberg;

use WP_User;

/**
 * Permission checks for block conversion operations.
 */
class Permission {

	/**
	 * Check whether a user can convert posts to blocks.
	 *
	 * Requires super admin on multisite, manage_options on single site.
	 * Filterable via 'classic_to_gutenberg_user_can_convert'.
	 *
	 * @param WP_User|null $user The user to check. Defaults to the current user.
	 *
	 * @return bool
	 */
	public static function user_can_convert( ?WP_User $user = null ): bool {
		if ( $user === null ) {
			$user = wp_get_current_user();
		}

		if ( is_multisite() ) {
			$can_convert = is_super_admin( $user->ID );
		} else {
			$can_convert = user_can( $user, 'manage_options' );
		}

		/**
		 * Filter whether a user can convert posts to blocks.
		 *
		 * @since 0.2.1
		 *
		 * @param bool    $can_convert Whether the user has permission.
		 * @param WP_User $user        The user being checked.
		 *
		 * @return bool
		 */
		return (bool) apply_filters( 'classic_to_gutenberg_user_can_convert', $can_convert, $user ); // @phpstan-ignore cast.useless
	}
}
