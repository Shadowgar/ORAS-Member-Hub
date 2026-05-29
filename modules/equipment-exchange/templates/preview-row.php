<?php
/**
 * Preview row template.
 *
 * @var array<int,WP_Post> $posts
 * @var string $grid_url
 * @var string $submit_url
 */
?>
<div class="oras-equipment-preview">
	<p class="oras-equipment-subtitle"><?php esc_html_e( 'Buy, sell, trade, or search for astronomy equipment with other ORAS members.', 'oras-member-hub' ); ?></p>
	<div class="oras-equipment-preview__row">
		<?php foreach ( $posts as $listing ) : ?>
			<?php require __DIR__ . '/card.php'; ?>
		<?php endforeach; ?>
	</div>
	<p class="oras-equipment-preview__actions">
		<a class="button" href="<?php echo esc_url( $grid_url ); ?>"><?php esc_html_e( 'Find More Equipment', 'oras-member-hub' ); ?></a>
		<a class="button" href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( 'List Equipment', 'oras-member-hub' ); ?></a>
	</p>
</div>
