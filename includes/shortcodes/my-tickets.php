<?php
/**
 * My Tickets shortcode and frontend asset bootstrap.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'oras_member_hub_register_my_tickets_shortcode' ) ) {
	/**
	 * Register the my tickets shortcode.
	 *
	 * @return void
	 */
	function oras_member_hub_register_my_tickets_shortcode() {
		add_shortcode( 'oras_my_tickets', 'oras_member_hub_render_my_tickets_shortcode' );
	}
}

if ( ! function_exists( 'oras_member_hub_my_tickets_enqueue_assets' ) ) {
	/**
	 * Enqueue frontend assets for the my tickets widget.
	 *
	 * @return void
	 */
	function oras_member_hub_my_tickets_enqueue_assets() {
		$script_handle = 'oras-member-hub-my-tickets';
		$rest_path     = (string) apply_filters( 'oras_member_hub_my_tickets_rest_path_base', '/oras-tickets/v1' );
		$print_path    = (string) apply_filters( 'oras_member_hub_my_tickets_print_path', '/oras-ticket/print' );
		$print_base    = site_url( '/' . ltrim( $print_path, '/' ) );
		$login_url     = wp_login_url( get_permalink() );

		wp_enqueue_script( 'wp-api-fetch' );

		if ( ! wp_script_is( $script_handle, 'enqueued' ) ) {
			wp_enqueue_script(
				$script_handle,
				ORAS_MEMBER_HUB_URL . 'assets/my-tickets.js',
				array( 'wp-api-fetch' ),
				ORAS_MEMBER_HUB_VERSION,
				true
			);
		}

		wp_localize_script(
			$script_handle,
			'orasMemberHubMyTickets',
			array(
				'restPathBase' => '/' . ltrim( $rest_path, '/' ),
				'printBase'    => $print_base,
				'isLoggedIn'   => is_user_logged_in(),
				'loginUrl'     => $login_url,
				'nonce'        => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

if ( ! function_exists( 'oras_member_hub_render_my_tickets_shortcode' ) ) {
	/**
	 * Render the My Tickets shortcode.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	function oras_member_hub_render_my_tickets_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(),
			$atts,
			'oras_my_tickets'
		);

		if ( ! is_user_logged_in() ) {
			return '<div class="oras-my-tickets oras-my-tickets--guest"><p>' . esc_html__( 'Please log in to view your tickets.', 'oras-member-hub' ) . '</p></div>';
		}

		oras_member_hub_my_tickets_enqueue_assets();

		$container_id = wp_unique_id( 'oras-my-tickets-' );

		return sprintf(
			'<div id="%1$s" class="oras-my-tickets" data-widget="oras-my-tickets" aria-live="polite"><p class="oras-my-tickets__loading">%2$s</p></div>',
			esc_attr( $container_id ),
			esc_html__( 'Loading your tickets...', 'oras-member-hub' )
		);
	}
}
