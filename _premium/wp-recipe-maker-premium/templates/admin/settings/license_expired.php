<?php
/**
 * Template for the license modal.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin/settings
 */

?>

<div id="wprm-activate-license-container" class="error">
	<p>
		<strong><?php echo esc_html( $product['name'] ); ?></strong><br/>
		<?php esc_html_e( 'Renew your license key to keep receiving updates.', 'wp-recipe-maker-premium' ); ?>.
		<div>
			<a href="https://bootstrapped.ventures/account/" class="button button-primary button-compact" target="_blank"><?php esc_html_e( 'Renew now', 'wp-recipe-maker-premium' ); ?></a>
		</div>
	</p>
</div>