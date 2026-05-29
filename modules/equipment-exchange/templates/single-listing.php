<?php
/**
 * Single listing template.
 *
 * @var WP_Post $listing
 * @var string $disclaimer
 */
$gallery      = array_map( 'intval', (array) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_GALLERY_IMAGE_IDS, true ) );
$listing_type = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
$pickup       = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PICKUP_AREA, true );
?>
<article class="oras-equipment-single">
	<h2><?php echo esc_html( get_the_title( $listing ) ); ?></h2>
	<p><?php echo esc_html( ORAS_MH_Equipment_Fields::listing_types()[ $listing_type ] ?? $listing_type ); ?> - <?php echo esc_html( ORAS_MH_Equipment_Shortcodes::format_price( $listing->ID ) ); ?></p>
	<p><?php echo esc_html( ORAS_MH_Equipment_Fields::get_public_status_label( $listing->ID ) ); ?> | <?php echo esc_html( $pickup ); ?></p>
	<div class="oras-equipment-gallery">
		<?php foreach ( $gallery as $attachment_id ) : ?>
			<?php $url = wp_get_attachment_image_url( $attachment_id, 'large' ); ?>
			<?php
			if ( $url ) :
				?>
				<img src="<?php echo esc_url( $url ); ?>" alt="" /><?php endif; ?>
		<?php endforeach; ?>
	</div>
	<div class="oras-equipment-description"><?php echo nl2br( esc_html( (string) $listing->post_content ) ); ?></div>
	<div class="oras-equipment-contact-block">
		<h3><?php esc_html_e( 'Contact Seller', 'oras-member-hub' ); ?></h3>
		<?php echo do_shortcode( '[oras_equipment_exchange_contact]' ); ?>
	</div>
	<p class="oras-equipment-disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
</article>
