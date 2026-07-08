<?php
/**
 * Integrations class for products integration availability.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 */

/**
 * Integrations class for products integration availability.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Integrations {

	/**
	 * Check if products integration is available.
	 *
	 * @since	10.2.0
	 * @return	boolean Whether products integration is available.
	 */
	public static function is_available() {
		// Check if WooCommerce is active and has the required classes
		return class_exists( 'WC_Data_Store' ) && function_exists( 'wc_get_product' );
	}

	/**
	 * Get available product plugins.
	 *
	 * @since	10.2.0
	 * @return	array Array of available product plugin names.
	 */
	public static function get_available_plugins() {
		$plugins = array();

		// WooCommerce
		if ( class_exists( 'WC_Data_Store' ) && function_exists( 'wc_get_product' ) ) {
			$plugins[] = 'woocommerce';
		}

		return $plugins;
	}

	/**
	 * Check if a specific product plugin is available.
	 *
	 * @since	10.2.0
	 * @param	string $plugin Plugin name to check.
	 * @return	boolean Whether the specific plugin is available.
	 */
	public static function is_plugin_available( $plugin ) {
		$available_plugins = self::get_available_plugins();
		return in_array( $plugin, $available_plugins );
	}
}
