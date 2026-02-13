<?php

if ( false ) {
	class WP_REST_Request {
		public function __construct( $method = 'GET', $route = '' ) {}
		public function set_param( $key, $value ) {}
	}

	class WP_REST_Response {
		public function get_status() {
			return 200;
		}

		public function get_data() {
			return array();
		}
	}

	class WP_REST_Server {
		public function get_routes() {
			return array();
		}
	}

	class WP_Query {
		public function __construct( $args = array() ) {}
		public function have_posts() {
			return false;
		}
		public function the_post() {}
	}

	class WP_User {
		public $ID = 0;
		public $display_name = '';
	}

	class Membership {}

	function apply_filters( $hook_name, $value, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null ) {
		return $value;
	}

	function _n( $single, $plural, $number, $domain = 'default' ) {
		return '';
	}

	function absint( $maybeint ) {
		return 0;
	}

	function get_current_user_id() {
		return 0;
	}

	function wp_reset_postdata() {}

	function get_post_meta( $post_id, $key = '', $single = false ) {
		return '';
	}

	function get_the_ID() {
		return 0;
	}

	function get_the_title( $post = 0 ) {
		return '';
	}

	function get_the_date( $format = '', $post = null ) {
		return '';
	}

	function rest_do_request( $request ) {
		return new WP_REST_Response();
	}

	function rest_get_server() {
		return new WP_REST_Server();
	}

	function shortcode_exists( $tag ) {
		return false;
	}

	function do_shortcode( $content, $ignore_html = false ) {
		return '';
	}

	function wp_strip_all_tags( $text, $remove_breaks = false ) {
		return '';
	}

	function wp_get_current_user() {
		return new WP_User();
	}
}
