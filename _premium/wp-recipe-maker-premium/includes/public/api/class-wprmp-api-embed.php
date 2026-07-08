<?php
/**
 * Handle the recipe embedding API.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.1.1
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the recipe embedding API.
 *
 * @since      10.1.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Embed {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.1.1
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    10.1.1
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_route' ) ) {
			register_rest_route( 'wp-recipe-maker/v1', '/embed/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_embed_recipe' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_embed_permission_check' ),
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_recipe_id' ),
					),
					'template' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'width' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'height' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'style' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'debug' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'format' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default' => 'full', // 'full', 'body', 'json'
					),
					'templates' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'passkey' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'signature' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'timestamp' => array(
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			) );
		}
	}

	/**
	 * Check permissions for the embed API.
	 *
	 * @since 10.1.1
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_embed_permission_check( $request ) {
		// Check if embed API is enabled
		if ( ! WPRM_Settings::get( 'embed_api_enabled' ) ) {
			return new WP_Error( 'embed_api_disabled', 'Embed API is disabled', array( 'status' => 403 ) );
		}

		$auth_method = WPRM_Settings::get( 'embed_api_auth_method' );
		
		// No authentication required
		if ( 'none' === $auth_method ) {
			return true;
		}

		// HMAC Signature authentication (recommended)
		if ( 'signature' === $auth_method ) {
			return self::verify_hmac_signature( $request );
		}

		// Simple passkey authentication (less secure)
		if ( 'passkey' === $auth_method ) {
			return self::verify_passkey( $request );
		}

		// Unknown auth method
		return new WP_Error( 'invalid_auth_method', 'Invalid authentication method', array( 'status' => 500 ) );
	}

	/**
	 * Verify HMAC signature authentication.
	 *
	 * @since 10.1.1
	 * @param WP_REST_Request $request Current request.
	 */
	private static function verify_hmac_signature( $request ) {
		$secret_key = WPRM_Settings::get( 'embed_api_secret_key' );
		
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'no_secret_key', 'Secret key not configured', array( 'status' => 500 ) );
		}

		$provided_signature = $request->get_param( 'signature' );
		$provided_timestamp = $request->get_param( 'timestamp' );
		$recipe_id = $request->get_param( 'id' );

		if ( empty( $provided_signature ) || empty( $provided_timestamp ) ) {
			return new WP_Error( 'missing_signature', 'Missing signature or timestamp', array( 'status' => 401 ) );
		}

		// Check timestamp to prevent replay attacks (24 hour window)
		$current_time = time();
		$request_time = intval( $provided_timestamp );
		
		if ( abs( $current_time - $request_time ) > 86400 ) { // 24 hours
			return new WP_Error( 'timestamp_expired', 'Request timestamp is too old', array( 'status' => 401 ) );
		}

		// Generate expected signature
		$expected_signature = self::generate_hmac_signature( $recipe_id, $provided_timestamp, $secret_key );

		// Use hash_equals to prevent timing attacks
		if ( ! hash_equals( $expected_signature, $provided_signature ) ) {
			return new WP_Error( 'invalid_signature', 'Invalid signature', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Verify simple passkey authentication.
	 *
	 * @since 10.1.1
	 * @param WP_REST_Request $request Current request.
	 */
	private static function verify_passkey( $request ) {
		$secret_key = WPRM_Settings::get( 'embed_api_secret_key' );
		
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'no_secret_key', 'Secret key not configured', array( 'status' => 500 ) );
		}

		$provided_passkey = $request->get_param( 'passkey' );
		
		if ( empty( $provided_passkey ) || ! hash_equals( $secret_key, $provided_passkey ) ) {
			return new WP_Error( 'invalid_passkey', 'Invalid or missing passkey', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Generate HMAC signature for authentication.
	 *
	 * @since 10.1.1
	 * @param string $recipe_id Recipe ID.
	 * @param string $timestamp Request timestamp.
	 * @param string $secret_key Secret key.
	 */
	public static function generate_hmac_signature( $recipe_id, $timestamp, $secret_key ) {
		$message = $recipe_id . '|' . $timestamp;
		return hash_hmac( 'sha256', $message, $secret_key );
	}

	/**
	 * Generate secure API URL for external embedding.
	 *
	 * @since 10.1.1
	 * @param int    $recipe_id Recipe ID.
	 * @param array  $params Additional parameters.
	 */
	public static function generate_secure_api_url( $recipe_id, $params = array() ) {
		// Check if embed API is enabled
		if ( ! WPRM_Settings::get( 'embed_api_enabled' ) ) {
			return false;
		}

		$base_url = home_url( '/wp-json/wp-recipe-maker/v1/embed/' . $recipe_id );
		$auth_method = WPRM_Settings::get( 'embed_api_auth_method' );
		$secret_key = WPRM_Settings::get( 'embed_api_secret_key' );

		// Add authentication parameters
		if ( 'signature' === $auth_method && ! empty( $secret_key ) ) {
			$timestamp = time();
			$signature = self::generate_hmac_signature( $recipe_id, $timestamp, $secret_key );
			$params['signature'] = $signature;
			$params['timestamp'] = $timestamp;
		} elseif ( 'passkey' === $auth_method && ! empty( $secret_key ) ) {
			$params['passkey'] = $secret_key;
		}

		// Add any additional parameters
		if ( ! empty( $params ) ) {
			$base_url = add_query_arg( $params, $base_url );
		}

		return $base_url;
	}

	/**
	 * Validate recipe ID in API call.
	 *
	 * @since 10.1.1
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function api_validate_recipe_id( $param, $request, $key ) {
		return is_numeric( $param ) && $param > 0;
	}

	/**
	 * Handle recipe embedding call to the REST API.
	 *
	 * @since 10.1.1
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_embed_recipe( $request ) {
		$recipe_id = intval( $request->get_param( 'id' ) );
		$template = $request->get_param( 'template' );
		$templates = $request->get_param( 'templates' );
		$width = $request->get_param( 'width' ) ?: '100%';
		$height = $request->get_param( 'height' ) ?: 'auto';
		$style = $request->get_param( 'style' );
		$debug = $request->get_param( 'debug' );
		$format = $request->get_param( 'format' ) ?: 'full';

		// Get the recipe
		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		
		if ( ! $recipe ) {
			return new WP_Error( 'recipe_not_found', 'Recipe not found', array( 'status' => 404 ) );
		}

		// Set up the template
		$template_obj = null;
		$available_templates = array();
		
		// Parse available templates if provided
		if ( $templates ) {
			$available_templates = array_map( 'trim', explode( ',', $templates ) );
		}
		
		if ( ! $template ) {
			$type = $recipe->type();
			$template_obj = WPRM_Template_Manager::get_template_by_type( 'single', $type );
			$template = $template_obj ? $template_obj['slug'] : '';
		} else {
			$template_obj = WPRM_Template_Manager::get_template_by_slug( $template );
		}

		// Generate the recipe HTML
		$recipe_html = self::generate_recipe_html( $recipe, $template, $available_templates, $recipe_id );
		
		// If recipe HTML is empty, try a simple fallback
		if ( empty( $recipe_html ) || strlen( $recipe_html ) < 50 ) {
			$recipe_html = '<div class="wprm-recipe"><h2>' . esc_html( $recipe->name() ) . '</h2><p>Recipe content not available</p></div>';
		}
		
		// Debug mode
		if ( $debug ) {
			$css = self::get_embed_css( $template_obj );
			$js = self::get_embed_js();
			$template_mode = WPRM_Settings::get( 'recipe_template_mode' );
			$public_css_file = WPRM_DIR . 'dist/public-' . $template_mode . '.css';
			$modern_css_file = WPRM_DIR . 'dist/public-modern.css';
			$print_css_file = WPRM_DIR . 'dist/print.css';
			$public_js_file = WPRM_DIR . 'dist/public-' . $template_mode . '.js';
			$modern_js_file = WPRM_DIR . 'dist/public-modern.js';
			$shared_js_file = WPRM_DIR . 'dist/shared.js';
			
			// Get premium asset info
			$premium_assets = array();
			if ( defined( 'WPRMP_BUNDLE' ) ) {
				$bundle = WPRMP_BUNDLE;
				$premium_assets['bundle'] = $bundle;
				
				if ( 'Elite' === $bundle ) {
					$premium_css_file = WPRMP_DIR . 'dist/public-elite.css';
					$premium_js_file = WPRMP_DIR . 'dist/public-elite.js';
				} elseif ( 'Pro' === $bundle ) {
					$premium_css_file = WPRMP_DIR . 'dist/public-pro.css';
					$premium_js_file = WPRMP_DIR . 'dist/public-pro.js';
				} else {
					$premium_css_file = WPRMP_DIR . 'dist/public-premium.css';
					$premium_js_file = WPRMP_DIR . 'dist/public-premium.js';
				}
				
				$premium_assets['css_exists'] = file_exists( $premium_css_file );
				$premium_assets['css_path'] = $premium_css_file;
				$premium_assets['js_exists'] = file_exists( $premium_js_file );
				$premium_assets['js_path'] = $premium_js_file;
				
			}
			
			$debug_info = array(
				'recipe_id' => $recipe_id,
				'recipe_name' => $recipe->name(),
				'template' => $template,
				'template_obj' => $template_obj,
				'template_mode' => $template_mode,
				'css_files' => array(
					'public_css_exists' => file_exists( $public_css_file ),
					'public_css_path' => $public_css_file,
					'modern_css_exists' => file_exists( $modern_css_file ),
					'modern_css_path' => $modern_css_file,
					'print_css_exists' => file_exists( $print_css_file ),
					'print_css_path' => $print_css_file,
				),
				'js_files' => array(
					'public_js_exists' => file_exists( $public_js_file ),
					'public_js_path' => $public_js_file,
					'modern_js_exists' => file_exists( $modern_js_file ),
					'modern_js_path' => $modern_js_file,
					'shared_js_exists' => file_exists( $shared_js_file ),
					'shared_js_path' => $shared_js_file,
				),
				'premium_assets' => $premium_assets,
				'recipe_html_length' => strlen( $recipe_html ),
				'recipe_html_preview' => substr( $recipe_html, 0, 500 ) . '...',
				'css_length' => strlen( $css ),
				'css_preview' => substr( $css, 0, 500 ) . '...',
				'js_length' => strlen( $js ),
				'js_preview' => substr( $js, 0, 500 ) . '...',
				'full_embed_html_length' => strlen( self::generate_embed_html( $recipe_html, $width, $height, $style, $template_obj ) ),
				'full_embed_html_preview' => substr( self::generate_embed_html( $recipe_html, $width, $height, $style, $template_obj ), 0, 1000 ) . '...'
			);
			
			return new WP_REST_Response( $debug_info, 200 );
		}
		
		// Handle different output formats
		$css = self::get_embed_css( $template_obj );
		$js = self::get_embed_js();

		// Set CORS headers for cross-domain embedding
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Headers: Content-Type' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		switch ( $format ) {
			case 'body':
				// Return just the recipe HTML body content
				header( 'Content-Type: text/html; charset=utf-8' );
				echo $recipe_html;
				break;

			case 'json':
				// Return structured data with separate CSS, JS, and HTML (including popup HTML)
				header( 'Content-Type: application/json; charset=utf-8' );
				$popup_html = self::get_popup_html();
				$combined_html = $recipe_html . $popup_html;
				$response_data = array(
					'html' => $combined_html,
					'css' => $css,
					'js' => $js,
					'recipe_id' => $recipe_id,
					'template' => $template,
					'width' => $width,
					'height' => $height,
					'custom_style' => $style,
				);
				
				// Include raw metadata if setting is enabled
				if ( WPRM_Settings::get( 'embed_api_include_metadata' ) ) {
					$metadata = WPRM_Metadata::sanitize_metadata( WPRM_Metadata::get_metadata( $recipe ) );
					if ( $metadata ) {
						$response_data['metadata'] = $metadata;
					}
				}
				
				echo wp_json_encode( $response_data );
				break;

			case 'full':
			default:
				// Return complete HTML document (default behavior)
				$embed_html = self::generate_embed_html( $recipe_html, $width, $height, $style, $template_obj );
				header( 'Content-Type: text/html; charset=utf-8' );
				echo $embed_html;
				break;
		}
		exit;
	}

	/**
	 * Generate the recipe HTML with all necessary assets.
	 *
	 * @since 10.1.1
	 * @param WPRM_Recipe $recipe Recipe object.
	 * @param string      $template Template slug.
	 * @param array       $available_templates Available template options.
	 * @param int         $recipe_id Recipe ID for API calls.
	 */
	private static function generate_recipe_html( $recipe, $template, $available_templates = array(), $recipe_id = 0 ) {
		// Set up the template
		$template_obj = WPRM_Template_Manager::get_template_by_slug( $template );
		if ( ! $template_obj ) {
			$type = $recipe->type();
			$template_obj = WPRM_Template_Manager::get_template_by_type( 'single', $type );
		}

		// Generate the recipe output directly (bypass shortcode to avoid asset loading issues)
		$api_url = home_url( '/wp-json/wp-recipe-maker/v1/embed/' . $recipe->id() );
		
		// Add authentication parameters based on method
		$auth_method = WPRM_Settings::get( 'embed_api_auth_method' );
		$secret_key = WPRM_Settings::get( 'embed_api_secret_key' );
		
		if ( 'signature' === $auth_method && ! empty( $secret_key ) ) {
			// Generate HMAC signature for secure authentication
			$timestamp = time();
			$signature = self::generate_hmac_signature( $recipe->id(), $timestamp, $secret_key );
			$api_url = add_query_arg( array(
				'signature' => $signature,
				'timestamp' => $timestamp,
			), $api_url );
		} elseif ( 'passkey' === $auth_method && ! empty( $secret_key ) ) {
			// Use simple passkey (less secure)
			$api_url = add_query_arg( 'passkey', $secret_key, $api_url );
		}
		
		$output = '<div id="wprm-recipe-container-' . esc_attr( $recipe->id() ) . '" class="wprm-recipe-container" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-servings="' . esc_attr( $recipe->servings() ) . '" data-api-url="' . esc_attr( $api_url ) . '">';
		
		// Add template switcher if multiple templates are available
		if ( ! empty( $available_templates ) && count( $available_templates ) > 1 ) {
			$output .= self::generate_template_switcher( $available_templates, $template, $recipe_id );
		}
		
		// Add metadata if setting is enabled
		if ( WPRM_Settings::get( 'embed_api_include_metadata' ) ) {
			$metadata_output = WPRM_Metadata::get_metadata_output( $recipe );
			if ( $metadata_output ) {
				$output .= $metadata_output;
			}
		}

		// Add template content
		$output .= WPRM_Template_Manager::get_template( $recipe, 'single', $template );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Generate the template switcher UI.
	 *
	 * @since 10.1.1
	 * @param array  $available_templates Available template options.
	 * @param string $current_template Current template slug.
	 * @param int    $recipe_id Recipe ID for API calls.
	 */
	private static function generate_template_switcher( $available_templates, $current_template, $recipe_id ) {
		$output = '<div class="wprm-template-switcher" data-recipe-id="' . esc_attr( $recipe_id ) . '">';
		$output .= '<div class="wprm-template-switcher-buttons">';
		
		// If no current template is set, use the first available template as active
		if ( empty( $current_template ) && ! empty( $available_templates ) ) {
			$current_template = $available_templates[0];
		}
		
		foreach ( $available_templates as $template_slug ) {
			$template_obj = WPRM_Template_Manager::get_template_by_slug( $template_slug );
			$template_name = $template_obj ? $template_obj['name'] : ucwords( str_replace( '-', ' ', $template_slug ) );
			$is_active = ( $template_slug === $current_template ) ? ' active' : '';
			
			$output .= '<button class="wprm-template-switcher-button' . $is_active . '" data-template="' . esc_attr( $template_slug ) . '">';
			$output .= esc_html( $template_name );
			$output .= '</button>';
		}
		
		$output .= '</div>';
		$output .= '</div>';
		
		return $output;
	}

	/**
	 * Generate the complete embed HTML document.
	 *
	 * @since 10.1.1
	 * @param string $recipe_html The recipe HTML content.
	 * @param string $width Container width.
	 * @param string $height Container height.
	 * @param string $custom_style Additional custom styles.
	 * @param object $template_obj Template object for CSS.
	 */
	private static function generate_embed_html( $recipe_html, $width, $height, $custom_style, $template_obj = null ) {
		// Get all necessary CSS
		$css = self::get_embed_css( $template_obj );
		
		// Get all necessary JavaScript
		$js = self::get_embed_js();

		// Get popup HTML that would normally be output in wp_footer
		$popup_html = self::get_popup_html();

		$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Card</title>
    <style>
        ' . $css . '
        
        /* Embed container styles */
        .wprm-embed-container {
            width: ' . esc_attr( $width ) . ';
            height: ' . esc_attr( $height ) . ';
            max-width: 100%;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        ' . ( $custom_style ? $custom_style : '' ) . '
    </style>
</head>
<body>
    <div class="wprm-embed-container">
        ' . $recipe_html . '
    </div>
    
    ' . $popup_html . '
    
    <script>
        ' . $js . '
    </script>
</body>
</html>';

		return $html;
	}

	/**
	 * Get all necessary CSS for the embed.
	 *
	 * @since 10.1.1
	 * @param object $template_obj Template object for CSS.
	 */
	private static function get_embed_css( $template_obj = null ) {
		$css = '';

		// Get template CSS if template object is provided
		if ( $template_obj ) {
			$css .= WPRM_Template_Manager::get_template_css( $template_obj );
		}

		// Get custom CSS
		$css .= WPRM_Assets::get_custom_css( 'recipe' );
		
		// Always try to include the main public CSS files
		$template_mode = WPRM_Settings::get( 'recipe_template_mode' );
		
		// Include the main public CSS file
		$public_css_file = WPRM_DIR . 'dist/public-' . $template_mode . '.css';
		if ( file_exists( $public_css_file ) ) {
			$css .= file_get_contents( $public_css_file );
		}
		
		// Also try the modern CSS as fallback
		$modern_css_file = WPRM_DIR . 'dist/public-modern.css';
		if ( file_exists( $modern_css_file ) && $public_css_file !== $modern_css_file ) {
			$css .= file_get_contents( $modern_css_file );
		}
		
		// Include print CSS for better styling
		$print_css_file = WPRM_DIR . 'dist/print.css';
		if ( file_exists( $print_css_file ) ) {
			$css .= file_get_contents( $print_css_file );
		}

		// Include premium assets based on bundle level
		if ( defined( 'WPRMP_BUNDLE' ) ) {
			$bundle = WPRMP_BUNDLE;
			
			// Include premium CSS files based on bundle
			if ( 'Elite' === $bundle ) {
				$premium_css_file = WPRMP_DIR . 'dist/public-elite.css';
				if ( file_exists( $premium_css_file ) ) {
					$css .= file_get_contents( $premium_css_file );
				}
			} elseif ( 'Pro' === $bundle ) {
				$premium_css_file = WPRMP_DIR . 'dist/public-pro.css';
				if ( file_exists( $premium_css_file ) ) {
					$css .= file_get_contents( $premium_css_file );
				}
			} else {
				// Premium bundle
				$premium_css_file = WPRMP_DIR . 'dist/public-premium.css';
				if ( file_exists( $premium_css_file ) ) {
					$css .= file_get_contents( $premium_css_file );
				}
			}
			
		}

		// Add comprehensive base styles for embed
		$css .= '
		/* Reset and base styles */
		* {
			box-sizing: border-box;
		}
		
		body {
			margin: 0;
			padding: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			line-height: 1.6;
			color: #333;
		}
		
		.wprm-embed-container {
			width: 100%;
			max-width: 100%;
			margin: 0;
			padding: 0;
			font-family: inherit;
		}
		
		/* Template Switcher Styles */
		.wprm-template-switcher {
			margin-bottom: 20px;
			text-align: center;
		}
		
		.wprm-template-switcher-buttons {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			justify-content: center;
			margin-bottom: 15px;
		}
		
		.wprm-template-switcher-button {
			background: #f8f9fa;
			border: 2px solid #e9ecef;
			border-radius: 6px;
			color: #495057;
			cursor: pointer;
			font-size: 14px;
			font-weight: 500;
			padding: 8px 16px;
			transition: all 0.2s ease;
		}
		
		.wprm-template-switcher-button:hover {
			background: #e9ecef;
			border-color: #dee2e6;
			color: #212529;
		}
		
		.wprm-template-switcher-button.active {
			background: #007cba;
			border-color: #007cba;
			color: #ffffff;
		}
		
		.wprm-template-switcher-button:focus {
			outline: none;
			box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.25);
		}
		
		.wprm-template-switcher-loading {
			opacity: 0.6;
			pointer-events: none;
		}';

		return $css;
	}

	/**
	 * Get all necessary JavaScript for the embed.
	 *
	 * @since 10.1.1
	 */
	private static function get_embed_js() {
		$js = '';

		// First, add the localized JavaScript variables that the JS files expect
		$js .= self::get_localized_js_variables();

		$template_mode = WPRM_Settings::get( 'recipe_template_mode' );
		
		// Include the main public JS file
		$public_js_file = WPRM_DIR . 'dist/public-' . $template_mode . '.js';
		if ( file_exists( $public_js_file ) ) {
			$js .= file_get_contents( $public_js_file );
		}
		
		// Also try the modern JS as fallback
		$modern_js_file = WPRM_DIR . 'dist/public-modern.js';
		if ( file_exists( $modern_js_file ) && $public_js_file !== $modern_js_file ) {
			$js .= file_get_contents( $modern_js_file );
		}
		
		// Include shared JS
		$shared_js_file = WPRM_DIR . 'dist/shared.js';
		if ( file_exists( $shared_js_file ) ) {
			$js .= file_get_contents( $shared_js_file );
		}

		// Include premium JavaScript based on bundle level
		if ( defined( 'WPRMP_BUNDLE' ) ) {
			$bundle = WPRMP_BUNDLE;
			
			// Include premium JS files based on bundle
			if ( 'Elite' === $bundle ) {
				$premium_js_file = WPRMP_DIR . 'dist/public-elite.js';
				if ( file_exists( $premium_js_file ) ) {
					$js .= file_get_contents( $premium_js_file );
				}
			} elseif ( 'Pro' === $bundle ) {
				$premium_js_file = WPRMP_DIR . 'dist/public-pro.js';
				if ( file_exists( $premium_js_file ) ) {
					$js .= file_get_contents( $premium_js_file );
				}
			} else {
				// Premium bundle
				$premium_js_file = WPRMP_DIR . 'dist/public-premium.js';
				if ( file_exists( $premium_js_file ) ) {
					$js .= file_get_contents( $premium_js_file );
				}
			}
			
		}

		// Add template switcher JavaScript
		$js .= self::get_template_switcher_js();

		// Add size conditions JavaScript if enabled
		if ( WPRM_Settings::get( 'load_size_conditions_js' ) ) {
			$size_conditions_js_file = WPRM_DIR . '/assets/js/other/size-conditions-min.js';
			if ( file_exists( $size_conditions_js_file ) ) {
				$js .= file_get_contents( $size_conditions_js_file );
			}
		}

		return $js;
	}

	/**
	 * Get template switcher JavaScript functionality.
	 *
	 * @since 10.1.1
	 */
	private static function get_template_switcher_js() {
		return '
		// Template Switcher Functionality
		(function() {
			
			// Wait for DOM to be ready
			function ready(fn) {
				if (document.readyState !== "loading") {
					// Add a small delay to ensure all content is loaded
					setTimeout(fn, 100);
				} else {
					document.addEventListener("DOMContentLoaded", function() {
						setTimeout(fn, 100);
					});
				}
			}
			
			ready(function() {
				
				// Find all template switchers
				const switchers = document.querySelectorAll(".wprm-template-switcher");
				
				switchers.forEach(function(switcher, index) {
					
					const buttons = switcher.querySelectorAll(".wprm-template-switcher-button");
					const recipeId = switcher.getAttribute("data-recipe-id");
					
					buttons.forEach(function(button, btnIndex) {
						const template = button.getAttribute("data-template");
						
						button.addEventListener("click", function() {
							if (!template) {
								return;
							}
							
							// Don\'t switch if already active
							if (this.classList.contains("active")) {
								return;
							}
							
							
							// Add loading state
							switcher.classList.add("wprm-template-switcher-loading");
							
							// Get the original API URL from the container data attribute
							const container = document.querySelector("#wprm-recipe-container-" + recipeId);
							const apiUrl = container ? container.getAttribute("data-api-url") : null;
							
							
							if (!apiUrl) {
								switcher.classList.remove("wprm-template-switcher-loading");
								return;
							}
							
							// Build new URL with template parameter
							const url = new URL(apiUrl);
							url.searchParams.set("template", template);
							
							// Preserve existing authentication parameters
							const existingPasskey = url.searchParams.get("passkey");
							const existingSignature = url.searchParams.get("signature");
							const existingTimestamp = url.searchParams.get("timestamp");
							
							if (existingPasskey) {
								url.searchParams.set("passkey", existingPasskey);
							}
							if (existingSignature) {
								url.searchParams.set("signature", existingSignature);
							}
							if (existingTimestamp) {
								url.searchParams.set("timestamp", existingTimestamp);
							}
							
							const newUrl = url.toString();
							
							
							// Make API call to get new template (using JSON format)
							fetch(newUrl + "&format=json")
								.then(function(response) {
									if (!response.ok) {
										throw new Error("Network response was not ok: " + response.status);
									}
									return response.json();
								})
								.then(function(data) {
									
									// Update CSS if new CSS is provided
									if (data.css) {
										
										// Find existing WPRM style element or create new one
										let styleElement = document.querySelector("#wprm-embed-styles");
										if (!styleElement) {
											styleElement = document.createElement("style");
											styleElement.id = "wprm-embed-styles";
											document.head.appendChild(styleElement);
										}
										
										// Update the CSS content
										styleElement.textContent = data.css;
									}
									
									// Find the current recipe container
									const currentContainer = document.querySelector("#wprm-recipe-container-" + recipeId);
									if (currentContainer) {
										
										// Parse the new HTML to extract just the recipe content (without template switcher)
										const parser = new DOMParser();
										const doc = parser.parseFromString(data.html, "text/html");
										const newRecipeContent = doc.querySelector(".wprm-recipe-container");
										
										if (newRecipeContent) {
											// Find the template switcher in the current container
											const existingSwitcher = currentContainer.querySelector(".wprm-template-switcher");
											
											// Replace only the recipe content, preserving the template switcher
											const recipeContent = currentContainer.querySelector(".wprm-recipe");
											if (recipeContent) {
												// Replace just the recipe content
												recipeContent.outerHTML = newRecipeContent.querySelector(".wprm-recipe").outerHTML;
											} else {
												// If no .wprm-recipe found, replace everything except the switcher
												const newContent = newRecipeContent.innerHTML;
												// Remove the template switcher from new content if it exists
												const tempDiv = document.createElement("div");
												tempDiv.innerHTML = newContent;
												const newSwitcher = tempDiv.querySelector(".wprm-template-switcher");
												if (newSwitcher) {
													newSwitcher.remove();
												}
												
												// Replace content but preserve existing switcher
												if (existingSwitcher) {
													// Keep the switcher, replace everything else
													const allChildren = Array.from(currentContainer.children);
													allChildren.forEach(child => {
														if (!child.classList.contains("wprm-template-switcher")) {
															child.remove();
														}
													});
													currentContainer.insertAdjacentHTML("beforeend", tempDiv.innerHTML);
												} else {
													// No existing switcher, replace everything
													currentContainer.innerHTML = tempDiv.innerHTML;
												}
											}
										}
										
										// Update active button
										buttons.forEach(function(btn) {
											btn.classList.remove("active");
										});
										button.classList.add("active");
										
									} else {
									}
								})
								.catch(function(error) {
									alert("Error loading template. Please try again.");
								})
								.finally(function() {
									// Remove loading state
									switcher.classList.remove("wprm-template-switcher-loading");
								});
						});
					});
				});
			});
		})();
		';
	}

	/**
	 * Get popup HTML that would normally be output in wp_footer.
	 *
	 * @since 10.1.1
	 */
	private static function get_popup_html() {
		// Capture the popup HTML that would normally be output in wp_footer
		ob_start();
		WPRM_Popup::output_html_for_all_modals();
		$popup_html = ob_get_contents();
		ob_end_clean();

		return $popup_html;
	}

	/**
	 * Get localized JavaScript variables for the embed.
	 *
	 * @since 10.1.1
	 */
	private static function get_localized_js_variables() {
		// Reuse the existing localization methods from WPRM_Assets and WPRMP_Assets
		$wprm_public_data = WPRM_Assets::localize_public();
		$wprmp_public_data = WPRMP_Assets::localize_public();

		// Override post_id for embed context (no post context in embed)
		$wprm_public_data['post_id'] = 0;

		// Generate the JavaScript code to define the required variables
		$js = '
		// Define wprm_public and wprmp_public variables for embed context
		window.wprm_public = ' . wp_json_encode( $wprm_public_data ) . ';
		window.wprmp_public = ' . wp_json_encode( $wprmp_public_data ) . ';';

		return $js;
	}

}

WPRMP_Api_Embed::init();
