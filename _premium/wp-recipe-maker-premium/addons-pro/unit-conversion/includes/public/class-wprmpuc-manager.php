<?php
/**
 * Handles the unit conversion.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 */

/**
 * Handles the unit conversion.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Manager {

	private static $weight_units = array( 'pound', 'ounce', 'kilogram', 'gram', 'milligram' );
	private static $volume_units = array( 'gallon', 'quart', 'pint', 'fluid_ounce', 'liter', 'deciliter', 'centiliter', 'milliliter', 'tablespoon', 'teaspoon' );
	private static $base_unit_conversions = array(
		// Base unit for weights is grams (1g = 1ml).
		'pound' => 453.592,
		'ounce' => 28.3496,
		'kilogram' => 1000.0,
		'gram' => 1.0,
		'milligram' => 0.001,
		// Base unit for volume is milliliters (1g = 1ml).
		'gallon' => 3785.41,
		'quart' => 946.352,
		'pint' => 473.176,
		'fluid_ounce' => 29.5735,
		'liter' => 1000.0,
		'deciliter' => 100.0,
		'centiliter' => 10.0,
		'milliliter' => 1.0,
		'tablespoon' => 14.7868,
		'teaspoon' => 4.92892,
	);

	/**
	 * Calculate unit conversion for an ingredient.
	 *
	 * @since    5.0.0
	 * @param 	 mixed   $ingredient Ingredient to calculate the unit conversion for.
	 * @param 	 int   	 $system     Unit System to convert to.
	 */
	public static function calculate_unit_conversion( $ingredient, $system = 2 ) {
		$conversion = false;

		// Clean up ingredient.
		$ingredient['amount'] = wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) );
		$ingredient['unit'] = wp_strip_all_tags( strip_shortcodes( $ingredient['unit'] ) );
		$ingredient['name'] = wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) );

		// Get units from and to.
		$unit_from = self::get_unit_from_alias( $ingredient['unit'] );
		$units_to = isset( $ingredient['units_to'] ) ? $ingredient['units_to'] : false;

		if ( ! $units_to ) {
			$units_to = self::calculate_units( $ingredient, $unit_from, $system );
		}

		if ( $units_to ) {
			$conversion = self::calculate_best_conversion( $ingredient, $unit_from, $units_to, $system );
		}

		if ( ! $conversion ) {
			$conversion = array(
				'amount' => $ingredient['amount'],
				'unit' => $unit_from,
				'alias' => $ingredient['unit'],
				'type' => 'failed',
			);
		} else {
			// Conversion type.
			$conversion['type'] = $conversion['unit'] === $unit_from ? 'none' : 'automatic';
		}

		return $conversion;
	}

	/**
	 * Calculate possible units to convert to for a specific unit.
	 *
	 * @since    1.0.0
	 * @param 	 mixed   $ingredient Ingredient to calculate the unit conversion for.
	 * @param 	 mixed   $unit_from Unit to convert from.
	 * @param 	 integer $system 	System to convert to.
	 */
	public static function calculate_units( $ingredient, $unit_from, $system ) {
		$units_to = false;

		// Don't recognize unit from, try to translate.
		if ( ! $unit_from ) {
			$translated = WPRMPT_Translate::translate( $ingredient['unit'] );
						
			if ( $translated ) {
				$unit_from = self::get_unit_from_alias( $translated );
			}
		}

		if ( $unit_from ) {
			$unit_type = in_array( $unit_from, self::$weight_units, true ) ? 'weight' : 'volume';

			// Special case: cup could be both weight or volume. Defaults to volume above.
			if ( 'cup' === $unit_from ) {
				$system_from = $system % 2 + 1;
				$system_from_weight_units = WPRM_Settings::get( 'unit_conversion_system_' . $system_from . '_weight_units' );
				$system_from_volume_units = WPRM_Settings::get( 'unit_conversion_system_' . $system_from . '_volume_units' );

				// Use weight if cup is only set as a potential weight unit.
				if ( in_array( 'cup', $system_from_weight_units ) && ! in_array( 'cup', $system_from_volume_units ) ) {
					$unit_type = 'weight';
				}

				// If cup is in both weight and volume units, use the API to check the ingredient consistency.
				if ( in_array( 'cup', $system_from_weight_units ) && in_array( 'cup', $system_from_volume_units ) ) {
					// Optionally get translated ingredient name for API.
					$api_name = WPRMPT_Translate::translate_or_keep( $ingredient['name'] );
					$consistency = self::get_consistency( $api_name );

					switch ( $consistency ) {
						case 'solid':
							$unit_type = 'weight';
							break;
						case 'liquid':
							$unit_type = 'volume';
							break;
					}
				}
			}

			// Get the potential units to convert to.
			$units_to = WPRM_Settings::get( 'unit_conversion_system_' . $system . '_' . $unit_type . '_units' );
		}

		return $units_to;
	}

	/**
	 * Calculate best unit conversion for a specific ingredient.
	 *
	 * @since    1.0.0
	 * @param 	 array $ingredient Ingredient to calculate the unit conversion for.
	 * @param 	 mixed $unit_from  Unit to convert from.
	 * @param 	 array $units_to   Possible units to convert to.
	 * @param 	 integer $system   System to convert to.
	 */
	public static function calculate_best_conversion( $ingredient, $unit_from, $units_to, $system = 2 ) {
		$best_conversion = false;

		// Check if we can just keep the same unit.
		if ( in_array( $unit_from, $units_to, true ) ) {
			// Same unit, same amount.
			$converted_amount = floatval( $ingredient['amount'] );
		
			// Check multiple type of cups, tablespoons and teaspoons.
			if ( 'cup' === $unit_from ) {
				$types = self::get_types( 'cup', $system );
				$converted_amount = self::convert_cup( $converted_amount, $types['from'], $types['to'] );
			} elseif ( 'tablespoon' === $unit_from ) {
				$types = self::get_types( 'tablespoon', $system );
				$converted_amount = self::convert_tablespoon( $converted_amount, $types['from'], $types['to'] );
			} elseif ( 'teaspoon' === $unit_from ) {
				$types = self::get_types( 'teaspoon', $system );
				$converted_amount = self::convert_teaspoon( $converted_amount, $types['from'], $types['to'] );
			}

			$best_conversion = array(
				'amount' => $converted_amount,
				'unit' => $unit_from,
				'alias' => $ingredient['unit'],
			);
		} else {
			// Find best match from possible units.
			foreach ( $units_to as $unit_to ) {
				$conversion = self::calculate_conversion( $ingredient, $unit_from, $unit_to, $system );

				if ( $conversion ) {
					if ( ! $best_conversion ) {
						$best_conversion = $conversion;
					} else {
						// Check if this new conversion is better than the other.
						$upper_limit = in_array( $conversion['unit'], array( 'teaspoon', 'tablespoon' ), true ) ? 10 : 999;

						if ( 1 <= $conversion['amount'] && $conversion['amount'] < $best_conversion['amount'] ) {
							$best_conversion = $conversion;
						} elseif ( $conversion['amount'] < $upper_limit && $conversion['amount'] > $best_conversion['amount'] ) {
							$best_conversion = $conversion;
						}
					}

					// Check if this conversion is good enough.
					$upper_limit = $best_conversion && in_array( $best_conversion['unit'], array( 'teaspoon', 'tablespoon' ), true ) ? 10 : 999;
					if ( 1 <= $best_conversion['amount'] && $best_conversion['amount'] <= $upper_limit ) {
						return $best_conversion;
					}
				}
			}
		}

		return $best_conversion;
	}

	/**
	 * Calculate unit conversion for a specific ingredient.
	 *
	 * @since    1.0.0
	 * @param 	 array $ingredient Ingredient to calculate the unit conversion for.
	 * @param 	 mixed $unit_from  Unit to convert from.
	 * @param 	 mixed $unit_to    Unit to convert to.
	 * @param 	 integer $system   System to convert to.
	 */
	public static function calculate_conversion( $ingredient, $unit_from, $unit_to, $system = 2 ) {
		$converted = false;

		$amount = floatval( $ingredient['amount'] );

		// Check if API calculation is needed.
		$api_calculation_needed = true;
		if ( $unit_from && $unit_to && 'cup' !== $unit_from && 'cup' !== $unit_to ) {
			// API calculation only not needed if unit from and to is within the same type (excluding cups).
			if ( in_array( $unit_from, self::$weight_units ) && in_array( $unit_to, self::$weight_units ) ) {
				$api_calculation_needed = false;
			} elseif ( in_array( $unit_from, self::$volume_units ) && in_array( $unit_to, self::$volume_units ) ) {
				$api_calculation_needed = false;
			}
		}

		if ( ! $api_calculation_needed ) {
			$unit_conversion_from = self::$base_unit_conversions;
			$unit_conversion_to = $unit_conversion_from;

			// Adjust based on spoon types.
			$tablespoon_types = self::get_types( 'tablespoon', $system );
			$teaspoon_types = self::get_types( 'teaspoon', $system );

			$unit_conversion_from['tablespoon'] = self::convert_tablespoon( $unit_conversion_from['tablespoon'], $tablespoon_types['from'], 'api' );
			$unit_conversion_from['teaspoon'] = self::convert_teaspoon( $unit_conversion_from['teaspoon'], $teaspoon_types['from'], 'api' );

			$unit_conversion_to['tablespoon'] = self::convert_tablespoon( $unit_conversion_to['tablespoon'], $tablespoon_types['to'], 'api' );
			$unit_conversion_to['teaspoon'] = self::convert_teaspoon( $unit_conversion_to['teaspoon'], $teaspoon_types['to'], 'api' );


			$converted_amount = $amount * $unit_conversion_from[ $unit_from ] / $unit_conversion_to[ $unit_to ];

			$converted = array(
				'amount' => $converted_amount,
				'unit' => $unit_to,
				'alias' => self::get_alias_for( $converted_amount, $unit_to ),
			);
		} else {
			// Maybe change cup, tablespoon or teaspoon value.
			$cup_types = self::get_types( 'cup', $system );
			$tablespoon_types = self::get_types( 'tablespoon', $system );
			$teaspoon_types = self::get_types( 'teaspoon', $system );

			if ( 'cup' === $unit_from ) {
				$amount = self::convert_cup( $amount, $cup_types['from'], 'api' );
			} elseif ( 'tablespoon' === $unit_from ) {
				$amount = self::convert_tablespoon( $amount, $tablespoon_types['from'], 'api' );
			} elseif ( 'teaspoon' === $unit_from ) {
				$amount = self::convert_teaspoon( $amount, $teaspoon_types['from'], 'api' );
			}
			
			// Get best unit to use for API.
			$api_unit = $unit_from ? str_replace( '_', ' ', $unit_from ) : false;

			// Try to translate if unit not found.
			if ( ! $api_unit ) {
				$translated = WPRMPT_Translate::translate( $ingredient['unit'] );
						
				if ( $translated ) {
					$translated_unit = WPRMPUC_Manager::get_unit_from_alias( $translated );

					if ( $translated_unit ) {
						$api_unit = str_replace( '_', ' ', $translated_unit );
					} else {
						$api_unit = $translated;
					}
				}
			}

			// Default to filled in unit name.
			if ( ! $api_unit ) {
				$api_unit = $ingredient['unit'];
			}

			// Optionally get translated ingredient name for API.
			$api_name = WPRMPT_Translate::translate_or_keep( $ingredient['name'] );

			$result = WPRMPUC_Conversion_Api::convert_ingredient( $amount, $api_unit, $api_name, str_replace( '_', ' ', $unit_to ) );

			if ( is_array( $result ) && ( !isset( $result['status'] ) || 'failure' !== $result['status'] ) && isset( $result['targetAmount'] ) && is_scalar( $result['targetAmount'] ) ) {
				$converted_amount = floatval( $result['targetAmount'] );

				// Maybe change cup value.
				if ( 'cup' === $unit_to ) {
					$converted_amount = self::convert_cup( $converted_amount, 'api', $cup_types['to'] );
				} elseif ( 'tablespoon' === $unit_to ) {
					$converted_amount = self::convert_tablespoon( $converted_amount, 'api', $tablespoon_types['to'] );
				} elseif ( 'teaspoon' === $unit_to ) {
					$converted_amount = self::convert_teaspoon( $converted_amount, 'api', $teaspoon_types['to'] );
				}

				$converted = array(
					'amount' => $converted_amount,
					'unit' => $unit_to,
					'alias' => self::get_alias_for( $converted_amount, $unit_to ),
				);
			}
		}
		return $converted;
	}

	/**
	 * Get unit from unit alias.
	 *
	 * @since    1.0.0
	 * @param 	 mixed $alias Alias to get the unit for.
	 */
	public static function get_unit_from_alias( $alias ) {
		// Clean up alias.
		$alias = trim( $alias );

		// Check all units for exact alias match.
		$units_data = WPRM_Settings::get( 'unit_conversion_units' );
		foreach ( $units_data as $unit => $data ) {
			if ( in_array( $alias, $data['aliases'], true ) ) {
				return $unit;
			}
		}

		// Nothing found? Check again, all lowercase.
		$alias = strtolower( $alias );

		foreach ( $units_data as $unit => $data ) {
			$aliases = array_map( 'strtolower', $data['aliases'] );
			if ( in_array( $alias, $aliases, true ) ) {
				return $unit;
			}
		}

		return false;
	}

	/**
	 * Get alias for a specific amount and unit.
	 *
	 * @since    1.0.0
	 * @param 	 float $amount Amount of the unit that we have.
	 * @param 	 mixed $unit   Unit to get the alias for.
	 */
	public static function get_alias_for( $amount, $unit ) {
		$units_data = WPRM_Settings::get( 'unit_conversion_units' );

		$type = 0 < $amount && $amount <= 1 ? 'singular' : 'plural';

		// If unit and type exist, return correct alias.
		if ( isset( $units_data[ $unit ] ) && isset( $units_data[ $unit ][ $type ] ) ) {
			return $units_data[ $unit ][ $type ];
		} else {
			// No standard unit found, maybe use plural of term.
			if ( 'plural' === $type ) {
				$unit_id = WPRM_Recipe_Sanitizer::get_ingredient_unit_id( $unit );

				if ( $unit_id ) {
					$plural = get_term_meta( $unit_id, 'wprm_ingredient_unit_plural', true );
					if ( $plural ) {
						return $plural;
					}
				}
			}
		}

		// No match, just use unit as is.
		return $unit;
	}

	/**
	 * Get the unit types.
	 *
	 * @since    7.0.0
	 * @param 	 mixed $unit   		Unit to get the types for.
	 * @param 	 mixed $system_to 	System we're converting to.
	 */
	public static function get_types( $unit, $system_to ) {
		$types = false;

		$system_from = 2 === $system_to ? 1 : 2;

		switch ( $unit ) {
			case 'cup':
			case 'tablespoon':
			case 'teaspoon':
				$types = array(
					'from' => WPRM_Settings::get( 'unit_conversion_system_' . $system_from . '_' . $unit . '_type' ),
					'to' => WPRM_Settings::get( 'unit_conversion_system_'  . $system_to . '_' . $unit . '_type' ),
				);
				break;
		}

		return $types;
	}

	/**
	 * Get consistency of an ingredient.
	 *
	 * @since    8.0.0
	 * @param 	 mixed $ingredient	Ingredient to get the consistency of.
	 */
	public static function get_consistency( $ingredient ) {
		$consistency = 'unknown';

		$result = WPRMPUC_Conversion_Api::parse_ingredient( $ingredient );

		if ( is_array( $result ) && ( !isset( $result['status'] ) || 'failure' !== $result['status'] ) ) {
			if ( isset( $result[0] ) ) {
				$match = (array) $result[0];
				if ( isset( $match['consistency'] ) ) {
					$consistency = $match['consistency'];
				}
			}
		}

		return $consistency;
	}

	/**
	 * Convert different cups.
	 *
	 * @since    7.0.0
	 * @param 	 float $amount Amount of the unit that we have.
	 * @param 	 mixed $from   Cup unit to convert from.
	 * @param 	 mixed $to     Cup unit to convert to.
	 */
	public static function convert_cup( $amount, $from, $to ) {
		$types = array(
			'japanese' => 200.00,
			'api' => 236.5882365,
			'us_customary' => 236.5882365,
			'us_legal' => 240.00,
			'metric' => 250.00,
		);

		// Make sure an existing cup type is set. Default to API cup.
		$from = in_array( $from, array_keys( $types ) ) ? $from : 'api';
		$to = in_array( $to, array_keys( $types ) ) ? $to : 'api';

		$converted = ( $amount / $types[ $to ] ) * $types[ $from ];

		return $converted;
	}

	/**
	 * Convert different tablespoons.
	 *
	 * @since    7.0.0
	 * @param 	 float $amount Amount of the unit that we have.
	 * @param 	 mixed $from   Tablespoon unit to convert from.
	 * @param 	 mixed $to     Tablespoon unit to convert to.
	 */
	public static function convert_tablespoon( $amount, $from, $to ) {
		$types = array(
			'api' => 14.7868,
			'us_customary' => 14.7868,
			'metric' => 15.00,
			'australian' => 20.00,
		);

		// Make sure an existing cup type is set. Default to API cup.
		$from = in_array( $from, array_keys( $types ) ) ? $from : 'api';
		$to = in_array( $to, array_keys( $types ) ) ? $to : 'api';

		$converted = ( $amount / $types[ $to ] ) * $types[ $from ];

		return $converted;
	}

	/**
	 * Convert different teaspoons.
	 *
	 * @since    7.0.0
	 * @param 	 float $amount Amount of the unit that we have.
	 * @param 	 mixed $from   Teaspoon unit to convert from.
	 * @param 	 mixed $to     Teaspoon unit to convert to.
	 */
	public static function convert_teaspoon( $amount, $from, $to ) {
		$types = array(
			'api' => 4.92892,
			'us_customary' => 4.92892,
			'metric' => 5.0,
		);

		// Make sure an existing cup type is set. Default to API cup.
		$from = in_array( $from, array_keys( $types ) ) ? $from : 'api';
		$to = in_array( $to, array_keys( $types ) ) ? $to : 'api';

		$converted = ( $amount / $types[ $to ] ) * $types[ $from ];

		return $converted;
	}

	/**
	 * Convert different units.
	 *
	 * @since    7.1.0
	 * @param 	 float $amount 	Amount of the unit that we have.
	 * @param 	 float $unit 	Type of unit that we have.
	 * @param 	 mixed $from   	Unit to convert from.
	 * @param 	 mixed $to     	Unit to convert to.
	 */
	public static function convert_unit( $amount, $unit, $from, $to ) {
		switch ( $unit ) {
			case 'cup':
				return self::convert_cup( $amount, $from, $to );
			case 'tablespoon':
				return self::convert_tablespoon( $amount, $from, $to );
			case 'teaspoon':
				return self::convert_teaspoon( $amount, $from, $to );
		}

		return $amount;
	}

	public static function maybe_combine_multiple_units( $lines ) {
		// Only 1, no need to combine.
		if ( 1 >= count( $lines ) ) {
			return $lines;
		}

		// Exactly 2, combine if possible and return.
		if ( 2 === count( $lines ) ) {
			$maybe_combined = self::maybe_combine_two_units( $lines[0], $lines[1] );

			if ( false !== $maybe_combined ) {
				return array( $maybe_combined );
			} else {
				return $lines;
			}
		}

		// More than 2, split.
		$combined = $lines;
		do {
			$starting_count = count( $combined );

			// Test all combinations.
			for ( $i = 0; $i < $starting_count - 1; $i++ ) {
				for ( $j = 1; $j < $starting_count; $j++ ) {
					if ( $i !== $j ) {
						$result = self::maybe_combine_multiple_units( array( $combined[ $i ], $combined[ $j ] ) );

						if ( 1 === count( $result ) ) {
							$combined[ $i ] = $result[0];
							array_splice( $combined, $j, 1 );

							// Get out of for loops.
							break 2;
						}
					}
				}
			}

			$maybe_reduced_count = count( $combined );
		} while ( $maybe_reduced_count < $starting_count && 1 < $maybe_reduced_count );

		return $combined;
	}

	public static function maybe_combine_two_units( $first, $second ) {
		$first_amount = WPRM_Recipe_Parser::parse_quantity( $first['amount'] );
		$second_amount = WPRM_Recipe_Parser::parse_quantity( $second['amount'] );

		// Same unit, combine.
		if ( $first['unit'] === $second['unit'] ) {
			return array(
				'amount' => $first_amount + $second_amount,
				'unit' => $first['unit'],
			);
		}

		// Check if both are units of the same type.
		if (
			( in_array( $first['unit'], self::$weight_units ) && in_array( $second['unit'], self::$weight_units ) )
			|| ( in_array( $first['unit'], self::$volume_units ) && in_array( $second['unit'], self::$volume_units ) )
		) {
			$first_amount = $first_amount * self::$base_unit_conversions[ $first['unit'] ];
			$second_amount = $second_amount * self::$base_unit_conversions[ $second['unit'] ];

			$combined = $first_amount + $second_amount;

			// Check which unit makes most sense.
			$amount_in_first_unit = $combined / self::$base_unit_conversions[ $first['unit'] ];
			$amount_in_second_unit = $combined / self::$base_unit_conversions[ $second['unit'] ];

			if ( self::get_amount_score( $amount_in_second_unit, $second['unit'] ) > self::get_amount_score( $amount_in_first_unit, $first['unit'] ) ) {
				return array(
					'amount' => $amount_in_second_unit,
					'unit' => $second['unit'],
				);
			} else {
				return array(
					'amount' => $amount_in_first_unit,
					'unit' => $first['unit'],
				);
			}
		}

		return false;
	}

	public static function get_amount_score( $amount, $unit ) {
		$score = 0;

		if ( in_array( $unit, array( 'tablespoon', 'teaspoon' ) ) ) {
			if ( $amount < 0.5 ) {
				$score -= 10;
			} elseif ( 5 < $amount ) {
				$score -= 10;
			} else {
				$score += 10;
			}
		} else {
			if ( $amount < 1 ) {
				$score -= 10;
			} elseif ( 1000 <= $amount ) {
				$score -= 5;
			} else {
				$score += 10;
			}
		}

		if ( round( $amount ) == $amount ) {
			$score += 5;
		}

		return $score;
	}
}