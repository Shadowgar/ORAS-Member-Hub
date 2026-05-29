<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName,Universal.Files.SeparateFunctionsFromOO.Mixed
/**
 * Equipment Exchange permission helpers.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'oras_members_hub_user_has_access' ) ) {
	/**
	 * Check whether a user can access members-only equipment exchange features.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function oras_members_hub_user_has_access( $user_id ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			$level = pmpro_getMembershipLevelForUser( $user_id );
			return ! empty( $level );
		}

		return true;
	}
}

/**
 * Permission class.
 */
final class ORAS_MH_Equipment_Permissions {
	/**
	 * Whether current user can manage listings globally.
	 *
	 * @return bool
	 */
	public static function can_manage_all() {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' );
	}

	/**
	 * Ensure current user has module access.
	 *
	 * @return bool
	 */
	public static function current_user_has_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return oras_members_hub_user_has_access( get_current_user_id() );
	}

	/**
	 * Whether user can edit listing.
	 *
	 * @param int $post_id Listing post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function can_edit_listing( $post_id, $user_id ) {
		if ( self::can_manage_all() ) {
			return true;
		}

		return (int) get_post_field( 'post_author', $post_id ) === (int) $user_id;
	}
}
