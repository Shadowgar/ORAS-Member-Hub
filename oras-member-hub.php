<?php
/**
 * Plugin Name: ORAS Member Hub
 * Description: Member-facing dashboard hub for ORAS.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: ORAS
 * Text Domain: oras-member-hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ORAS_MEMBER_HUB_VERSION' ) ) {
	define( 'ORAS_MEMBER_HUB_VERSION', '0.1.0' );
}

if ( ! defined( 'ORAS_MEMBER_HUB_FILE' ) ) {
	define( 'ORAS_MEMBER_HUB_FILE', __FILE__ );
}

if ( ! defined( 'ORAS_MEMBER_HUB_PATH' ) ) {
	define( 'ORAS_MEMBER_HUB_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ORAS_MEMBER_HUB_URL' ) ) {
	define( 'ORAS_MEMBER_HUB_URL', plugin_dir_url( __FILE__ ) );
}

require_once ORAS_MEMBER_HUB_PATH . 'includes/member-hub-modules.php';
require_once ORAS_MEMBER_HUB_PATH . 'includes/member-hub-shortcode.php';
require_once ORAS_MEMBER_HUB_PATH . 'includes/shortcodes/my-tickets.php';

if ( ! function_exists( 'oras_member_hub_bootstrap' ) ) {
	/**
	 * Initialize plugin hooks.
	 *
	 * @return void
	 */
	function oras_member_hub_bootstrap() {
		oras_member_hub_register_shortcode();
		oras_member_hub_register_my_tickets_shortcode();
	}
}

add_action( 'plugins_loaded', 'oras_member_hub_bootstrap' );
