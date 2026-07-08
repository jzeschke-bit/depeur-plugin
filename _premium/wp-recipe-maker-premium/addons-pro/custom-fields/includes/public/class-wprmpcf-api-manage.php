<?php
/**
 * Handle the Custom Fields manage API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 */

/**
 * Handle the Custom Fields manage API.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPCF_Api_Manage {

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
			register_rest_route( 'wp-recipe-maker/v1', '/manage/custom-fields', array(
				'callback' => array( __CLASS__, 'api_manage_custom_fields' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since    5.2.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle manage saved collections call to the REST API.
	 *
	 * @since    5.2.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_manage_custom_fields( $request ) {
		// Parameters.
		$params = $request->get_params();

		$page = isset( $params['page'] ) ? intval( $params['page'] ) : 0;
		$page_size = isset( $params['pageSize'] ) ? intval( $params['pageSize'] ) : 25;

		$starting_index = $page * $page_size;
		$ending_index = $starting_index + $page_size;
		
		$rows = array();
		$custom_fields = WPRMPCF_Manager::get_custom_fields();

		$counter = 0;
		foreach ( $custom_fields as $custom_field ) {
			if ( $starting_index <= $counter && $counter < $ending_index ) {
				$rows[] = $custom_field;
			}
			$counter++;
		}

		$data = array(
			'rows' => $rows,
			'total' => count( $custom_fields ),
			'filtered' => count( $custom_fields ),
			'pages' => ceil( count( $custom_fields ) / $page_size ),
		);

		return rest_ensure_response( $data );
	}
}

WPRMPCF_Api_Manage::init();
