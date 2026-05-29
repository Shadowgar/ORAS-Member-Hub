<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange module coordinator.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-equipment-fields.php';
require_once __DIR__ . '/class-equipment-settings.php';
require_once __DIR__ . '/class-equipment-permissions.php';
require_once __DIR__ . '/class-equipment-post-type.php';
require_once __DIR__ . '/class-equipment-taxonomies.php';
require_once __DIR__ . '/class-equipment-notifications.php';
require_once __DIR__ . '/class-equipment-forms.php';
require_once __DIR__ . '/class-equipment-contact.php';
require_once __DIR__ . '/class-equipment-shortcodes.php';
require_once __DIR__ . '/class-equipment-admin.php';

/**
 * Module bootstrap.
 */
final class ORAS_MH_Equipment_Exchange {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		ORAS_MH_Equipment_Settings::register();
		ORAS_MH_Equipment_Post_Type::register();
		ORAS_MH_Equipment_Taxonomies::register();
		ORAS_MH_Equipment_Forms::register();
		ORAS_MH_Equipment_Contact::register();
		ORAS_MH_Equipment_Shortcodes::register();
		ORAS_MH_Equipment_Admin::register();

		add_filter( 'oras_member_hub_main_modules', array( __CLASS__, 'inject_preview_module' ) );
		add_filter( 'oras_member_hub_sidebar_modules', array( __CLASS__, 'inject_sidebar_module' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'oras_mh_equipment_exchange_expire_listings', array( __CLASS__, 'expire_listings' ) );
		add_action( 'init', array( __CLASS__, 'ensure_cron' ) );
	}

	/**
	 * Register module assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style(
			'oras-equipment-exchange',
			ORAS_MEMBER_HUB_URL . 'modules/equipment-exchange/assets/equipment-exchange.css',
			array(),
			ORAS_MEMBER_HUB_VERSION
		);

		wp_register_script(
			'oras-equipment-exchange',
			ORAS_MEMBER_HUB_URL . 'modules/equipment-exchange/assets/equipment-exchange.js',
			array(),
			ORAS_MEMBER_HUB_VERSION,
			true
		);
	}

	/**
	 * Add preview module to main hub list.
	 *
	 * @param array<string,string> $modules Existing modules.
	 * @return array<string,string>
	 */
	public static function inject_preview_module( $modules ) {
		if ( ! ORAS_MH_Equipment_Settings::is_enabled() ) {
			return $modules;
		}

		$modules['equipment-exchange-preview'] = array( ORAS_MH_Equipment_Shortcodes::class, 'render_preview_module' );
		return $modules;
	}

	/**
	 * Inject sidebar marketplace menu between membership and account blocks.
	 *
	 * @param array<string,string> $modules Existing modules.
	 * @return array<string,string>
	 */
	public static function inject_sidebar_module( $modules ) {
		if ( ! ORAS_MH_Equipment_Settings::is_enabled() ) {
			return $modules;
		}

		$result = array();
		foreach ( $modules as $key => $callback ) {
			$result[ $key ] = $callback;
			if ( 'membership' === $key ) {
				$result['equipment_marketplace'] = array( ORAS_MH_Equipment_Shortcodes::class, 'render_sidebar_marketplace_module' );
			}
		}

		if ( ! isset( $result['equipment_marketplace'] ) ) {
			$result['equipment_marketplace'] = array( ORAS_MH_Equipment_Shortcodes::class, 'render_sidebar_marketplace_module' );
		}

		return $result;
	}

	/**
	 * Ensure expiration cron is scheduled.
	 *
	 * @return void
	 */
	public static function ensure_cron() {
		if ( ! wp_next_scheduled( 'oras_mh_equipment_exchange_expire_listings' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'oras_mh_equipment_exchange_expire_listings' );
		}
	}

	/**
	 * Expire listings past expiration date.
	 *
	 * @return void
	 */
	public static function expire_listings() {
		$today = gmdate( 'Y-m-d' );
		$post_ids = get_posts(
			array(
				'post_type'      => ORAS_MH_Equipment_Post_Type::POST_TYPE,
				'post_status'    => array( 'publish', 'pending' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE,
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			)
		);

		foreach ( $post_ids as $post_id ) {
			ORAS_MH_Equipment_Fields::update_public_status( (int) $post_id, 'expired' );
			ORAS_MH_Equipment_Fields::update_moderation_status( (int) $post_id, 'expired' );
		}
	}
}
