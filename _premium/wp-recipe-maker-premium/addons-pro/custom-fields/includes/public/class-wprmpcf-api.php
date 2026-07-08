<?php
/**
 * Handle the Custom Fields API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 */

/**
 * Handle the Custom Fields API.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPCF_Api {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.2.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.2.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/custom-fields', array(
				'callback' => array( __CLASS__, 'api_create_field' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/custom-fields', array(
				'callback' => array( __CLASS__, 'api_update_field' ),
				'methods' => 'PUT',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/custom-fields', array(
				'callback' => array( __CLASS__, 'api_delete_field' ),
				'methods' => 'DELETE',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 5.2.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle create custom field call to the REST API.
	 *
	 * @since    5.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_create_field( $request ) {
		// Parameters.
		$params = $request->get_params();
		return rest_ensure_response( WPRMPCF_Manager::create_custom_field( $params ) );
	}

	/**
	 * Handle update custom field call to the REST API.
	 *
	 * @since    5.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_update_field( $request ) {
		// Parameters.
		$params = $request->get_params();
		return rest_ensure_response( WPRMPCF_Manager::update_custom_field( $params ) );
	}

	/**
	 * Handle delete custom field call to the REST API.
	 *
	 * @since    5.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_delete_field( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		return rest_ensure_response( WPRMPCF_Manager::delete_custom_field( $key ) );
	}
}

WPRMPCF_Api::init();
