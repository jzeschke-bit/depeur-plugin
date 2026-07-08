<?php
/**
 * Handle the license API.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the license API.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_License {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    10.2.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/license/status', array(
				'callback' => array( __CLASS__, 'api_get_license_status' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/license/update', array(
				'callback' => array( __CLASS__, 'api_update_license' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 10.2.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle get license status call to the REST API.
	 *
	 * @since    10.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_get_license_status( $request ) {
		$params = $request->get_params();
		$product_id = isset( $params['product_id'] ) ? sanitize_text_field( $params['product_id'] ) : '';

		if ( ! $product_id ) {
			return rest_ensure_response( array( 'error' => 'Product ID is required' ) );
		}

		$status = WPRMP_License::get_license_status( $product_id );

		return rest_ensure_response( array(
			'product_id' => $product_id,
			'status' => $status,
		) );
	}

	/**
	 * Handle update license call to the REST API.
	 *
	 * @since    10.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_update_license( $request ) {
		$params = $request->get_params();
		$product_id = isset( $params['product_id'] ) ? sanitize_text_field( $params['product_id'] ) : '';
		$license_key = isset( $params['license_key'] ) ? sanitize_text_field( $params['license_key'] ) : '';
		$allow_tracking = isset( $params['allow_tracking'] ) ? (bool) $params['allow_tracking'] : null;

		if ( ! $product_id ) {
			return rest_ensure_response( array( 'error' => 'Product ID is required' ) );
		}

		// Get current settings
		$old_settings = WPRM_Settings::get_settings_with_defaults();
		$new_settings = $old_settings;

		// Update license key
		$new_settings[ 'license_' . $product_id ] = $license_key;

		// Update tracking preference if provided
		if ( null !== $allow_tracking ) {
			$new_settings['license_allow_tracking'] = $allow_tracking;
		}

		// Update settings (this will trigger the license activation via the filter)
		WPRM_Settings::update_settings( $new_settings );

		// Get the license status after activation
		$status = WPRMP_License::get_license_status( $product_id );

		return rest_ensure_response( array(
			'product_id' => $product_id,
			'status' => $status,
		) );
	}
}

WPRMP_Api_License::init();

