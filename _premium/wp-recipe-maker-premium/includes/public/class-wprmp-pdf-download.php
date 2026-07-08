<?php
/**
 * Handle dedicated recipe PDF download functionality.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle dedicated recipe PDF download functionality.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_PDF_Download {
	/**
	 * Prefix for PDF download token transients.
	 *
	 * @since	10.6.0
	 */
	const TRANSIENT_PREFIX = 'wprm_pdf_download_';

	/**
	 * Expiration in seconds for PDF download tokens.
	 *
	 * @since	10.6.0
	 */
	const TOKEN_TTL = 600;

	/**
	 * Register actions and filters.
	 *
	 * @since    10.6.0
	 */
	public static function init() {
		add_filter( 'wprm_print_arg_options', array( __CLASS__, 'add_print_arg_option' ) );
		add_filter( 'wprm_print_is_pdf_download_page', array( __CLASS__, 'is_pdf_download_page' ), 10, 2 );
		add_filter( 'wprm_print_output_template_file', array( __CLASS__, 'print_output_template_file' ), 10, 3 );
		add_filter( 'wprm_print_custom_css_setting', array( __CLASS__, 'print_custom_css_setting' ), 10, 3 );
		add_filter( 'wprm_print_output', array( __CLASS__, 'output_first' ), 20, 2 );
	}

	/**
	 * Add dedicated PDF argument to print page options.
	 *
	 * @since	10.6.0
	 * @param	array $options Existing print argument options.
	 */
	public static function add_print_arg_option( $options ) {
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$options[] = 'pdf';
		return array_values( array_unique( $options ) );
	}

	/**
	 * Check if this is the dedicated PDF download page.
	 *
	 * @since	10.6.0
	 * @param	bool  $is_pdf_download_page Current value.
	 * @param	array $args Print page arguments.
	 */
	public static function is_pdf_download_page( $is_pdf_download_page, $args ) {
		return is_array( $args ) && isset( $args[0] ) && 'pdf' === $args[0];
	}

	/**
	 * Use dedicated template file for PDF download output.
	 *
	 * @since	10.6.0
	 * @param	string $template_file Current print template file.
	 * @param	array  $output        Print page output.
	 * @param	array  $args          Print page arguments.
	 */
	public static function print_output_template_file( $template_file, $output, $args ) {
		if ( isset( $output['type'] ) && 'pdf-download' === $output['type'] ) {
			return apply_filters( 'wprm_pdf_download_file', WPRMP_DIR . 'templates/public/pdf-download.php', $output, $args );
		}

		return $template_file;
	}

	/**
	 * Use dedicated CSS setting for PDF download page.
	 *
	 * @since	10.6.0
	 * @param	string $setting Setting key.
	 * @param	array  $args    Print page arguments.
	 * @param	array  $output  Print page output.
	 */
	public static function print_custom_css_setting( $setting, $args, $output ) {
		if ( self::is_pdf_download_page( false, $args ) || ( isset( $output['type'] ) && 'pdf-download' === $output['type'] ) ) {
			return 'pdf_download_css';
		}

		return $setting;
	}

	/**
	 * Get output for dedicated PDF download page.
	 *
	 * @since	10.6.0
	 * @param	array $output Current print page output.
	 * @param	array $args   Print page arguments.
	 */
	public static function output_first( $output, $args ) {
		if ( ! self::is_pdf_download_page( false, $args ) ) {
			return $output;
		}

		$token = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
		$pdf_data = self::consume_token( $token );
		$recipe_id = isset( $pdf_data['recipe_id'] ) ? intval( $pdf_data['recipe_id'] ) : 0;

		if ( ! $recipe_id || WPRM_POST_TYPE !== get_post_type( $recipe_id ) ) {
			return $output;
		}

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe || ! WPRM_Print::has_permission( $recipe ) ) {
			return $output;
		}

		// Get template to output.
		$template = false;
		if ( isset( $pdf_data['template'] ) && $pdf_data['template'] ) {
			$template_slug = sanitize_key( $pdf_data['template'] );
			$template = WPRM_Template_Manager::get_template_by_slug( $template_slug );
		}

		// Use default PDF template if no specific template set.
		if ( ! $template ) {
			$template = WPRM_Template_Manager::get_template_by_type( 'pdf', $recipe->type() );
		}

		if ( ! $template || ! isset( $template['slug'] ) ) {
			return $output;
		}

		// Add styling for this recipe's PDF template.
		$output['assets'][] = array(
			'type' => 'custom',
			'html' => '<style type="text/css">' . WPRM_Template_Manager::get_template_css( $template ) . '</style>',
		);

		$output['type'] = 'pdf-download';
		$output['recipe'] = $recipe;
		$output['recipe_ids'][] = $recipe_id;
		$output['title'] = $recipe->name() . ' - ' . get_bloginfo( 'name' );
		$output['url'] = $recipe->permalink();
		$output['html'] = '<div id="wprm-print-recipe-0" data-recipe-id="' . $recipe_id . '" class="wprm-print-recipe wprm-print-recipe-' . $recipe_id . '" data-servings="' . esc_attr( $recipe->servings() ) . '">' . WPRM_Template_Manager::get_template( $recipe, 'pdf', $template['slug'] ) . '</div>';

		return $output;
	}

	/**
	 * Create token to access the dedicated PDF download page.
	 *
	 * @since	10.6.0
	 * @param	int    $recipe_id      Recipe to create token for.
	 * @param	string $template_slug Optional template slug to force.
	 */
	public static function create_token( $recipe_id, $template_slug = '' ) {
		$recipe_id = intval( $recipe_id );
		if ( ! $recipe_id ) {
			return false;
		}

		$template_slug = $template_slug ? sanitize_key( $template_slug ) : '';

		try {
			$token = bin2hex( random_bytes( 16 ) );
		} catch ( Exception $e ) {
			$token = md5( uniqid( '', true ) . wp_rand() );
		}

		$stored = set_transient(
			self::transient_key( $token ),
			array(
				'recipe_id' => $recipe_id,
				'template' => $template_slug,
				'created_at' => time(),
			),
			self::TOKEN_TTL
		);

		return $stored ? $token : false;
	}

	/**
	 * Consume and invalidate a PDF download token.
	 *
	 * @since	10.6.0
	 * @param	string $token Token to validate.
	 */
	public static function consume_token( $token ) {
		$token = sanitize_key( $token );
		if ( ! $token ) {
			return false;
		}

		$transient_key = self::transient_key( $token );
		$data = get_transient( $transient_key );

		// One-time token: invalidate immediately.
		delete_transient( $transient_key );

		if ( ! is_array( $data ) ) {
			return false;
		}

		return $data;
	}

	/**
	 * Get URL for the dedicated PDF download page.
	 *
	 * @since	10.6.0
	 * @param	string $token Token to include in URL.
	 */
	public static function url( $token ) {
		$token = sanitize_key( $token );
		if ( ! $token ) {
			return false;
		}

		$home_url = WPRM_Compatibility::get_home_url();
		$query_params = false;

		if ( false !== strpos( $home_url, '?' ) ) {
			$home_url_parts = explode( '?', $home_url, 2 );

			$home_url = trailingslashit( $home_url_parts[0] );
			$query_params = $home_url_parts[1];
		}

		if ( get_option( 'permalink_structure' ) ) {
			$pdf_download_url = $home_url . WPRM_Print::slug() . '/pdf/' . $token;

			if ( $query_params ) {
				$pdf_download_url .= '?' . $query_params;
			}
		} else {
			$pdf_download_url = $home_url . '?' . WPRM_Print::slug() . '=pdf&' . $token;

			if ( $query_params ) {
				$pdf_download_url .= '&' . $query_params;
			}
		}

		return $pdf_download_url;
	}

	/**
	 * Get transient key for a PDF download token.
	 *
	 * @since	10.6.0
	 * @param	string $token Token.
	 */
	private static function transient_key( $token ) {
		return self::TRANSIENT_PREFIX . sanitize_key( $token );
	}
}

WPRMP_PDF_Download::init();
