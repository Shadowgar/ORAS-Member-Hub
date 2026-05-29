<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange notifications.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notification helper.
 */
final class ORAS_MH_Equipment_Notifications {
	/**
	 * Notify admins about pending listing.
	 *
	 * @param int $post_id Listing post ID.
	 * @return void
	 */
	public static function notify_admin_pending( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$settings = ORAS_MH_Equipment_Settings::get();
		$to       = ! empty( $settings['admin_notification_email'] ) ? (string) $settings['admin_notification_email'] : (string) get_option( 'admin_email' );
		/* translators: %s: listing title. */
		$subject  = sprintf( __( 'Equipment Listing Pending Review: %s', 'oras-member-hub' ), $post->post_title );
		$body     = sprintf(
			"A new equipment listing is pending review.\n\nTitle: %s\nAuthor: %s\nEdit: %s",
			$post->post_title,
			get_the_author_meta( 'display_name', (int) $post->post_author ),
			admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' )
		);
		wp_mail( $to, $subject, $body );
	}
}
