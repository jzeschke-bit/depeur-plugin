<?php
/**
 * Handle the Recipe Modal.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.5.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 */

/**
 * Handle the Recipe Modal.
 *
 * @since      5.5.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPCF_Modal {

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
		$data['custom_fields'] = array(
			'fields' => WPRMPCF_Manager::get_custom_fields(),
			'types' => WPRMPCF_Manager::get_type_options(),
		);

		return $data;
	}
}

WPRMPCF_Modal::init();
