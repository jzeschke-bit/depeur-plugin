<?php
/**
 * Template for import from JSON form.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin
 */
?>

<p><?php esc_html_e( 'Select the .json file containing taxonomy terms in the WP Recipe Maker format:', 'wp-recipe-maker' ); ?></p>
<form method="POST" action="<?php echo admin_url( 'admin.php?page=wprm_import_taxonomies' ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wprm_import_taxonomies', 'wprm_import_taxonomies' ); ?>
	<input type="file" name="json">
	<?php submit_button( __( 'Import JSON', 'wp-recipe-maker' ) ); ?>
</form>

