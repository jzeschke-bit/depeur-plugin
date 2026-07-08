<?php
/**
 * Handle the Recipe Collections post type.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections post type.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Post_Type {

	/**
	 * Register actions and filters.
	 *
	 * @since    4.1.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 1 );
	}

	/**
	 * Register the Recipe post type.
	 *
	 * @since    1.0.0
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => _x( 'Collections', 'post type general name', 'wp-recipe-maker-premium' ),
			'singular_name'      => _x( 'Collection', 'post type singular name', 'wp-recipe-maker-premium' ),
		);

		$args = apply_filters( 'wprm_recipe_collections_post_type_arguments', array(
			'labels'            	=> $labels,
			'public'            	=> false,
			'rewrite'           	=> false,
			'capability_type'   	=> 'post',
			'query_var'         	=> false,
			'has_archive'       	=> false,
			'supports' 				=> array( 'title', 'author' ),
			'show_in_rest'			=> true,
			'rest_base'				=> WPRMPRC_POST_TYPE,
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		));

		register_post_type( WPRMPRC_POST_TYPE, $args );
	}
}

WPRMPRC_Post_Type::init();
