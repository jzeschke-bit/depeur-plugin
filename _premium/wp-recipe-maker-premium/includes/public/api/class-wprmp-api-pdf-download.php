<?php
/**
 * API for PDF download.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * API for PDF download.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_PDF_Download {
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
		register_rest_route( 'wp-recipe-maker/v1', '/utilities/pdf-download-url', array(
			'callback' => array( __CLASS__, 'api_get_pdf_download_url' ),
			'methods' => 'POST',
			'permission_callback' => array( __CLASS__, 'api_permissions_pdf_download_url' ),
		));
	}

	/**
	 * Required permissions for the PDF download URL API.
	 *
	 * @since 10.6.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_permissions_pdf_download_url( $request ) {
		$params = $request->get_params();
		$nonce = isset( $params['nonce'] ) ? sanitize_text_field( $params['nonce'] ) : '';

		// Validate nonce when available, but allow unauthenticated visitors
		// to request a tokenized URL to avoid issues with cached nonce values.
		if ( $nonce && wp_verify_nonce( $nonce, 'wprm' ) ) {
			return true;
		}

		return true;
	}

	/**
	 * Handle get PDF download URL call to the REST API.
	 *
	 * @since 10.6.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_pdf_download_url( $request ) {
		if ( ! WPRM_Settings::get( 'pdf_download_enabled' ) ) {
			return new WP_Error( 'wprm_pdf_download_disabled', __( 'PDF download is disabled.', 'wp-recipe-maker' ), array( 'status' => 403 ) );
		}

		$params = $request->get_params();
		$recipe_id = isset( $params['recipeId'] ) ? intval( $params['recipeId'] ) : 0;

		if ( ! $recipe_id ) {
			return new WP_Error( 'wprm_invalid_recipe', __( 'Invalid recipe ID.', 'wp-recipe-maker' ), array( 'status' => 400 ) );
		}

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe || ! $recipe->id() ) {
			return new WP_Error( 'wprm_recipe_not_found', __( 'Recipe not found.', 'wp-recipe-maker' ), array( 'status' => 404 ) );
		}

		if ( ! WPRM_Print::has_permission( $recipe ) ) {
			return new WP_Error( 'wprm_forbidden_recipe', __( 'You are not allowed to access this recipe.', 'wp-recipe-maker' ), array( 'status' => 403 ) );
		}

		$template = isset( $params['template'] ) ? sanitize_key( $params['template'] ) : '';
		if ( $template ) {
			$selected_template = WPRM_Template_Manager::get_template_by_slug( $template );
			if ( ! $selected_template ) {
				$template = '';
			}
		}

		$token = WPRMP_PDF_Download::create_token( $recipe_id, $template );
		if ( ! $token ) {
			return new WP_Error( 'wprm_pdf_download_token_failed', __( 'Could not generate a PDF download URL.', 'wp-recipe-maker' ), array( 'status' => 500 ) );
		}

		$url = WPRMP_PDF_Download::url( $token );
		if ( ! $url ) {
			return new WP_Error( 'wprm_pdf_download_url_failed', __( 'Could not generate a PDF download URL.', 'wp-recipe-maker' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'url' => $url,
		) );
	}
}

WPRMP_Api_PDF_Download::init();
