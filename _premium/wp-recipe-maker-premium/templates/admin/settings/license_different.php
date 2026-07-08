<?php
/**
 * Template for the license modal.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin/settings
 */

?>

<div id="wprm-activate-license-container" class="error">
	<p>
		<strong><?php echo esc_html( $product['name'] ); ?></strong><br/>
		<?php esc_html_e( 'The license key you have activated is for a different WP Recipe Maker Bundle. Make sure the correct plugin file is installed.', 'wp-recipe-maker-premium' ); ?>
		<div>
			<a href="https://help.bootstrapped.ventures/article/63-installing-wp-recipe-maker" class="button button-primary button-compact" target="_blank"><?php esc_html_e( 'Learn more', 'wp-recipe-maker-premium' ); ?></a>
		</div>
	</p>
</div>