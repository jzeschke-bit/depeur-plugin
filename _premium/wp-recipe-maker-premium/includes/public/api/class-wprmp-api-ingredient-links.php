<?php
/**
 * Handle the ingredient links API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the ingredient links API.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Ingredient_Links {

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
			register_rest_route( 'wp-recipe-maker/v1', '/ingredient-links', array(
				'callback' => array( __CLASS__, 'api_get_ingredient_links' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/ingredient-links', array(
				'callback' => array( __CLASS__, 'api_save_ingredient_links' ),
				'methods' => 'PUT',
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
	 * Handle get ingredient links call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_ingredient_links( $request ) {
		// Parameters.
		$params = $request->get_params();

		$ingredients = isset( $params['ingredients'] ) ? $params['ingredients'] : array();
		$links = array();

		foreach ( $ingredients as $index => $ingredient ) {
			$links[ $index ] = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient['name'], true );
		}

		return rest_ensure_response( array(
			'links' => $links,
		) );
	}

	/**
	 * Handle save ingredient links call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_ingredient_links( $request ) {
		// Parameters.
		$params = $request->get_params();

		$links = isset( $params['links'] ) ? $params['links'] : array();
		WPRMP_Ingredient_Links::update_ingredient_links( $links );

		return rest_ensure_response( true );
	}
}

WPRMP_Api_Ingredient_Links::init();
