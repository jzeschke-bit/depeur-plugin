<?php
/**
 * Handle the ingredient groups.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the ingredient groups.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Ingredient_Groups {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.0.0
	 */
	public static function init() {
	}

	/**
	 * Get the group for a specific ingredient.
	 *
	 * @since	5.0.0
	 * @param	array $ingredient_id Ingredient ID to get the group for.
	 */
	public static function get_group( $ingredient_id ) {
		if ( $ingredient_id ) {
			$group = get_term_meta( $ingredient_id, 'wprmp_ingredient_group', true );

			if ( $group ) {
				return $group;
			}
		}

		return '';
	}

	/**
	 * Set the group for a specific ingredient.
	 *
	 * @since	5.0.0
	 * @param	array $ingredient_id Ingredient ID to set the group for.
	 */
	public static function set_group( $ingredient_id, $group ) {
		if ( $ingredient_id ) {
			$group = sanitize_text_field( $group );
			update_term_meta( $ingredient_id, 'wprmp_ingredient_group', $group );
			return $group;
		}
		return '';
	}
}

WPRMPRC_Ingredient_Groups::init();
