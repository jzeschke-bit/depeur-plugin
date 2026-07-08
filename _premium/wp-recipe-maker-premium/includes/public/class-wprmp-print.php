<?php
/**
 * Handle Premium features for recipe printing.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle Premium features for recipe printing.
 *
 * @since      6.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Print {

	/**
	 * Register actions and filters.
	 *
	 * @since    6.1.0
	 */
	public static function init() {
		add_filter( 'wprm_print_output', array( __CLASS__, 'output' ), 2, 2 );
		add_filter( 'wprm_print_header_images', array( __CLASS__, 'print_header_images' ), 9, 2 );
		add_filter( 'wprm_print_header_buttons', array( __CLASS__, 'print_header_buttons' ), 10, 2 );
	}

	/**
	 * Get output for the print page.
	 *
	 * @since    6.1.0
	 * @param	array $output 	Current output for the print page.
	 * @param	array $args	 	Arguments for the print page.
	 */
	public static function output( $output, $args ) {
		$is_pdf_download_page = apply_filters( 'wprm_print_is_pdf_download_page', false, $args );

		// Load premium styling.
		$output['assets'][] = array(
			'type' => 'css',
			'url' => WPRMP_URL . 'dist/public-' . strtolower( WPRMP_BUNDLE ) . '.css',
			'version' => WPRMP_VERSION,
		);
		$output['assets'][] = array(
			'type' => 'css',
			'url' => WPRMP_URL . 'dist/print.css',
			'version' => WPRMP_VERSION,
		);
		$output['assets'][] = array(
			'type' => 'js',
			'url' => WPRMP_URL . 'dist/print.js',
			'version' => WPRMP_VERSION,
		);
		$output['assets'][] = array(
			'type' => 'custom',
			'html' => self::print_accent_color_styling(),
		);

		if ( 'recipe' === $output['type'] || $is_pdf_download_page ) {
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprmp_public = { settings : ' . wp_json_encode( self::single_recipe_print_settings() ) . ' };</script>',
			);
		}

		if ( 'recipe' === $output['type'] ) {
			$recipe = $output['recipe'];

			// Nutrition toggle header.
			$output['header'] .= self::get_nutrition_toggle_header( $recipe );

			// Adjustable servings header.
			$servings = $recipe ? $recipe->servings() : false;

			if ( $servings && WPRM_Settings::get( 'print_adjustable_servings' ) ) {
				$output['header'] .= '<div class="wprm-print-spacer"></div>';
				$output['header'] .= '<div id="wprm-print-servings-container">';
				$output['header'] .= '<span class="wprm-print-servings-decrement wprm-print-servings-change">–</span><input id="wprm-print-servings" type="text" value="' . esc_attr( $servings ) . '" min="1"><span class="wprm-print-servings-increment wprm-print-servings-change">+</span>';
				$output['header'] .= '&nbsp;<span id="wprm-print-servings-unit">' . __( 'servings', 'wp-recipe-maker-premium' ) . '</span>';
				$output['header'] .= '</div>';
			}

			// Unit Conversion Header.
			if ( $recipe && WPRM_Settings::get( 'print_unit_conversion' ) && WPRM_Addons::is_active( 'unit-conversion' ) ) {
				$ingredients = $recipe->ingredients_without_groups();

				$unit_systems = array(
					1 => true, // Default unit system.
				);

				foreach ( $ingredients as $ingredient ) {
					if ( isset( $ingredient['converted'] ) ) {
						foreach ( $ingredient['converted'] as $system => $values ) {
							if ( $values['amount'] || $values['unit'] ) {
								$unit_systems[ $system ] = true;
							}
						}
					}
				}

				// Add UC header:
				$recipe_unit_system = intval( $recipe->unit_system() );
				$output['header'] .= self::get_unit_conversion_header( $unit_systems, $recipe_unit_system );
			}
		}

		if ( 'recipes' === $output['type'] ) {
			// Needed for adjustable servings.
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprm_public = ' . json_encode( WPRM_Assets::localize_public() ) . ';</script>',
			);
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprmp_public = { settings : { recipe_template_mode: "' . WPRM_Settings::get( 'recipe_template_mode' ) . '", features_adjustable_servings : true, adjustable_servings_round_to_decimals: ' . WPRM_Settings::get( 'adjustable_servings_round_to_decimals' ) . ' } };</script>',
			);

			// Need serving information for each recipe.
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>var wprmp_print_recipes = ' . wp_json_encode( $output['recipes'] ) . ';</script>',
			);

			// Nutrition toggle header.
			$output['header'] .= self::get_nutrition_toggle_header();

			// Loop over all recipes to check for unit system.
			$unit_systems = array(
				1 => true, // Default unit system.
			);

			foreach ( $output['recipes'] as $recipe_output ) {
				$recipe_id = isset( $recipe_output['recipe_id'] ) ? $recipe_output['recipe_id'] : $recipe_output['id'];
				$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

				if ( $recipe ) {
					$ingredients = $recipe->ingredients_without_groups();
	
					foreach ( (array) $ingredients as $key => $value ) {
						// Check for second unit system.
						if ( isset( $value['converted'] ) ) {
							foreach ( $value['converted'] as $system => $values ) {
								if ( $values['amount'] || $values['unit'] ) {
									$unit_systems[ $system ] = true;
								}
							}
						}
					}
				}
			}

			// Will add if there are multiple unit systems.
			$output['header'] .= self::get_unit_conversion_header( $unit_systems );
		}

		return $output;
	}

	/**
	 * Add extra buttons to the print page header.
	 *
	 * @since	10.6.0
	 * @param	string $buttons Current buttons HTML.
	 * @param	array  $output  Current print output data.
	 */
	public static function print_header_buttons( $buttons, $output ) {
		if ( ! isset( $output['type'] ) || 'recipe' !== $output['type'] ) {
			return $buttons;
		}

		if ( ! WPRM_Settings::get( 'pdf_download_enabled' ) || ! WPRM_Settings::get( 'print_download_pdf_button' ) ) {
			return $buttons;
		}

		$template = '';
		$recipe = isset( $output['recipe'] ) ? $output['recipe'] : false;
		if ( $recipe ) {
			$pdf_template = WPRM_Template_Manager::get_template_by_type( 'pdf', $recipe->type() );
			if ( $pdf_template && isset( $pdf_template['slug'] ) ) {
				$template = sanitize_key( $pdf_template['slug'] );
			}
		}

		$buttons .= '<a href="#" id="wprm-print-button-pdf" class="wprm-print-button" data-template="' . esc_attr( $template ) . '">' . esc_html( __( 'Download PDF', 'wp-recipe-maker' ) ) . '</a>';
		return $buttons;
	}

	/**
	 * Get settings needed for premium single recipe print functionality.
	 *
	 * @since	10.6.0
	 */
	private static function single_recipe_print_settings() {
		return array(
			'recipe_template_mode' => WPRM_Settings::get( 'recipe_template_mode' ),
			'features_adjustable_servings' => true,
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
		);
	}

	/**
	 * Get header for nutrition toggle.
	 *
	 * @since    8.1.0
	 */
	private static function get_nutrition_toggle_header( $recipe = false ) {
		$header = '';

		// Recipe nutrition toggle.
		$has_nutrition = false;
		$nutrition = $recipe ? $recipe->nutrition() : array();

		foreach ( $nutrition as $field => $value ) {
			if ( $value ) {
				$has_nutrition = true;
				break;
			}
		}

		if ( false === $recipe || $has_nutrition ) {
			$checked = WPRM_Settings::get( 'print_show_nutrition' ) ? ' checked="checked"' : '';

			$header .= '<div class="wprm-print-toggle-container">';
			$header .= '<input type="checkbox" id="wprm-print-toggle-recipe-nutrition" class="wprm-print-toggle" value="1" ' . $checked . '/><label for="wprm-print-toggle-recipe-nutrition">' . __( 'Nutrition Label', 'wp-recipe-maker' ) . '</label>';
			$header .= '</div>';
			$header .= '<div class="wprm-print-spacer"></div>';
		}

		return $header;
	}

	/**
	 * Get header for unit conversion.
	 *
	 * @since    7.2.0
	 */
	private static function get_unit_conversion_header( $unit_systems, $recipe_unit_system = 1 ) {
		$output = '';

		if ( count( $unit_systems ) > 1 && WPRM_Settings::get( 'print_unit_conversion' ) && WPRM_Addons::is_active( 'unit-conversion' ) ) {
			$output .= '<div id="wprm-print-unit-conversion-container" class="wprm-print-option-container">';

			foreach ( $unit_systems as $unit_system => $value ) {
				$active = $recipe_unit_system === $unit_system ? ' wprmpuc-active' : '';
				$unit_system_label = WPRM_Settings::get( 'unit_conversion_system_' . $unit_system );
				$output .= '<a href="#" role="button" class="wprm-unit-conversion wprm-print-option' . $active . '" data-system="' . esc_attr( $unit_system ) . '" aria-label="' . __( 'Change unit system to', 'wp-recipe-maker' ) . ' ' . $unit_system_label . '">' . $unit_system_label . '</a>';
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get custom styling for the print accent color.
	 *
	 * @since    6.1.0
	 */
	public static function print_accent_color_styling() {
		$output = '';
		$color = WPRM_Settings::get( 'print_accent_color' );
		$color_default = WPRM_Settings::get_default( 'print_accent_color' );

		if ( $color !== $color_default ) {
			$output .= '<style>';
			$output .= ' #wprm-print-servings-container #wprm-print-servings { border-color: ' . $color . ' !important; }';
			$output .= ' #wprm-print-servings-container .wprm-print-servings-change { border-color: ' . $color . ' !important; background-color: ' . $color . ' !important; }';
			$output .= ' #wprm-print-unit-conversion-container a.wprm-unit-conversion { border-color: ' . $color . ' !important; }';
			$output .= ' #wprm-print-unit-conversion-container a.wprm-unit-conversion.wprmpuc-active { background-color: ' . $color . ' !important; }';
			$output .= '</style>';
		}

		return $output;
	}
}

WPRMP_Print::init();
