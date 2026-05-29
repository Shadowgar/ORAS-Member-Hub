<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange meta and display helpers.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field helper class.
 */
final class ORAS_MH_Equipment_Fields {
	const META_LISTING_TYPE        = '_listing_type';
	const META_PRICE_TYPE          = '_price_type';
	const META_PRICE_AMOUNT        = '_price_amount';
	const META_PUBLIC_STATUS       = '_public_status';
	const META_PICKUP_AREA         = '_pickup_area';
	const META_SHIPPING_AVAILABLE  = '_shipping_available';
	const META_TRADE_AVAILABLE     = '_trade_available';
	const META_PRICE_NEGOTIABLE    = '_price_negotiable';
	const META_INCLUDED_ITEMS      = '_included_items';
	const META_KNOWN_ISSUES        = '_known_issues';
	const META_TRADE_DETAILS       = '_trade_details';
	const META_CONTACT_PREFERENCE  = '_contact_preference';
	const META_SELLER_USER_ID      = '_seller_user_id';
	const META_EXPIRATION_DATE     = '_expiration_date';
	const META_GALLERY_IMAGE_IDS   = '_gallery_image_ids';
	const META_ADMIN_NOTES         = '_admin_notes';
	const META_MODERATION_STATUS   = '_moderation_status';

	/**
	 * Get default meta values.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			self::META_LISTING_TYPE       => 'sale',
			self::META_PRICE_TYPE         => 'fixed',
			self::META_PRICE_AMOUNT       => '',
			self::META_PUBLIC_STATUS      => 'available',
			self::META_PICKUP_AREA        => '',
			self::META_SHIPPING_AVAILABLE => 'no',
			self::META_TRADE_AVAILABLE    => 'no',
			self::META_PRICE_NEGOTIABLE   => 'no',
			self::META_INCLUDED_ITEMS     => '',
			self::META_KNOWN_ISSUES       => '',
			self::META_TRADE_DETAILS      => '',
			self::META_CONTACT_PREFERENCE => 'contact_form_only',
			self::META_SELLER_USER_ID     => 0,
			self::META_EXPIRATION_DATE    => '',
			self::META_GALLERY_IMAGE_IDS  => array(),
			self::META_ADMIN_NOTES        => '',
			self::META_MODERATION_STATUS  => 'pending_review',
		);
	}

	/**
	 * Get listing types.
	 *
	 * @return array<string,string>
	 */
	public static function listing_types() {
		return array(
			'sale'   => __( 'For Sale', 'oras-member-hub' ),
			'trade'  => __( 'For Trade', 'oras-member-hub' ),
			'wanted' => __( 'Wanted', 'oras-member-hub' ),
			'free'   => __( 'Free / Giveaway', 'oras-member-hub' ),
		);
	}

	/**
	 * Get public status labels for a listing type.
	 *
	 * @param string $listing_type Listing type.
	 * @return array<string,string>
	 */
	public static function public_status_labels( $listing_type ) {
		$base = array(
			'available' => __( 'Available', 'oras-member-hub' ),
			'pending'   => __( 'Pending', 'oras-member-hub' ),
			'sold'      => __( 'Sold', 'oras-member-hub' ),
			'traded'    => __( 'Traded', 'oras-member-hub' ),
			'found'     => __( 'Found', 'oras-member-hub' ),
			'claimed'   => __( 'Claimed', 'oras-member-hub' ),
			'expired'   => __( 'Expired', 'oras-member-hub' ),
			'removed'   => __( 'Removed', 'oras-member-hub' ),
		);

		switch ( $listing_type ) {
			case 'wanted':
				return array(
					'available' => __( 'Still Looking', 'oras-member-hub' ),
					'found'     => $base['found'],
					'expired'   => $base['expired'],
				);
			case 'trade':
				return array(
					'available' => $base['available'],
					'pending'   => $base['pending'],
					'traded'    => $base['traded'],
					'expired'   => $base['expired'],
				);
			case 'free':
				return array(
					'available' => $base['available'],
					'pending'   => $base['pending'],
					'claimed'   => $base['claimed'],
					'expired'   => $base['expired'],
				);
			case 'sale':
			default:
				return array(
					'available' => $base['available'],
					'pending'   => $base['pending'],
					'sold'      => $base['sold'],
					'expired'   => $base['expired'],
				);
		}
	}

	/**
	 * Get moderation status labels.
	 *
	 * @return array<string,string>
	 */
	public static function moderation_status_labels() {
		return array(
			'pending_review' => __( 'Pending Review', 'oras-member-hub' ),
			'approved'       => __( 'Approved', 'oras-member-hub' ),
			'rejected'       => __( 'Rejected', 'oras-member-hub' ),
			'removed'        => __( 'Removed', 'oras-member-hub' ),
			'expired'        => __( 'Expired', 'oras-member-hub' ),
		);
	}

	/**
	 * Return display label for public status.
	 *
	 * @param int $post_id Listing post ID.
	 * @return string
	 */
	public static function get_public_status_label( $post_id ) {
		$listing_type = (string) get_post_meta( $post_id, self::META_LISTING_TYPE, true );
		$status       = (string) get_post_meta( $post_id, self::META_PUBLIC_STATUS, true );
		$labels       = self::public_status_labels( $listing_type );
		return isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Available', 'oras-member-hub' );
	}

	/**
	 * Update public status.
	 *
	 * @param int    $post_id Listing post ID.
	 * @param string $status Status.
	 * @return void
	 */
	public static function update_public_status( $post_id, $status ) {
		update_post_meta( $post_id, self::META_PUBLIC_STATUS, sanitize_key( $status ) );
	}

	/**
	 * Update moderation status.
	 *
	 * @param int    $post_id Listing post ID.
	 * @param string $status Status.
	 * @return void
	 */
	public static function update_moderation_status( $post_id, $status ) {
		update_post_meta( $post_id, self::META_MODERATION_STATUS, sanitize_key( $status ) );
	}

	/**
	 * Whether status action is valid for listing type.
	 *
	 * @param string $listing_type Listing type.
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_valid_public_status_for_type( $listing_type, $status ) {
		$labels = self::public_status_labels( $listing_type );
		return isset( $labels[ $status ] );
	}

	/**
	 * Get all public status labels used by filter controls.
	 *
	 * @return array<string,string>
	 */
	public static function all_public_status_labels() {
		return array(
			'available' => __( 'Available / Still Looking', 'oras-member-hub' ),
			'pending'   => __( 'Pending', 'oras-member-hub' ),
			'sold'      => __( 'Sold', 'oras-member-hub' ),
			'traded'    => __( 'Traded', 'oras-member-hub' ),
			'found'     => __( 'Found', 'oras-member-hub' ),
			'claimed'   => __( 'Claimed', 'oras-member-hub' ),
			'expired'   => __( 'Expired', 'oras-member-hub' ),
		);
	}
}
