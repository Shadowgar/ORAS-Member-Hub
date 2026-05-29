<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange settings.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
final class ORAS_MH_Equipment_Settings {
	const OPTION_KEY = 'oras_mh_equipment_exchange_settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_options_page(
			__( 'Equipment Exchange', 'oras-member-hub' ),
			__( 'Equipment Exchange', 'oras-member-hub' ),
			'manage_options',
			'oras-mh-equipment-exchange',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'oras_mh_equipment_exchange',
			self::OPTION_KEY,
			array( __CLASS__, 'sanitize' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,mixed> $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		$input = (array) $input;
		return array(
			'enabled'                   => ! empty( $input['enabled'] ) ? 1 : 0,
			'require_approval'          => ! empty( $input['require_approval'] ) ? 1 : 0,
			'max_photos'                => max( 1, min( 20, (int) ( $input['max_photos'] ?? 8 ) ) ),
			'max_upload_mb'             => max( 1, min( 20, (int) ( $input['max_upload_mb'] ?? 5 ) ) ),
			'expiration_days'           => max( 7, min( 365, (int) ( $input['expiration_days'] ?? 90 ) ) ),
			'admin_notification_email'  => sanitize_email( (string) ( $input['admin_notification_email'] ?? '' ) ),
			'allowed_categories'        => sanitize_text_field( (string) ( $input['allowed_categories'] ?? '' ) ),
			'disclaimer_text'           => sanitize_textarea_field( (string) ( $input['disclaimer_text'] ?? '' ) ),
			'rules_text'                => sanitize_textarea_field( (string) ( $input['rules_text'] ?? '' ) ),
			'grid_page_url'             => esc_url_raw( (string) ( $input['grid_page_url'] ?? '' ) ),
			'submit_page_url'           => esc_url_raw( (string) ( $input['submit_page_url'] ?? '' ) ),
			'my_listings_page_url'      => esc_url_raw( (string) ( $input['my_listings_page_url'] ?? '' ) ),
			'single_listing_page_url'   => esc_url_raw( (string) ( $input['single_listing_page_url'] ?? '' ) ),
		);
	}

	/**
	 * Get settings defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'                  => 1,
			'require_approval'         => 1,
			'max_photos'               => 8,
			'max_upload_mb'            => 5,
			'expiration_days'          => 90,
			'admin_notification_email' => get_option( 'admin_email' ),
			'allowed_categories'       => '',
			'disclaimer_text'          => 'ORAS provides the Equipment Exchange as a member-to-member listing board only. ORAS does not process payments, guarantee item condition, arrange pickup, handle shipping, or participate in disputes. Buyers and sellers are responsible for all transaction details, payment, pickup, delivery, shipping, and item condition.',
			'rules_text'               => 'Only astronomy, observing, astrophotography, observatory, educational astronomy, or star-party related equipment may be listed. ORAS may remove listings that are unrelated, inappropriate, unsafe, misleading, or otherwise unsuitable for the Equipment Exchange.',
			'grid_page_url'            => home_url( '/members-hub/equipment-exchange/' ),
			'submit_page_url'          => home_url( '/members-hub/equipment-exchange/list-equipment/' ),
			'my_listings_page_url'     => home_url( '/members-hub/equipment-exchange/my-listings/' ),
			'single_listing_page_url'  => home_url( '/members-hub/equipment-exchange/listing/' ),
		);
	}

	/**
	 * Get full settings array.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	/**
	 * Is module enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = self::get();
		return ! empty( $settings['enabled'] );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$settings = self::get();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'ORAS Equipment Exchange Settings', 'oras-member-hub' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'oras_mh_equipment_exchange' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'Enable Equipment Exchange', 'oras-member-hub' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'oras-member-hub' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Require admin approval', 'oras-member-hub' ); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[require_approval]" value="1" <?php checked( ! empty( $settings['require_approval'] ) ); ?> /> <?php esc_html_e( 'Require approval before public listing', 'oras-member-hub' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Max photos per listing', 'oras-member-hub' ); ?></th><td><input type="number" min="1" max="20" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_photos]" value="<?php echo esc_attr( (string) $settings['max_photos'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Max upload size (MB)', 'oras-member-hub' ); ?></th><td><input type="number" min="1" max="20" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_upload_mb]" value="<?php echo esc_attr( (string) $settings['max_upload_mb'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Listing expiration days', 'oras-member-hub' ); ?></th><td><input type="number" min="7" max="365" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[expiration_days]" value="<?php echo esc_attr( (string) $settings['expiration_days'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Admin notification email', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="email" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[admin_notification_email]" value="<?php echo esc_attr( (string) $settings['admin_notification_email'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Allowed categories (comma-separated, optional)', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[allowed_categories]" value="<?php echo esc_attr( (string) $settings['allowed_categories'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Grid page URL', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[grid_page_url]" value="<?php echo esc_attr( (string) $settings['grid_page_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Submit page URL', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[submit_page_url]" value="<?php echo esc_attr( (string) $settings['submit_page_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'My Listings page URL', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[my_listings_page_url]" value="<?php echo esc_attr( (string) $settings['my_listings_page_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Single Listing page URL', 'oras-member-hub' ); ?></th><td><input class="regular-text" type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[single_listing_page_url]" value="<?php echo esc_attr( (string) $settings['single_listing_page_url'] ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Disclaimer text', 'oras-member-hub' ); ?></th><td><textarea class="large-text" rows="4" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[disclaimer_text]"><?php echo esc_textarea( (string) $settings['disclaimer_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Rules text', 'oras-member-hub' ); ?></th><td><textarea class="large-text" rows="4" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rules_text]"><?php echo esc_textarea( (string) $settings['rules_text'] ); ?></textarea></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get configured URL.
	 *
	 * @param string $key URL setting key.
	 * @return string
	 */
	public static function get_page_url( $key ) {
		$settings = self::get();
		$url      = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		$url      = apply_filters( 'oras_mh_equipment_exchange_' . $key, $url );
		return (string) $url;
	}
}
