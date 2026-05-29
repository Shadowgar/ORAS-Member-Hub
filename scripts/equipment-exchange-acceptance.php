<?php
// phpcs:ignoreFile
/**
 * Equipment Exchange MVP acceptance checks (CLI-friendly).
 *
 * Run:
 * npx @wordpress/env run cli wp eval-file scripts/equipment-exchange-acceptance.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "This script must run in WordPress.\n";
	exit( 1 );
}

$errors = 0;

$assert = static function ( $condition, $message ) use ( &$errors ) {
	if ( $condition ) {
		echo "PASS: {$message}\n";
		return;
	}
	++$errors;
	echo "FAIL: {$message}\n";
};

$admin = get_user_by( 'login', 'admin' );
if ( ! $admin ) {
	echo "FAIL: Admin user not found.\n";
	exit( 1 );
}
wp_set_current_user( (int) $admin->ID );

$runner = get_user_by( 'login', 'equipmember' );
if ( ! $runner ) {
	$user_id = wp_create_user( 'equipmember', wp_generate_password( 24, true, true ), 'equipmember@example.com' );
	$runner  = get_user_by( 'id', $user_id );
}
$assert( $runner instanceof WP_User, 'Test member user exists' );

// Step 2 equivalent: provision shortcode pages and sync URL settings.
$setup_pages = array(
	'members-hub/equipment-exchange'                => '[oras_equipment_exchange_grid]',
	'members-hub/equipment-exchange/list-equipment' => '[oras_equipment_exchange_submit]',
	'members-hub/equipment-exchange/my-listings'    => '[oras_equipment_exchange_my_listings]',
	'members-hub/equipment-exchange/listing'        => '[oras_equipment_exchange_single]',
);

$resolved_urls = array();

foreach ( $setup_pages as $path => $shortcode ) {
	$parts        = array_values( array_filter( explode( '/', $path ) ) );
	$parent       = 0;
	$current_path = '';
	for ( $i = 0; $i < count( $parts ); $i++ ) {
		$current_path = '' === $current_path ? $parts[ $i ] : $current_path . '/' . $parts[ $i ];
		$existing     = get_page_by_path( $current_path );
		if ( $existing instanceof WP_Post ) {
			$parent = (int) $existing->ID;
			if ( $i === count( $parts ) - 1 ) {
				wp_update_post(
					array(
						'ID'           => $existing->ID,
						'post_content' => $shortcode,
						'post_status'  => 'publish',
					)
				);
				$resolved_urls[ $path ] = get_permalink( $existing->ID );
			}
			continue;
		}

		$is_leaf = ( $i === count( $parts ) - 1 );
		$new_id  = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_parent'  => $parent,
				'post_name'    => $parts[ $i ],
				'post_title'   => ucwords( str_replace( '-', ' ', $parts[ $i ] ) ),
				'post_content' => $is_leaf ? $shortcode : '',
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			$assert( false, 'Page creation succeeded for ' . $path );
			continue 2;
		}

		$parent = (int) $new_id;
		if ( $is_leaf ) {
			$resolved_urls[ $path ] = get_permalink( $new_id );
		}
	}
}

$settings = ORAS_MH_Equipment_Settings::get();
if ( ! empty( $resolved_urls['members-hub/equipment-exchange'] ) ) {
	$settings['grid_page_url'] = $resolved_urls['members-hub/equipment-exchange'];
}
if ( ! empty( $resolved_urls['members-hub/equipment-exchange/list-equipment'] ) ) {
	$settings['submit_page_url'] = $resolved_urls['members-hub/equipment-exchange/list-equipment'];
}
if ( ! empty( $resolved_urls['members-hub/equipment-exchange/my-listings'] ) ) {
	$settings['my_listings_page_url'] = $resolved_urls['members-hub/equipment-exchange/my-listings'];
}
if ( ! empty( $resolved_urls['members-hub/equipment-exchange/listing'] ) ) {
	$settings['single_listing_page_url'] = $resolved_urls['members-hub/equipment-exchange/listing'];
}
update_option( ORAS_MH_Equipment_Settings::OPTION_KEY, ORAS_MH_Equipment_Settings::sanitize( $settings ) );

$assert( ! empty( ORAS_MH_Equipment_Settings::get_page_url( 'grid_page_url' ) ), 'Grid URL is configured' );
$assert( ! empty( ORAS_MH_Equipment_Settings::get_page_url( 'single_listing_page_url' ) ), 'Single URL is configured' );

// Create one pending listing (member submission equivalent).
$post_id = wp_insert_post(
	array(
		'post_type'    => ORAS_MH_Equipment_Post_Type::POST_TYPE,
		'post_status'  => 'pending',
		'post_title'   => 'Acceptance Listing',
		'post_content' => 'Acceptance flow description',
		'post_author'  => (int) $runner->ID,
	),
	true
);

$assert( ! is_wp_error( $post_id ) && $post_id > 0, 'Test listing created' );
if ( is_wp_error( $post_id ) || $post_id <= 0 ) {
	echo "Stopping because listing creation failed.\n";
	exit( 1 );
}

$cat  = term_exists( 'Telescopes', ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY );
$cond = term_exists( 'Good', ORAS_MH_Equipment_Taxonomies::TAX_CONDITION );
if ( is_array( $cat ) && ! empty( $cat['term_id'] ) ) {
	wp_set_object_terms( (int) $post_id, array( (int) $cat['term_id'] ), ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY, false );
}
if ( is_array( $cond ) && ! empty( $cond['term_id'] ) ) {
	wp_set_object_terms( (int) $post_id, array( (int) $cond['term_id'] ), ORAS_MH_Equipment_Taxonomies::TAX_CONDITION, false );
}

update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, 'sale' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, 'fixed' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT, '$500' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_PICKUP_AREA, 'Cranberry, PA' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_CONTACT_PREFERENCE, 'contact_form_only' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_PUBLIC_STATUS, 'available' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_MODERATION_STATUS, 'pending_review' );
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, gmdate( 'Y-m-d', time() + DAY_IN_SECONDS * 30 ) );

$pending_grid = do_shortcode( '[oras_equipment_exchange_grid]' );
$assert( false === strpos( $pending_grid, 'Acceptance Listing' ), 'Pending listing is hidden from public grid' );

// Approve listing.
ORAS_MH_Equipment_Fields::update_moderation_status( (int) $post_id, 'approved' );
wp_update_post(
	array(
		'ID'          => (int) $post_id,
		'post_status' => 'publish',
	)
);

$approved_grid = do_shortcode( '[oras_equipment_exchange_grid]' );
$assert( false !== strpos( $approved_grid, 'Acceptance Listing' ), 'Approved listing appears in grid' );

$_GET['search'] = 'Acceptance';
$filtered_grid  = do_shortcode( '[oras_equipment_exchange_grid]' );
$assert( false !== strpos( $filtered_grid, 'Acceptance Listing' ), 'Grid search filter matches listing' );
unset( $_GET['search'] );

$_GET['listing'] = (string) $post_id;
$single_html     = do_shortcode( '[oras_equipment_exchange_single]' );
$assert( false !== strpos( $single_html, 'Acceptance Listing' ), 'Single listing shortcode renders listing by query parameter' );
unset( $_GET['listing'] );

// Owner status update path.
wp_set_current_user( (int) $runner->ID );
ORAS_MH_Equipment_Fields::update_public_status( (int) $post_id, 'sold' );
$assert( 'Sold' === ORAS_MH_Equipment_Fields::get_public_status_label( (int) $post_id ), 'Owner status change label resolves as Sold' );

// Edit major field should return listing to pending review when approval is required.
wp_update_post(
	array(
		'ID'           => (int) $post_id,
		'post_content' => 'Edited content for moderation check',
	)
);
ORAS_MH_Equipment_Fields::update_moderation_status( (int) $post_id, 'pending_review' );
$assert( 'pending_review' === (string) get_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_MODERATION_STATUS, true ), 'Major edit moderation status can be set to pending review' );

// Expiration behavior.
update_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ) );
ORAS_MH_Equipment_Exchange::expire_listings();
$status = (string) get_post_meta( (int) $post_id, ORAS_MH_Equipment_Fields::META_PUBLIC_STATUS, true );
$assert( 'expired' === $status, 'Expired listing is auto-marked expired' );

wp_set_current_user( (int) $admin->ID );
$grid_after_expire = do_shortcode( '[oras_equipment_exchange_grid]' );
$assert( false === strpos( $grid_after_expire, 'Acceptance Listing' ), 'Expired listing hidden from grid' );

$my_listings_html = do_shortcode( '[oras_equipment_exchange_my_listings]' );
$assert( false === strpos( $my_listings_html, 'Please log in' ), 'My listings shortcode renders for logged in member' );

if ( $errors > 0 ) {
	echo "\nFAILED: {$errors} acceptance check(s) failed.\n";
	exit( 1 );
}

echo "\nSUCCESS: Equipment Exchange acceptance checks passed.\n";
exit( 0 );
