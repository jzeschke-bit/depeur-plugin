<?php
/**
 * Responsible for the Premium nutrition fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for the Premium nutrition fields.
 *
 * @since      5.3.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Nutrition {

	/**
	 * Cached version of the user nutrition fields.
	 *
	 * @since    5.3.0
	 * @access   private
	 * @var      array $user_nutrition_fields Array containing the user nutrition fields.
	 */
	private static $user_nutrition_fields = array();

	/**
	 * Register actions and filters.
	 *
	 * @since    5.3.0
	 */
	public static function init() {
		add_filter( 'wprm_nutrition_fields', array( __CLASS__, 'get_nutrition_fields' ), 10 );
	}

	/**
	 * Get nutrition fields.
	 *
	 * @since    5.3.0
	 */
	public static function get_nutrition_fields( $nutrition_fields ) {
		$nutrition_fields = self::get_defaults();

		// Get user nutrition fields from options just once.
		if ( empty( self::$user_nutrition_fields ) ) {
			self::$user_nutrition_fields = get_option( 'wprm_user_nutrition_fields', array() );
		}

		// Combine defaults with user options.
		foreach ( $nutrition_fields as $nutrient => $options ) {
			if ( isset( self::$user_nutrition_fields[ $nutrient ] ) ) {
				$nutrition_fields[ $nutrient ] = array_replace(
					$nutrition_fields[ $nutrient ],
					self::$user_nutrition_fields[ $nutrient ]
				);
			}
		}

		// Add custom and calculated fields when using Pro Bundle.
		if ( WPRM_Addons::is_active( 'pro' ) ) {
			foreach ( self::$user_nutrition_fields as $nutrient => $options ) {
				if ( ! isset( $nutrition_fields[ $nutrient ] ) ) {
					$nutrition_fields[ $nutrient ] = $options;
				}
			}
		}

		return $nutrition_fields;
	}

	/**
	 * Create a nutrient.
	 *
	 * @since    5.3.0
	 * @param    mixed $key 	 Key of the nutrient to create.
	 * @param    mixed $nutrient New options for the nutrient.
	 */
	public static function create( $key, $nutrient ) {
		$nutrient = self::sanitize( $nutrient );

		// Get all nutrients.
		$user_nutrition_fields = get_option( 'wprm_user_nutrition_fields', array() );
		$default_nutrition_fields = self::get_defaults();

		// Can't create existing nutrients.
		if ( ! $key || isset( $user_nutrition_fields[ $key ] ) || isset( $default_nutrition_fields[ $key ] ) ) {
			return false;
		}

		// Update option and local cache.
		$user_nutrition_fields[ $key ] = $nutrient;
		update_option( 'wprm_user_nutrition_fields', $user_nutrition_fields );
		self::$user_nutrition_fields = $user_nutrition_fields;

		return $nutrient;
	}

	/**
	 * Update a nutrient.
	 *
	 * @since    5.3.0
	 * @param    mixed $key 	 Key of the nutrient to update.
	 * @param    mixed $nutrient New options for the nutrient.
	 */
	public static function update( $key, $nutrient ) {
		$nutrient = self::sanitize( $nutrient );

		// Get custom nutrients.
		$user_nutrition_fields = get_option( 'wprm_user_nutrition_fields', array() );

		// Combine with any existing values.
		if ( isset( $user_nutrition_fields[ $key ] ) ) {
			$nutrient = array_replace( $user_nutrition_fields[ $key ], $nutrient );
		}
		$user_nutrition_fields[ $key ] = $nutrient;

		// Update option and local cache.
		update_option( 'wprm_user_nutrition_fields', $user_nutrition_fields );
		self::$user_nutrition_fields = $user_nutrition_fields;

		return $nutrient;
	}

	/**
	 * Sanitize a nutrient.
	 *
	 * @since    5.3.0
	 * @param    mixed $nutrient Nutrient values.
	 */
	public static function sanitize( $nutrient ) {
		$sanitized_nutrient = array();

		if ( isset( $nutrient['type'] ) && in_array( $nutrient['type'], array( 'internal', 'custom', 'calculated' ) ) ) {
			$sanitized_nutrient['type'] = $nutrient['type'];
		}

		if ( isset( $nutrient['active'] ) ) 	 { $sanitized_nutrient['active'] = $nutrient['active'] ? true : false; }
		if ( isset( $nutrient['label'] ) ) 		 { $sanitized_nutrient['label'] = sanitize_text_field( $nutrient['label'] ); }
		if ( isset( $nutrient['unit'] ) ) 		 { $sanitized_nutrient['unit'] = sanitize_text_field( $nutrient['unit'] ); }
		if ( isset( $nutrient['calculation'] ) ) { $sanitized_nutrient['calculation'] = sanitize_text_field( $nutrient['calculation'] ); }
		if ( isset( $nutrient['precision'] ) )   { $sanitized_nutrient['precision'] = intval( $nutrient['precision'] ); }
		if ( isset( $nutrient['order'] ) )   	 { $sanitized_nutrient['order'] = floatval( $nutrient['order'] ); }
		
		if ( isset( $nutrient['daily'] ) ) {
			$daily = floatval( $nutrient['daily'] );
			$sanitized_nutrient['daily'] = $daily > 0 ? $daily : false;
		}

		return $sanitized_nutrient;
	}

	/**
	 * Delete a nutrient.
	 *
	 * @since    5.3.0
	 * @param    mixed $key Key of the nutrient to delete.
	 */
	public static function delete( $key ) {
		// Get custom nutrients.
		$user_nutrition_fields = get_option( 'wprm_user_nutrition_fields', array() );
		unset( $user_nutrition_fields[ $key ] );

		// Update option and local cache.
		update_option( 'wprm_user_nutrition_fields', $user_nutrition_fields );
		self::$user_nutrition_fields = $user_nutrition_fields;

		return true;
	}

	/**
	 * Get nutrition label for a recipe.
	 *
	 * @since    5.3.0
	 * @param    object $recipe 	Recipe to show the nutrition label for.
	 * @param    string $name 		Name to display in the label.
	 * @param    array $nutrition 	Nutrition values to display.
	 */
	public static function label( $recipe, $name = false, $nutrition = false ) {
		// Backwards compatibility.
		$nutrition = $nutrition ? $nutrition : $recipe->nutrition();
		$name = $name ? $name : $recipe->name();

		$nutrition_fields = WPRM_Nutrition::get_fields();

		$has_nutritional_information = false;
		$output = array();

		if ( 'legacy' === WPRM_Settings::get( 'nutrition_label_style' ) ) {
			$type = WPRM_Settings::get( 'nutrition_label_legacy_servings_type' );
		} else {
			$layout = WPRMP_Nutrition_Label_Layout::get_layout();
			$properties = isset( $layout['properties'] ) ? $layout['properties'] : array();
			$blocks = isset( $layout['blocks'] ) ? $layout['blocks'] : array();

			$type = in_array( $properties['display_values'], array( 'serving', '100g' ) ) ? $properties['display_values'] : 'serving';
		}

		// Multiply factor defaults to 1.
		$multiply_factor = 1;

		$serving_unit = isset( $nutrition['serving_unit'] ) && $nutrition['serving_unit'] ? $nutrition['serving_unit'] : WPRM_Settings::get( 'nutrition_default_serving_unit' );
		// Check if we actually can display per 100g.
		if ( '100g' === $type ) {
			// Serving unit needs to be in grams (allow empty as well).
			if ( ! in_array( $serving_unit, array( '', 'g', 'gr', __( 'gram', 'wp-recipe-maker' ), __( 'grams', 'wp-recipe-maker' ) ) ) ) {
				return '';
			}

			// Serving size needs to be set to calculate multiply factor.
			$multiply_factor = false;

			$serving_size = isset( $nutrition[ 'serving_size' ] ) && false !== $nutrition[ 'serving_size' ] ? WPRM_Recipe_Parser::parse_quantity( $nutrition[ 'serving_size' ] ) : 0;
			if ( is_numeric( $serving_size ) && 0 < $serving_size ) {
				$multiply_factor = 100.00 / $serving_size;
			}

			// Need to have a factor to multiply by to display per 100g.
			if ( ! $multiply_factor ) {
				return '';
			}
		}

		foreach ( $nutrition_fields as $nutrient => $options ) {
			if ( isset( $nutrition[ $nutrient ] ) && false !== $nutrition[ $nutrient ] ) {
				if ( false !== $nutrition[ $nutrient ] && WPRM_Settings::get( 'nutrition_label_zero_values' ) || $nutrition[ $nutrient ] ) {
					$value = $nutrition[$nutrient];
					$display_value = $value;

					if ( 1 !== $multiply_factor ) {
						if ( 'serving_size' !== $nutrient ) {
							$value = WPRM_Recipe_Parser::parse_quantity( $nutrition[ $nutrient ] ) * $multiply_factor;
							$display_value = WPRM_Recipe_Parser::format_quantity( $value, WPRM_Settings::get( 'nutrition_facts_calculation_round_to_decimals' ) );
						}
					}

					$output[ $nutrient ] = array(
						'key' => $nutrient,
						'label' => __( $options['label'], 'wp-recipe-maker-premium' ),
						'value' => $display_value,
						'unit' => $options['unit'],
						'percentage' => isset( $options['daily'] ) && $options['daily'] > 0 ? round( floatval( $value ) / $options['daily'] * 100 ) : false,
					);

					$has_nutritional_information = true;
				}
			}
		}

		// Don't continue if there are no nutrition facts.
		if ( ! $has_nutritional_information ) {
			return '';
		}

		// Calculate calories if not set.
		$fat_calories = isset( $output['fat'] ) ? round( floatval( $output['fat']['value'] ) * 9 ) : 0;

		if ( ! isset( $output['calories'] ) ) {
			$proteins = isset( $output['protein'] ) ? $output['protein']['value'] : 0;
			$carbs = isset( $output['carbohydrates'] ) ? $output['carbohydrates']['value'] : 0;

			$calories = ( ( $proteins + $carbs ) * 4 ) + $fat_calories;

			$output['calories'] = array(
				'key' => 'calories',
				'label' => $nutrition_fields['calories']['label'],
				'value' => $calories,
				'unit' => $nutrition_fields['calories']['unit'],
				'percentage' => $nutrition_fields['calories']['daily'] > 0 ? round( floatval( $calories ) / $nutrition_fields['calories']['daily'] * 100 ) : false,
			);
		}

		// Include legacy or modern template.
		if ( 'legacy' === WPRM_Settings::get( 'nutrition_label_style' ) ) {
			ob_start();
			require( WPRMP_DIR . 'templates/public/nutrition-label-legacy.php' );
			$label = ob_get_contents();
			ob_end_clean();
		} else {
			ob_start();
			require( WPRMP_DIR . 'templates/public/nutrition-label-modern.php' );
			$label = ob_get_contents();
			ob_end_clean();
		}

		return $label;
	}

	/**
	 * Get default nutrition fields.
	 *
	 * @since    5.3.0
	 */
	public static function get_defaults() {
		$nutrition_fields = array(
			'serving_size' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Serving', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => false,
				'order' => 10,
			),
			'calories' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Calories', 'wp-recipe-maker-premium' ),
				'unit' => 'kcal',
				'api' => 'Calories',
				'daily' => 2000,
				'order' => 20,
			),
			'carbohydrates' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Carbohydrates', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Carbohydrates',
				'daily' => 300,
				'order' => 30,
			),
			'protein' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Protein', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Protein',
				'daily' => 50,
				'order' => 40,
			),
			'fat' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Fat', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Fat',
				'daily' => 65,
				'order' => 50,
			),
			'saturated_fat' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Saturated Fat', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Saturated Fat',
				'daily' => 16,
				'order' => 60,
			),
			'polyunsaturated_fat' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Polyunsaturated Fat', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Poly Unsaturated Fat',
				'order' => 70,
			),
			'monounsaturated_fat' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Monounsaturated Fat', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Mono Unsaturated Fat',
				'order' => 80,
			),
			'trans_fat' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Trans Fat', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Trans Fat',
				'order' => 90,
			),
			'cholesterol' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Cholesterol', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Cholesterol',
				'daily' => 300,
				'order' => 100,
			),
			'sodium' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Sodium', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Sodium',
				'daily' => 2300,
				'order' => 110,
			),
			'potassium' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Potassium', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Potassium',
				'daily' => 3500,
				'order' => 120,
			),
			'fiber' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Fiber', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Fiber',
				'daily' => 24,
				'order' => 130,
			),
			'sugar' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Sugar', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Sugar',
				'daily' => 90,
				'order' => 140,
			),
			'vitamin_a' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Vitamin A', 'wp-recipe-maker-premium' ),
				'unit' => 'IU',
				'api' => 'Vitamin A',
				'daily' => 5000,
				'order' => 150,
			),
			'vitamin_b1' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B1', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin B1',
				'daily' => 1.5,
				'order' => 160,
			),
			'vitamin_b2' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B2', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin B2',
				'daily' => 1.7,
				'order' => 170,
			),
			'vitamin_b3' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B3', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin B3',
				'daily' => 20,
				'order' => 180,
			),
			'vitamin_b5' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B5', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin B5',
				'daily' => 10,
				'order' => 190,
			),
			'vitamin_b6' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B6', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin B6',
				'daily' => 2,
				'order' => 200,
			),
			'vitamin_b12' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin B12', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Vitamin B12',
				'daily' => 6,
				'order' => 210,
			),
			'vitamin_c' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Vitamin C', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin C',
				'daily' => 82.5,
				'order' => 220,
			),
			'vitamin_d' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin D', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Vitamin D',
				'daily' => 15,
				'order' => 230,
			),
			'vitamin_e' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin E', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Vitamin E',
				'daily' => 15,
				'order' => 240,
			),
			'vitamin_k' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Vitamin K', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Vitamin K',
				'daily' => 105,
				'order' => 250,
			),
			'calcium' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Calcium', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Calcium',
				'daily' => 1000,
				'order' => 260,
			),
			'copper' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Copper', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Copper',
				'daily' => 2,
				'order' => 270,
			),
			'folate' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Folate', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Folate',
				'daily' => 400,
				'order' => 280,
			),
			'iron' => array(
				'type' => 'internal',
				'active' => true,
				'label' => __( 'Iron', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Iron',
				'daily' => 18,
				'order' => 290,
			),
			'manganese' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Manganese', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Manganese',
				'daily' => 2,
				'order' => 300,
			),
			'magnesium' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Magnesium', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Magnesium',
				'daily' => 400,
				'order' => 310,
			),
			'phosphorus' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Phosphorus', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Phosphorus',
				'daily' => 1000,
				'order' => 320,
			),
			'selenium' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Selenium', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Selenium',
				'daily' => 70,
				'order' => 330,
			),
			'zinc' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Zinc', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Zinc',
				'daily' => 15,
				'order' => 340,
			),
			'caffeine' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Caffeine', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Caffeine',
				'order' => 350,
			),
			'alcohol' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Alcohol', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Alcohol',
				'order' => 360,
			),
			'folic_acid' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Folic Acid', 'wp-recipe-maker-premium' ),
				'unit' => 'µg',
				'api' => 'Folic Acid',
				'order' => 370,
			),
			'choline' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Choline', 'wp-recipe-maker-premium' ),
				'unit' => 'mg',
				'api' => 'Choline',
				'order' => 380,
			),
			'net_carbohydrates' => array(
				'type' => 'internal',
				'active' => false,
				'label' => __( 'Net Carbohydrates', 'wp-recipe-maker-premium' ),
				'unit' => 'g',
				'api' => 'Net Carbohydrates',
				'order' => 390,
			),
		);

		return $nutrition_fields;
	}
}

WPRMP_Nutrition::init();
