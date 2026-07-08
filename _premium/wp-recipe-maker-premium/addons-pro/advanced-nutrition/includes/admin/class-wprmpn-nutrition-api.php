<?php
/**
 * Handles interactions with the Nutrition API.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/admin
 */

/**
 * Handles interactions with the Nutrition API.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPN_Nutrition_Api {


	/**
	 *  Current waiting time to prevent rate limiting.
	 *
	 * @since    8.10.2
	 * @access   private
	 * @var      string $waiting_time Waiting time.
	 */
	private static $waiting_time = 100000;

	/**
	 * Search for an ingredient by name.
	 *
	 * @since    1.0.0
	 * @param    mixed   $name    Name of the ingredient to search for.
	 * @param    integer $results Number of results to show. Defaults to 20.
	 */
	public static function search_ingredient( $name, $results = 20 ) {
		$args = array(
			'query' => wp_strip_all_tags( strip_shortcodes( $name ) ),
			'number' => $results,
			'metaInformation' => 'true',
		);

		return self::api_call( 'search', $args );
	}

	/**
	 * Get the nutrition data for a specific ingredient.
	 *
	 * @since    1.0.0
	 * @param    mixed   $amount Amount to get the nutrition facts for.
	 * @param    mixed   $unit   Unit to get the nutrition facts for.
	 * @param    integer $ingredient Ingredient ID to get the nutrition facts for.
	 */
	public static function get_nutrition_for( $amount, $unit, $ingredient ) {
		$args = array(
			'id' => $ingredient,
		);

		if ( $amount ) {
			$args['amount'] = wp_strip_all_tags( strip_shortcodes( $amount ) );
		}

		if ( $unit ) {
			$args['unit'] = wp_strip_all_tags( strip_shortcodes( $unit ) );
		}

		return self::api_call( 'nutrition', $args );
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
			case 'search':
				$endpoint = 'food/ingredients/autocomplete';
				$proxy_data = $args;
				break;
			case 'nutrition':
				$endpoint = 'food/ingredients/' . $args['id'] . '/information';
				unset( $args['id'] );
				$proxy_data = $args;
				break;
			default:
				return false;
		}

		// Add endpoint to proxy data.
		$proxy_data['endpoint'] = $endpoint;

		// Wait a little bit to prevent API rate limiting issues.
		usleep( self::$waiting_time );

		$response = WPRMP_Proxy::call( 'food', $proxy_data );

		if ( false === $response ) {
			return array();
		}

		// Handle rate limiting from proxy response.
		if ( isset( $response['error'] ) && 'Rate limit exceeded' === $response['error'] ) {
			// If we're not already waiting for 25 seconds or more, double the waiting time.
			if ( self::$waiting_time < 25600000 ) {
				self::$waiting_time *= 2;

				return self::api_call( $function, $args );
			}
		}

		return $response;
	}
}
