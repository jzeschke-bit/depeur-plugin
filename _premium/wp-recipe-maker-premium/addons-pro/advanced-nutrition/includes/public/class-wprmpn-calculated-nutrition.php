<?php
/**
 * Handle the calculated nutrition fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.3.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 */

/**
 * Handle the calculated nutrition fields.
 *
 * @since      5.3.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPN_Calculated_Nutrition {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.3.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_nutrition', array( __CLASS__, 'add_calculated_nutrition_fields' ) );
		add_filter( 'wprm_nutrition_ingredient_nutrition', array( __CLASS__, 'add_calculated_nutrition_fields' ) );
	}

	/**
	 * Add calculated nutrition fields.
	 *
	 * @since   5.3.0
	 * @param	array $nutrition Nutrition values for a recipe.
	 */
	public static function add_calculated_nutrition_fields( $nutrition ) {
		$calculated = self::get_calculated_nutrition_fields( $nutrition );
		return array_replace( $nutrition, $calculated );
	}

	/**
	 * Get calculated nutrition fields.
	 *
	 * @since   5.3.0
	 * @param	array $nutrition Nutrition values for a recipe.
	 */
	public static function get_calculated_nutrition_fields( $nutrition ) {
		$calculated = array();
		$fields = WPRM_Nutrition::get_fields();

		foreach ( $fields as $nutrient => $options ) {
			if ( 'calculated' === $options['type'] ) {
				$calculated_nutrient = self::calculate( $options['calculation'], $options['precision'], $nutrition );

				if ( false !== $calculated_nutrient ) {
					$calculated[ $nutrient ] = $calculated_nutrient;
				}
			}
		}

		return $calculated;
	}

	/**
	 * Perform a nutrition calculation.
	 *
	 * @since    5.3.0
	 * @param	array $calculation Calculation to perform.
	 * @param	array $nutrition   Nutrition values for the recipe.
	 */
	public static function calculate( $calculation, $precision, $nutrition ) {
		require_once( WPRMPN_DIR . 'vendor/matex/Evaluator.php' );

		$evaluator = new \Matex\Evaluator();
		$evaluator->onVariable = function( $name, &$value ) use ( $nutrition ) {
			$value = 0;
			if ( isset( $nutrition[ $name ] ) ) {
				$nutrition_value = floatval( trim( str_replace( ',', '.', $nutrition[ $name ] ) ) );

				if ( is_numeric( $nutrition_value ) ) {
					$value = $nutrition_value;
				}
			}
		};

		// Use try-catch to prevent division by zero problems.
		try {
			$result = $evaluator->execute( $calculation );
			$result = round( $result, $precision );
		} catch (Exception $e) {
			$result = false;
		}

		return $result;
	}
}

WPRMPN_Calculated_Nutrition::init();
