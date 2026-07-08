<?php
/**
 * Handle the products manage page.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/admin
 */

/**
 * Handle the recipe submission manage page.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Manage {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_admin_manage_localize', array( __CLASS__, 'localize' ) );
		add_filter( 'wprm_admin_modal_localize', array( __CLASS__, 'localize' ) );
	}

	/**
	 * Localize data for the manage page and modal.
	 *
	 * @since   10.2.0
	 * @param	array $data Localized data.
	 */
	public static function localize( $data ) {
		$data['products_integrations_available'] = WPRMPP_Integrations::is_available();

		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			$data['settings'] = array();
		}

		$data['settings']['products_default_linked_ingredient_amount'] = WPRM_Settings::get( 'products_default_linked_ingredient_amount' );
		$data['settings']['products_default_linked_equipment_amount'] = WPRM_Settings::get( 'products_default_linked_equipment_amount' );

		return $data;
	}
}

WPRMPP_Manage::init();
