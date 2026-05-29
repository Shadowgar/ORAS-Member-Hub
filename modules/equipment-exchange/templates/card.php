<?php
/**
 * Listing card template.
 *
 * @var WP_Post $listing
 */
$single_url   = add_query_arg( array( 'listing' => $listing->ID ), ORAS_MH_Equipment_Settings::get_page_url( 'single_listing_page_url' ) );
$img          = get_the_post_thumbnail_url( $listing->ID, 'medium' );
$listing_type = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_LISTING_TYPE, true );
$terms        = get_the_terms( $listing->ID, ORAS_MH_Equipment_Taxonomies::TAX_CATEGORY );
$category     = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';
$pickup       = (string) get_post_meta( $listing->ID, ORAS_MH_Equipment_Fields::META_PICKUP_AREA, true );
$status_label = ORAS_MH_Equipment_Fields::get_public_status_label( $listing->ID );
?>
<article class="oras-equipment-card">
	<a href="<?php echo esc_url( $single_url ); ?>" class="oras-equipment-card__image-link">
		<?php if ( $img ) : ?>
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $listing ) ); ?>" class="oras-equipment-card__image" />
		<?php endif; ?>
	</a>
	<div class="oras-equipment-card__body">
		<h3><a href="<?php echo esc_url( $single_url ); ?>"><?php echo esc_html( get_the_title( $listing ) ); ?></a></h3>
		<div class="oras-equipment-card__badges">
			<span class="oras-equipment-badge oras-equipment-badge--type"><?php echo esc_html( ORAS_MH_Equipment_Fields::listing_types()[ $listing_type ] ?? $listing_type ); ?></span>
			<span class="oras-equipment-badge oras-equipment-badge--status"><?php echo esc_html( $status_label ); ?></span>
		</div>
		<p class="oras-equipment-card__price"><?php echo esc_html( ORAS_MH_Equipment_Shortcodes::format_price( $listing->ID ) ); ?></p>
		<p class="oras-equipment-card__meta"><?php echo esc_html( $category ); ?><?php if ( $category && $pickup ) : ?> <span aria-hidden="true">|</span> <?php endif; ?><?php echo esc_html( $pickup ); ?></p>
	</div>
</article>
