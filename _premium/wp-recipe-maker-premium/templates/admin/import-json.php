<?php
/**
 * Template for import from JSON form.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin
 */
?>

<p><?php esc_html_e( 'Select the .json file containing recipes in the WP Recipe Maker format:', 'wp-recipe-maker' ); ?></p>
<form method="POST" action="<?php echo admin_url( 'admin.php?page=wprm_import_json' ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'wprm_import_json', 'wprm_import_json' ); ?>
	<h4>Import type</h4>
	<div><input type="radio" name="wprm-import-type" value="create" id="wprm-import-type-create" checked=""><label for="wprm-import-type-create">Create all as new recipes</label></div>
	<br/>
	<div><input type="radio" name="wprm-import-type" value="edit-id" id="wprm-import-type-edit-id"><label for="wprm-import-type-edit-id">Only edit existing recipes, using id field to match</label></div>
	<div><input type="radio" name="wprm-import-type" value="edit-slug" id="wprm-import-type-edit-slug"><label for="wprm-import-type-edit-slug">Only edit existing recipes, using slug field to match</label></div>
	<br/>
	<div><input type="radio" name="wprm-import-type" value="merge-id" id="wprm-import-type-merge-id"><label for="wprm-import-type-merge-id">Edit existing recipes, using id field to match, create if no match found</label></div>
	<div><input type="radio" name="wprm-import-type" value="merge-slug" id="wprm-import-type-merge-slug"><label for="wprm-import-type-merge-slug">Edit existing recipes, using slug field to match, create if no match found</label></div>
	<br/>
	<div><input type="radio" name="wprm-import-type" value="ignore-id" id="wprm-import-type-ignore-id"><label for="wprm-import-type-ignore-id">Ignore existing recipes, using id field to match, create if no match found</label></div>
	<div><input type="radio" name="wprm-import-type" value="ignore-slug" id="wprm-import-type-ignore-slug"><label for="wprm-import-type-ignore-slug">Ignore existing recipes, using slug field to match, create if no match found</label></div>
	<br/>
	<div style="color: darkred;">WARNING! Editing existing recipes will overwrite their values with the values in the import file.</div>
	<h4>Import file</h4>
	<input type="file" name="json">
	<?php submit_button( __( 'Import JSON', 'wp-recipe-maker' ) ); ?>
</form>
