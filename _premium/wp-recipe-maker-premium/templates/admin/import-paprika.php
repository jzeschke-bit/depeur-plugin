<?php
/**
 * Template for import from Paprika form.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin
 */
?>

<p><?php esc_html_e( 'Select the .paprikarecipes file containing recipes in the Paprika export format:', 'wp-recipe-maker' ); ?></p>
<form method="POST" action="<?php echo admin_url( 'admin.php?page=wprm_import_paprika' ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wprm_import_paprika', 'wprm_import_paprika' ); ?>
	<h4>Import file</h4>
	<input type="file" name="paprika">
	<?php submit_button( __( 'Import from Paprika', 'wp-recipe-maker' ) ); ?>
</form>
