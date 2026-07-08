<?php
/**
 * Handle the favorites API.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the favorites API.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Favorites {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.6.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    10.6.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route(
				'wp-recipe-maker/v1',
				'/favorites',
				array(
					'callback' => array( __CLASS__, 'api_get_favorites' ),
					'methods' => 'GET',
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				'wp-recipe-maker/v1',
				'/favorites/merge',
				array(
					'callback' => array( __CLASS__, 'api_merge_favorites' ),
					'methods' => 'POST',
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				'wp-recipe-maker/v1',
				'/favorites/(?P<id>\d+)',
				array(
					'callback' => array( __CLASS__, 'api_set_favorite_for_recipe' ),
					'methods' => 'POST',
					'args' => array(
						'id' => array(
							'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
						),
					),
					'permission_callback' => '__return_true',
				)
			);
			register_rest_route(
				'wp-recipe-maker/v1',
				'/favorites/render',
				array(
					'callback' => array( __CLASS__, 'api_render_favorites' ),
					'methods' => 'POST',
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since    10.6.0
	 * @param    mixed           $param   Parameter to validate.
	 * @param    WP_REST_Request $request Current request.
	 * @param    mixed           $key     Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Get favorites for the active visitor.
	 *
	 * @since    10.6.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_get_favorites( $request ) {
		return rest_ensure_response(
			array(
				'favorites' => WPRMP_Favorites::get_current_favorites_ids(),
			)
		);
	}

	/**
	 * Merge local browser favorites into the logged in account.
	 *
	 * @since    10.6.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_merge_favorites( $request ) {
		$params = $request->get_params();
		$favorites = isset( $params['favorites'] ) ? $params['favorites'] : array();

		return rest_ensure_response(
			array(
				'favorites' => WPRMP_Favorites::merge_local_favorites_into_account( $favorites ),
			)
		);
	}

	/**
	 * Set favorite status for a specific recipe.
	 *
	 * @since    10.6.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_set_favorite_for_recipe( $request ) {
		$params = $request->get_params();
		$favorite = isset( $params['favorite'] ) ? filter_var( $params['favorite'], FILTER_VALIDATE_BOOLEAN ) : false;
		$favorites = isset( $params['favorites'] ) ? $params['favorites'] : array();
		$recipe_id = intval( $request['id'] );

		return rest_ensure_response(
			array(
				'favorites' => WPRMP_Favorites::set_recipe_favorite_status( $recipe_id, $favorite, $favorites ),
			)
		);
	}

	/**
	 * Render favorite recipes HTML for the current visitor.
	 *
	 * @since    10.6.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_render_favorites( $request ) {
		$params = $request->get_params();
		$favorites = isset( $params['favorites'] ) ? $params['favorites'] : array();
		$favorites = WPRMP_Favorites::get_current_favorites_ids( $favorites );

		return rest_ensure_response(
			array(
				'favorites' => $favorites,
				'html' => WPRMP_Favorites_Display::get_favorite_recipes_html( $favorites ),
			)
		);
	}
}

WPRMP_Api_Favorites::init();
