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
		<?php esc_html_e( 'Activate your license key to receive automatic updates.', 'wp-recipe-maker-premium' ); ?> <a href="https://help.bootstrapped.ventures/article/93-activating-your-license-key" target="_blank"><?php esc_html_e( 'Need help activing?', 'wp-recipe-maker-premium' ); ?></a> - <a href="https://bootstrapped.ventures/wp-recipe-maker/get-the-plugin/" target="_blank"><?php esc_html_e( 'Get a License', 'wp-recipe-maker-premium' ); ?></a>.
		<div>
			<input name="license_<?php echo esc_attr( $id ); ?>" type="text" class="wprm-license" id="license_<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( WPRM_Settings::get( 'license_' . $id ) ); ?>" placeholder="<?php esc_html_e( 'Your license key', 'wp-recipe-maker-premium' ); ?>" class="regular-text">
			<?php if ( ! WPRMP_License::get_allow_tracking() ) : ?>
			<div style="margin-top: 10px;">
				<label>
					<input type="checkbox" name="license_allow_tracking" value="1" />
					<?php esc_html_e( 'Allow the licensing API to collect usage data about your site, such as the PHP and WordPress versions.', 'wp-recipe-maker-premium' ); ?>
				</label>
			</div>
			<?php endif; ?>
			<?php submit_button( __( 'Activate now', 'wp-recipe-maker-premium' ), 'primary', 'wprm-activate-license', true ); ?>
		</div>
	</p>
</div>