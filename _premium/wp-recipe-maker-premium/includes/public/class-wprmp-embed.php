<?php
/**
 * Handle serving the embed script for external sites.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.1.1
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle serving the embed script for external sites.
 *
 * @since      10.1.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Embed {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.1.1
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'serve_embed_script' ) );
	}

	/**
	 * Serve the embed script when requested.
	 *
	 * @since 10.1.1
	 */
	public static function serve_embed_script() {
		// Check if this is a request for the embed script
		if ( ! isset( $_GET['wprm_embed_script'] ) ) {
			return;
		}

		// Check if embed API is enabled
		if ( ! WPRM_Settings::get( 'embed_api_enabled' ) ) {
			http_response_code( 403 );
			echo 'Embed API is disabled';
			exit;
		}

		// Set headers for JavaScript file
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Headers: Content-Type' );

		// Get the embed script file
		$script_file = WPRMP_DIR . 'assets/js/other/wprm-embed.js';
		
		if ( file_exists( $script_file ) ) {
			// Read and output the script
			$script_content = file_get_contents( $script_file );
			
			// Add a comment with version info
			$version = defined( 'WPRMP_VERSION' ) ? WPRMP_VERSION : '1.0.0';
			$script_content = "/* WP Recipe Maker Premium Embed Script v{$version} */\n" . $script_content;
			
			echo $script_content;
		} else {
			http_response_code( 404 );
			echo 'Embed script not found';
		}
		
		exit;
	}

	/**
	 * Get the URL for the embed script.
	 *
	 * @since 10.1.1
	 * @return string|false The embed script URL or false if not available.
	 */
	public static function get_embed_script_url() {
		if ( ! WPRM_Settings::get( 'embed_api_enabled' ) ) {
			return false;
		}

		return home_url( '/?wprm_embed_script=1' );
	}

	/**
	 * Generate a simple embed snippet for a recipe.
	 *
	 * @since 10.1.1
	 * @param int    $recipe_id Recipe ID.
	 * @param array  $options Embed options.
	 * @return string|false The embed snippet or false if not available.
	 */
	public static function generate_embed_snippet( $recipe_id, $options = array() ) {
		$script_url = self::get_embed_script_url();
		if ( ! $script_url ) {
			return false;
		}

		$defaults = array(
			'width' => '100%',
			'height' => 'auto',
			'template' => '',
			'style' => '',
			'container' => 'wprm-embed-' . $recipe_id,
		);

		$options = wp_parse_args( $options, $defaults );

		// Build the script tag
		$script_tag = '<script src="' . esc_url( $script_url ) . '"';
		$script_tag .= ' data-recipe-id="' . esc_attr( $recipe_id ) . '"';
		
		if ( $options['width'] !== $defaults['width'] ) {
			$script_tag .= ' data-width="' . esc_attr( $options['width'] ) . '"';
		}
		
		if ( $options['height'] !== $defaults['height'] ) {
			$script_tag .= ' data-height="' . esc_attr( $options['height'] ) . '"';
		}
		
		if ( ! empty( $options['template'] ) ) {
			$script_tag .= ' data-template="' . esc_attr( $options['template'] ) . '"';
		}
		
		if ( ! empty( $options['style'] ) ) {
			$script_tag .= ' data-style="' . esc_attr( $options['style'] ) . '"';
		}
		
		if ( $options['container'] !== $defaults['container'] ) {
			$script_tag .= ' data-container="' . esc_attr( $options['container'] ) . '"';
		}
		
		$script_tag .= '></script>';

		return $script_tag;
	}


}

WPRMP_Embed::init();
