<?php
/**
 * Handle the proxy server for premium features.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the proxy server for premium features.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Proxy {

	/**
	 * Demo mode flag. When enabled, returns demo data instead of calling the proxy API.
	 *
	 * @since    1.0.0
	 * @var      bool
	 */
	public static $demo_mode = false;

	/**
	 * Call the proxy server.
	 *
	 * @since    1.0.0
	 * @param    string $endpoint_key Endpoint key to call.
	 * @param    array  $data         Data to send.
	 * @param    array  $headers      Additional headers.
	 */
	public static function call( $endpoint_key, $data = array(), $headers = array() ) {
		// Return demo data if demo mode is enabled.
		if ( self::$demo_mode ) {
			return self::get_demo_data( $endpoint_key, $data );
		}
		// Valid endpoints.
		$endpoints = array(
			'food' => array(
				'url' => 'api/food',
				'method' => 'GET',
			),
			'food_post' => array(
				'url' => 'api/food',
				'method' => 'POST',
			),
			'ai_suggest_tags' => array(
				'url' => 'api/ai-suggest-tags',
				'method' => 'POST',
			),
			'ai_generate_ideas' => array(
				'url' => 'api/ai-generate-ideas',
				'method' => 'POST',
			),
			'ai_review_nutrition_matches' => array(
				'url' => 'api/ai-review-nutrition-matches',
				'method' => 'POST',
			),
			'ai_review_unit_conversions' => array(
				'url' => 'api/ai-review-unit-conversions',
				'method' => 'POST',
			),
			'ai_recipe_import' => array(
				'url' => 'api/ai-recipe-import',
				'method' => 'POST',
			),
		);

		// Check if endpoint exists.
		if ( ! isset( $endpoints[ $endpoint_key ] ) ) {
			return false;
		}
		$endpoint = $endpoints[ $endpoint_key ];

		// Get license key details.
		$license_details = WPRMP_License::get_license_details();

		// Add AI language header for AI endpoints.
		if ( 0 === strpos( $endpoint_key, 'ai_' ) ) {
			$default_language = WPRM_Settings::get( 'ai_assistant_default_language' );
			if ( ! $default_language ) {
				$default_language = 'auto';
			}
			$headers['X-AI-Language'] = $default_language;
		}

		// Add additional headers.
		$headers = array_merge( array(
			'accept' => 'application/json',
			'content-type' => 'application/json',
			'X-Plugin-Version'  => WPRMP_VERSION,
			'X-Site-URL'        => home_url(),
			'X-Proxy-Secret'    => 'Kpw8mxnRtY3vqLsNjh9kBZWd',
			'X-License-Key'     => $license_details['key'],
			'X-Product-ID'      => $license_details['item_id'],
		), $headers );

		// Clean up data before sending.
		$data = self::cleanup_data( $data );

		// Build URL with query parameters for GET requests
		$url = 'https://proxy.bootstrapped.ventures/' . $endpoint['url'];
		if ( 'GET' === $endpoint['method'] && ! empty( $data ) ) {
			$url .= '?' . http_build_query( $data );
		}

		// Call proxy server.
		$request_args = array(
			'timeout' => 60,
			'sslverify' => false,
			'headers' => $headers,
		);

		if ( 'POST' === $endpoint['method'] ) {
			$request_args['body'] = json_encode( $data );
			$response = wp_remote_post( $url, $request_args );
		} else {
			$response = wp_remote_get( $url, $request_args );
		}

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true ); // true = return as array instead of object
		
		return $decoded;
	}

	/**
	 * Clean up data before sending to proxy endpoint.
	 * If data contains a recipe object, only keep fields needed by the proxy AI function.
	 *
	 * @since    1.0.0
	 * @param    array $data Data to clean up.
	 * @return   array Cleaned data.
	 */
	private static function cleanup_data( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['recipe'] ) ) {
			return $data;
		}

		// Clean up recipe object, only keep fields needed by proxy AI function.
		$recipe = $data['recipe'];
		$allowed_fields = array(
			'name',
			'summary',
			'ingredients_flat',
			'instructions_flat',
			'servings',
			'servings_unit',
			'prep_time',
			'cook_time',
			'total_time',
		);

		$cleaned_recipe = array();
		foreach ( $allowed_fields as $field ) {
			if ( isset( $recipe[ $field ] ) ) {
				$cleaned_recipe[ $field ] = $recipe[ $field ];
			}
		}

		$data['recipe'] = $cleaned_recipe;
		return $data;
	}

	/**
	 * Get demo data for an endpoint when demo mode is enabled.
	 *
	 * @since    1.0.0
	 * @param    string $endpoint_key Endpoint key.
	 * @param    array  $data         Request data.
	 * @return   array|false Demo data or false if endpoint not supported.
	 */
	private static function get_demo_data( $endpoint_key, $data = array() ) {
		switch ( $endpoint_key ) {
			case 'ai_suggest_tags':
				// Return demo suggestions based on categories in the request.
				$categories = isset( $data['categories'] ) ? $data['categories'] : array();
				$suggestions = array();

				// Generate demo suggestions for each category.
				foreach ( $categories as $category ) {
					// Handle both object format (with 'key' property) and string format.
					$category_key = is_array( $category ) && isset( $category['key'] ) ? $category['key'] : $category;
					
					switch ( $category_key ) {
						case 'course':
							$suggestions[ $category_key ] = array( 'Main Course', 'Dessert', 'Appetizer', 'Side Dish' );
							break;
						case 'cuisine':
							$suggestions[ $category_key ] = array( 'Italian', 'American', 'French', 'Asian' );
							break;
						case 'keyword':
							$suggestions[ $category_key ] = array( 'Easy', 'Quick', 'Healthy', 'Vegetarian' );
							break;
						case 'suitablefordiet':
							$suggestions[ $category_key ] = array( 'VegetarianDiet', 'VeganDiet', 'LowFatDiet' );
							break;
						default:
							$suggestions[ $category_key ] = array( 'Demo Tag 1', 'Demo Tag 2', 'Demo Tag 3' );
							break;
					}
				}

				return array(
					'success' => true,
					'suggestions' => $suggestions,
				);

			case 'ai_generate_ideas':
				$count = isset( $data['count'] ) ? intval( $data['count'] ) : 5;
				$ideas = array();

				$demo_ideas = array(
					array( 'name' => 'Lemon Herb Grilled Chicken', 'summary' => 'A bright and flavorful grilled chicken marinated in lemon juice, garlic, and fresh herbs. Perfect for summer dinners.', 'type' => 'recipe' ),
					array( 'name' => 'Spicy Thai Basil Noodles', 'summary' => 'Quick stir-fried rice noodles with Thai basil, chili, and vegetables. Ready in under 20 minutes.', 'type' => 'recipe' ),
					array( 'name' => 'Top 10 Easy Weeknight Dinners', 'summary' => 'A curated roundup of the best simple dinner recipes that can be made in 30 minutes or less.', 'type' => 'list' ),
					array( 'name' => 'Mediterranean Quinoa Bowl', 'summary' => 'A nutritious grain bowl with roasted vegetables, feta cheese, olives, and a tangy lemon dressing.', 'type' => 'recipe' ),
					array( 'name' => 'Classic Banana Bread', 'summary' => 'Moist and tender banana bread with a crispy top. Uses overripe bananas for maximum flavor.', 'type' => 'recipe' ),
					array( 'name' => 'Best Meal Prep Recipes for Beginners', 'summary' => 'A collection of easy-to-prepare recipes that store well and simplify your weekly meal planning.', 'type' => 'list' ),
					array( 'name' => 'Creamy Tuscan Pasta', 'summary' => 'Rich and creamy pasta with sun-dried tomatoes, spinach, and garlic in a parmesan cream sauce.', 'type' => 'recipe' ),
					array( 'name' => 'Crispy Air Fryer Tofu', 'summary' => 'Extra crispy tofu bites made in the air fryer with a savory soy-ginger glaze. Great as a protein addition to any bowl.', 'type' => 'recipe' ),
					array( 'name' => 'Seasonal Fall Soup Recipes', 'summary' => 'Warm and comforting soup recipes featuring autumn ingredients like butternut squash, apple, and sweet potato.', 'type' => 'list' ),
					array( 'name' => 'Homemade Pizza Dough', 'summary' => 'Simple no-knead pizza dough that rises overnight for the perfect chewy, crispy crust every time.', 'type' => 'recipe' ),
				);

				for ( $i = 0; $i < min( $count, count( $demo_ideas ) ); $i++ ) {
					$ideas[] = $demo_ideas[ $i ];
				}

				return array(
					'success' => true,
					'ideas'   => $ideas,
				);

			case 'food':
			case 'food_post':
				// Return demo nutrition data.
				return array(
					'success' => true,
					'data' => array(
						'name' => isset( $data['name'] ) ? $data['name'] : 'Demo Food',
						'calories' => 100,
						'protein' => 5,
						'fat' => 3,
						'carbs' => 15,
					),
				);

			default:
				return false;
		}
	}
}
