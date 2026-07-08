<?php
/**
 * Handle the custom taxonomies API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the custom taxonomies API.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Custom_Taxonomies {

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
			register_rest_route( 'wp-recipe-maker/v1', '/custom-taxonomies', array(
				'callback' => array( __CLASS__, 'api_create_taxonomy' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/custom-taxonomies', array(
				'callback' => array( __CLASS__, 'api_delete_taxonomy' ),
				'methods' => 'DELETE',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 5.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle create taxonomy call to the REST API.
	 *
	 * @since    5.0.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_create_taxonomy( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		$singular_name = isset( $params['singular_name'] ) ? sanitize_text_field( $params['singular_name'] ) : '';
		$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$slug = isset( $params['slug'] ) ? sanitize_key( $params['slug'] ) : '';
		$archive = isset( $params['archive'] ) && $params['archive'] ? true : false;

		if ( $key && $singular_name && $name ) {
			$key = 'wprm_' . $key;
			$key = substr( $key, 0, 32 ); // Max length for taxonomies.

			if ( 'wprm_recipe' !== $key && ! taxonomy_exists( $key ) ) {
				$taxonomies = get_option( 'wprm_custom_taxonomies', array() );
				$taxonomies[ $key ] = array(
					'name' => $name,
					'singular_name' => $singular_name,
					'slug' => $slug,
					'archive' => $archive,
				);
				update_option( 'wprm_custom_taxonomies', $taxonomies );

				$data = array(
					'key' => $key,
					'singular_name' => $singular_name,
					'name' => $name,
					'slug' => $slug,
					'archive' => $archive,
				);

				return rest_ensure_response( $data );
			}
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle delete taxonomy call to the REST API.
	 *
	 * @since    5.0.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_delete_taxonomy( $request ) {
		// Parameters.
		$params = $request->get_params();

		$key = isset( $params['key'] ) ? sanitize_key( $params['key'] ) : '';

		if ( $key ) {
			$key = 'wprm_' . $key;
			$taxonomies = get_option( 'wprm_custom_taxonomies', array() );

			if ( array_key_exists( $key, $taxonomies ) ) {
				unset( $taxonomies[ $key ] );
				update_option( 'wprm_custom_taxonomies', $taxonomies );

				return rest_ensure_response( true );
			}
		}

		return rest_ensure_response( false );
	}
}

WPRMP_Api_Custom_Taxonomies::init();
