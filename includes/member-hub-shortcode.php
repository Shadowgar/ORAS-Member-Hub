<?php
/**
 * Shortcode registration and hub layout rendering.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'oras_member_hub_register_shortcode' ) ) {
	/**
	 * Register the Member Hub shortcode.
	 *
	 * @return void
	 */
	function oras_member_hub_register_shortcode() {
		add_shortcode( 'oras_member_hub', 'oras_member_hub_render_shortcode' );
	}
}

if ( ! function_exists( 'oras_member_hub_render_shortcode' ) ) {
	/**
	 * Render shortcode output.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	function oras_member_hub_render_shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(),
			$atts,
			'oras_member_hub'
		);

		return oras_member_hub_render_hub( $atts );
	}
}

if ( ! function_exists( 'oras_member_hub_enqueue_assets' ) ) {
	/**
	 * Enqueue hub frontend assets.
	 *
	 * @return void
	 */
	function oras_member_hub_enqueue_assets() {
		$style_handle = 'oras-member-hub';
		$script_handle = 'oras-member-hub';

		if ( ! wp_style_is( $style_handle, 'enqueued' ) ) {
			wp_enqueue_style(
				$style_handle,
				ORAS_MEMBER_HUB_URL . 'assets/member-hub.css',
				array(),
				ORAS_MEMBER_HUB_VERSION
			);
		}

		if ( file_exists( ORAS_MEMBER_HUB_PATH . 'assets/member-hub.js' ) && ! wp_script_is( $script_handle, 'enqueued' ) ) {
			wp_enqueue_script(
				$script_handle,
				ORAS_MEMBER_HUB_URL . 'assets/member-hub.js',
				array(),
				ORAS_MEMBER_HUB_VERSION,
				true
			);
		}
	}
}

if ( ! function_exists( 'oras_member_hub_render_hub' ) ) {
	/**
	 * Render the full hub page structure.
	 *
	 * @param array<string, mixed> $atts Render attributes.
	 * @return string
	 */
	function oras_member_hub_render_hub( array $atts = array() ) {
		oras_member_hub_enqueue_assets();

		$renew_url = function_exists( 'pmpro_url' ) ? (string) pmpro_url( 'account' ) : (string) home_url( '/membership-account/' );

		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );

			return sprintf(
				'<section class="oras-hub oras-member-hub oras-member-hub--guest"><header class="oras-hub__header"><h1>%1$s</h1><p>%2$s</p></header><p>%3$s <a href="%4$s">%5$s</a>.</p></section>',
				esc_html__( 'Members Hub', 'oras-member-hub' ),
				esc_html__( 'Mission Briefing', 'oras-member-hub' ),
				esc_html__( 'Please sign in to access your member dashboard.', 'oras-member-hub' ),
				esc_url( $login_url ),
				esc_html__( 'Log in', 'oras-member-hub' )
			);
		}

		$main_modules = apply_filters(
			'oras_member_hub_main_modules',
			array(
				'conditions' => 'oras_member_hub_module_conditions_summary',
				'events'     => 'oras_mh_module_upcoming_events',
				'tickets'    => 'oras_member_hub_module_my_tickets_reminders',
				'resources'  => 'oras_member_hub_module_resources',
				'community'  => 'oras_member_hub_module_community_updates',
			)
		);

		$sidebar_modules = apply_filters(
			'oras_member_hub_sidebar_modules',
			array(
				'membership' => 'oras_member_hub_sidebar_module_membership_status',
				'account'    => 'oras_member_hub_sidebar_module_account_links',
				'orders'     => 'oras_member_hub_sidebar_module_order_history',
				'profile'    => 'oras_member_hub_sidebar_module_profile_settings',
			)
		);

		ob_start();
		?>
		<section class="oras-hub oras-member-hub" aria-label="<?php echo esc_attr__( 'ORAS Member Hub', 'oras-member-hub' ); ?>">
			<header class="oras-hub__header">
				<h1><?php echo esc_html__( 'Members Hub', 'oras-member-hub' ); ?></h1>
				<p><?php echo esc_html__( 'Mission Briefing', 'oras-member-hub' ); ?></p>
			</header>
			<nav class="oras-hub__actions" aria-label="<?php echo esc_attr__( 'Quick actions', 'oras-member-hub' ); ?>">
				<a href="#oras-hub-module-my-tickets-reminders"><?php echo esc_html__( 'Tickets', 'oras-member-hub' ); ?></a>
				<a href="#oras-hub-module-upcoming-events"><?php echo esc_html__( 'Events', 'oras-member-hub' ); ?></a>
				<a href="<?php echo esc_url( $renew_url ); ?>"><?php echo esc_html__( 'Renew', 'oras-member-hub' ); ?></a>
				<a href="#oras-hub-module-resources"><?php echo esc_html__( 'Toolkit', 'oras-member-hub' ); ?></a>
			</nav>
			<div class="oras-hub__grid oras-member-hub__layout">
				<main class="oras-hub__main oras-member-hub__main" role="main">
					<?php
					foreach ( $main_modules as $callback ) {
						if ( is_callable( $callback ) ) {
							echo wp_kses_post( (string) call_user_func( $callback ) );
						}
					}
					?>
				</main>
				<aside class="oras-hub__sidebar oras-member-hub__sidebar" role="complementary" aria-label="<?php echo esc_attr__( 'Member account tools', 'oras-member-hub' ); ?>">
					<div class="oras-member-hub__sidebar-inner">
						<?php
						foreach ( $sidebar_modules as $callback ) {
							if ( is_callable( $callback ) ) {
								echo wp_kses_post( (string) call_user_func( $callback ) );
							}
						}
						?>
					</div>
				</aside>
			</div>
		</section>
		<?php

		$output = ob_get_clean();

		return (string) apply_filters( 'oras_member_hub_render_output', $output, $atts, $main_modules, $sidebar_modules );
	}
}
