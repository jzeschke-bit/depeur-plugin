<?php
/**
 * Handles nutrition labels for saved nutrition ingredients.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 */

/**
 * Handles nutrition labels for saved nutrition ingredients.
 *
 * @since      6.8.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPN_Ingredient_Nutrition_Label {

	/**
	 * Register actions and filters.
	 *
	 * @since    6.8.0
	 */
	public static function init() {
		add_shortcode( 'wprm-ingredient-nutrition-label', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Register the ingredient nutrition label shortcode.
	 *
	 * @since    6.8.0
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
			'align' => 'left',
		), $atts, 'wprm_ingredient_nutrition_label' );

		$ingredient = WPRMPN_Ingredient_Manager::get_ingredient( $atts['id'] );
		if ( ! $ingredient ) {
			return '';
		}

		// Get label.
		$name = isset( $ingredient['name'] ) ? $ingredient['name'] : '';
		$nutrition = isset( $ingredient['nutrition'] ) && isset( $ingredient['nutrition']['nutrients'] ) ? $ingredient['nutrition']['nutrients'] : array();
		
		if ( isset( $ingredient['nutrition'] ) && isset( $ingredient['nutrition']['amount'] ) && isset( $ingredient['nutrition']['unit'] ) ) {
			$nutrition['serving_size'] = $ingredient['nutrition']['amount'];
			$nutrition['serving_unit'] = $ingredient['nutrition']['unit'];
		}

		$label = WPRMP_Nutrition::label( false, $name, $nutrition );
		if ( ! $label ) {
			return '';
		}

		// Output.
		$classes = array(
			'wprm-ingredient-nutrition-label-container',
		);
		$align = in_array( $atts['align'], array( 'center', 'right' ) ) ? $atts['align'] : 'left';

		$output = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="text-align: ' . esc_attr( $align ) . ';">';
		$output .= $label;
		$output .= '</div>';

		// Make sure assets are loaded and output label.
		WPRM_Assets::load();
		return apply_filters( 'wprm_ingredient_nutrition_label_shortcode', $output, $atts );
	}
}

WPRMPN_Ingredient_Nutrition_Label::init();
