<?php
/**
 * Grid template.
 *
 * @var array<int,WP_Post> $posts
 * @var string $disclaimer
 * @var string $submit_url
 * @var string $my_listings
 */
?>
<section class="oras-equipment-grid-page">
	<h2><?php esc_html_e( 'ORAS Equipment Exchange', 'oras-member-hub' ); ?></h2>
	<p><?php esc_html_e( 'Member-to-member astronomy equipment listings. ORAS does not process payment or arrange pickup.', 'oras-member-hub' ); ?></p>
	<p><a class="button" href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( 'List Equipment', 'oras-member-hub' ); ?></a> <a class="button" href="<?php echo esc_url( $my_listings ); ?>"><?php esc_html_e( 'My Listings', 'oras-member-hub' ); ?></a></p>
	<p class="oras-equipment-disclaimer"><?php echo esc_html( $disclaimer ); ?></p>
	<div class="oras-equipment-grid">
		<?php if ( empty( $posts ) ) : ?>
			<p><?php esc_html_e( 'No equipment listings found.', 'oras-member-hub' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $posts as $listing ) : ?>
			<?php require __DIR__ . '/card.php'; ?>
		<?php endforeach; ?>
	</div>
</section>
