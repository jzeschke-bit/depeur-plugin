<?php
/**
 * Handle the Premium advanced adjustable servings shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium advanced adjustable servings shortcode.
 *
 * @since      8.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Advanced_Adjustable_Servings {
	public static function init() {
		add_filter( 'wprm_recipe_advanced_adjustable_servings_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Filter the advanced adjustable servings shortcode.
	 *
	 * @since	8.0.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ! $recipe->servings_advanced_enabled() ) {
			return '';
		}

		$servings = $recipe->servings_advanced();

		// Check if all necessary values are set.
		$valid = false;
		switch ( $servings['shape'] ) {
			case 'round':
				$valid = 0 < $servings['diameter'];
				break;
			case 'rectangle':
				$valid = 0 < $servings['width'] && 0 < $servings['length'];
				break;
		}

		if ( ! $valid ) {
			return '';
		}
		
		$classes = array(
			'wprm-recipe-advanced-servings-container',
			'wprm-recipe-advanced-servings-' . $recipe->id() . '-container',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		// Use link tag in Template Editor preview.
		$input_tag = $atts['is_template_editor_preview'] ? 'a' : 'span';

		// Attributes.
		$attributes = '';
		$attributes .= ' data-recipe="' . esc_attr( $recipe->id() ) . '"';

		// Output.
		$output = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $attributes . '>';

		if ( $atts['before_text'] ) {
			$output .= '<span class="wprm-recipe-advanced-servings-before">' . WPRM_Shortcode_Helper::sanitize_html( $atts['before_text'] ) . '&nbsp;</span>';
		}

		$hidden = 'round' === $servings['shape'] ? '' : ' style="display: none;"';
		$output .= '<span class="wprm-recipe-advanced-servings-round"' . $hidden . '>';
		$output .= self::number_field( $input_tag, 'diameter', $servings['diameter'], __( 'Change diameter of the baking form', 'wp-recipe-maker-premium' ) );
		$output .= self::unit_field( $input_tag, $servings['unit'] );
		$output .= '</span>';

		$hidden = 'rectangle' === $servings['shape'] ? '' : ' style="display: none;"';
		$output .= '<span class="wprm-recipe-advanced-servings-rectangle"' . $hidden . '>';
		$output .= self::number_field( $input_tag, 'width', $servings['width'], __( 'Change width of the baking form', 'wp-recipe-maker-premium' ) );
		$output .= '<span class="wprm-recipe-advanced-servings-input-between"> x </span>';
		$output .= self::number_field( $input_tag, 'length', $servings['length'], __( 'Change length of the baking form', 'wp-recipe-maker-premium' ) );
		$output .= self::unit_field( $input_tag, $servings['unit'] );
		$output .= '</span>';

		$output .= '<span class="wprm-recipe-advanced-servings-shape">';
		$output .= self::shape_field( $input_tag, $servings['shape'] );

		if ( 0 < $servings['height'] ) {
			$output .= '<span class="wprm-recipe-advanced-servings-between">,&nbsp;</span>';
		}

		$output .= '</span>';

		if ( 0 < $servings['height'] ) {
			$output .= '<span class="wprm-recipe-advanced-servings-height">';
			$output .= self::number_field( $input_tag, 'height', $servings['height'], __( 'Change height of the baking form', 'wp-recipe-maker-premium' ) );
			$output .= self::unit_field( $input_tag, $servings['unit'] );
			$output .= '<span class="wprm-recipe-advanced-servings-input-unit-suffix"> ' . __( 'height', 'wp-recipe-maker-premium' ) . '</span>';
			$output .= '</span>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get HTML for a number field.
	 *
	 * @since	8.0.0
	 * @param	string 	$tag	Tag to use for the field.
	 * @param	string 	$type	Type of input.
	 * @param	mixed 	$value	Value for the input.
	 * @param	string 	$label	Descriptive label for the input.
	 */
	private static function number_field( $tag, $type, $value, $label ) {
		return '<' . $tag . ' class="wprm-recipe-advanced-servings-input wprm-recipe-advanced-servings-input-' . esc_attr( $type ) . '" data-type="' . esc_attr( $type ) . '" aria-label="' . esc_attr( $label ) . '">' . $value . '</' . $tag . '>';
	}

	/**
	 * Get HTML for the shape field.
	 *
	 * @since	8.0.0
	 * @param	string 	$tag	Tag to use for the field.
	 * @param	mixed 	$value	Initial shape value.
	 */
	private static function shape_field( $tag, $value ) {
		$shapes = array(
			'round' => __( 'round', 'wp-recipe-maker-premium' ),
			'rectangle' => __( 'rectangle', 'wp-recipe-maker-premium' ),
		);
		
		$aria_label = __( 'Change shape of the baking form', 'wp-recipe-maker-premium' );
		return '<' . $tag . ' class="wprm-recipe-advanced-servings-input wprm-recipe-advanced-servings-input-shape" aria-label="' . esc_attr( $aria_label ) . '" data-shape-round="' . esc_attr( $shapes['round'] ) .'" data-shape-rectangle="' . esc_attr( $shapes['rectangle'] ) .'"> ' . $shapes[ $value ] . '</' . $tag . '>';
	}

	/**
	 * Get HTML for the unit field.
	 *
	 * @since	8.0.0
	 * @param	string 	$tag	Tag to use for the field.
	 * @param	mixed 	$value	Initial unit value.
	 */
	private static function unit_field( $tag, $value ) {
		$units = array(
			'cm' => __( 'cm', 'wp-recipe-maker-premium' ),
			'inch' => __( 'inch', 'wp-recipe-maker-premium' ),
		);

		$class = WPRM_Settings::get( 'advanced_adjustable_unit_conversion' ) ? 'wprm-recipe-advanced-servings-input' : 'wprm-recipe-advanced-servings-input-fixed';
		
		$aria_label = __( 'Change unit for the baking form values', 'wp-recipe-maker-premium' );
		return '<' . $tag . ' class="' . $class . ' wprm-recipe-advanced-servings-input-unit" aria-label="' . esc_attr( $aria_label ) . '" data-unit-cm="' . esc_attr( $units['cm'] ) .'" data-unit-inch="' . esc_attr( $units['inch'] ) .'">' . $units[ $value ] . '</' . $tag . '>';
	}
}

WPRMP_SC_Advanced_Adjustable_Servings::init();