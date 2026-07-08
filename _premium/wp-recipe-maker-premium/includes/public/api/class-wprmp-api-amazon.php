<?php
/**
 * Handle the Amazon API.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the Amazon API.
 *
 * @since      9.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Amazon {

	/**
	 * Register actions and filters.
	 *
	 * @since    9.1.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    9.1.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/amazon/search', array(
				'callback' => array( __CLASS__, 'api_search_products' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/amazon/get', array(
				'callback' => array( __CLASS__, 'api_get_products' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 9.1.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle search products call to the REST API.
	 *
	 * @since    9.1.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_search_products( $request ) {
		// Parameters.
		$params = $request->get_params();

		$search = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

		$response = WPRMP_Amazon::search_products( $search );

		return rest_ensure_response( $response );
	}

	/**
	 * Handle get products call to the REST API.
	 *
	 * @since    9.1.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_get_products( $request ) {
		// Parameters.
		$params = $request->get_params();

		$asins = isset( $params['asins'] ) && is_array( $params['asins'] ) ? array_map( 'sanitize_text_field', $params['asins'] ) : array();

		$response = WPRMP_Amazon::get_products( $asins );

		return rest_ensure_response( $response );
	}
}

WPRMP_Api_Amazon::init();
