<?php
/**
 * Grid template.
 *
 * @var array<int,WP_Post> $posts
 * @var string $disclaimer
 * @var string $submit_url
 * @var string $my_listings
 * @var array<string,mixed> $filters
 * @var array<int,WP_Term> $categories
 * @var array<int,WP_Term> $conditions
 */
?>
<section class="oras-equipment-grid-page">
	<h2><?php esc_html_e( 'ORAS Equipment Exchange', 'oras-member-hub' ); ?></h2>
	<p><?php esc_html_e( 'Member-to-member astronomy equipment listings. ORAS does not process payment or arrange pickup.', 'oras-member-hub' ); ?></p>
	<p class="oras-equipment-page-actions"><a class="button" href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( 'List Equipment', 'oras-member-hub' ); ?></a> <a class="button" href="<?php echo esc_url( $my_listings ); ?>"><?php esc_html_e( 'My Listings', 'oras-member-hub' ); ?></a></p>
	<div class="oras-equipment-marketplace-layout">
		<aside class="oras-equipment-filter-sidebar">
			<h3><?php esc_html_e( 'Filter Results', 'oras-member-hub' ); ?></h3>
			<form method="get" class="oras-equipment-grid-filters">
				<p><label><?php esc_html_e( 'Search', 'oras-member-hub' ); ?> <input type="text" name="search" value="<?php echo esc_attr( (string) ( $filters['search'] ?? '' ) ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Listing type', 'oras-member-hub' ); ?> <select name="listing_type"><option value=""><?php esc_html_e( 'All', 'oras-member-hub' ); ?></option>
		<?php
		foreach ( ORAS_MH_Equipment_Fields::listing_types() as $filter_type_key => $filter_type_label ) :
			?>
			<option value="<?php echo esc_attr( $filter_type_key ); ?>" <?php selected( (string) ( $filters['listing_type'] ?? '' ), $filter_type_key ); ?>><?php echo esc_html( $filter_type_label ); ?></option><?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Category', 'oras-member-hub' ); ?> <select name="category"><option value="0"><?php esc_html_e( 'All', 'oras-member-hub' ); ?></option>
		<?php
		foreach ( $categories as $filter_category ) :
			?>
			<option value="<?php echo esc_attr( (string) $filter_category->term_id ); ?>" <?php selected( (int) ( $filters['category'] ?? 0 ), (int) $filter_category->term_id ); ?>><?php echo esc_html( $filter_category->name ); ?></option><?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Condition', 'oras-member-hub' ); ?> <select name="condition"><option value="0"><?php esc_html_e( 'All', 'oras-member-hub' ); ?></option>
		<?php
		foreach ( $conditions as $filter_condition ) :
			?>
			<option value="<?php echo esc_attr( (string) $filter_condition->term_id ); ?>" <?php selected( (int) ( $filters['condition'] ?? 0 ), (int) $filter_condition->term_id ); ?>><?php echo esc_html( $filter_condition->name ); ?></option><?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Status', 'oras-member-hub' ); ?> <select name="status"><option value=""><?php esc_html_e( 'All', 'oras-member-hub' ); ?></option>
		<?php
		foreach ( ORAS_MH_Equipment_Fields::all_public_status_labels() as $status_key => $status_label ) :
			?>
			<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( (string) ( $filters['status'] ?? '' ), $status_key ); ?>><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Price min', 'oras-member-hub' ); ?> <input type="number" step="0.01" min="0" name="price_min" value="<?php echo esc_attr( (string) ( $filters['price_min'] ?? '' ) ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Price max', 'oras-member-hub' ); ?> <input type="number" step="0.01" min="0" name="price_max" value="<?php echo esc_attr( (string) ( $filters['price_max'] ?? '' ) ); ?>" /></label></p>
				<p><label><?php esc_html_e( 'Sort', 'oras-member-hub' ); ?> <select name="sort"><option value=""><?php esc_html_e( 'Newest', 'oras-member-hub' ); ?></option><option value="price_low" <?php selected( (string) ( $filters['sort'] ?? '' ), 'price_low' ); ?>><?php esc_html_e( 'Price low to high', 'oras-member-hub' ); ?></option><option value="price_high" <?php selected( (string) ( $filters['sort'] ?? '' ), 'price_high' ); ?>><?php esc_html_e( 'Price high to low', 'oras-member-hub' ); ?></option></select></label></p>
				<p><button class="button" type="submit"><?php esc_html_e( 'Apply Filters', 'oras-member-hub' ); ?></button></p>
			</form>
		</aside>
		<div class="oras-equipment-results-column">
			<p class="oras-equipment-disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
			<div class="oras-equipment-grid">
				<?php if ( empty( $posts ) ) : ?>
					<p><?php esc_html_e( 'No equipment listings found.', 'oras-member-hub' ); ?></p>
				<?php endif; ?>
				<?php foreach ( $posts as $listing ) : ?>
					<?php require __DIR__ . '/card.php'; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
