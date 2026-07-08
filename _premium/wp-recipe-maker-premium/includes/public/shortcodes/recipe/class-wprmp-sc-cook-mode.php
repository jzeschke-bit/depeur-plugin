<?php
/**
 * Handle the Premium cook mode shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium cook mode shortcode.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Cook_Mode {
	public static function init() {
		add_filter( 'wprm_recipe_cook_mode_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Cook mode shortcode.
	 *
	 * @since	10.2.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		// Make sure a recipe is set.
		if ( ! $recipe || ! $recipe->id() ) {
			return $output;
		}

		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-cook-mode-icon">' . $icon . '</span> ';
			}
		}

		// Output.
		$classes = array(
			'wprm-recipe-cook-mode',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-cook-mode-' . $atts['style'];
			$classes[] = 'wprm-recipe-link-' . $atts['style'];
			$classes[] = 'wprm-color-accent';

			$style .= 'background-color: ' . $atts['button_color'] . ';';
			$style .= 'border-color: ' . $atts['border_color'] . ';';
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
			$style .= 'padding: ' . $atts['vertical_padding'] . ' ' . $atts['horizontal_padding'] . ';';
		}

		// Backwards compatibility.
		if ( 'legacy' === WPRM_Settings::get( 'recipe_template_mode' ) ) {
			$style = '';
		}

		// Text and optional aria-label.
		$text = WPRM_i18n::maybe_translate( $atts['text'] );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . __( 'Start Cooking', 'wp-recipe-maker' ) . '"';
		}

		$output = '<a href="#" role="button" style="' . esc_attr( $style ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';
		return $output;
	}
}

WPRMP_SC_Cook_Mode::init();