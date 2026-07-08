<?php
/**
 * Handle the private notes API.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.7.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the private notes API.
 *
 * @since      7.7.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Private_Notes {

	/**
	 * Register actions and filters.
	 *
	 * @since    7.7.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    7.7.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/private-notes/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_get_notes_for_recipe' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/private-notes/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_save_notes_for_recipe' ),
				'methods' => 'POST',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
		}
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since 8.0.0
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Handle get private notes for recipe call to the REST API.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_notes_for_recipe( $request ) {
		$recipe_id = intval( $request['id'] );
		return rest_ensure_response( WPRMP_Private_Notes::get( $recipe_id ) );
	}

	/**
	 * Handle save private notes for recipe call to the REST API.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_notes_for_recipe( $request ) {
		$params = $request->get_params();
		$notes = isset( $params['notes'] ) ? wp_kses_post( $params['notes'] ) : '';

		$recipe_id = intval( $request['id'] );

		if ( $recipe_id ) {
			return rest_ensure_response( WPRMP_Private_Notes::save( $recipe_id, $notes ) );
		}

		return rest_ensure_response( false );
	}
}

WPRMP_Api_Private_Notes::init();
