<?php
/**
 * Handle the Premium recipe favorite shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium recipe favorite shortcode.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Recipe_Favorite {
	public static function init() {
		add_filter( 'wprm_recipe_favorite_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Get tooltip data for a specific favorites button state.
	 *
	 * @since	10.6.0
	 * @param	string $setting Setting ID to load tooltip content from.
	 */
	private static function get_tooltip_data( $setting ) {
		$tooltip = trim( WPRM_Settings::get( $setting ) );

		if ( ! $tooltip ) {
			return '';
		}

		$link_text = trim( WPRM_Settings::get( 'favorite_recipes_link_text' ) );
		if ( ! $link_text ) {
			$link_text = __( 'Your Favorites', 'wp-recipe-maker-premium' );
		}

		// Check if there actually is a link set.
		$link = trim( WPRM_Settings::get( 'favorite_recipes_link' ) );

		$favorites_link = $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $link_text ) . '</a>' : esc_html( $link_text );
		$tooltip = str_replace( '%favorites_link%', $favorites_link, $tooltip );

		return WPRM_Tooltip::get_tooltip_data( $tooltip );
	}

	/**
	 * Recipe favorite shortcode.
	 *
	 * @since    10.6.0
	 * @param    mixed $output Current output.
	 * @param    array $atts   Options passed along with the shortcode.
	 * @param    mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ! $recipe->id() ) {
			return $output;
		}

		$is_favorited = WPRMP_Favorites::is_recipe_favorited( $recipe->id() );

		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-favorite-icon wprm-recipe-favorite-inactive">' . $icon . '</span> ';
			}
		}

		$icon_active = '';
		if ( $atts['icon_active'] ) {
			$icon_active = WPRM_Icon::get( $atts['icon_active'], $atts['icon_color'] );

			if ( $icon_active ) {
				$icon_active = '<span class="wprm-recipe-icon wprm-recipe-favorite-icon wprm-recipe-favorite-active">' . $icon_active . '</span> ';
			}
		}

		$classes = array(
			'wprm-recipe-favorite',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		if ( $atts['class'] ) {
			$classes[] = esc_attr( $atts['class'] );
		}

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-favorite-' . $atts['style'];
			$classes[] = 'wprm-recipe-link-' . $atts['style'];
			$classes[] = 'wprm-color-accent';

			$style .= 'background-color: ' . $atts['button_color'] . ';';
			$style .= 'border-color: ' . $atts['border_color'] . ';';
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
			$style .= 'padding: ' . $atts['vertical_padding'] . ' ' . $atts['horizontal_padding'] . ';';
		}

		if ( 'legacy' === WPRM_Settings::get( 'recipe_template_mode' ) ) {
			$style = '';
		}

		$text = __( $atts['text'], 'wp-recipe-maker' );
		$text_active = __( $atts['text_active'], 'wp-recipe-maker' );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . esc_attr__( 'Favorite Recipe', 'wp-recipe-maker' ) . '"';
		}

		$aria_label_active = '';
		if ( ! $text_active ) {
			$aria_label_active = ' aria-label="' . esc_attr__( 'Unfavorite Recipe', 'wp-recipe-maker' ) . '"';
		}

		$output = '';
		$output .= WPRM_Shortcode_Helper::get_section_header( $atts, 'favorite' );

		if ( (bool) $atts['has_container'] ) {
			$output .= WPRM_Shortcode_Helper::get_internal_container( $atts, 'favorite' );
		}

		$output .= '<span class="wprm-recipe-favorite-wrapper">';

		$inactive_style = $style . ( $is_favorited ? 'display: none;' : '' );
		$active_style = $style . ( $is_favorited ? '' : 'display: none;' );
		$inactive_tooltip_data = self::get_tooltip_data( 'favorite_recipes_tooltip_inactive' );
		$active_tooltip_data = self::get_tooltip_data( 'favorite_recipes_tooltip_active' );
		$inactive_classes = $classes;
		$active_classes = $classes;

		$inactive_classes[] = 'wprm-recipe-favorite-inactive';
		$active_classes[] = 'wprm-recipe-favorite-active';

		if ( $inactive_tooltip_data ) {
			$inactive_classes[] = 'wprm-tooltip';
		}

		if ( $active_tooltip_data ) {
			$active_classes[] = 'wprm-tooltip';
		}

		$output .= '<a href="#" style="' . esc_attr( $inactive_style ) . '" class="' . esc_attr( implode( ' ', $inactive_classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '"' . $inactive_tooltip_data . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';
		$output .= '<a href="#" style="' . esc_attr( $active_style ) . '" class="' . esc_attr( implode( ' ', $active_classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '"' . $active_tooltip_data . $aria_label_active . '>' . $icon_active . WPRM_Shortcode_Helper::sanitize_html( $text_active ) . '</a>';
		$output .= '</span>';

		if ( (bool) $atts['has_container'] ) {
			$output .= '</div>';
		}

		return $output;
	}
}

WPRMP_SC_Recipe_Favorite::init();
