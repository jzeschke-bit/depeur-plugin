<?php
/**
 * Responsible for displaying the Nutrition Label for recipes.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for displaying the Nutrition Label for recipes.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Nutrition_Label {

	/**
	 * Get nutrition label for a recipe.
	 *
	 * @since    1.0.0
	 * @param    object $recipe Recipe to show the nutrition label for.
	 */
	public static function nutrition_label( $recipe ) {
		ob_start();
		require( WPRMP_DIR . 'templates/public/nutrition-label.php' );
		$label = ob_get_contents();
		ob_end_clean();

		return $label;
	}
}
