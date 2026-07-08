<?php
/**
 * Handle the Recipe Modal.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.5.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/admin
 */

/**
 * Handle the Recipe Modal.
 *
 * @since      5.5.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Modal {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.5.0
	 */
	public static function init() {
		add_filter( 'wprm_admin_modal_localize', array( __CLASS__, 'localize' ) );
	}

	/**
	 * Localize data for the recipe modal.
	 *
	 * @since   5.5.0
	 * @param	array $data Localized data.
	 */
	public static function localize( $data ) {
		if ( WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
			$data['unit_conversion'] = array(
				'default_system' => WPRM_Settings::get( 'unit_conversion_system_default' ),
				'systems' => array(
					1 => array(
						'label' => WPRM_Settings::get( 'unit_conversion_system_1' ),
						'weight' => WPRM_Settings::get( 'unit_conversion_system_1_weight_units' ),
						'volume' => WPRM_Settings::get( 'unit_conversion_system_1_volume_units' ),
					),
					2 => array(
						'label' => WPRM_Settings::get( 'unit_conversion_system_2' ),
						'weight' => WPRM_Settings::get( 'unit_conversion_system_2_weight_units' ),
						'volume' => WPRM_Settings::get( 'unit_conversion_system_2_volume_units' ),
					),
				),
				'units' => array(
					'data' => WPRM_Settings::get( 'unit_conversion_units' ),
					// Deprecated - Keep below for backwards compatibilty.
					'weight' => WPRM_Settings::get( 'unit_conversion_system_2_weight_units' ),
					'volume' => WPRM_Settings::get( 'unit_conversion_system_2_volume_units' ),
				),
			);
		}

		return $data;
	}
}
WPRMPUC_Modal::init();
