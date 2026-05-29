<?php
/**
 * Contact seller form template.
 *
 * @var WP_Post $listing
 */
$member_user = wp_get_current_user();
?>
<form method="post" class="oras-equipment-contact-form">
	<?php wp_nonce_field( 'oras_equipment_contact', 'oras_equipment_nonce' ); ?>
	<input type="hidden" name="oras_equipment_action" value="contact_seller" />
	<input type="hidden" name="listing_id" value="<?php echo esc_attr( (string) $listing->ID ); ?>" />
	<p><label><?php esc_html_e( 'Name', 'oras-member-hub' ); ?><br /><input type="text" name="contact_name" value="<?php echo esc_attr( (string) $member_user->display_name ); ?>" required /></label></p>
	<p><label><?php esc_html_e( 'Email', 'oras-member-hub' ); ?><br /><input type="email" name="contact_email" value="<?php echo esc_attr( (string) $member_user->user_email ); ?>" required /></label></p>
	<p><label><?php esc_html_e( 'Message', 'oras-member-hub' ); ?><br /><textarea name="contact_message" rows="5" required></textarea></label></p>
	<p><button class="button" type="submit"><?php esc_html_e( 'Send Message', 'oras-member-hub' ); ?></button></p>
</form>
