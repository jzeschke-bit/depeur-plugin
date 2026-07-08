<?php
/**
 * Save premium recipe input fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Save premium recipe input fields.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Recipe_Saver {
	public static function init() {
		add_filter( 'wprm_recipe_save_meta', array( __CLASS__, 'meta' ), 10, 3 );
	}

	/**
	 * Save Premium recipe meta fields.
	 *
	 * @since	5.6.0
	 * @param	array $meta Meta fields to save.
	 * @param	int   $id Post ID of the recipe.
	 * @param	array $recipe Recipe fields to save.
	 */
	public static function meta( $meta, $id, $recipe ) {
		// Custom fields.
		if ( isset( $recipe['custom_fields'] ) && WPRM_Addons::is_active( 'custom-fields' ) ) {
			WPRMPCF_Fields::save( $id, $recipe['custom_fields'] );
		}

		return $meta;
	}
}
WPRMP_Recipe_Saver::init();
