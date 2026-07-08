<?php
/**
 * Handle the Premium nutrition shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium nutrition shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Nutrition {
	public static function init() {
		add_filter( 'wprm_nutrition_shortcode_nutrient', array( __CLASS__, 'nutrient' ), 10, 3 );
	}

	/**
	 * Nutrition label shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed $nutrient	Nutrient to output.
	 * @param	array $atts   	Options passed along with the shortcode.
	 * @param	mixed $recipe 	Recipe the shortcode is getting output for.
	 */
	public static function nutrient( $nutrient, $atts, $recipe ) {
		$show_daily = (bool) $atts['daily'];
		$type = in_array( $atts['display_value'], array( 'serving', '100g' ) ) ? $atts['display_value'] : 'serving';
		
		$nutrition = $recipe->nutrition();
		$nutrition_fields = WPRM_Nutrition::get_fields();
		$value = isset( $nutrition[ $atts['field'] ] ) ? $nutrition[ $atts['field'] ] : false;
		$display_value = $value;

		if ( $value !== false && ( WPRM_Settings::get( 'nutrition_label_zero_values' ) || $value ) ) {

			// Check if we actually can display per 100g.
			if ( '100g' === $type ) {
				// Serving unit needs to be in grams (allow empty as well).
				$serving_unit = isset( $nutrition['serving_unit'] ) && $nutrition['serving_unit'] ? $nutrition['serving_unit'] : WPRM_Settings::get( 'nutrition_default_serving_unit' );
				if ( ! in_array( $serving_unit, array( '', 'g', __( 'gram', 'wp-recipe-maker' ), __( 'grams', 'wp-recipe-maker' ) ) ) ) {
					$nutrient['value'] = '';
					return $nutrient;
				}

				// Serving size needs to be set to calculate multiply factor.
				$multiply_factor = false;

				$serving_size = isset( $nutrition[ 'serving_size' ] ) && false !== $nutrition[ 'serving_size' ] ? WPRM_Recipe_Parser::parse_quantity( $nutrition[ 'serving_size' ] ) : 0;
				if ( is_numeric( $serving_size ) && 0 < $serving_size ) {
					$multiply_factor = 100.00 / $serving_size;
				}

				// Need to have a factor to multiply by to display per 100g.
				if ( ! $multiply_factor ) {
					$nutrient['value'] = '';
					return $nutrient;
				}

				// Recalculate value per 100g.
				$value = $value * $multiply_factor;
				$display_value = WPRM_Recipe_Parser::format_quantity( $value, WPRM_Settings::get( 'nutrition_facts_calculation_round_to_decimals' ) );
			}

			if ( $show_daily ) {
				$daily = isset( $nutrition_fields[ $atts['field'] ]['daily'] ) ? $nutrition_fields[ $atts['field'] ]['daily'] : false;

				if ( $daily ) {
					$nutrient['value'] = round( floatval( $value ) / $daily * 100 );
					$nutrient['unit'] = '%';
				}
			} else {
				$nutrient['value'] = $display_value;

				if ( 'serving_size' === $atts['field'] ) {
					$serving_unit = isset( $nutrition['serving_unit'] ) && $nutrition['serving_unit'] ? $nutrition['serving_unit'] : WPRM_Settings::get( 'nutrition_default_serving_unit' );
					$nutrient['unit'] = $serving_unit;
				} else {
					$nutrient['unit'] = $nutrition_fields[ $atts['field'] ]['unit'];
				}
			}
		}

		return $nutrient;
	}
}

WPRMP_SC_Nutrition::init();