<?php
/**
 * Settings for the Recipe Submission addon.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 */

/**
 * Settings for the Recipe Submission addon.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Settings {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
	}

	/**
	 * Add recipe submission settings.
	 *
	 * @since    3.0.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMPP_DIR . 'templates/admin/settings/products.php' );
		$structure['products'] = $products;

		return $structure;
	}
}

WPRMPP_Settings::init();
