<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange admin behavior.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
final class ORAS_MH_Equipment_Admin {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'manage_' . ORAS_MH_Equipment_Post_Type::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . ORAS_MH_Equipment_Post_Type::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_action' ) );
	}

	/**
	 * Add custom columns.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public static function columns( $columns ) {
		$columns['equipment_seller']     = __( 'Seller', 'oras-member-hub' );
		$columns['equipment_type']       = __( 'Type', 'oras-member-hub' );
		$columns['equipment_price']      = __( 'Price', 'oras-member-hub' );
		$columns['equipment_status']     = __( 'Status', 'oras-member-hub' );
		$columns['equipment_moderation'] = __( 'Moderation', 'oras-member-hub' );
		$columns['equipment_photos']     = __( 'Photos', 'oras-member-hub' );
		return $columns;
	}

	/**
	 * Render custom column.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'equipment_seller':
				echo esc_html( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ) );
				break;
			case 'equipment_type':
				$types = ORAS_MH_Equipment_Fields::listing_types();
				$type  = (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
				echo esc_html( $types[ $type ] ?? $type );
				break;
			case 'equipment_price':
				echo esc_html( ORAS_MH_Equipment_Shortcodes::format_price( $post_id ) );
				break;
			case 'equipment_status':
				echo esc_html( ORAS_MH_Equipment_Fields::get_public_status_label( $post_id ) );
				break;
			case 'equipment_moderation':
				$labels = ORAS_MH_Equipment_Fields::moderation_status_labels();
				$key    = (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_MODERATION_STATUS, true );
				echo esc_html( $labels[ $key ] ?? $key );
				break;
			case 'equipment_photos':
				$ids = (array) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_GALLERY_IMAGE_IDS, true );
				echo esc_html( (string) count( array_filter( array_map( 'intval', $ids ) ) ) );
				break;
		}
	}

	/**
	 * Add moderation row actions.
	 *
	 * @param array<string,string> $actions Existing actions.
	 * @param WP_Post              $post Post object.
	 * @return array<string,string>
	 */
	public static function row_actions( $actions, $post ) {
		if ( ORAS_MH_Equipment_Post_Type::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$base = admin_url( 'edit.php?post_type=' . ORAS_MH_Equipment_Post_Type::POST_TYPE );
		$mk   = static function ( $action, $label ) use ( $post, $base ) {
			$url = wp_nonce_url(
				add_query_arg(
					array(
						'oras_equipment_admin_action' => $action,
						'listing_id'                  => $post->ID,
					),
					$base
				),
				'oras_equipment_admin_action_' . $action . '_' . $post->ID
			);
			return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		};

		$actions['oras_approve'] = $mk( 'approve', __( 'Approve', 'oras-member-hub' ) );
		$actions['oras_reject']  = $mk( 'reject', __( 'Reject', 'oras-member-hub' ) );
		$actions['oras_remove']  = $mk( 'remove', __( 'Remove', 'oras-member-hub' ) );
		$actions['oras_expire']  = $mk( 'expire', __( 'Mark Expired', 'oras-member-hub' ) );
		return $actions;
	}

	/**
	 * Handle moderation action.
	 *
	 * @return void
	 */
	public static function handle_admin_action() {
		if ( ! is_admin() || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$action  = isset( $_GET['oras_equipment_admin_action'] ) ? sanitize_key( (string) wp_unslash( $_GET['oras_equipment_admin_action'] ) ) : '';
		$post_id = isset( $_GET['listing_id'] ) ? (int) $_GET['listing_id'] : 0;
		if ( '' === $action || $post_id <= 0 ) {
			return;
		}

		check_admin_referer( 'oras_equipment_admin_action_' . $action . '_' . $post_id );

		switch ( $action ) {
			case 'approve':
				ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'approved' );
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
				break;
			case 'reject':
				ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'rejected' );
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
				break;
			case 'remove':
				ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'removed' );
				ORAS_MH_Equipment_Fields::update_public_status( $post_id, 'removed' );
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
				break;
			case 'expire':
				ORAS_MH_Equipment_Fields::update_moderation_status( $post_id, 'expired' );
				ORAS_MH_Equipment_Fields::update_public_status( $post_id, 'expired' );
				break;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . ORAS_MH_Equipment_Post_Type::POST_TYPE ) );
		exit;
	}
}
