<?php
/**
 * Handle the custom taxonomies.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the custom taxonomies.
 *
 * @since      1.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Custom_Taxonomies {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.2.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_taxonomies', array( __CLASS__, 'recipe_taxonomies' ) );
	}

	/**
	 * Add custom taxonomies to the recipe taxonomies.
	 *
	 * @since    1.2.0
	 * @param 	 array $taxonomies Recipe taxonomies.
	 */
	public static function recipe_taxonomies( $taxonomies ) {
		$custom_taxonomies = self::get_custom_taxonomies();

		// Only add non-existing ones.
		foreach ( $custom_taxonomies as $key => $options ) {
			if ( ! array_key_exists( $key, $taxonomies ) ) {
				$taxonomies[ $key ] = $options;
			}
		}

		return $taxonomies;
	}

	/**
	 * Get all custom taxonomies.
	 *
	 * @since    1.2.0
	 */
	public static function get_custom_taxonomies() {
		return get_option( 'wprm_custom_taxonomies', array() );
	}
}

WPRMP_Custom_Taxonomies::init();
