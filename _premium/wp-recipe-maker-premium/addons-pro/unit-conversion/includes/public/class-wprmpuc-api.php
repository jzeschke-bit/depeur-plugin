<?php
/**
 * Handle the Unit Conversion API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 */

/**
 * Handle the Unit Conversion API.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Api {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.0.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/unit-conversion', array(
				'callback' => array( __CLASS__, 'api_unit_conversion' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 5.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle unit conversion call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_unit_conversion( $request ) {
		// Required classes.
		require_once( WPRMPUC_DIR . 'includes/admin/class-wprmpuc-conversion-api.php' );

		// Parameters.
		$params = $request->get_params();

		$ingredients = isset( $params['ingredients'] ) ? $params['ingredients'] : array();
		$system = isset( $params['system'] ) ? intval( $params['system'] ) : 'default';
		$conversions = array();

		if ( 'default' === $system || ! in_array( $system, array( 1, 2 ) ) ) {
			$default_system = intval( WPRM_Settings::get( 'unit_conversion_system_default' ) );
			$converted_system = 2 === $default_system ? 1 : 2;
		} else {
			$converted_system = $system;
		}

		foreach ( $ingredients as $index => $ingredient ) {
			$conversions[ $index ] = WPRMPUC_Manager::calculate_unit_conversion( $ingredient, $converted_system );
		}

		return rest_ensure_response( array(
			'conversions' => $conversions,
		) );
	}
}

WPRMPUC_Api::init();
