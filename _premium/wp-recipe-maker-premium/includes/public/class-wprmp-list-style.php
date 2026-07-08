<?php
/**
 * Advanced list style for the recipe template.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.10.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Advanced list style for the recipe template.
 *
 * @since      5.10.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_List_Style {

	/**
	 * Register actions and filters.
	 *
	 * @since	5.10.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_equipment_shortcode', array( __CLASS__, 'shortcode' ), 10, 2 );
		add_filter( 'wprm_recipe_ingredients_shortcode', array( __CLASS__, 'shortcode' ), 10, 2 );
		add_filter( 'wprm_recipe_instructions_shortcode', array( __CLASS__, 'shortcode' ), 10, 2 );
	}

	/**
	 * Filter shortcode output to add advanced styling.
	 *
	 * @since	5.10.0
	 */
	public static function shortcode( $output, $atts ) {
		if ( 'advanced' === $atts['list_style'] ) {
			// Check if $output is using ul or ol.
			$tag = stripos( $output, '<ol class="' ) !== false ? 'ol' : 'ul';

			// Add UID class, could be ol or ul.
			$output = str_ireplace( '<' . $tag . ' class="', '<' . $tag . ' class="wprm-advanced-list ', $output );

			// Continue numbering or not.
			if ( ! (bool) $atts['list_style_continue_numbers'] ) {
				$output = str_ireplace( 'wprm-advanced-list ', 'wprm-advanced-list wprm-advanced-list-reset ', $output );
			}

			// Get CSS variables to use.
			$css_variables = '';
			if ( '0px' !== $atts['list_style_top_position'] ) {
				$css_variables .= '--wprm-advanced-list-top-position: ' . $atts['list_style_top_position'] . ';';
			}
			if ( '0px' !== $atts['list_style_left_position'] ) {
				$css_variables .= '--wprm-advanced-list-left-position: ' . $atts['list_style_left_position'] . ';';
			}
			if ( '#444444' !== $atts['list_style_background'] ) {
				$css_variables .= '--wprm-advanced-list-background: ' . $atts['list_style_background'] . ';';
			}
			if ( '#ffffff' !== $atts['list_style_text'] ) {
				$css_variables .= '--wprm-advanced-list-text: ' . $atts['list_style_text'] . ';';
			}
			if ( '18px' !== $atts['list_style_size'] ) {
				$css_variables .= '--wprm-advanced-list-size: ' . $atts['list_style_size'] . ';';
			}
			if ( '12px' !== $atts['list_style_text_size'] ) {
				$css_variables .= '--wprm-advanced-list-text-size: ' . $atts['list_style_text_size'] . ';';
			}

			// If we have variables to add.
			if ( $css_variables ) {
				// If target tag already has style attribute, add variables to it, otherwise add new style attribute.
				if ( preg_match( '/<' . preg_quote( $tag, '/' ) . '([^>]*?) style="([^"]*)"/', $output, $matches ) ) {
					$output = str_replace( $matches[0], '<' . $tag . $matches[1] . ' style="' . $css_variables . $matches[2] . '"', $output );
				} else {
					$output = str_ireplace( '<' . $tag . ' ', '<' . $tag . ' style="' . $css_variables . '" ', $output );
				}	
			}
		}		
		return $output;
	}
}

WPRMP_List_Style::init();
