<?php
// phpcs:ignoreFile WordPress.Files.FileName.InvalidClassFileName
/**
 * Equipment Exchange taxonomies.
 *
 * @package ORAS_Member_Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy class.
 */
final class ORAS_MH_Equipment_Taxonomies {
	const TAX_CATEGORY  = 'oras_equipment_category';
	const TAX_CONDITION = 'oras_equipment_condition';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'init', array( __CLASS__, 'seed_terms' ), 20 );
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public static function register_taxonomies() {
		register_taxonomy(
			self::TAX_CATEGORY,
			ORAS_MH_Equipment_Post_Type::POST_TYPE,
			array(
				'label'        => __( 'Equipment Categories', 'oras-member-hub' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => false,
			)
		);

		register_taxonomy(
			self::TAX_CONDITION,
			ORAS_MH_Equipment_Post_Type::POST_TYPE,
			array(
				'label'        => __( 'Equipment Conditions', 'oras-member-hub' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => false,
			)
		);
	}

	/**
	 * Seed default terms.
	 *
	 * @return void
	 */
	public static function seed_terms() {
		$categories = array(
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

		$conditions = array(
			'New',
			'Like New',
			'Good',
			'Fair',
			'For Parts / Repair',
			'Not Applicable',
		);

		foreach ( $categories as $term_name ) {
			if ( ! term_exists( $term_name, self::TAX_CATEGORY ) ) {
				wp_insert_term( $term_name, self::TAX_CATEGORY );
			}
		}

		foreach ( $conditions as $term_name ) {
			if ( ! term_exists( $term_name, self::TAX_CONDITION ) ) {
				wp_insert_term( $term_name, self::TAX_CONDITION );
			}
		}
	}
}
