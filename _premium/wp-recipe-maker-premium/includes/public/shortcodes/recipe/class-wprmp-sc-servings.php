<?php
/**
 * Handle the Premium servings shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium servings shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Servings {
	public static function init() {
		add_filter( 'wprm_recipe_servings_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
		add_filter( 'wprm_recipe_adjustable_servings_shortcode', array( __CLASS__, 'adjustable_shortcode' ), 10, 3 );
	}

	/**
	 * Filter the servings shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ! $recipe->servings() ) {
			return $output;
		}

		// Output.
		$classes = array(
			'wprm-recipe-servings',
			'wprm-recipe-details',
			'wprm-recipe-servings-' . $recipe->id(),
			'wprm-recipe-servings-adjustable-' . $atts['adjustable'],
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		// Style in Preview.
		$output = '';
		if ( $atts['is_template_editor_preview'] ) {
			switch ( $atts['adjustable'] ) {
				case 'text-buttons':
					$button_style = '';
					$button_style .= 'background-color: ' . $atts['button_background'] . ';';
					$button_style .= 'border-color: ' . $atts['button_background'] . ';';
					$button_style .= 'color: ' . $atts['button_accent'] . ';';
					$button_style .= 'border-radius: ' . $atts['button_radius'] . ';';

					$input_style = 'border-color: ' . $atts['button_background'] . ';';

					$classes[] = 'wprm-recipe-servings-text-buttons-container';

					$output .= '<span class="' . esc_attr( implode( ' ', $classes ) ) . '">';
					$output .= '<span class="wprm-recipe-servings-decrement wprm-recipe-servings-change" style="' . esc_attr( $button_style ) . '">–</span>';
					$output .= '<input type="text" value="' . esc_attr( $recipe->servings() ) . '" class="wprm-recipe-servings"  data-recipe="' . esc_attr( $recipe->id() ) . '" aria-label="' . esc_attr__( 'Adjust recipe servings', 'wp-recipe-maker-premium' ) . '" style="' . esc_attr( $input_style ) . '">';
					$output .= '<span class="wprm-recipe-servings-increment wprm-recipe-servings-change" style="' . esc_attr( $button_style ) . '">+</span>';
					$output .= '</span>';
					break;
				case 'text':
					$output = '<input type="number" value="' . esc_attr( $recipe->servings() ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe="' . esc_attr( $recipe->id() ) . '" aria-label="' . esc_attr__( 'Adjust recipe servings', 'wp-recipe-maker-premium' ) . '">';
					break;
				case 'tooltip':
					$output = '<a class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe="' . esc_attr( $recipe->id() ) . '">' . $recipe->servings() . '</a>';
					break;
			}
		}

		// No output yet, so we're not previewing.
		if ( ! $output ) {
			$style_data = '';
			if ( 'text-buttons' === $atts['adjustable'] ) {
				$style_data = ' data-button-background="' . esc_attr( $atts['button_background'] ) .'" data-button-accent="' . esc_attr( $atts['button_accent'] ) .'" data-button-radius="' . esc_attr( $atts['button_radius'] ) .'"';
			}

			$output = '<span class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe="' . esc_attr( $recipe->id() ) . '" aria-label="' . esc_attr__( 'Adjust recipe servings', 'wp-recipe-maker-premium' ) . '"' . $style_data .'>' . $recipe->servings() . '</span>';
		}

		if ( (bool) $atts['label_container'] ) {
			$unit = WPRM_SC_Servings_Unit::shortcode( $atts );
			if ( $unit ) {
				$output = '<span class="wprm-recipe-servings-with-unit">' . $output . ' ' . $unit . '</span>';
			}

			$output = WPRM_Shortcode_Helper::get_label_container( $atts, 'servings', $output );
		}

		return $output;
	}

	/**
	 * Filter the adjustable servings shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function adjustable_shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ( ! $recipe->servings() && ! WPRM_Settings::get( 'adjustable_servings_without_servings' ) ) ) {
			return '';
		}

		$recipe_servings = $recipe->servings() ? $recipe->servings() : 1;

		$classes = array(
			'wprm-recipe-adjustable-servings-container',
			'wprm-recipe-adjustable-servings-' . $recipe->id() . '-container',
			'wprm-toggle-container',
			'wprm-toggle-' . $atts['style'] . '-container',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Custom style.
		$style = '';
		if ( 'buttons' === $atts['style'] ) {
			if ( '#ffffff' !== $atts['button_background'] ) {
				$style .= '--wprm-toggle-button-background: ' . $atts['button_background'] . ';';
			}
			if ( '#333333' !== $atts['button_accent'] ) {
				$style .= '--wprm-toggle-button-accent: ' . $atts['button_accent'] . ';';
			}
			if ( '3px' !== $atts['button_radius'] ) {
				$style .= '--wprm-toggle-button-radius: ' . $atts['button_radius'] . ';';
			}
		}

		if ( 'pills' === $atts['style'] ) {
			if ( '28px' !== $atts['pills_height'] ) {
				$style .= '--wprm-toggle-pills-height: ' . $atts['pills_height'] . ';';
			}
			if ( '10px' !== $atts['pills_gap'] ) {
				$style .= '--wprm-toggle-pills-gap: ' . $atts['pills_gap'] . ';';
			}
			if ( '#ffffff' !== $atts['pills_background'] ) {
				$style .= '--wprm-toggle-pills-background: ' . $atts['pills_background'] . ';';
			}
			if ( '#333333' !== $atts['pills_text'] ) {
				$style .= '--wprm-toggle-pills-text: ' . $atts['pills_text'] . ';';
			}
			if ( '#333333' !== $atts['pills_border'] ) {
				$style .= '--wprm-toggle-pills-border: ' . $atts['pills_border'] . ';';
			}
			if ( '#333333' !== $atts['pills_active_background'] ) {
				$style .= '--wprm-toggle-pills-active-background: ' . $atts['pills_active_background'] . ';';
			}
			if ( '#333333' !== $atts['pills_active_text'] ) {
				$style .= '--wprm-toggle-pills-active-text: ' . $atts['pills_active_text'] . ';';
			}
			if ( '#ffffff' !== $atts['pills_active_border'] ) {
				$style .= '--wprm-toggle-pills-active-border: ' . $atts['pills_active_border'] . ';';
			}
			if ( '999px' !== $atts['pills_radius'] ) {
				$style .= '--wprm-toggle-pills-radius: ' . $atts['pills_radius'] . ';';
			}
		}

		// Buttons.
		$buttons = array();
		$serving_options = explode( ';', $atts['serving_options'] );
		$serving_options_labels = isset( $atts['serving_options_labels'] ) ? explode( ';', $atts['serving_options_labels'] ) : array();

		foreach ( $serving_options as $index => $option ) {
			$option = trim( $option );
			$value = WPRM_Recipe_Parser::parse_quantity( $option );
			$label = isset( $serving_options_labels[ $index ] ) ? $serving_options_labels[ $index ] : '';

			if ( 0 < $value ) {
				$buttons[ '' . $value ] = '' !== trim( $label ) ? $label : $option; // Make sure key is string or floats get truncated.
			}
		}

		// Set default buttons if not at least 2 valid options set.
		if ( 2 > count( $buttons ) ) {
			$buttons = array(
				'1' => '1x',
				'2' => '2x',
				'3' => '3x',
			);
		}

		// Optional "any value".
		$any_value_label = isset( $atts['serving_options_any_value'] ) ? $atts['serving_options_any_value'] : '';
		if ( '' !== trim( $any_value_label ) ) {
			$buttons['?'] = $any_value_label;
		}

		$buttons_output = '';

		$first_button = true;
		foreach ( $buttons as $multiplier => $button_text ) {
			$button_text_for_aria = trim( $button_text );

			if ( '?' === $multiplier ) {
				$aria_label = __( 'Change servings to...', 'wp-recipe-maker-premium' );
			} else {
				$aria_label = __( 'Adjust servings by', 'wp-recipe-maker-premium' ) . ' ' . $button_text_for_aria;
			}

			$leading_spaces = strlen( $button_text ) - strlen( ltrim( $button_text, ' ' ) );
			$trailing_spaces = strlen( $button_text ) - strlen( rtrim( $button_text, ' ' ) );
			$button_text_without_edge_spaces = trim( $button_text, ' ' );
			$button_text_output = str_repeat( '&nbsp;', $leading_spaces ) . esc_html( $button_text_without_edge_spaces ) . str_repeat( '&nbsp;', $trailing_spaces );

			$active = 1 === $multiplier ? ' wprm-toggle-active' : '';
			$buttons_output .= '<button class="wprm-recipe-adjustable-servings wprm-toggle' . $active . '" data-multiplier="' . esc_attr( $multiplier ) . '" data-servings="' . esc_attr( $recipe_servings ) . '" data-recipe="' . esc_attr( $recipe->id() ) . '" aria-label="' . esc_attr( $aria_label ) . '">' . $button_text_output . '</button>';
		}

		// Output.
		$output = '<div class="' . implode( ' ', $classes ) . '" style="' . $style . '">' . $buttons_output . '</div>';

		return $output;
	}

	/**
	 * Get initial servings set in the URL.
	 *
	 * @since	8.1.0
	 */
	public static function get_initial_servings() {
		// Check if URL parameter is set.
		$initial_servings = '';
		if ( WPRM_Settings::get( 'adjustable_servings_url' ) ) {
			$param = trim( WPRM_Settings::get( 'adjustable_servings_url_param' ) );
			$param = $param ? $param : 'servings';

			if ( isset( $_GET[ $param ] ) ) {
				$param_servings = floatval( $_GET[ $param ] );
				
				if ( $param_servings ) {
					$initial_servings = $param_servings;
				}
			}
		}

		return $initial_servings;
	}
}

WPRMP_SC_Servings::init();
