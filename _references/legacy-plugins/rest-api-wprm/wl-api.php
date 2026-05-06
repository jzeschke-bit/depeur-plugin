<?php
/**
 * Plugin Name: Custom API
 * Plugin URI: http://chrushingit.com
 * Description: Crushing it!
 * Version: 1.0
 * Author: Art Vandelay
 * Author URI: http://watch-learn.com
 */

add_filter( 'rest_wprm_recipe_query', 'se35728943_change_post_per_page', 10, 2 );

/*Custom Recipe Json File */
function se35728943_change_post_per_page( $args, $request ) {
    $max = max( (int) $request->get_param( 'custom_per_page' ), 200 );
    $args['posts_per_page'] = $max;    
    return $args;
}



function wl_posts( $slug ) {
	$args = [
		'name' => $slug['slug'],
		'post_type' => 'wprm_recipe'
	];

	$post = get_posts($args);


	$data['id'] = $post[0]->ID;
	$data['id2'] = $post[0]->ParrentID;
	$data['title'] = $post[0]->post_title;
	$data['content'] = "hallo";
	$data['slug'] = $post[0]->post_name;
	$data['featured_image']['thumbnail'] = get_the_post_thumbnail_url($post[0]->ID, 'thumbnail');
	$data['featured_image']['medium'] = get_the_post_thumbnail_url($post[0]->ID, 'medium');
	$data['featured_image']['large'] = get_the_post_thumbnail_url($post[0]->ID, 'large');

	return $data;
}


add_action('rest_api_init', function() {
	register_rest_route('wl/v1', 'posts', [
		'methods' => 'GET',
		'callback' => 'wl_posts',
		
	]);

});


class XWPRM_Api_Rating {

	/**
	 * Register actions and filters.
	 *
	 * @since    2.4.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'aapi_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    2.4.0
	 */
	public static function aapi_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wrm/v1', 'rating', array(
				'callback' => array( __CLASS__, 'aapi_get_ratings' ),
				'methods' => 'GET',
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', '/rating', array(
				'callback' => array( __CLASS__, 'aapi_add_or_update_rating' ),
				'methods' => 'POST',
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', '/rating/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_get_rating' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'aapi_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', '/rating/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_delete_rating' ),
				'methods' => 'DELETE',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'aapi_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', '/rating/recipe/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_get_ratings_for_recipe' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', 'rating/recipe/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_delete_ratings_for_recipe' ),
				'methods' => 'DELETE',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', 'rating/comment/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_get_rating_for_comment' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wrm/v1', 'rating/comment/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'aapi_delete_rating_for_comment' ),
				'methods' => 'DELETE',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'aapi_validate_numeric' ),
					),
				),
				//'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
		}
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since 2.4.0
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function aapi_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 2.4.0
	 */
	public static function aapi_required_permissions() {
		//
		//return current_user_can( 'moderate_comments' );
		return null;
	}

	/**
	 * Handle get ratings call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_get_ratings( $request ) {
		return WPRM_Rating_Database::get_ratings( array() );
	}

	/**
	 * Handle add or update rating call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_add_or_update_rating( $request ) {
		$params = $request->get_params();
		$rating = isset( $params['rating'] ) ? $params['rating'] : array();
		return WPRM_Rating_Database::add_or_update_rating( $rating );
	}

	/**
	 * Handle get rating call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_get_rating( $request ) {
		return WPRM_Rating_Database::get_rating(array(
			'where' => 'id = ' . $request['id'],
		));
	}

	/**
	 * Handle delete rating call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_delete_rating( $request ) {
		return WPRM_Rating_Database::delete_rating( $request['id'] );
	}

	/**
	 * Handle get ratings for recipe call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_get_ratings_for_recipe( $request ) {
		return WPRM_Rating_Database::get_ratings(array(
			'where' => 'recipe_id = ' . $request['id'],
		));
	}

	/**
	 * Handle delete ratings for recipe call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_delete_ratings_for_recipe( $request ) {
		return WPRM_Rating_Database::delete_ratings_for( $request['id'] );
	}

	/**
	 * Handle get rating for comment call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_get_rating_for_comment( $request ) {
		return WPRM_Rating_Database::get_rating(array(
			'where' => 'comment_id = ' . $request['id'],
		));
	}

	/**
	 * Handle delete rating for comment call to the REST API.
	 *
	 * @since 2.4.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function aapi_delete_rating_for_comment( $request ) {
		return WPRM_Rating_Database::delete_ratings_for_comment( $request['id'] );
	}
}

XWPRM_Api_Rating::init();

