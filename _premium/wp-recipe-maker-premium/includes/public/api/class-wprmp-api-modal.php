<?php
/**
 * Handle the modal API for premium features.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the modal API for premium features.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Modal {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    1.0.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/modal/ai-suggest-tags', array(
				'callback' => array( __CLASS__, 'api_ai_suggest_tags' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/modal/ai-recipe-import', array(
				'callback' => array( __CLASS__, 'api_ai_recipe_import' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 1.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle AI suggest tags call to the REST API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_ai_suggest_tags( $request ) {
		// Parameters.
		$params = $request->get_params();

		$recipe = isset( $params['recipe'] ) ? $params['recipe'] : false;
		$categories = isset( $params['categories'] ) ? $params['categories'] : array();
		$existing_terms = isset( $params['existingTerms'] ) ? $params['existingTerms'] : array();
		$popular_terms = isset( $params['popularTerms'] ) ? $params['popularTerms'] : array();

		if ( ! $recipe ) {
			return new WP_Error( 'missing_recipe', __( 'Recipe is required.', 'wp-recipe-maker-premium' ), array( 'status' => 400 ) );
		}

		if ( ! is_array( $categories ) || empty( $categories ) ) {
			return new WP_Error( 'missing_categories', __( 'Categories are required.', 'wp-recipe-maker-premium' ), array( 'status' => 400 ) );
		}

		// Call proxy server.
		$proxy_data = array(
			'recipe' => $recipe,
			'categories' => $categories,
		);
		if ( is_array( $existing_terms ) && ! empty( $existing_terms ) ) {
			$proxy_data['existingTerms'] = $existing_terms;
		}
		if ( is_array( $popular_terms ) && ! empty( $popular_terms ) ) {
			$proxy_data['popularTerms'] = $popular_terms;
		}

		$response = WPRMP_Proxy::call( 'ai_suggest_tags', $proxy_data );

		if ( false === $response ) {
			return new WP_Error( 'proxy_error', __( 'Failed to get AI suggestions.', 'wp-recipe-maker-premium' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Handle AI recipe import call to the REST API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_ai_recipe_import( $request ) {
		$params = $request->get_params();

		$text = isset( $params['text'] ) ? wp_unslash( $params['text'] ) : '';
		$text = is_string( $text ) ? trim( $text ) : '';

		if ( ! $text ) {
			return new WP_Error( 'missing_text', __( 'Text is required.', 'wp-recipe-maker-premium' ), array( 'status' => 400 ) );
		}

		$response = WPRMP_Proxy::call(
			'ai_recipe_import',
			array(
				'text' => $text,
			)
		);

		if ( false === $response ) {
			return new WP_Error( 'proxy_error', __( 'Failed to import recipe with AI.', 'wp-recipe-maker-premium' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( $response );
	}
}

WPRMP_Api_Modal::init();
