<?php
/**
 * Handle the Premium recipe instructions shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium recipe instructions shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Instructions {
	public static function init() {
		add_filter( 'wprm_recipe_instructions_shortcode_checkbox', array( __CLASS__, 'checkbox' ) );
	}

	/**
	 * Add checkboxes.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 */
	public static function checkbox( $output ) {
		return WPRMP_Checkboxes::checkbox( $output );
	}
}

WPRMP_SC_Instructions::init();