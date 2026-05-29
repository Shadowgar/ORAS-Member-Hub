<?php
/**
 * Deterministic checks for Equipment Exchange module.
 *
 * Run via WP-CLI eval-file inside wp-env:
 * npx @wordpress/env run cli wp eval-file scripts/equipment-exchange-checks.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This script must run inside WordPress (WP-CLI eval-file).\n";
	exit( 1 );
}

$errors = 0;

$assert = static function ( $condition, $message ) use ( &$errors ) {
	if ( $condition ) {
		echo "PASS: {$message}\n";
		return;
	}
	$errors++;
	echo "FAIL: {$message}\n";
};

$assert( post_type_exists( 'oras_equip_listing' ), 'CPT oras_equip_listing is registered' );
$assert( taxonomy_exists( 'oras_equipment_category' ), 'Taxonomy oras_equipment_category is registered' );
$assert( taxonomy_exists( 'oras_equipment_condition' ), 'Taxonomy oras_equipment_condition is registered' );

$category_terms = array(
	'Telescopes',
	'Mounts / Tripods',
	'Eyepieces',
	'Barlows / Reducers',
	'Filters',
	'Cameras / Imaging',
	'Focusers / Adapters',
	'Finder Scopes / Guiders',
	'Power Supplies / Cables',
	'Cases / Storage',
	'Observatory Equipment',
	'Books / Star Charts',
	'Camping / Star Party Gear',
	'Parts / Repair',
	'Wanted',
	'Other',
);

foreach ( $category_terms as $term_name ) {
	$assert( false !== term_exists( $term_name, 'oras_equipment_category' ), "Default category exists: {$term_name}" );
}

$condition_terms = array(
	'New',
	'Like New',
	'Good',
	'Fair',
	'For Parts / Repair',
	'Not Applicable',
);

foreach ( $condition_terms as $term_name ) {
	$assert( false !== term_exists( $term_name, 'oras_equipment_condition' ), "Default condition exists: {$term_name}" );
}

$settings = get_option( 'oras_mh_equipment_exchange_settings', array() );
$defaults = ORAS_MH_Equipment_Settings::defaults();
$merged   = wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );

$assert( 8 === (int) $merged['max_photos'], 'Default max_photos is 8' );
$assert( 5 === (int) $merged['max_upload_mb'], 'Default max_upload_mb is 5' );
$assert( 90 === (int) $merged['expiration_days'], 'Default expiration_days is 90' );
$assert( ! empty( $merged['single_listing_page_url'] ), 'Single listing URL setting exists' );

$labels_wanted = ORAS_MH_Equipment_Fields::public_status_labels( 'wanted' );
$assert( isset( $labels_wanted['available'] ) && 'Still Looking' === $labels_wanted['available'], 'Wanted status label maps available to Still Looking' );

$labels_trade = ORAS_MH_Equipment_Fields::public_status_labels( 'trade' );
$assert( isset( $labels_trade['traded'] ), 'Trade status includes traded' );

$labels_free = ORAS_MH_Equipment_Fields::public_status_labels( 'free' );
$assert( isset( $labels_free['claimed'] ), 'Free status includes claimed' );

$assert( shortcode_exists( 'oras_equipment_exchange_preview' ), 'Preview shortcode is registered' );
$assert( shortcode_exists( 'oras_equipment_exchange_grid' ), 'Grid shortcode is registered' );
$assert( shortcode_exists( 'oras_equipment_exchange_submit' ), 'Submit shortcode is registered' );
$assert( shortcode_exists( 'oras_equipment_exchange_my_listings' ), 'My listings shortcode is registered' );
$assert( shortcode_exists( 'oras_equipment_exchange_single' ), 'Single shortcode is registered' );
$assert( shortcode_exists( 'oras_equipment_exchange_contact' ), 'Contact shortcode is registered' );

if ( $errors > 0 ) {
	echo "\nFAILED: {$errors} check(s) failed.\n";
	exit( 1 );
}

echo "\nSUCCESS: all equipment exchange checks passed.\n";
exit( 0 );
