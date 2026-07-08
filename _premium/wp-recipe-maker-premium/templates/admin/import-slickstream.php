<?php
/**
 * Template for import from SlickStream form.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.10.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin
 */
?>

<p><?php esc_html_e( 'Select the .csv file containing the SlickStream export (email, permalink, username):', 'wp-recipe-maker' ); ?></p>
<form method="POST" action="<?php echo admin_url( 'admin.php?page=wprm_import_slickstream' ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wprm_import_slickstream', 'wprm_import_slickstream' ); ?>
	<input type="file" name="csv">
	<?php submit_button( __( 'Import SlickStream', 'wp-recipe-maker' ) ); ?>
</form>
