<?php
/**
 * Handle the Product API.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 */

/**
 * Handle the Products API.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Api {

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
			register_rest_route( 'wp-recipe-maker/v1', '/product/search', array(
				'callback' => array( __CLASS__, 'api_search_products' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
		register_rest_route( 'wp-recipe-maker/v1', '/product/bulk', array(
			'callback' => array( __CLASS__, 'api_get_products' ),
			'methods' => 'POST',
			'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
		));
		register_rest_route( 'wp-recipe-maker/v1', '/product/variations', array(
			'callback' => array( __CLASS__, 'api_get_variations' ),
			'methods' => 'POST',
			'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
		));
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 10.2.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since 10.2.0
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Handle search products call to the REST API.
	 *
	 * @since 10.2.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_search_products( $request ) {
		// Parameters.
		$params = $request->get_params();
		$search = isset( $params['search'] ) ? $params['search'] : '';

		return rest_ensure_response( array(
			'products' => WPRMPP_Product_Manager::search( $search ),
		) );
	}

	/**
	 * Handle get products call to the REST API.
	 *
	 * @since 10.2.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_products( $request ) {
		// Parameters.
		$params = $request->get_params();

		$taxonomy = isset( $params['taxonomy'] ) ? sanitize_key( $params['taxonomy'] ) : false;
		$items = isset( $params['items'] ) ? $params['items'] : array();
		$products = array();

		if ( $taxonomy && is_array( $items ) ) {
			foreach ( $items as $index => $item ) {
				$term_id = WPRM_Recipe_Sanitizer::get_term_id_by_name( $taxonomy, $item['name'] );
				$products[ $index ] = $term_id ? WPRMPP_Meta::get_product_from_term_id( $term_id ) : false;
			}
		}

		return rest_ensure_response( array(
			'products' => $products,
		) );
	}

	/**
	 * Handle get variations call to the REST API.
	 *
	 * @since 10.2.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_variations( $request ) {
		// Parameters.
		$params = $request->get_params();
		$product_id = isset( $params['product_id'] ) ? intval( $params['product_id'] ) : 0;

		$variations = array();
		if ( $product_id > 0 ) {
			$variations = WPRMPP_Product_Manager::get_variations( $product_id );
		}

		return rest_ensure_response( array(
			'variations' => $variations,
		) );
	}

}

WPRMPP_Api::init();
