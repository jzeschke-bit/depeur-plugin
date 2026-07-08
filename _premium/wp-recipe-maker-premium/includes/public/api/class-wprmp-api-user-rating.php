<?php
/**
 * Handle the user ratings API.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the user ratings API.
 *
 * @since      9.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_User_Rating {

	/**
	 * Register actions and filters.
	 *
	 * @since    9.2.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    9.2.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/user-rating/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_user_rating_for_recipe' ),
				'methods' => 'POST',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/user-rating/summary-popup', array(
				'callback' => array( __CLASS__, 'api_user_rating_summary_popup' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
		}
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since	9.2.0
	 * @param	mixed           $param Parameter to validate.
	 * @param	WP_REST_Request $request Current request.
	 * @param	mixed           $key Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Handle user rating call to the REST API.
	 *
	 * @since	9.2.0
	 * @param	WP_REST_Request $request Current request.
	 */
	public static function api_user_rating_for_recipe( $request ) {
		$params = $request->get_params();

		$data = isset( $params['data'] ) ? $params['data'] : false;
		$recipe_id = intval( $request['id'] );

		// Rate the recipe.
		$rated_recipe = false;
		if ( $data && $recipe_id ) {
			$rated_recipe = WPRMP_User_Rating::rate_recipe( $recipe_id, $data );
		}

		if ( $rated_recipe ) {
			// Get new recipe object.
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			if ( $recipe ) {
				return rest_ensure_response( $recipe->rating() );
			}
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle summary popup call to the REST API.
	 *
	 * @since	9.5.0
	 * @param	WP_REST_Request $request Current request.
	 */
	public static function api_user_rating_summary_popup( $request ) {
		$params = $request->get_params();

		$recipe_id = isset( $params['recipeId'] ) ? intval( $params['recipeId'] ) : 0;
		$post_id = isset( $params['postId'] ) ? intval( $params['postId'] ) : 0;

		$html = WPRMP_User_Rating_Comments::get_content_for_summary_popup( $post_id, $recipe_id );

		return rest_ensure_response( array(
			'html' => $html,
		) );
	}
}

WPRMP_Api_User_Rating::init();
