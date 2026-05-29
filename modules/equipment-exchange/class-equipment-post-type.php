<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange post type.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT class.
 */
final class ORAS_MH_Equipment_Post_Type {
	const POST_TYPE = 'oras_equip_listing';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	/**
	 * Register CPT.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Equipment Listings', 'oras-member-hub' ),
					'singular_name' => __( 'Equipment Listing', 'oras-member-hub' ),
					'add_new_item'  => __( 'Add New Listing', 'oras-member-hub' ),
					'edit_item'     => __( 'Edit Listing', 'oras-member-hub' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
}
