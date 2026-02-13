<?php
/**
 * Local static-analysis stubs for WordPress/PMPRO symbols.
 */

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( $code = '', $message = '', $data = '' ) {}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		public function __construct( $method = 'GET', $route = '' ) {}

		public function set_param( $key, $value ) {}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public function get_status() {
			return 200;
		}

		public function get_data() {
			return array();
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		public function get_routes() {
			return array();
		}
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query {
		public function __construct( $args = array() ) {}

		public function have_posts() {
			return false;
		}

		public function the_post() {}
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	class WP_User {
		public $ID = 0;
		public $display_name = '';
	}
}

if ( ! class_exists( 'Membership' ) ) {
	class Membership {}
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'ORAS_MEMBER_HUB_VERSION' ) ) {
	define( 'ORAS_MEMBER_HUB_VERSION', '0.0.0' );
}

if ( ! defined( 'ORAS_MEMBER_HUB_FILE' ) ) {
	define( 'ORAS_MEMBER_HUB_FILE', __FILE__ );
}

if ( ! defined( 'ORAS_MEMBER_HUB_PATH' ) ) {
	define( 'ORAS_MEMBER_HUB_PATH', '' );
}

if ( ! defined( 'ORAS_MEMBER_HUB_URL' ) ) {
	define( 'ORAS_MEMBER_HUB_URL', '' );
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $_context = 'display' ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	function sanitize_html_class( $classname, $fallback = '' ) {
		$clean = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $classname );
		if ( '' === $clean ) {
			return (string) $fallback;
		}

		return $clean;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
		return is_array( $atts ) ? array_merge( $pairs, $atts ) : $pairs;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value, ...$args ) {
		return $value;
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		return 1 === (int) $number ? (string) $single : (string) $plural;
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return '';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return '';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = '' ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		if ( 'timestamp' === $type ) {
			return time();
		}

		return '';
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		$ts = null === $timestamp ? time() : (int) $timestamp;

		return gmdate( (string) $format, $ts );
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		return new \DateTimeZone( 'UTC' );
	}
}

if ( ! function_exists( 'wp_style_is' ) ) {
	function wp_style_is( $handle, $list = 'enqueued' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {}
}

if ( ! function_exists( 'wp_script_is' ) ) {
	function wp_script_is( $handle, $list = 'enqueued' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( $handle, $object_name, $l10n ) {
		return true;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return '';
	}
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() {
		return false;
	}
}

if ( ! function_exists( 'wp_login_url' ) ) {
	function wp_login_url( $redirect = '', $force_reauth = false ) {
		return '';
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = null, $leavename = false ) {
		return '';
	}
}

if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '', $scheme = null ) {
		return (string) $path;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		return (string) $path;
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	function wp_unique_id( $prefix = '' ) {
		static $counter = 0;
		$counter++;

		return (string) $prefix . $counter;
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) {
		return new WP_Error( 'stubbed', 'Stubbed response.' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return 200;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'rest_do_request' ) ) {
	function rest_do_request( $request ) {
		return new WP_REST_Response();
	}
}

if ( ! function_exists( 'rest_get_server' ) ) {
	function rest_get_server() {
		return new WP_REST_Server();
	}
}

if ( ! function_exists( 'shortcode_exists' ) ) {
	function shortcode_exists( $tag ) {
		return false;
	}
}

if ( ! function_exists( 'do_shortcode' ) ) {
	function do_shortcode( $content, $ignore_html = false ) {
		return (string) $content;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text, $remove_breaks = false ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		return '';
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() {
		return 0;
	}
}

if ( ! function_exists( 'get_the_date' ) ) {
	function get_the_date( $format = '', $post = null ) {
		return '';
	}
}

if ( ! function_exists( 'wp_reset_postdata' ) ) {
	function wp_reset_postdata() {}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return new WP_User();
	}
}

if ( ! function_exists( 'tribe_get_events' ) ) {
	function tribe_get_events( $args = array(), $full = false ) {
		return array();
	}
}

if ( ! function_exists( 'tribe_get_start_date' ) ) {
	function tribe_get_start_date( $post = null, $display_time = true, $date_format = '' ) {
		return '';
	}
}

if ( ! function_exists( 'wc_get_account_endpoint_url' ) ) {
	function wc_get_account_endpoint_url( $endpoint ) {
		return '';
	}
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	function wc_get_page_permalink( $page ) {
		return '';
	}
}

if ( ! function_exists( 'pmpro_url' ) ) {
	function pmpro_url( $page = '' ) {
		return '';
	}
}

if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
	function pmpro_getMembershipLevelForUser( $user_id = null, $force = false ) {
		return null;
	}
}

if ( ! function_exists( 'pmpro_getMemberOrders' ) ) {
	function pmpro_getMemberOrders( $user_id = null, $limit = null, $args = array() ) {
		return array();
	}
}