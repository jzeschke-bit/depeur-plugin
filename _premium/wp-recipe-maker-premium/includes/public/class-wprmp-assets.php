<?php
/**
 * Responsible for loading the plugin assets.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for loading the plugin assets.
 *
 * @since      1.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Assets {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.6.0
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
		add_action( 'amp_post_template_css', array( __CLASS__, 'amp_style' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'block_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'block_assets' ) );

		add_action( 'wprm_load_assets', array( __CLASS__, 'load' ) );

		// Hook into free plugin's translation filters
		add_filter( 'wprm_translations_admin', array( __CLASS__, 'add_premium_translations' ) );
		add_filter( 'wprm_translations_public', array( __CLASS__, 'add_premium_translations' ) );
	}

	/**
	 * Enqueue stylesheets and scripts.
	 *
	 * @since    1.6.0
	 */
	public static function enqueue() {
		$filename = 'public-' . strtolower( WPRMP_BUNDLE );
		wp_register_style( 'wprmp-public', WPRMP_URL . 'dist/' . $filename . '.css', array(), WPRMP_VERSION, 'all' );

		// Only include scripts when not AMP page.
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) {
			wp_register_script( 'wprmp-public', WPRMP_URL . 'dist/' . $filename . '.js', array( 'wprm-public' ), WPRMP_VERSION, true );
			wp_localize_script( 'wprmp-public', 'wprmp_public', self::localize_public() );
		}
	}

	/**
	 * Array for premium public JS file.
	 *
	 * @since    10.2.0
	 */
	public static function localize_public() {
		// Get Timer Icons.
		ob_start();
		include( WPRM_DIR . 'assets/icons/timer/pause.svg' );
		$pause = ob_get_contents();
		ob_end_clean();

		ob_start();
		include( WPRM_DIR . 'assets/icons/timer/play.svg' );
		$play = ob_get_contents();
		ob_end_clean();

		ob_start();
		include( WPRM_DIR . 'assets/icons/timer/close.svg' );
		$close = ob_get_contents();
		ob_end_clean();

		$data = array(
			'user' => get_current_user_id(),
			'endpoints' => array(
				'favorites' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/favorites' ), '/' ),
				'private_notes' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/private-notes' ), '/' ),
				'user_rating' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/user-rating' ), '/' ),
			),
			'favorites' => array(
				'storage_key' => WPRMP_Favorites::KEY,
				'merge_session_key' => WPRMP_Favorites::KEY . '-merged',
			),
			'settings' => array(
				'recipe_template_mode' => WPRM_Settings::get( 'recipe_template_mode' ),
				'features_adjustable_servings' => WPRM_Settings::get( 'features_adjustable_servings' ),
				'adjustable_servings_url' => WPRM_Settings::get( 'adjustable_servings_url' ),
				'adjustable_servings_url_param' => WPRM_Settings::get( 'adjustable_servings_url_param' ),
				'adjustable_servings_round_to_decimals' => WPRM_Settings::get( 'adjustable_servings_round_to_decimals' ),
				'unit_conversion_remember' => WPRM_Settings::get( 'unit_conversion_remember' ),
				'unit_conversion_temperature' => WPRM_Settings::get( 'unit_conversion_temperature' ),
				'unit_conversion_temperature_precision' => WPRM_Settings::get( 'unit_conversion_temperature_precision' ),
				'unit_conversion_system_1_temperature' => WPRM_Settings::get( 'unit_conversion_system_1_temperature' ),
				'unit_conversion_system_2_temperature' => WPRM_Settings::get( 'unit_conversion_system_2_temperature' ),
				'unit_conversion_advanced_servings_conversion' => WPRM_Settings::get( 'unit_conversion_advanced_servings_conversion' ),
				'unit_conversion_system_1_length_unit' => WPRM_Settings::get( 'unit_conversion_system_1_length_unit' ),
				'unit_conversion_system_2_length_unit' => WPRM_Settings::get( 'unit_conversion_system_2_length_unit' ),
				'fractions_enabled' => WPRM_Settings::get( 'fractions_enabled' ),
				'fractions_use_mixed' => WPRM_Settings::get( 'fractions_use_mixed' ),
				'fractions_use_symbols' => WPRM_Settings::get( 'fractions_use_symbols' ),
				'fractions_max_denominator' => WPRM_Settings::get( 'fractions_max_denominator' ),
				'unit_conversion_system_1_fractions' => WPRM_Settings::get( 'unit_conversion_system_1_fractions' ),
				'unit_conversion_system_2_fractions' => WPRM_Settings::get( 'unit_conversion_system_2_fractions' ),
				'unit_conversion_enabled' => WPRM_Settings::get( 'unit_conversion_enabled' ),
				'decimal_separator' => WPRM_Settings::get( 'decimal_separator' ),
				'features_comment_ratings' => WPRM_Settings::get( 'features_comment_ratings' ),
				'features_user_ratings' => WPRM_Settings::get( 'features_user_ratings' ),
				'user_ratings_type' => WPRM_Settings::get( 'user_ratings_type' ),
				'user_ratings_force_comment_scroll_to_smooth' => WPRM_Settings::get( 'user_ratings_force_comment_scroll_to_smooth' ),
				'user_ratings_modal_title' => WPRM_Settings::get( 'user_ratings_modal_title' ),
				'user_ratings_thank_you_title' => WPRM_Settings::get( 'user_ratings_thank_you_title' ),
				'user_ratings_thank_you_message_with_comment' => WPRM_Settings::get( 'user_ratings_thank_you_message_with_comment' ),
				'user_ratings_problem_message' => WPRM_Settings::get( 'user_ratings_problem_message' ),
				'user_ratings_force_comment_scroll_to' => WPRM_Settings::get( 'user_ratings_force_comment_scroll_to' ),
				'user_ratings_open_url_parameter' => WPRM_Settings::get( 'user_ratings_open_url_parameter' ),
				'user_ratings_require_comment' => WPRM_Settings::get( 'user_ratings_require_comment' ),
				'user_ratings_require_name' => WPRM_Settings::get( 'user_ratings_require_name' ),
				'user_ratings_require_email' => WPRM_Settings::get( 'user_ratings_require_email' ),
				'user_ratings_comment_suggestions_enabled' => WPRM_Settings::get( 'user_ratings_comment_suggestions_enabled' ),
				'rating_details_zero' => WPRM_Settings::get( 'rating_details_zero' ),
				'rating_details_one' => WPRM_Settings::get( 'rating_details_one' ),
				'rating_details_multiple' => WPRM_Settings::get( 'rating_details_multiple' ),
				'rating_details_user_voted' => WPRM_Settings::get( 'rating_details_user_voted' ),
				'rating_details_user_not_voted' => WPRM_Settings::get( 'rating_details_user_not_voted' ),
				'servings_changer_display' => WPRM_Settings::get( 'servings_changer_display' ),
				'template_ingredient_list_style' => WPRM_Settings::get( 'template_ingredient_list_style' ),
				'template_instruction_list_style' => WPRM_Settings::get( 'template_instruction_list_style' ),
				'template_color_icon' => WPRM_Settings::get( 'template_color_icon' ),
			),
			'timer' => array(
				'sound_file' => WPRMP_URL . 'assets/sounds/alarm.mp3',
				'text' => array(
					'start_timer' => __( 'Click to Start Timer', 'wp-recipe-maker-premium' ),
				),
				'icons' => array(
					'pause' => $pause,
					'play' => $play,
					'close' => $close,
				),
			),
			'recipe_submission' => array(
				'max_file_size' => wp_max_upload_size(),
				'text' => array(
					'image_size' => __( 'The file is too large. Maximum size:', 'wp-recipe-maker-premium' ),
				),
			),
		);

		// Apply filters to allow addons to add their data
		return apply_filters( 'wprmp_localize_public', $data );
	}

	/**
	 * Actually load assets.
	 *
	 * @since	5.5.0
	 */
	public static function load() {
		wp_enqueue_style( 'wprmp-public' );

		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) {
			wp_enqueue_script( 'wprmp-public' );
		}
	}

	/**
	 * Enqueue Gutenberg block editor assets (scripts only).
	 *
	 * @since    4.0.0
	 */
	public static function block_editor_assets() {
		$filename = 'blocks-' . strtolower( WPRMP_BUNDLE );
		wp_enqueue_script( 'wprmp-blocks', WPRMP_URL . 'dist/' . $filename . '.js', array(), WPRMP_VERSION, true );
	}

	/**
	 * Enqueue Gutenberg block assets (styles for both editor and frontend).
	 * This hook loads styles in both contexts, including the iframe editor (API version 3).
	 *
	 * @since    4.0.0
	 */
	public static function block_assets() {
		$filename = 'public-' . strtolower( WPRMP_BUNDLE );
		$public_css = WPRMP_URL . 'dist/' . $filename . '.css';
		wp_enqueue_style( 'wprmp-public', $public_css, array(), WPRMP_VERSION, 'all' );
	}

	/**
	 * Enqueue admin stylesheets and scripts.
	 *
	 * @since    2.0.0
	 */
	public static function enqueue_admin() {
		if ( method_exists( 'WPRM_Assets', 'should_load_admin_assets' ) && ! WPRM_Assets::should_load_admin_assets() ) {
			return;
		}

		$filename = 'admin-' . strtolower( WPRMP_BUNDLE );
		wp_enqueue_style( 'wprmp-admin', WPRMP_URL . 'dist/' . $filename . '.css', array(), WPRMP_VERSION, 'all' );
		wp_enqueue_script( 'wprmp-admin', WPRMP_URL . 'dist/' . $filename . '.js', array( 'wprm-shared', 'jquery', 'jquery-ui-sortable' ), WPRMP_VERSION, true );

		$count_posts = wp_count_posts( WPRM_POST_TYPE );

		wp_localize_script( 'wprmp-admin', 'wprmp_admin', apply_filters( 'wprmp_localize_admin',
			array(
				'settings' => array(
					'nutrition_facts_calculation_round_to_decimals' => WPRM_Settings::get( 'nutrition_facts_calculation_round_to_decimals' ),
					'nutrition_facts_calculation_ignore_small_quantity' => WPRM_Settings::get( 'nutrition_facts_calculation_ignore_small_quantity' ),
					'unit_conversion_round_to_decimals' => WPRM_Settings::get( 'unit_conversion_round_to_decimals' ),
					'fractions_enabled' => WPRM_Settings::get( 'fractions_enabled' ),
					'fractions_use_mixed' => WPRM_Settings::get( 'fractions_use_mixed' ),
					'fractions_use_symbols' => WPRM_Settings::get( 'fractions_use_symbols' ),
					'fractions_max_denominator' => WPRM_Settings::get( 'fractions_max_denominator' ),
					'unit_conversion_system_1_fractions' => WPRM_Settings::get( 'unit_conversion_system_1_fractions' ),
					'unit_conversion_system_2_fractions' => WPRM_Settings::get( 'unit_conversion_system_2_fractions' ),
					'decimal_separator' => WPRM_Settings::get( 'decimal_separator' ),
				),
				'manage' => array(
					'collections_url' => admin_url( 'admin.php?page=wprm_recipe_collections' ),
					'recipe_collections_link' => WPRM_Settings::get( 'recipe_collections_link' ),
					'recipe_submissions' => isset( $count_posts->pending ) ? $count_posts->pending : 0,
				),
				'endpoints' => array(
					'ai_assistant' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/ai-assistant' ), '/' ),
					'amazon' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/amazon' ), '/' ),
					'collections' => WPRM_Addons::is_active( 'elite' ) ? get_rest_url( null, 'wp/v2/' . WPRMPRC_POST_TYPE ) : '',
					'saved_collection' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/saved-collection' ), '/' ),
					'nutrient' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/nutrient' ), '/' ),
					'nutrition' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/nutrition' ), '/' ),
					'product' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/product' ), '/' ),
					'unit_conversion' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/unit-conversion' ), '/' ),
					'equipment_affiliate' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/equipment-affiliate' ), '/' ),
					'ingredient_links' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/ingredient-links' ), '/' ),
					'custom_fields' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/custom-fields' ), '/' ),
					'recipe_submission' => rtrim( get_rest_url( null, 'wp-recipe-maker/v1/recipe-submission' ), '/' ),
				),
			)
		) );
	}

	/**
	 * Enqueue template style on AMP pages.
	 *
	 * @since    2.0.1
	 */
	public static function amp_style() {
		// Get AMP specific CSS.
		ob_start();
		include( WPRMP_DIR . 'dist/amp.css' );
		$css = ob_get_contents();
		ob_end_clean();

		// Get rid of !important flags.
		$css = str_ireplace( ' !important', '', $css );
		$css = str_ireplace( '!important', '', $css );

		echo $css;
	}

	/**
	 * Get JS translations for Premium plugin.
	 *
	 * @since    5.9.0
	 * @param    string $type Type of translations ('admin' or 'public').
	 * @return   array  Translations array.
	 */
	public static function get_translations( $type ) {
		$translations = array();

		// Load premium translations (same file for both admin and public)
		if ( file_exists( WPRMP_DIR . 'templates/translations.php' ) ) {
			include( WPRMP_DIR . 'templates/translations.php' );
			$translations = isset( $translations ) ? $translations : array();
		}

		return apply_filters( 'wprmp_translations_' . $type, $translations );
	}

	/**
	 * Add premium translations to free plugin translations.
	 *
	 * @since    5.9.0
	 * @param    array $translations Existing translations from free plugin.
	 * @return   array Merged translations.
	 */
	public static function add_premium_translations( $translations ) {
		$type = current_filter() === 'wprm_translations_admin' ? 'admin' : 'public';
		$premium_translations = self::get_translations( $type );
		return array_merge( $translations, $premium_translations );
	}
}

WPRMP_Assets::init();
