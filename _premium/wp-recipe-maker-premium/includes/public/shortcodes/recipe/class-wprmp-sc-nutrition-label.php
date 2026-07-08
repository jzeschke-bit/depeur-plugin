<?php
/**
 * Handle the Premium nutrition label shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium nutrition label shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Nutrition_Label {
	public static function init() {
		add_filter( 'wprm_nutrition_label_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Nutrition label shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || 'disabled' === $atts['align'] ) {
			return $output;
		}

		$output = '<div id="recipe-' . esc_attr( $recipe->id() ) . '-nutrition" class="wprm-nutrition-label-shortcode-container">';

		$align = in_array( $atts['align'], array( 'center', 'right' ) ) ? $atts['align'] : 'left';
		$output .= WPRM_Shortcode_Helper::get_section_header( $atts, 'nutrition' );

		if ( (bool) $atts['has_container'] ) {
			$output .= WPRM_Shortcode_Helper::get_internal_container( $atts, 'nutrition' );
		}

		// Output.
		$classes = array(
			'wprm-nutrition-label-container',
			'wprm-nutrition-label-container-' . $atts['style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		if ( 'label' !== $atts['style'] ) {
			$classes[] = 'wprm-block-text-' . $atts['text_style'];
		}

		// Style.
		$style = 'text-align: ' . $align . ';';

		// Only for the grouped nutrition label style.
		if ( 'grouped' === $atts['style'] ) {
			$classes[] = 'wprm-nutrition-label-container-grouped-' . $atts['group_item_style'];

			if ( '0px' !== $atts['group_column_gap'] ) {
				$style .= 'column-gap: ' . esc_attr( $atts['group_column_gap'] ) . ';';
			}
			if ( 'flex-start' !== $atts['group_alignment'] ) {
				$style .= 'justify-content: ' . esc_attr( $atts['group_alignment'] ) . ';';
			}

			if ( 'pills' === $atts['group_item_style'] ) {
				if ( '10px' !== $atts['pills_row_gap'] ) {
					$style .= '--wprm-nutrition-pills-row-gap: ' . $atts['pills_row_gap'] . ';';
				}
				if ( '#ffffff' !== $atts['pills_background'] ) {
					$style .= '--wprm-nutrition-pills-background: ' . $atts['pills_background'] . ';';
				}
				if ( '#333333' !== $atts['pills_border'] ) {
					$style .= '--wprm-nutrition-pills-border: ' . $atts['pills_border'] . ';';
				}
				if ( '1px' !== $atts['pills_border_width'] ) {
					$style .= '--wprm-nutrition-pills-border-width: ' . $atts['pills_border_width'] . ';';
				}
				if ( '100px' !== $atts['pills_border_radius'] ) {
					$style .= '--wprm-nutrition-pills-border-radius: ' . $atts['pills_border_radius'] . ';';
				}
				if ( '15px' !== $atts['pills_horizontal_padding'] ) {
					$style .= '--wprm-nutrition-pills-horizontal-padding: ' . $atts['pills_horizontal_padding'] . ';';
				}
				if ( '5px' !== $atts['pills_vertical_padding'] ) {
					$style .= '--wprm-nutrition-pills-vertical-padding: ' . $atts['pills_vertical_padding'] . ';';
				}
			}
		}

		$output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '">';

		switch ( $atts['style'] ) {
			case 'simple':
			case 'grouped':
				$nutrition = $recipe->nutrition();
				$serving_unit = isset( $nutrition['serving_unit'] ) && $nutrition['serving_unit'] ? $nutrition['serving_unit'] : WPRM_Settings::get( 'nutrition_default_serving_unit' );

				$show_daily = (bool) $atts['daily'];
				$type = in_array( $atts['nutrition_values'], array( 'serving', '100g' ) ) ? $atts['nutrition_values'] : 'serving';

				// Multiply factor defaults to 1.
				$multiply_factor = 1 ;

				// Check if we actually can display per 100g.
				if ( '100g' === $type ) {
					// Serving unit needs to be in grams (allow empty as well).
					if ( ! in_array( $serving_unit, array( '', 'g', __( 'gram', 'wp-recipe-maker' ), __( 'grams', 'wp-recipe-maker' ) ) ) ) {
						return '';
					}

					// Serving size needs to be set to calculate multiply factor.
					$multiply_factor = false;

					$serving_size = isset( $nutrition[ 'serving_size' ] ) && false !== $nutrition[ 'serving_size' ] ? WPRM_Recipe_Parser::parse_quantity( $nutrition[ 'serving_size' ] ) : 0;
					if ( is_numeric( $serving_size ) && 0 < $serving_size ) {
						$multiply_factor = 100.00 / $serving_size;
					}

					// Need to have a factor to multiply by to display per 100g.
					if ( ! $multiply_factor ) {
						return '';
					}
				}

				// Get nutrition output for each field.
				$nutrition_output = array();

				$nutrition_fields = WPRM_Nutrition::get_fields();
				$nutrition_fields['serving_size']['unit'] = $serving_unit;

				foreach ( $nutrition_fields as $field => $options ) {
					if ( isset( $nutrition[ $field ] ) && false !== $nutrition[ $field ] && ( WPRM_Settings::get( 'nutrition_label_zero_values' ) || $nutrition[ $field ] ) ) {

						// Nutrition value.
						$value = $nutrition[ $field ];
						$display_value = $value;

						if ( 1 !== $multiply_factor ) {
							$value = WPRM_Recipe_Parser::parse_quantity( $nutrition[ $field ] ) * $multiply_factor;
							$display_value = WPRM_Recipe_Parser::format_quantity( $value, WPRM_Settings::get( 'nutrition_facts_calculation_round_to_decimals' ) );
						}

						// Make sure correct separator is used.
						if ( 'comma' === WPRM_Settings::get( 'decimal_separator' ) ) {
							$display_value = str_replace( '.', '|', $display_value );
							$display_value = str_replace( ',', '.', $display_value );
							$display_value = str_replace( '|', ',', $display_value );
						}

						// Calculate percentage if needed.
						$percentage = false;
						if ( $show_daily && isset( $options['daily'] ) && $options['daily'] ) {
							$percentage = round( floatval( $value ) / $options['daily'] * 100 );
						}

						$style = '';
						if ( 'grouped' === $atts['style'] ) {
							$style = 'style="flex-basis: ' . esc_attr( $atts['group_width'] ) . ';';

							// Add placeholder until we know which field is the last one.
							if ( 'text' === $atts['group_item_style'] && (bool) $atts['bottom_border'] ) {
								$style .= '%wprm_bottom_border_placeholder%';
							}

							$style .= '"';
						}

						// Nutritient classes.
						$nutrient_classes = array(
							'wprm-nutrition-label-text-nutrition-container',
							'wprm-nutrition-label-text-nutrition-container-' . esc_attr( $field ),
						);

						$combine_unit = false;
						if ( 'grouped' === $atts['style'] && (bool) $atts['separate_value_from_label'] ) {
							$nutrient_classes[] = 'wprm-nutrition-label-text-nutrition-container-separate';
							$combine_unit = true;
						}

						$field_output = '<span class="' . implode( ' ', $nutrient_classes ) . '"' . $style .'>';
						$field_output .= '<span class="wprm-nutrition-label-text-nutrition-label  wprm-block-text-' . esc_attr( $atts['label_style'] ) . '" style="color: ' . esc_attr( $atts['label_color'] ) . '">' . WPRM_Shortcode_Helper::sanitize_html( __( $options['label'] , 'wp-recipe-maker-premium' ) ) . WPRM_Shortcode_Helper::sanitize_html( $atts['label_separator'] ) . '</span>';

						if ( $combine_unit ) {
							$field_output .= '<span class="wprm-nutrition-label-text-nutrition-value-unit-container">';
						}

						$field_output .= '<span class="wprm-nutrition-label-text-nutrition-value" style="color: ' . esc_attr( $atts['value_color'] ) . '">' . $display_value . '</span>';
						$field_output .= WPRM_Shortcode_Helper::sanitize_html( $atts['unit_separator'] );
						$field_output .= '<span class="wprm-nutrition-label-text-nutrition-unit" style="color: ' . esc_attr( $atts['value_color'] ) . '">' . $options['unit'] . '</span>';

						if ( $percentage ) {
							$field_output .= '<span class="wprm-nutrition-label-text-nutrition-daily" style="color: ' . esc_attr( $atts['value_color'] ) . '">';

							switch ( $atts['daily_seperator'] ) {
								case 'parentheses':
									$field_output .= ' (' . $percentage . '%)';
									break;
								case 'dash':
									$field_output .= ' - ' . $percentage . '%';
									break;
								default:
									$field_output .= ' ' . $percentage . '%';
							}

							$field_output .= '</span>';
						}

						if ( $combine_unit ) {
							$field_output .= '</span>';
						}

						$field_output .= '</span>';

						$nutrition_output[] = $field_output;
					}
				}

				if ( ! count( $nutrition_output ) ) {
					return '';
				}

				// Add border to all fields except the last one.
				if ( 'text' === $atts['group_item_style'] && (bool) $atts['bottom_border'] ) {
					$bottom_border = 'border-bottom: ' . esc_attr( $atts['bottom_border_width'] ) . ' ' . esc_attr( $atts['bottom_border_style'] ) . ' ' . esc_attr( $atts['bottom_border_color'] ) . ';';
					$bottom_border .= 'padding-bottom: ' . esc_attr( $atts['bottom_border_gap'] ) . ';';
					$bottom_border .= 'margin-bottom: ' . esc_attr( $atts['bottom_border_gap'] ) . ';';

					foreach ( $nutrition_output as $key => $field ) {
						if ( $key === count( $nutrition_output ) - 1 ) {
							$nutrition_output[ $key ] = str_replace( '%wprm_bottom_border_placeholder%', '', $field );
						} else {
							$nutrition_output[ $key ] = str_replace( '%wprm_bottom_border_placeholder%', $bottom_border, $field );
						}
					}
				}

				$nutrition_separator = '';
				if ( 'simple' === $atts['style'] ) {
					$nutrition_separator = '<span style="color: ' . esc_attr( $atts['label_color'] ) . '">' . WPRM_Shortcode_Helper::sanitize_html( $atts['nutrition_separator'] ) . '</span>';
				}
				
				$output .= implode( $nutrition_separator, $nutrition_output );

				// Check if output was odd, if so add extra element for grouped style.
				if ( 'grouped' === $atts['style'] && count( $nutrition_output ) % 2 ) {
					$output .= '<span class="wprm-nutrition-label-text-nutrition-container wprm-nutrition-label-text-nutrition-container-empty" style="flex-basis: ' . esc_attr( $atts['group_width'] ) . '"></span>';
				}

				break;
			default:
				$label = WPRMP_Nutrition::label( $recipe );
				if ( ! $label ) {
					return '';
				}

				if ( 'legacy' === WPRM_Settings::get( 'nutrition_label_style' ) ) {
					$style = 'style="';
					$style .= 'background-color: ' . esc_attr( $atts['label_background_color'] ) . ';';
					$style .= 'color: ' . esc_attr( $atts['label_text_color'] ) . ';';
					$style .= '"';

					$label = str_replace( 'class="wprm-nutrition-label"', 'class="wprm-nutrition-label" ' . $style, $label );
				}
				
				$output .= $label;
			}

		$output .= '</div>';

		if ( (bool) $atts['has_container'] ) {
			$output .= '</div>';
		}

		$output .= '</div>';

		return $output;
	}
}

WPRMP_SC_Nutrition_Label::init();