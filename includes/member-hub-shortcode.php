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

if ( ! function_exists( 'oras_member_hub_render_hub' ) ) {
	/**
	 * Render the full hub page structure.
	 *
	 * @param array<string, mixed> $atts Render attributes.
	 * @return string
	 */
	function oras_member_hub_render_hub( array $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );

			return sprintf(
				'<section class="oras-member-hub oras-member-hub--guest"><h2>%1$s</h2><p>%2$s <a href="%3$s">%4$s</a>.</p></section>',
				esc_html__( 'ORAS Member Hub', 'oras-member-hub' ),
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
		<section class="oras-member-hub" aria-label="<?php echo esc_attr__( 'ORAS Member Hub', 'oras-member-hub' ); ?>">
			<div class="oras-member-hub__layout">
				<main class="oras-member-hub__main" role="main">
					<?php
					foreach ( $main_modules as $callback ) {
						if ( is_callable( $callback ) ) {
							echo wp_kses_post( (string) call_user_func( $callback ) );
						}
					}
					?>
				</main>
				<aside class="oras-member-hub__sidebar" role="complementary" aria-label="<?php echo esc_attr__( 'Member account tools', 'oras-member-hub' ); ?>">
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
