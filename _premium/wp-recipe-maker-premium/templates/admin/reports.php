<?php
/**
 * Template for Premium reports page.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin
 */

?>
<?php if ( WPRM_Addons::is_active( 'elite' ) ) : ?>
<div class="wprm-tools-panel">
	<span class="wprm-tools-tag"><?php esc_html_e( 'Elite', 'wp-recipe-maker-premium' ); ?></span>
	<h2><?php esc_html_e( 'Recipe Collections', 'wp-recipe-maker-premium' ); ?></h2>
	<p><?php esc_html_e( 'Review how Recipe Collections are used. This report can only take collections created by logged-in users into account.', 'wp-recipe-maker-premium' ); ?></p>
	<div class="wprm-tools-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprm_report_recipe_collections' ) ); ?>" class="button button-secondary button-compact" id="report_recipe_collections"><?php esc_html_e( 'Generate Recipe Collections Usage Report', 'wp-recipe-maker-premium' ); ?></a>
	</div>
</div>
<?php endif; ?>
