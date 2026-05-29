<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange contact seller handler.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact handler.
 */
final class ORAS_MH_Equipment_Contact {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		// Registration handled by forms class post router.
	}

	/**
	 * Handle contact submission.
	 *
	 * @return void
	 */
	public static function handle_submission() {
		check_admin_referer( 'oras_equipment_contact', 'oras_equipment_nonce' );

		if ( ! ORAS_MH_Equipment_Permissions::current_user_has_access() ) {
			self::redirect_with_notice( 'error', __( 'Access denied.', 'oras-member-hub' ) );
		}

		$post_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
		$post    = get_post( $post_id );
		if ( ! $post || ORAS_MH_Equipment_Post_Type::POST_TYPE !== $post->post_type ) {
			self::redirect_with_notice( 'error', __( 'Listing not found.', 'oras-member-hub' ) );
		}

		$name    = sanitize_text_field( (string) wp_unslash( $_POST['contact_name'] ?? '' ) );
		$email   = sanitize_email( (string) wp_unslash( $_POST['contact_email'] ?? '' ) );
		$message = sanitize_textarea_field( (string) wp_unslash( $_POST['contact_message'] ?? '' ) );

		if ( '' === $name || '' === $email || '' === $message ) {
			self::redirect_with_notice( 'error', __( 'Please complete all contact form fields.', 'oras-member-hub' ) );
		}

		$seller_email = (string) get_the_author_meta( 'user_email', (int) $post->post_author );
		if ( '' === $seller_email ) {
			self::redirect_with_notice( 'error', __( 'Seller contact email unavailable.', 'oras-member-hub' ) );
		}

		$single_url = add_query_arg(
			array( 'listing' => $post->ID ),
			ORAS_MH_Equipment_Settings::get_page_url( 'single_listing_page_url' )
		);

		/* translators: %s: listing title. */
		$subject = sprintf( __( 'ORAS Equipment Exchange Inquiry: %s', 'oras-member-hub' ), $post->post_title );
		$body    = sprintf(
			"Listing: %s\nListing URL: %s\n\nBuyer Name: %s\nBuyer Email: %s\n\nMessage:\n%s",
			$post->post_title,
			$single_url,
			$name,
			$email,
			$message
		);
		$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
		wp_mail( $seller_email, $subject, $body, $headers );

		self::redirect_with_notice( 'success', __( 'Your message has been sent to the seller.', 'oras-member-hub' ) );
	}

	/**
	 * Redirect with frontend notice.
	 *
	 * @param string $type Notice type.
	 * @param string $message Message text.
	 * @return void
	 */
	private static function redirect_with_notice( $type, $message ) {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = ORAS_MH_Equipment_Settings::get_page_url( 'grid_page_url' );
		}
		$redirect = add_query_arg(
			array(
				'oras_equipment_notice'      => rawurlencode( $message ),
				'oras_equipment_notice_type' => rawurlencode( $type ),
			),
			$redirect
		);
		wp_safe_redirect( $redirect );
		exit;
	}
}
