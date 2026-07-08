<?php
/**
 * Handle the Premium add to collection shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium add to collection shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Add_To_Collection {
	public static function init() {
		add_filter( 'wprm_recipe_add_to_collection_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Add to collection shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		// Recipe or grid needs to be set.
		$grid_id = trim( $atts['grid'] );
		$recipe = WPRM_Template_Shortcodes::get_recipe( $atts['id'] );
		$button_type = $grid_id ? 'grid' : 'recipe';
		if ( ! $grid_id && ( ! $recipe || ! $recipe->id() ) ) {
			return '';
		}

		// If it's a grid button, only show when WP Ultimate Post Grid is activated.
		if ( 'grid' === $button_type ) {
			if ( ! class_exists( 'WPUPG_Grid_Manager' ) || false === WPUPG_Grid_Manager::get_grid( $grid_id ) ) {
				return '';
			}
		}

		// Make sure link to collections feature has been set.
		$recipe_collections_link = WPRM_Settings::get( 'recipe_collections_link' );
		if ( ! WPRM_Addons::is_active( 'recipe-collections' ) || ! $recipe_collections_link ) {
			return '';
		}

		// Check if user needs to be logged in.
		if ( ! is_user_logged_in() && 'logged_in' === WPRM_Settings::get( 'recipe_collections_access' ) && 'hide' === WPRM_Settings::get( 'recipe_collections_add_button_not_logged_in' ) ) {
			return;
		}

		$in_collection = 'recipe' === $button_type ? $recipe->in_collection( 'inbox' ) : false;

		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-add-to-collection-icon wprm-recipe-not-in-collection">' . $icon . '</span> ';
			}
		}
		$icon_added = '';
		if ( $atts['icon_added'] ) {
			$icon_added = WPRM_Icon::get( $atts['icon_added'], $atts['icon_color'] );

			if ( $icon_added ) {
				$icon_added = '<span class="wprm-recipe-icon wprm-recipe-add-to-collection-icon wprm-recipe-in-collection">' . $icon_added . '</span> ';
			}
		}

		// Output.
		$classes = array(
			'wprm-recipe-add-to-collection-' . $button_type,
			'wprm-recipe-add-to-collection',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		// Disabled class if button won't work.
		if ( ! is_user_logged_in() && 'logged_in' === WPRM_Settings::get( 'recipe_collections_access' ) && 'disabled' === WPRM_Settings::get( 'recipe_collections_add_button_not_logged_in' ) ) {
			$classes[] = 'wprm-recipe-link-disabled';
		}

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-add-to-collection-' . $atts['style'];
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
		$text = __( $atts['text'], 'wp-recipe-maker' );
		$text_added = __( $atts['text_added'], 'wp-recipe-maker' );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . __( 'Add to Collection', 'wp-recipe-maker' ) . '"';
		}
		$aria_label_added = '';
		if ( ! $text_added ) {
			$aria_label_added = ' aria-label="' . __( 'Go to Collections', 'wp-recipe-maker' ) . '"';
		}

		$output = '';
		if ( 'recipe' === $button_type ) {
			if ( ! $in_collection || 'choose' === WPRM_Settings::get( 'recipe_collections_add_button_behaviour' ) ) {
				$collections_data = json_encode( WPRMPRC_Manager::get_collections_data_for_recipe( $recipe ) );
	
				$output .= '<a href="' . esc_url( $recipe_collections_link ) . '" style="' . esc_attr( $style ) . '" class="wprm-recipe-not-in-collection ' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-recipe="' . esc_attr( $collections_data ) . '"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';
				$style .= 'display: none;';
			}
			$output .= '<a href="' . esc_url( $recipe_collections_link ) . '" style="' . esc_attr( $style ) . '" class="wprm-recipe-in-collection ' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-text-added=""' . $aria_label_added. '>' . $icon_added . WPRM_Shortcode_Helper::sanitize_html( $text_added ) . '</a>';
		} else {
			$output .= '<div class="wprm-recipe-add-to-collection-grid-container">';
			$output .= '<a href="' . esc_url( $recipe_collections_link ) . '" style="' . esc_attr( $style ) . '" class="wprm-recipe-not-in-collection ' . esc_attr( implode( ' ', $classes ) ) . '" data-grid-id="' . esc_attr( $grid_id ) . '"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';
			$style .= 'display: none;';
			$output .= '<a href="' . esc_url( $recipe_collections_link ) . '" style="' . esc_attr( $style ) . '" class="wprm-recipe-in-collection ' . esc_attr( implode( ' ', $classes ) ) . '" data-grid-id="' . esc_attr( $grid_id ) . '" data-text-added=""' . $aria_label_added. '>' . $icon_added . WPRM_Shortcode_Helper::sanitize_html( $text_added ) . '</a>';
			$output .= '<div>';
		}

		return $output;
	}
}

WPRMP_SC_Add_To_Collection::init();