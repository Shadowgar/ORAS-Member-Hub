<?php
/**
 * My listings template.
 *
 * @var array<int,WP_Post> $posts
 * @var string $notice_html
 * @var array<int,WP_Term> $categories
 * @var array<int,WP_Term> $conditions
 */
?>
<section class="oras-equipment-my-listings">
	<h2><?php esc_html_e( 'My Listings', 'oras-member-hub' ); ?></h2>
	<?php echo wp_kses_post( $notice_html ); ?>
	<?php if ( empty( $posts ) ) : ?>
		<p><?php esc_html_e( 'You have no listings yet.', 'oras-member-hub' ); ?></p>
	<?php endif; ?>

	<div class="oras-equipment-my-listings__stack">
		<?php foreach ( $posts as $listing ) : ?>
			<?php
			$listing_type          = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
			$listing_type_label    = ORAS_MH_Equipment_Fields::listing_types()[ $listing_type ] ?? $listing_type;
			$listing_status_label  = ORAS_MH_Equipment_Fields::get_public_status_label( $listing->ID );
			$listing_category_ids  = wp_get_object_terms( $listing->ID, ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY, array( 'fields' => 'ids' ) );
			$listing_condition_ids = wp_get_object_terms( $listing->ID, ORAS_MH_Equipment_Taxonomies::TAX_CONDITION, array( 'fields' => 'ids' ) );
			$thumb_url             = get_the_post_thumbnail_url( $listing->ID, 'medium' );
			if ( ! $thumb_url ) {
				$thumb_url = ORAS_MH_Equipment_Fields::placeholder_image_url();
			}
			?>
			<article class="oras-equipment-my-listing-card">
				<div class="oras-equipment-my-listing-card__media">
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( get_the_title( $listing ) ); ?>" />
				</div>
				<div class="oras-equipment-my-listing-card__body">
					<header class="oras-equipment-my-listing-card__header">
						<h3><?php echo esc_html( get_the_title( $listing ) ); ?></h3>
						<div class="oras-equipment-my-listing-card__badges">
							<span class="oras-equipment-badge oras-equipment-badge--type"><?php echo esc_html( $listing_type_label ); ?></span>
							<span class="oras-equipment-badge oras-equipment-badge--status"><?php echo esc_html( $listing_status_label ); ?></span>
						</div>
						<p class="oras-equipment-my-listing-card__meta">
							<?php esc_html_e( 'Posted:', 'oras-member-hub' ); ?> <?php echo esc_html( get_the_date( '', $listing ) ); ?>
							<span aria-hidden="true">|</span>
							<?php esc_html_e( 'Expires:', 'oras-member-hub' ); ?> <?php echo esc_html( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, true ) ); ?>
						</p>
					</header>

					<div class="oras-equipment-my-listing-card__actions">
						<form method="post" class="oras-equipment-inline-form">
							<?php wp_nonce_field( 'oras_equipment_status', 'oras_equipment_nonce' ); ?>
							<input type="hidden" name="oras_equipment_action" value="update_status" />
							<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
							<div class="oras-equipment-status-update-row">
								<label>
									<span class="screen-reader-text"><?php esc_html_e( 'Update status', 'oras-member-hub' ); ?></span>
									<select name="public_status">
										<?php foreach ( ORAS_MH_Equipment_Fields::public_status_labels( $listing_type ) as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<button class="button" type="submit"><?php esc_html_e( 'Update Status', 'oras-member-hub' ); ?></button>
							</div>
						</form>

						<div class="oras-equipment-my-listing-card__quick-actions">
							<form method="post" class="oras-equipment-inline-form">
								<?php wp_nonce_field( 'oras_equipment_renew', 'oras_equipment_nonce' ); ?>
								<input type="hidden" name="oras_equipment_action" value="renew_listing" />
								<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
								<button class="button" type="submit"><?php esc_html_e( 'Renew', 'oras-member-hub' ); ?></button>
							</form>
							<form method="post" class="oras-equipment-inline-form" onsubmit="return confirm('Delete this listing?');">
								<?php wp_nonce_field( 'oras_equipment_delete', 'oras_equipment_nonce' ); ?>
								<input type="hidden" name="oras_equipment_action" value="delete_listing" />
								<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
								<button class="button oras-equipment-button--danger" type="submit"><?php esc_html_e( 'Delete', 'oras-member-hub' ); ?></button>
							</form>
						</div>
					</div>

					<details class="oras-equipment-my-listing-card__edit">
						<summary><?php esc_html_e( 'Edit Listing', 'oras-member-hub' ); ?></summary>
						<form method="post" enctype="multipart/form-data" class="oras-equipment-edit-form">
							<?php wp_nonce_field( 'oras_equipment_edit', 'oras_equipment_nonce' ); ?>
							<input type="hidden" name="oras_equipment_action" value="edit_listing" />
							<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
							<div class="oras-equipment-edit-form__grid">
								<p><label><?php esc_html_e( 'Item title *', 'oras-member-hub' ); ?><br /><input type="text" name="listing_title" value="<?php echo esc_attr( (string) $listing->post_title ); ?>" required /></label></p>
								<p><label><?php esc_html_e( 'Listing type *', 'oras-member-hub' ); ?><br /><select name="listing_type" required>
										<?php foreach ( ORAS_MH_Equipment_Fields::listing_types() as $my_type_key => $my_type_label ) : ?>
											<option value="<?php echo esc_attr( $my_type_key ); ?>" <?php selected( $listing_type, $my_type_key ); ?>><?php echo esc_html( $my_type_label ); ?></option>
										<?php endforeach; ?>
									</select></label></p>
								<p><label><?php esc_html_e( 'Category *', 'oras-member-hub' ); ?><br /><select name="equipment_category" required><option value=""><?php esc_html_e( 'Select category', 'oras-member-hub' ); ?></option>
										<?php foreach ( $categories as $my_category ) : ?>
											<option value="<?php echo esc_attr( (string) $my_category->term_id ); ?>" <?php selected( ! empty( $listing_category_ids ) && ! is_wp_error( $listing_category_ids ) ? (int) $listing_category_ids[0] : 0, (int) $my_category->term_id ); ?>><?php echo esc_html( $my_category->name ); ?></option>
										<?php endforeach; ?>
									</select></label></p>
								<p><label><?php esc_html_e( 'Condition', 'oras-member-hub' ); ?><br /><select name="equipment_condition"><option value=""><?php esc_html_e( 'Select condition', 'oras-member-hub' ); ?></option>
										<?php foreach ( $conditions as $my_condition ) : ?>
											<option value="<?php echo esc_attr( (string) $my_condition->term_id ); ?>" <?php selected( ! empty( $listing_condition_ids ) && ! is_wp_error( $listing_condition_ids ) ? (int) $listing_condition_ids[0] : 0, (int) $my_condition->term_id ); ?>><?php echo esc_html( $my_condition->name ); ?></option>
										<?php endforeach; ?>
									</select></label></p>
								<p><label><?php esc_html_e( 'Price type', 'oras-member-hub' ); ?><br /><select name="price_type"><option value="fixed" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, true ), 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'oras-member-hub' ); ?></option><option value="obo" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, true ), 'obo' ); ?>><?php esc_html_e( 'OBO', 'oras-member-hub' ); ?></option><option value="free" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, true ), 'free' ); ?>><?php esc_html_e( 'Free', 'oras-member-hub' ); ?></option><option value="trade" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, true ), 'trade' ); ?>><?php esc_html_e( 'Trade', 'oras-member-hub' ); ?></option><option value="wanted" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_TYPE, true ), 'wanted' ); ?>><?php esc_html_e( 'Wanted', 'oras-member-hub' ); ?></option></select></label></p>
								<p><label><?php esc_html_e( 'Price amount / budget', 'oras-member-hub' ); ?><br /><input type="text" name="price_amount" value="<?php echo esc_attr( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PRICE_AMOUNT, true ) ); ?>" /></label></p>
								<p class="oras-equipment-edit-form__full"><label><?php esc_html_e( 'Description *', 'oras-member-hub' ); ?><br /><textarea name="listing_description" rows="5" required><?php echo esc_textarea( (string) $listing->post_content ); ?></textarea></label></p>
								<p><label><?php esc_html_e( 'Included items', 'oras-member-hub' ); ?><br /><textarea name="included_items" rows="3"><?php echo esc_textarea( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_INCLUDED_ITEMS, true ) ); ?></textarea></label></p>
								<p><label><?php esc_html_e( 'Known issues', 'oras-member-hub' ); ?><br /><textarea name="known_issues" rows="3"><?php echo esc_textarea( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_KNOWN_ISSUES, true ) ); ?></textarea></label></p>
								<p><label><?php esc_html_e( 'Trade details', 'oras-member-hub' ); ?><br /><textarea name="trade_details" rows="3"><?php echo esc_textarea( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_TRADE_DETAILS, true ) ); ?></textarea></label></p>
								<p><label><?php esc_html_e( 'Pickup/general area *', 'oras-member-hub' ); ?><br /><input type="text" name="pickup_area" value="<?php echo esc_attr( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PICKUP_AREA, true ) ); ?>" required /></label></p>
								<p><label><input type="checkbox" name="shipping_available" value="1" <?php checked( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_SHIPPING_AVAILABLE, true ), 'yes' ); ?> /> <?php esc_html_e( 'Shipping available', 'oras-member-hub' ); ?></label></p>
								<p><label><?php esc_html_e( 'Add/replace photos', 'oras-member-hub' ); ?><br /><input type="file" name="listing_photos[]" accept="image/jpeg,image/png,image/webp" multiple /></label></p>
								<p><label><?php esc_html_e( 'Contact preference', 'oras-member-hub' ); ?><br /><select name="contact_preference"><option value="contact_form_only" <?php selected( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_CONTACT_PREFERENCE, true ), 'contact_form_only' ); ?>><?php esc_html_e( 'Contact form only', 'oras-member-hub' ); ?></option></select></label></p>
							</div>
							<?php echo wp_kses_post( ORAS_MH_Equipment_Shortcodes::turnstile_widget_html() ); ?>
							<p><button class="button" type="submit"><?php esc_html_e( 'Save Changes', 'oras-member-hub' ); ?></button></p>
						</form>
					</details>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
