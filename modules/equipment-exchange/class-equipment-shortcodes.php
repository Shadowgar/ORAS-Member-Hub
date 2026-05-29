<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange shortcodes and rendering.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes class.
 */
final class ORAS_MH_Equipment_Shortcodes {
	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'oras_equipment_exchange_preview', array( __CLASS__, 'shortcode_preview' ) );
		add_shortcode( 'oras_equipment_exchange_grid', array( __CLASS__, 'shortcode_grid' ) );
		add_shortcode( 'oras_equipment_exchange_submit', array( __CLASS__, 'shortcode_submit' ) );
		add_shortcode( 'oras_equipment_exchange_my_listings', array( __CLASS__, 'shortcode_my_listings' ) );
		add_shortcode( 'oras_equipment_exchange_single', array( __CLASS__, 'shortcode_single' ) );
		add_shortcode( 'oras_equipment_exchange_contact', array( __CLASS__, 'shortcode_contact' ) );
	}

	/**
	 * Render preview in hub module wrapper.
	 *
	 * @return string
	 */
	public static function render_preview_module() {
		$content = self::shortcode_preview();
		return oras_member_hub_wrap_module( 'equipment-exchange', __( 'ORAS Equipment Exchange', 'oras-member-hub' ), $content, 'main' );
	}

	/**
	 * Preview shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_preview() {
		self::enqueue_assets();
		$posts = self::query_listings( 8 );
		return self::render_template(
			'preview-row.php',
			array(
				'posts'      => $posts,
				'grid_url'   => ORAS_MH_Equipment_Settings::get_page_url( 'grid_page_url' ),
				'submit_url' => ORAS_MH_Equipment_Settings::get_page_url( 'submit_page_url' ),
			)
		);
	}

	/**
	 * Grid shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_grid() {
		if ( ! self::guard_member_access() ) {
			return self::members_only_message();
		}

		self::enqueue_assets();
		$filters     = self::read_grid_filters();
		$posts       = self::query_listings( 24, $filters );
		$disclaimer  = ORAS_MH_Equipment_Settings::get()['disclaimer_text'];
		$submit_url  = ORAS_MH_Equipment_Settings::get_page_url( 'submit_page_url' );
		$my_listings = ORAS_MH_Equipment_Settings::get_page_url( 'my_listings_page_url' );
		return self::render_template(
			'grid.php',
			array(
				'posts'        => $posts,
				'disclaimer'   => $disclaimer,
				'submit_url'   => $submit_url,
				'my_listings'  => $my_listings,
				'filters'      => $filters,
				'categories'   => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY,
						'hide_empty' => false,
					)
				),
				'conditions'   => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CONDITION,
						'hide_empty' => false,
					)
				),
			)
		);
	}

	/**
	 * Submit shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_submit() {
		if ( ! self::guard_member_access() ) {
			return self::members_only_message();
		}

		self::enqueue_assets();
		$settings = ORAS_MH_Equipment_Settings::get();
		return self::render_template(
			'submit-listing.php',
			array(
				'settings'   => $settings,
				'categories' => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY,
						'hide_empty' => false,
					)
				),
				'conditions' => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CONDITION,
						'hide_empty' => false,
					)
				),
				'notice_html' => self::notice_html(),
			)
		);
	}

	/**
	 * My listings shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_my_listings() {
		if ( ! self::guard_member_access() ) {
			return self::members_only_message();
		}

		self::enqueue_assets();
		$posts = get_posts(
			array(
				'post_type'      => ORAS_MH_Equipment_Post_Type::POST_TYPE,
				'author'         => get_current_user_id(),
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => 100,
			)
		);
		return self::render_template(
			'my-listings.php',
			array(
				'posts'       => $posts,
				'notice_html' => self::notice_html(),
				'categories'  => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY,
						'hide_empty' => false,
					)
				),
				'conditions'  => get_terms(
					array(
						'taxonomy'   => ORAS_MH_Equipment_Taxonomies::TAX_CONDITION,
						'hide_empty' => false,
					)
				),
			)
		);
	}

	/**
	 * Single listing shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_single() {
		if ( ! self::guard_member_access() ) {
			return self::members_only_message();
		}

		self::enqueue_assets();
		$listing = self::resolve_single_listing();
		if ( ! $listing ) {
			return '<p>' . esc_html__( 'Listing not found.', 'oras-member-hub' ) . '</p>';
		}

		$moderation = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_MODERATION_STATUS, true );
		$author_ok  = (int) get_current_user_id() === (int) $listing->post_author;
		if ( 'approved' !== $moderation && ! $author_ok && ! ORAS_MH_Equipment_Permissions::can_manage_all() ) {
			return '<p>' . esc_html__( 'Listing not available.', 'oras-member-hub' ) . '</p>';
		}

		return self::render_template(
			'single-listing.php',
			array(
				'listing'    => $listing,
				'disclaimer' => ORAS_MH_Equipment_Settings::get()['disclaimer_text'],
			)
		);
	}

	/**
	 * Contact shortcode.
	 *
	 * @return string
	 */
	public static function shortcode_contact() {
		if ( ! self::guard_member_access() ) {
			return self::members_only_message();
		}
		$listing = self::resolve_single_listing();
		if ( ! $listing ) {
			return '';
		}
		return self::render_template( 'contact-seller-form.php', array( 'listing' => $listing ) );
	}

	/**
	 * Format price display.
	 *
	 * @param int $post_id Listing ID.
	 * @return string
	 */
	public static function format_price( $post_id ) {
		$type   = (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
		$amount = trim( (string) get_post_meta( $post_id, ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT, true ) );
		if ( 'wanted' === $type ) {
			/* translators: %s: buyer budget text entered by member. */
			return '' !== $amount ? sprintf( __( 'Wanted (Budget: %s)', 'oras-member-hub' ), $amount ) : __( 'Wanted', 'oras-member-hub' );
		}
		if ( 'free' === $type ) {
			return __( 'Free', 'oras-member-hub' );
		}
		if ( 'trade' === $type ) {
			return __( 'Trade', 'oras-member-hub' );
		}
		return '' !== $amount ? $amount : __( 'Contact seller', 'oras-member-hub' );
	}

	/**
	 * Query approved listings.
	 *
	 * @param int $limit Limit.
	 * @param array<string,mixed> $filters Grid filters.
	 * @return array<int,WP_Post>
	 */
	private static function query_listings( $limit, $filters = array() ) {
		$args = array(
				'post_type'      => ORAS_MH_Equipment_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				's'              => (string) ( $filters['search'] ?? '' ),
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => ORAS_MH_Equipment_Fields::META_MODERATION_STATUS,
						'value' => 'approved',
					),
					array(
						'key'     => ORAS_MH_Equipment_Fields::META_PUBLIC_STATUS,
						'value'   => array( 'expired', 'removed' ),
						'compare' => 'NOT IN',
					),
				),
			);

		if ( ! empty( $filters['listing_type'] ) ) {
			$args['meta_query'][] = array(
				'key'   => ORAS_MH_Equipment_Fields::META_LISTING_TYPE,
				'value' => (string) $filters['listing_type'],
			);
		}

		if ( ! empty( $filters['status'] ) ) {
			$args['meta_query'][] = array(
				'key'   => ORAS_MH_Equipment_Fields::META_PUBLIC_STATUS,
				'value' => (string) $filters['status'],
			);
		}

		$price_min = isset( $filters['price_min'] ) ? (float) $filters['price_min'] : 0.0;
		$price_max = isset( $filters['price_max'] ) ? (float) $filters['price_max'] : 0.0;
		if ( $price_min > 0 ) {
			$args['meta_query'][] = array(
				'key'     => ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT,
				'value'   => $price_min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $price_max > 0 ) {
			$args['meta_query'][] = array(
				'key'     => ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT,
				'value'   => $price_max,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		if ( ! empty( $filters['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array( (int) $filters['category'] ),
			);
		}

		if ( ! empty( $filters['condition'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => ORAS_MH_Equipment_Taxonomies::TAX_CONDITION,
				'field'    => 'term_id',
				'terms'    => array( (int) $filters['condition'] ),
			);
		}

		if ( ! empty( $filters['sort'] ) && in_array( $filters['sort'], array( 'price_low', 'price_high' ), true ) ) {
			$args['meta_key'] = ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT;
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'price_low' === $filters['sort'] ? 'ASC' : 'DESC';
		}

		return get_posts( $args );
	}

	/**
	 * Read grid filters from query string.
	 *
	 * @return array<string,mixed>
	 */
	private static function read_grid_filters() {
		return array(
			'search'       => isset( $_GET['search'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['search'] ) ) : '',
			'listing_type' => isset( $_GET['listing_type'] ) ? sanitize_key( (string) wp_unslash( $_GET['listing_type'] ) ) : '',
			'category'     => isset( $_GET['category'] ) ? (int) $_GET['category'] : 0,
			'condition'    => isset( $_GET['condition'] ) ? (int) $_GET['condition'] : 0,
			'status'       => isset( $_GET['status'] ) ? sanitize_key( (string) wp_unslash( $_GET['status'] ) ) : '',
			'price_min'    => isset( $_GET['price_min'] ) ? (float) $_GET['price_min'] : 0,
			'price_max'    => isset( $_GET['price_max'] ) ? (float) $_GET['price_max'] : 0,
			'sort'         => isset( $_GET['sort'] ) ? sanitize_key( (string) wp_unslash( $_GET['sort'] ) ) : '',
		);
	}

	/**
	 * Resolve listing from query parameter.
	 *
	 * @return WP_Post|null
	 */
	private static function resolve_single_listing() {
		$identifier = isset( $_GET['listing'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['listing'] ) ) : '';
		if ( '' === $identifier ) {
			return null;
		}

		if ( is_numeric( $identifier ) ) {
			$post = get_post( (int) $identifier );
			return $post instanceof WP_Post ? $post : null;
		}

		$post = get_page_by_path( $identifier, OBJECT, ORAS_MH_Equipment_Post_Type::POST_TYPE );
		return $post instanceof WP_Post ? $post : null;
	}

	/**
	 * Render module template.
	 *
	 * @param string               $file Template file.
	 * @param array<string,mixed>  $vars Template vars.
	 * @return string
	 */
	private static function render_template( $file, $vars = array() ) {
		$path = ORAS_MEMBER_HUB_PATH . 'modules/equipment-exchange/templates/' . $file;
		if ( ! file_exists( $path ) ) {
			return '';
		}
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		ob_start();
		require $path;
		return (string) ob_get_clean();
	}

	/**
	 * Enqueue module assets.
	 *
	 * @return void
	 */
	private static function enqueue_assets() {
		wp_enqueue_style( 'oras-equipment-exchange' );
		wp_enqueue_script( 'oras-equipment-exchange' );
	}

	/**
	 * Access guard.
	 *
	 * @return bool
	 */
	private static function guard_member_access() {
		return ORAS_MH_Equipment_Settings::is_enabled() && ORAS_MH_Equipment_Permissions::current_user_has_access();
	}

	/**
	 * Members-only message.
	 *
	 * @return string
	 */
	private static function members_only_message() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to access the Equipment Exchange.', 'oras-member-hub' ) . '</p>';
		}
		return '<p>' . esc_html__( 'This section is for active ORAS members only.', 'oras-member-hub' ) . '</p>';
	}

	/**
	 * Render frontend notice.
	 *
	 * @return string
	 */
	private static function notice_html() {
		$msg  = isset( $_GET['oras_equipment_notice'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['oras_equipment_notice'] ) ) : '';
		$type = isset( $_GET['oras_equipment_notice_type'] ) ? sanitize_key( (string) wp_unslash( $_GET['oras_equipment_notice_type'] ) ) : 'success';
		if ( '' === $msg ) {
			return '';
		}
		$class = 'oras-equipment-notice oras-equipment-notice--' . ( 'error' === $type ? 'error' : 'success' );
		return '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $msg ) . '</p></div>';
	}
}
