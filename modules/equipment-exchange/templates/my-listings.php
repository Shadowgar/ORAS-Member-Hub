<?php
/**
 * My listings template.
 *
 * @var array<int,WP_Post> $posts
 * @var string $notice_html
 */
?>
<section class="oras-equipment-my-listings">
	<h2><?php esc_html_e( 'My Listings', 'oras-member-hub' ); ?></h2>
	<?php echo wp_kses_post( $notice_html ); ?>
	<?php if ( empty( $posts ) ) : ?>
		<p><?php esc_html_e( 'You have no listings yet.', 'oras-member-hub' ); ?></p>
	<?php endif; ?>
	<?php foreach ( $posts as $listing ) : ?>
		<?php $listing_type = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true ); ?>
		<article class="oras-equipment-my-listing">
			<h3><?php echo esc_html( get_the_title( $listing ) ); ?></h3>
			<p><?php echo esc_html( ORAS_MH_Equipment_Fields::listing_types()[ $listing_type ] ?? $listing_type ); ?> | <?php echo esc_html( ORAS_MH_Equipment_Fields::get_public_status_label( $listing->ID ) ); ?></p>
			<p><?php esc_html_e( 'Posted:', 'oras-member-hub' ); ?> <?php echo esc_html( get_the_date( '', $listing ) ); ?> | <?php esc_html_e( 'Expires:', 'oras-member-hub' ); ?> <?php echo esc_html( (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_EXPIRATION_DATE, true ) ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'oras_equipment_status', 'oras_equipment_nonce' ); ?>
				<input type="hidden" name="oras_equipment_action" value="update_status" />
				<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
				<select name="public_status">
					<?php foreach ( ORAS_MH_Equipment_Fields::public_status_labels( $listing_type ) as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button" type="submit"><?php esc_html_e( 'Update Status', 'oras-member-hub' ); ?></button>
			</form>
			<form method="post">
				<?php wp_nonce_field( 'oras_equipment_renew', 'oras_equipment_nonce' ); ?>
				<input type="hidden" name="oras_equipment_action" value="renew_listing" />
				<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
				<button class="button" type="submit"><?php esc_html_e( 'Renew', 'oras-member-hub' ); ?></button>
			</form>
			<form method="post" onsubmit="return confirm('Delete this listing?');">
				<?php wp_nonce_field( 'oras_equipment_delete', 'oras_equipment_nonce' ); ?>
				<input type="hidden" name="oras_equipment_action" value="delete_listing" />
				<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
				<button class="button" type="submit"><?php esc_html_e( 'Delete', 'oras-member-hub' ); ?></button>
			</form>
		</article>
	<?php endforeach; ?>
</section>
