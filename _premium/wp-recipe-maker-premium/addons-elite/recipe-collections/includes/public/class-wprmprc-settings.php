<?php
/**
 * Settings for Recipe Collections.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Settings for Recipe Collections.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Settings {

	/**
	 * Register actions and filters.
	 *
	 * @since    2.1.0
	 */
	public static function init() {
		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
	}

	/**
	 * Add recipe submission settings.
	 *
	 * @since    3.0.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMPRC_DIR . 'templates/admin/settings/recipe-collections.php' );
		$structure['recipeCollections'] = $recipe_collections;

		// Insert before recipeCollections key in $structure.
		$structure = array_merge(
			array_slice( $structure, 0, array_search( 'recipeCollections', array_keys( $structure ) ) ),
			array(
				'group_collections' => array( 'header' => __( 'Meal Planning', 'wp-recipe-maker' ) ),
			),
			array_slice( $structure, array_search( 'recipeCollections', array_keys( $structure ) ) )
		);

		// Insert after recipeCollections key in $structure.
		$structure = array_merge(
			array_slice( $structure, 0, array_search( 'recipeCollections', array_keys( $structure ) ) + 1 ),
			array(
				'recipeCollectionsAppearance' => $recipe_collections_appearance,
				'recipeCollectionsFunctionality' => $recipe_collections_functionality,
				'recipeCollectionsShoppingList' => $recipe_collections_shopping_list,
			),
			array_slice( $structure, array_search( 'recipeCollections', array_keys( $structure ) ) + 1 )
		);

		return $structure;
	}
}

WPRMPRC_Settings::init();
