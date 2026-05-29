<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange frontend forms.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Missing

/**
 * Forms class.
 */
final class ORAS_MH_Equipment_Forms {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'handle_form_posts' ) );
	}

	/**
	 * Handle all frontend postbacks.
	 *
	 * @return void
	 */
	public static function handle_form_posts() {
		if ( 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		$action = isset( $_POST['oras_equipment_action'] ) ? sanitize_key( (string) wp_unslash( $_POST['oras_equipment_action'] ) ) : '';
		if ( '' === $action ) {
			return;
		}

		if ( ! ORAS_MH_Equipment_Permissions::current_user_has_access() ) {
			self::redirect_with_notice( 'error', 'Access denied.' );
		}

		switch ( $action ) {
			case 'submit_listing':
				self::submit_listing();
				break;
			case 'edit_listing':
				self::edit_listing();
				break;
			case 'update_status':
				self::update_status();
				break;
			case 'renew_listing':
				self::renew_listing();
				break;
			case 'delete_listing':
				self::delete_listing();
				break;
			case 'contact_seller':
				ORAS_MH_Equipment_Contact::handle_submission();
				break;
		}
	}

	/**
	 * Submit listing.
	 *
	 * @return void
	 */
	private static function submit_listing() {
		check_admin_referer( 'oras_equipment_submit', 'oras_equipment_nonce' );
		$result = self::build_post_data_from_request( 0 );
		if ( is_wp_error( $result ) ) {
			self::redirect_with_notice( 'error', __( 'Unable to submit listing. Please verify required fields.', 'oras-member-hub' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => ORAS_MH_Equipment_Post_Type::POST_TYPE,
				'post_status'  => $result['post_status'],
				'post_title'   => $result['post_title'],
				'post_content' => $result['post_content'],
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			self::redirect_with_notice( 'error', __( 'Unable to create listing.', 'oras-member-hub' ) );
		}

		self::save_meta_and_taxonomies( (int) $post_id, $result, true );
		ORAS_MH_Equipment_Notifications::notify_admin_pending( (int) $post_id );
		self::redirect_with_notice( 'success', __( 'Your listing has been submitted for review. It will appear in the Equipment Exchange after approval.', 'oras-member-hub' ) );
	}

	/**
	 * Edit listing.
	 *
	 * @return void
	 */
	private static function edit_listing() {
		check_admin_referer( 'oras_equipment_edit', 'oras_equipment_nonce' );
		$post_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
		if ( $post_id <= 0 || ! ORAS_MH_Equipment_Permissions::can_edit_listing( $post_id, get_current_user_id() ) ) {
			self::redirect_with_notice( 'error', __( 'You cannot edit this listing.', 'oras-member-hub' ) );
		}

		$before = self::snapshot_listing_state( $post_id );
		$result = self::build_post_data_from_request( $post_id, false );
		if ( is_wp_error( $result ) ) {
			self::redirect_with_notice( 'error', __( 'Unable to update listing. Please verify required fields.', 'oras-member-hub' ) );
		}

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $result['post_title'],
				'post_content' => $result['post_content'],
			)
		);

		self::save_meta_and_taxonomies( $post_id, $result, false, $before );
		self::redirect_with_notice( 'success', __( 'Listing updated.', 'oras-member-hub' ) );
	}

	/**
	 * Update public status.
	 *
	 * @return void
	 */
	private static function update_status() {
		check_admin_referer( 'oras_equipment_status', 'oras_equipment_nonce' );
		$post_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
		$status  = isset( $_POST['public_status'] ) ? sanitize_key( (string) wp_unslash( $_POST['public_status'] ) ) : '';
		if ( $post_id <= 0 || '' === $status || ! ORAS_MH_Equipment_Permissions::can_edit_listing( $post_id, get_current_user_id() ) ) {
			self::redirect_with_notice( 'error', __( 'Invalid status update.', 'oras-member-hub' ) );
		}
		$listing_type = (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
		if ( ! ORAS_MH_Equipment_Fields::is_valid_public_status_for_type( $listing_type, $status ) ) {
			self::redirect_with_notice( 'error', __( 'Status is not valid for this listing type.', 'oras-member-hub' ) );
		}

		ORAS_MH_Equipment_Fields::update_public_status( $post_id, $status );
		self::redirect_with_notice( 'success', __( 'Listing status updated.', 'oras-member-hub' ) );
	}

	/**
	 * Renew listing.
	 *
	 * @return void
	 */
	private static function renew_listing() {
		check_admin_referer( 'oras_equipment_renew', 'oras_equipment_nonce' );
		$post_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
		if ( $post_id <= 0 || ! ORAS_MH_Equipment_Permissions::can_edit_listing( $post_id, get_current_user_id() ) ) {
			self::redirect_with_notice( 'error', __( 'Cannot renew listing.', 'oras-member-hub' ) );
		}

		$settings   = ORAS_MH_Equipment_Settings::get();
		$expires_at = gmdate( 'Y-m-d', time() + ( DAY_IN_SECONDS * (int) $settings['expiration_days'] ) );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, $expires_at );
		ORAS_MH_Equipment_Fields::update_public_status( $post_id, 'available' );

		if ( ! empty( $settings['require_approval'] ) ) {
			ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'pending_review' );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'pending' ) );
		} else {
			ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'approved' );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		}

		self::redirect_with_notice( 'success', __( 'Listing renewed.', 'oras-member-hub' ) );
	}

	/**
	 * Delete listing.
	 *
	 * @return void
	 */
	private static function delete_listing() {
		check_admin_referer( 'oras_equipment_delete', 'oras_equipment_nonce' );
		$post_id = isset( $_POST['listing_id'] ) ? (int) $_POST['listing_id'] : 0;
		if ( $post_id <= 0 || ! ORAS_MH_Equipment_Permissions::can_edit_listing( $post_id, get_current_user_id() ) ) {
			self::redirect_with_notice( 'error', __( 'Cannot delete listing.', 'oras-member-hub' ) );
		}
		wp_trash_post( $post_id );
		self::redirect_with_notice( 'success', __( 'Listing moved to trash.', 'oras-member-hub' ) );
	}

	/**
	 * Build and validate listing payload.
	 *
	 * @param int $post_id Existing post ID.
	 * @param bool $is_new Whether this is a new listing submission.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function build_post_data_from_request( $post_id, $is_new = true ) {
		$post_id      = (int) $post_id;
		$title        = sanitize_text_field( (string) wp_unslash( $_POST['listing_title'] ?? '' ) );
		$description  = sanitize_textarea_field( (string) wp_unslash( $_POST['listing_description'] ?? '' ) );
		$listing_type = sanitize_key( (string) wp_unslash( $_POST['listing_type'] ?? 'sale' ) );
		$price_type   = sanitize_key( (string) wp_unslash( $_POST['price_type'] ?? 'fixed' ) );
		$price_amount = sanitize_text_field( (string) wp_unslash( $_POST['price_amount'] ?? '' ) );
		$pickup_area  = sanitize_text_field( (string) wp_unslash( $_POST['pickup_area'] ?? '' ) );
		$included     = sanitize_textarea_field( (string) wp_unslash( $_POST['included_items'] ?? '' ) );
		$issues       = sanitize_textarea_field( (string) wp_unslash( $_POST['known_issues'] ?? '' ) );
		$trade        = sanitize_textarea_field( (string) wp_unslash( $_POST['trade_details'] ?? '' ) );
		$contact_pref = sanitize_key( (string) wp_unslash( $_POST['contact_preference'] ?? 'contact_form_only' ) );
		$category_id  = isset( $_POST['equipment_category'] ) ? (int) $_POST['equipment_category'] : 0;
		$condition_id = isset( $_POST['equipment_condition'] ) ? (int) $_POST['equipment_condition'] : 0;
		$shipping     = ! empty( $_POST['shipping_available'] ) ? 'yes' : 'no';
		$agreement    = ! empty( $_POST['listing_agreement'] );

		if ( '' === $title || '' === $description || '' === $listing_type || $category_id <= 0 || '' === $pickup_area ) {
			return new WP_Error( 'invalid', __( 'Please complete all required fields.', 'oras-member-hub' ) );
		}

		if ( $is_new && ! $agreement ) {
			return new WP_Error( 'agreement', __( 'You must accept the agreement to submit.', 'oras-member-hub' ) );
		}

		if ( 'sale' === $listing_type && in_array( $price_type, array( 'fixed', 'obo' ), true ) && '' === trim( $price_amount ) ) {
			return new WP_Error( 'price_required', __( 'Price amount is required for fixed or OBO sale listings.', 'oras-member-hub' ) );
		}

		$gallery_ids = self::handle_uploads( $post_id );
		if ( is_wp_error( $gallery_ids ) ) {
			return $gallery_ids;
		}

		if ( $is_new ) {
			if ( empty( $gallery_ids ) ) {
				return new WP_Error( 'photo_required', __( 'At least one photo is required.', 'oras-member-hub' ) );
			}
		} elseif ( empty( $gallery_ids ) ) {
			$gallery_ids = array_map( 'intval', (array) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_GALLERY_IMAGE_IDS, true ) );
		}

		$settings = ORAS_MH_Equipment_Settings::get();
		$status   = ! empty( $settings['require_approval'] ) ? 'pending' : 'publish';

		return array(
			'post_title'           => $title,
			'post_content'         => $description,
			'post_status'          => $status,
			'listing_type'         => $listing_type,
			'price_type'           => $price_type,
			'price_amount'         => $price_amount,
			'pickup_area'          => $pickup_area,
			'included_items'       => $included,
			'known_issues'         => $issues,
			'trade_details'        => $trade,
			'contact_preference'   => in_array( $contact_pref, array( 'contact_form_only', 'show_email_to_members', 'show_phone_to_members' ), true ) ? $contact_pref : 'contact_form_only',
			'category_id'          => $category_id,
			'condition_id'         => $condition_id,
			'shipping_available'   => $shipping,
			'gallery_image_ids'    => $gallery_ids,
			'moderation_status'    => ! empty( $settings['require_approval'] ) ? 'pending_review' : 'approved',
		);
	}

	/**
	 * Save listing meta and taxonomy terms.
	 *
	 * @param int                 $post_id Listing post ID.
	 * @param array<string,mixed> $data Data.
	 * @param bool                $is_new Whether new listing.
	 * @param array<string,mixed> $before Snapshot before update.
	 * @return void
	 */
	private static function save_meta_and_taxonomies( $post_id, $data, $is_new, $before = array() ) {
		$post_id = (int) $post_id;

		wp_set_object_terms( $post_id, array( (int) $data['category_id'] ), ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY, false );
		if ( ! empty( $data['condition_id'] ) ) {
			wp_set_object_terms( $post_id, array( (int) $data['condition_id'] ), ORAS_MH_Equipment_Taxonomies::TAX_CONDITION, false );
		}

		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, $data['listing_type'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, $data['price_type'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT, $data['price_amount'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_PICKUP_AREA, $data['pickup_area'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_INCLUDED_ITEMS, $data['included_items'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_KNOWN_ISSUES, $data['known_issues'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_TRADE_DETAILS, $data['trade_details'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_CONTACT_PREFERENCE, $data['contact_preference'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_SHIPPING_AVAILABLE, $data['shipping_available'] );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_GALLERY_IMAGE_IDS, array_values( array_unique( array_map( 'intval', (array) $data['gallery_image_ids'] ) ) ) );
		update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_SELLER_USER_ID, (int) get_post_field( 'post_author', $post_id ) );

		$settings   = ORAS_MH_Equipment_Settings::get();
		$expires_at = gmdate( 'Y-m-d', time() + ( DAY_IN_SECONDS * (int) $settings['expiration_days'] ) );
		if ( $is_new || '' === (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, true ) ) {
			update_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, $expires_at );
		}

		ORAS_MH_Equipment_Fields::update_public_status( $post_id, 'available' );
		ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, $data['moderation_status'] );

		if ( ! empty( $data['gallery_image_ids'] ) ) {
			set_post_thumbnail( $post_id, (int) $data['gallery_image_ids'][0] );
		}

		if ( ! $is_new ) {
			self::maybe_demote_after_major_edit( $post_id, $data, $before );
		}
	}

	/**
	 * Demote to pending review for major edits.
	 *
	 * @param int                 $post_id Listing ID.
	 * @param array<string,mixed> $data Payload.
	 * @param array<string,mixed> $before Snapshot before update.
	 * @return void
	 */
	private static function maybe_demote_after_major_edit( $post_id, $data, $before ) {
		$settings = ORAS_MH_Equipment_Settings::get();
		if ( empty( $settings['require_approval'] ) ) {
			return;
		}

		$after = array(
			'title'              => (string) $data['post_title'],
			'description'        => (string) $data['post_content'],
			'price_amount'       => (string) $data['price_amount'],
			'category_id'        => (int) $data['category_id'],
			'condition_id'       => (int) $data['condition_id'],
			'contact_preference' => (string) $data['contact_preference'],
			'gallery_hash'       => md5( wp_json_encode( array_map( 'intval', (array) $data['gallery_image_ids'] ) ) ),
		);

		$major_changes = false;
		$keys          = array( 'title', 'description', 'price_amount', 'category_id', 'condition_id', 'contact_preference', 'gallery_hash' );
		foreach ( $keys as $key ) {
			$before_value = isset( $before[ $key ] ) ? (string) $before[ $key ] : '';
			$after_value  = (string) $after[ $key ];
			if ( $before_value !== $after_value ) {
				$major_changes = true;
				break;
			}
		}

		if ( $major_changes ) {
			ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'pending_review' );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'pending' ) );
		}
	}

	/**
	 * Snapshot listing fields used to decide major edit moderation.
	 *
	 * @param int $post_id Listing ID.
	 * @return array<string,mixed>
	 */
	private static function snapshot_listing_state( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$category_ids  = wp_get_object_terms( $post_id, ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY, array( 'fields' => 'ids' ) );
		$condition_ids = wp_get_object_terms( $post_id, ORAS_MH_Equipment_Taxonomies::TAX_CONDITION, array( 'fields' => 'ids' ) );

		return array(
			'title'              => (string) $post->post_title,
			'description'        => (string) $post->post_content,
			'price_amount'       => (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT, true ),
			'category_id'        => ! empty( $category_ids ) && ! is_wp_error( $category_ids ) ? (int) $category_ids[0] : 0,
			'condition_id'       => ! empty( $condition_ids ) && ! is_wp_error( $condition_ids ) ? (int) $condition_ids[0] : 0,
			'contact_preference' => (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_CONTACT_PREFERENCE, true ),
			'gallery_hash'       => md5( wp_json_encode( array_map( 'intval', (array) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_GALLERY_IMAGE_IDS, true ) ) ) ),
		);
	}

	/**
	 * Validate and upload photos.
	 *
	 * @param int $post_id Listing post ID.
	 * @return array<int,int>|WP_Error
	 */
	private static function handle_uploads( $post_id ) {
		$post_id  = (int) $post_id;
		$settings = ORAS_MH_Equipment_Settings::get();
		$max      = (int) $settings['max_photos'];
		$max_size = (int) $settings['max_upload_mb'] * 1024 * 1024;
		$allowed  = array( 'image/jpeg', 'image/png', 'image/webp' );
		$ids      = array();

		if ( empty( $_FILES['listing_photos'] ) || ! is_array( $_FILES['listing_photos']['name'] ) ) {
			return array();
		}

		$count = count( $_FILES['listing_photos']['name'] );
		if ( $count > $max ) {
			/* translators: %d: max number of photos per listing. */
			return new WP_Error( 'too_many_files', sprintf( __( 'Maximum %d photos allowed.', 'oras-member-hub' ), $max ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		for ( $i = 0; $i < $count; $i++ ) {
			if ( empty( $_FILES['listing_photos']['name'][ $i ] ) ) {
				continue;
			}

			if ( (int) $_FILES['listing_photos']['size'][ $i ] > $max_size ) {
				return new WP_Error( 'file_too_large', __( 'One or more images exceed the max upload size.', 'oras-member-hub' ) );
			}

			$tmp_name = (string) $_FILES['listing_photos']['tmp_name'][ $i ];
			$mime     = (string) mime_content_type( $tmp_name );
			if ( ! in_array( $mime, $allowed, true ) ) {
				return new WP_Error( 'bad_file_type', __( 'Only JPG, JPEG, PNG, and WebP files are allowed.', 'oras-member-hub' ) );
			}

			$file = array(
				'name'     => $_FILES['listing_photos']['name'][ $i ],
				'tmp_name' => $_FILES['listing_photos']['tmp_name'][ $i ],
				'error'    => $_FILES['listing_photos']['error'][ $i ],
				'size'     => $_FILES['listing_photos']['size'][ $i ],
				'type'     => $_FILES['listing_photos']['type'][ $i ],
			);

			$_FILES['oras_equipment_single_upload'] = $file;
			$attachment_id                          = media_handle_upload( 'oras_equipment_single_upload', $post_id );

			if ( is_wp_error( $attachment_id ) ) {
				return new WP_Error( 'upload_error', __( 'Unable to upload one or more images.', 'oras-member-hub' ) );
			}

			$ids[] = (int) $attachment_id;
		}

		return $ids;
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
