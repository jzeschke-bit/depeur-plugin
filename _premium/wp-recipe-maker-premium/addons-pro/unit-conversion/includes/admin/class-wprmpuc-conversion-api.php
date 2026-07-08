<?php
/**
 * Handles interactions with the Nutrition API.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/admin
 */

/**
 * Handles interactions with the Nutrition API.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Conversion_Api {


	/**
	 * Convert a specific ingredient to a specific unit.
	 *
	 * @since    1.0.0
	 * @param    mixed $amount  Amount to convert.
	 * @param    mixed $unit    Unit to convert.
	 * @param    mixed $name    Name of the ingredient to convert.
	 * @param    mixed $unit_to Unit to convert to.
	 */
	public static function convert_ingredient( $amount, $unit, $name, $unit_to ) {
		// Remove special characters and numbers from name.
		$name = trim( preg_replace('/[^a-z\s]/i', '', $name ) );

		$args = array(
			'ingredientName' => $name,
			'sourceAmount' => $amount,
			'sourceUnit' => $unit,
			'targetUnit' => $unit_to,
		);

		return self::api_call( 'convert', $args );
	}

	/**
	 * Parse an ingredient.
	 *
	 * @since    8.8.0
	 * @param    mixed $ingredient  Ingredient to parse.
	 */
	public static function parse_ingredient( $ingredient ) {
		$args = array(
			'ingredientList' => $ingredient,
			'servings' => '1',
		);

		return self::api_call( 'parse_ingredient', $args );
	}

	/**
	 * Perform an API call.
	 *
	 * @since    1.0.0
	 * @param 	 mixed $function API function to call.
	 * @param 	 array $args     Arguments for the API call.
	 */
	private static function api_call( $function, $args ) {
		$endpoint = '';
		$proxy_data = array();

		switch ( $function ) {
			case 'convert':
				$endpoint = 'recipes/convert';
				$proxy_data = $args;
				break;
			case 'parse_ingredient':
				$endpoint = 'recipes/parseIngredients';
				$proxy_data = $args;
				break;
			default:
				return false;
		}

		// Add endpoint to proxy data.
		$proxy_data['endpoint'] = $endpoint;

		// Use POST endpoint for parseIngredients, GET for convert.
		$endpoint_key = 'parse_ingredient' === $function ? 'food_post' : 'food';
		$response = WPRMP_Proxy::call( $endpoint_key, $proxy_data );

		if ( false === $response || null === $response ) {
			return array();
		}

		return $response;
	}
}
