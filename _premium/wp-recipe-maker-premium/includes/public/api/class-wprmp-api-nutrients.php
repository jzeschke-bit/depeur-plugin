<?php
/**
 * Handle the nutrients API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the nutrients API.
 *
 * @since      5.3.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Nutrients {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.3.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.3.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/manage/nutrient', array(
				'callback' => array( __CLASS__, 'api_manage_nutrient' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/nutrient', array(
				'callback' => array( __CLASS__, 'api_create_nutrient' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/nutrient', array(
				'callback' => array( __CLASS__, 'api_update_nutrient' ),
				'methods' => 'PUT',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/nutrient', array(
				'callback' => array( __CLASS__, 'api_delete_nutrient' ),
				'methods' => 'DELETE',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 5.3.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle manage nutrients call to the REST API.
	 *
	 * @since    5.3.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_manage_nutrient( $request ) {
		// Parameters.
		$params = $request->get_params();

		$page = isset( $params['page'] ) ? intval( $params['page'] ) : 0;
		$page_size = isset( $params['pageSize'] ) ? intval( $params['pageSize'] ) : 25;

		$starting_index = $page * $page_size;
		$ending_index = $starting_index + $page_size;
		
		$rows = array();
		$nutrients = WPRM_Nutrition::get_fields( true ); // Make sure to get inactive as well.

		$counter = 0;
		foreach ( $nutrients as $key => $nutrient ) {
			if ( $starting_index <= $counter && $counter < $ending_index ) {
				$nutrient['key'] = $key;
				$rows[] = $nutrient;
			}
			$counter++;
		}

		$data = array(
			'rows' => $rows,
			'total' => count( $nutrients ),
			'filtered' => count( $nutrients ),
			'pages' => ceil( count( $nutrients ) / $page_size ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Handle create nutrient call to the REST API.
	 *
	 * @since    5.3.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_create_nutrient( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		$nutrient = isset( $params['nutrient'] ) ? $params['nutrient'] : false;

		if ( $key && $nutrient ) {
			return rest_ensure_response( WPRMP_Nutrition::create( $key, $nutrient ) );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle update nutrient call to the REST API.
	 *
	 * @since    5.3.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_update_nutrient( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		$nutrient = isset( $params['nutrient'] ) ? $params['nutrient'] : false;

		if ( $key && $nutrient ) {
			return rest_ensure_response( WPRMP_Nutrition::update( $key, $nutrient ) );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle delete nutrient call to the REST API.
	 *
	 * @since    5.3.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_delete_nutrient( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';

		if ( $key ) {
			return rest_ensure_response( WPRMP_Nutrition::delete( $key ) );
		}

		return rest_ensure_response( false );
	}
}

WPRMP_Api_Nutrients::init();
