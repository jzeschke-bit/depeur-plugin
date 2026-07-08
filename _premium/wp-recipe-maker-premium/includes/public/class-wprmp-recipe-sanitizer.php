<?php
/**
 * Santize premium recipe input fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Santize premium recipe input fields.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Recipe_Sanitizer {
	public static function init() {
		add_filter( 'wprm_recipe_sanitize', array( __CLASS__, 'sanitize' ), 10, 2 );
	}

	/**
	 * Sanitize recipe array.
	 *
	 * @since	5.6.0
	 * @param	array $sanitized_recipe Sanitized recipe.
	 * @param	array $recipe 			Array containing all recipe input data.
	 */
	public static function sanitize( $sanitized_recipe, $recipe ) {
		// Global ingredient links.
		if ( isset( $recipe['global_ingredient_links'] ) ) {
			WPRMP_Ingredient_Links::update_ingredient_links( $recipe['global_ingredient_links'] );
		}

		// Custom fields.
		if ( isset( $recipe['custom_fields'] ) && WPRM_Addons::is_active( 'custom-fields' ) ) {
			$sanitized_recipe['custom_fields'] = WPRMPCF_Fields::sanitize( $recipe['custom_fields'] );
		}

		return $sanitized_recipe;
	}
}
WPRMP_Recipe_Sanitizer::init();
