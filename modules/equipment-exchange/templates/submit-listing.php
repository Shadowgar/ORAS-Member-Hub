<?php
/**
 * Submit listing form template.
 *
 * @var array<string,mixed> $settings
 * @var array<int,WP_Term> $categories
 * @var array<int,WP_Term> $conditions
 * @var string $notice_html
 */
?>
<section class="oras-equipment-submit">
	<div class="oras-equipment-page-intro">
		<h2><?php esc_html_e( 'List Astronomy Equipment', 'oras-member-hub' ); ?></h2>
	</div>
	<?php echo wp_kses_post( $notice_html ); ?>
	<div class="oras-equipment-submit__intro">
		<p class="oras-equipment-rules"><?php echo esc_html( (string) $settings['rules_text'] ); ?></p>
	</div>
	<form method="post" enctype="multipart/form-data" class="oras-equipment-form">
		<?php wp_nonce_field( 'oras_equipment_submit', 'oras_equipment_nonce' ); ?>
		<input type="hidden" name="oras_equipment_action" value="submit_listing" />
		<div class="oras-equipment-form__grid">
			<section class="oras-equipment-form__panel">
				<h3><?php esc_html_e( 'Item Details', 'oras-member-hub' ); ?></h3>
				<p><label><?php esc_html_e( 'Item title *', 'oras-member-hub' ); ?><br /><input type="text" name="listing_title" required /></label></p>
				<p><label><?php esc_html_e( 'Listing type *', 'oras-member-hub' ); ?><br /><select name="listing_type" required><?php foreach ( ORAS_MH_Equipment_Fields::listing_types() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Category *', 'oras-member-hub' ); ?><br /><select name="equipment_category" required><option value=""><?php esc_html_e( 'Select category', 'oras-member-hub' ); ?></option><?php foreach ( $categories as $category ) : ?>
			<option value="<?php echo esc_attr( (string) $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
		<?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Condition', 'oras-member-hub' ); ?><br /><select name="equipment_condition"><option value=""><?php esc_html_e( 'Select condition', 'oras-member-hub' ); ?></option><?php foreach ( $conditions as $condition ) : ?>
			<option value="<?php echo esc_attr( (string) $condition->term_id ); ?>"><?php echo esc_html( $condition->name ); ?></option>
		<?php endforeach; ?></select></label></p>
				<p><label><?php esc_html_e( 'Price type', 'oras-member-hub' ); ?><br /><select name="price_type"><option value="fixed"><?php esc_html_e( 'Fixed', 'oras-member-hub' ); ?></option><option value="obo"><?php esc_html_e( 'OBO', 'oras-member-hub' ); ?></option><option value="free"><?php esc_html_e( 'Free', 'oras-member-hub' ); ?></option><option value="trade"><?php esc_html_e( 'Trade', 'oras-member-hub' ); ?></option><option value="wanted"><?php esc_html_e( 'Wanted', 'oras-member-hub' ); ?></option></select></label></p>
				<p><label><?php esc_html_e( 'Price amount / budget', 'oras-member-hub' ); ?><br /><input type="text" name="price_amount" /></label></p>
				<p><label><?php esc_html_e( 'Description *', 'oras-member-hub' ); ?><br /><textarea name="listing_description" rows="6" required></textarea></label></p>
				<p><label><?php esc_html_e( 'Included items', 'oras-member-hub' ); ?><br /><textarea name="included_items" rows="3"></textarea></label></p>
				<p><label><?php esc_html_e( 'Known issues', 'oras-member-hub' ); ?><br /><textarea name="known_issues" rows="3"></textarea></label></p>
				<p><label><?php esc_html_e( 'Trade details', 'oras-member-hub' ); ?><br /><textarea name="trade_details" rows="3"></textarea></label></p>
			</section>

			<aside class="oras-equipment-form__panel oras-equipment-form__panel--side">
				<h3><?php esc_html_e( 'Location And Contact', 'oras-member-hub' ); ?></h3>
				<p><label><?php esc_html_e( 'Pickup/general area *', 'oras-member-hub' ); ?><br /><input type="text" name="pickup_area" required /></label></p>
				<p><label><input type="checkbox" name="shipping_available" value="1" /> <?php esc_html_e( 'Shipping available', 'oras-member-hub' ); ?></label></p>
				<p><label><?php esc_html_e( 'Photos *', 'oras-member-hub' ); ?><br /><input type="file" name="listing_photos[]" accept="image/jpeg,image/png,image/webp" multiple required /></label></p>
				<p><label><?php esc_html_e( 'Contact preference', 'oras-member-hub' ); ?><br /><select name="contact_preference"><option value="contact_form_only"><?php esc_html_e( 'Contact form only', 'oras-member-hub' ); ?></option></select></label></p>
				<p class="oras-equipment-disclaimer"><?php echo esc_html( (string) $settings['disclaimer_text'] ); ?></p>
				<p><label><input type="checkbox" name="listing_agreement" value="1" required /> <?php esc_html_e( 'I understand that this is a private member-to-member transaction and that ORAS is not responsible for payment, pickup, delivery, shipping, item condition, or disputes.', 'oras-member-hub' ); ?></label></p>
				<p><button class="button" type="submit"><?php esc_html_e( 'Submit Listing For Review', 'oras-member-hub' ); ?></button></p>
			</aside>
		</div>
	</form>
</section>
