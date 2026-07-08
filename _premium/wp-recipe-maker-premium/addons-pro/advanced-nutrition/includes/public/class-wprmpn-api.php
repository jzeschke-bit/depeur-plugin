<?php
/**
 * Handle the Nutrition Calculation API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 */

/**
 * Handle the Nutrition Calculation API.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPN_Api {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.0.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/matches', array(
				'callback' => array( __CLASS__, 'api_get_matches' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/api/options', array(
				'callback' => array( __CLASS__, 'api_get_api_options' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/api/facts', array(
				'callback' => array( __CLASS__, 'api_get_api_facts' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/custom', array(
				'callback' => array( __CLASS__, 'api_create_custom_ingredient' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/custom/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_get_custom_ingredient' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/custom/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_update_custom_ingredient' ),
				'methods' => 'PUT',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/custom/search', array(
				'callback' => array( __CLASS__, 'api_search_custom_ingredients' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/custom/match', array(
				'callback' => array( __CLASS__, 'api_save_custom_match' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/nutrition/calculated', array(
				'callback' => array( __CLASS__, 'api_get_calculated_nutrition' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 5.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since 5.0.0
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Handle get matches call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_matches( $request ) {
		// Required classes.
		require_once( WPRMPN_DIR . 'includes/admin/class-wprmpn-nutrition-api.php' );

		// Parameters.
		$params = $request->get_params();

		$ingredients = isset( $params['ingredients'] ) ? $params['ingredients'] : array();

		foreach ( $ingredients as $index => $ingredient ) {
			// Check for previous match.
			$prev_match = false;
			$match = false;
			$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient['name'] );
			if ( $ingredient_id ) {
				$prev_match = get_term_meta( $ingredient_id, 'wprmpn_previous_match', true );
				$match = $prev_match;

				// Get nutrition facts for matched custom nutrition ingredient.
				if ( $match && $match['id'] && 'custom' === $match['source'] ) {
					$match['ingredient'] = WPRMPN_Ingredient_Manager::get_ingredient( $match['id'] );
				}
			}

			// Find potential matches if there is no previous match.
			$match_options = false;
			$match_search = '';
			if ( ! $match && isset( $ingredient['name'] ) && $ingredient['name'] ) {
				$search = WPRMPT_Translate::translate_or_keep( $ingredient['name'] );
				$match_options = WPRMPN_Nutrition_API::search_ingredient( $search );
				$match_search = $search;

				if ( $match_options && isset( $match_options[0] ) ) {
					$match = (array) $match_options[0];
				} else {
					// If we searched for translation and didn't find anything, search for regular name again.
					if ( $search !== $ingredient['name'] ) {
						$match_options = WPRMPN_Nutrition_API::search_ingredient( $ingredient['name'] );
						$match_search = $ingredient['name'];

						if ( $match_options && isset( $match_options[0] ) ) {
							$match = (array) $match_options[0];
						}
					}
				}
			}

			// Make sure array exists.
			if ( ! isset( $ingredients[ $index ]['nutrition'] ) ) {
				$ingredients[ $index ]['nutrition'] = array();
			}

			// Set matches.
			$ingredients[ $index ]['nutrition']['source'] = isset( $match['source'] ) ? $match['source'] : 'api';
			$ingredients[ $index ]['nutrition']['match'] = $match;
			$ingredients[ $index ]['nutrition']['matchOptions'] = $match_options;
			$ingredients[ $index ]['nutrition']['matchSearch'] = $match_search;
			$ingredients[ $index ]['nutrition']['factsUsed'] = true;

			// Clean up values.
			$ingredients[ $index ]['amount'] = wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) );
			$ingredients[ $index ]['unit'] = wp_strip_all_tags( strip_shortcodes( $ingredient['unit'] ) );
			$ingredients[ $index ]['name'] = wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) );
			$ingredients[ $index ]['notes'] = wp_strip_all_tags( strip_shortcodes( $ingredient['notes'] ) );

			// Use unit and amount from prev match.
			if (
				// Prev match exists and has needed fields.
				$prev_match && isset( $prev_match['amount'] ) && isset( $prev_match['amount_original'] ) && isset( $prev_match['unit'] ) && isset( $prev_match['unit_original'] )
				// Amounts and unit match completely.
				&& $prev_match['amount_original'] === $ingredients[ $index ]['amount'] && $prev_match['unit_original'] === $ingredients[ $index ]['unit']
			) {
				$ingredients[ $index ]['nutrition']['amount'] = $prev_match['amount'];
				$ingredients[ $index ]['nutrition']['unit'] = $prev_match['unit'];
			} else {
				// Find best unit for nutrition.
				if ( WPRM_Addons::is_active( 'unit-conversion' ) ) {
					$unit = WPRMPUC_Manager::get_unit_from_alias( $ingredients[ $index ]['nutrition']['unit'] );

					if ( $unit ) {
						$ingredients[ $index ]['nutrition']['unit'] = str_replace( '_', ' ', $unit );
					} else {
						// Try to translate if unit not found.
						$translated = WPRMPT_Translate::translate( $ingredients[ $index ]['nutrition']['unit'] );
						
						if ( $translated ) {
							$unit = WPRMPUC_Manager::get_unit_from_alias( $translated );

							if ( $unit ) {
								$ingredients[ $index ]['nutrition']['unit'] = str_replace( '_', ' ', $unit );
							} else {
								$ingredients[ $index ]['nutrition']['unit'] = $translated;
							}
						}
					}
				}
			}
		}

		return rest_ensure_response( array(
			'ingredients' => $ingredients,
		) );
	}

	/**
	 * Handle get API options call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_api_options( $request ) {
		// Required classes.
		require_once( WPRMPN_DIR . 'includes/admin/class-wprmpn-nutrition-api.php' );

		// Parameters.
		$params = $request->get_params();
		$search = isset( $params['search'] ) ? $params['search'] : array();

		return rest_ensure_response( array(
			'matchOptions' => WPRMPN_Nutrition_API::search_ingredient( $search ),
		) );
	}

	/**
	 * Handle get API options call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_api_facts( $request ) {
		// Required classes.
		require_once( WPRMPN_DIR . 'includes/admin/class-wprmpn-nutrition-api.php' );

		// Parameters.
		$params = $request->get_params();
		$ingredients = isset( $params['ingredients'] ) ? $params['ingredients'] : array();
		$api_ingredients = array();

		foreach ( $ingredients as $index => $ingredient ) {
			$facts = false;

			$nutrition = isset( $ingredient['nutrition'] ) ? $ingredient['nutrition'] : false;
			$source = $nutrition && isset( $nutrition['source'] ) ? $nutrition['source'] : 'api';

			// Save non-API for reuse.
			if ( 'api' !== $source ) {
				$previous_match = array(
					'id' => 0,
					'source' => $source,
				);

				if ( $nutrition ) {
					$previous_match['amount_original'] = $ingredient['amount'];
					$previous_match['unit_original'] = $ingredient['unit'];
					$previous_match['amount'] = $nutrition['amount'];
					$previous_match['unit'] = $nutrition['unit'];
				}

				// Save for reuse.
				$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient['name'] );
				if ( $ingredient_id ) {
					update_term_meta( $ingredient_id, 'wprmpn_previous_match', $previous_match );
				}

				// Don't fetch facts from API. This is from another source.
				continue;
			}
			
			if ( $nutrition ) {
				$match = isset( $nutrition['match'] ) ? $nutrition['match'] : false;

				if ( $match ) {
					$match_id = isset( $match['id'] ) ? intval( $match['id'] ) : 0;
					$match_name = isset( $match['name'] ) && $match['name'] ? $match['name'] : 'unknown';

					if ( isset( $match['aisle'] ) && $match['aisle'] ) {
						$match_name .= ' (' . $match['aisle'] . ')';
					}

					$amount = $nutrition['amount'];
					$unit = $nutrition['unit'];

					// Save for reuse.
					$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient['name'] );
					if ( $ingredient_id ) {
						update_term_meta( $ingredient_id, 'wprmpn_previous_match', array(
							'id' => $match_id,
							'amount_original' => $ingredient['amount'],
							'unit_original' => $ingredient['unit'],
							'amount' => $amount,
							'unit' => $unit,
							'name' => $match_name,
							'possibleUnits' => isset( $match['possibleUnits'] ) && is_array( $match['possibleUnits'] ) ? $match['possibleUnits'] : array(), 
							'source' => 'api',
						) );
					}

					// If cups or spoons, convert amount to API format.
					$unit_amount = floatval( $amount );
					if ( $unit_amount && in_array( $unit, array( 'cup', 'tablespoon', 'teaspoon' ) ) ) {
						$unit_type = WPRM_Settings::get( 'unit_conversion_system_1_' . $unit . '_type' );

						$corrected_amount = WPRMPUC_Manager::convert_unit( $unit_amount, $unit, $unit_type, 'api' );

						if ( $corrected_amount ) {
							$amount = $corrected_amount;
						}
					}
				
					// Get nutrition facts from API.
					$api_facts = (array) WPRMPN_Nutrition_API::get_nutrition_for( $amount, $unit, $match_id );

					if ( isset( $api_facts['nutrition'] ) ) {
						$api_nutrition = (array) $api_facts['nutrition'];

						if ( isset( $api_nutrition['nutrients'] ) ) {
							$api_nutrients = (array) $api_nutrition['nutrients'];
							$facts = array();
	
							$nutrition_fields = WPRM_Nutrition::get_fields();
							foreach ( $nutrition_fields as $field => $options ) {
								$api_value = false;
								$api_search = isset( $options['api'] ) ? $options['api'] : false;
	
								if ( $api_search ) {
									$api_match = false;
	
									foreach ( $api_nutrients as $api_nutrient ) {
										$api_nutrient = (array) $api_nutrient;

										// Support both title and name.
										$api_name = isset( $api_nutrient['title'] ) ? $api_nutrient['title'] : false;
										if ( ! $api_name ) {
											$api_name = isset( $api_nutrient['name'] ) ? $api_nutrient['name'] : false;
										}

										if ( $api_search === $api_name ) {
											$api_match = $api_nutrient;
											break;
										}
									}
	
									if ( $api_match ) {
										$api_value = $api_match['amount'];
									}
								}
	
								$facts[ $field ] = $api_value;
							}

							// Ignore serving size.
							unset( $facts['serving_size'] );
						}
					}
				}
			}

			$ingredient['nutrition']['facts'] = $facts;
			$api_ingredients[] = $ingredient;
		}

		return rest_ensure_response( array(
			'ingredients' => $api_ingredients,
		) );
	}

	/**
	 * Handle get save custom ingredient call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_create_custom_ingredient( $request ) {
		// Parameters.
		$params = $request->get_params();
		$amount = isset( $params['amount'] ) ? $params['amount'] : '';
		$unit = isset( $params['unit'] ) ? $params['unit'] : '';
		$name = isset( $params['name'] ) ? $params['name'] : '';
		$nutrients = isset( $params['nutrients'] ) ? $params['nutrients'] : array();

		return rest_ensure_response( array(
			'id' => WPRMPN_Ingredient_Manager::save_ingredient( 0, $amount, $unit, $name, $nutrients ),
		) );
	}

	/**
	 * Handle get custom ingredient call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_custom_ingredient( $request ) {
		return rest_ensure_response( array(
			'ingredient' => WPRMPN_Ingredient_Manager::get_ingredient( $request['id'] ),
		) );
	}

	/**
	 * Handle update custom ingredient call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_update_custom_ingredient( $request ) {
		// Parameters.
		$params = $request->get_params();
		$amount = isset( $params['amount'] ) ? $params['amount'] : '';
		$unit = isset( $params['unit'] ) ? $params['unit'] : '';
		$name = isset( $params['name'] ) ? $params['name'] : '';
		$nutrients = isset( $params['nutrients'] ) ? $params['nutrients'] : array();

		WPRMPN_Ingredient_Manager::save_ingredient( $request['id'], $amount, $unit, $name, $nutrients );

		return rest_ensure_response( self::api_get_custom_ingredient( $request ) );
	}

	/**
	 * Handle search custom ingredients call to the REST API.
	 *
	 * @since 5.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_search_custom_ingredients( $request ) {
		// Parameters.
		$params = $request->get_params();
		$search = isset( $params['search'] ) ? $params['search'] : '';

		return rest_ensure_response( array(
			'ingredients' => WPRMPN_Ingredient_Manager::search_saved_ingredients( $search ),
		) );
	}

	/**
	 * Handle save custom match call to the REST API.
	 *
	 * @since 7.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_custom_match( $request ) {
		// Parameters.
		$params = $request->get_params();
		$ingredient = isset( $params['ingredient'] ) ? $params['ingredient'] : false;
		$match_id = isset( $params['id'] ) ? intval( $params['id'] ) : false;

		if ( $ingredient && $match_id ) {
			$ingredient_id = $ingredient['id'];

			if ( ! $ingredient_id ) {
				$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient['name'] );
			}

			if ( $ingredient_id ) {
				$new_match = array(
					'id' => $match_id,
					'source' => 'custom',
				);

				// Check previously saved data.
				$prev_match = get_term_meta( $ingredient_id, 'wprmpn_previous_match', true );

				// Already same match, return.
				if ( $prev_match && 'custom' === $prev_match['source'] && $match_id === $prev_match['id'] ) {
					return rest_ensure_response( true );
				}

				// Update saved match.
				if ( $prev_match && 'custom' === $prev_match['source'] && 0 === $prev_match['id'] ) {
					$new_match = $prev_match;
					$new_match['id'] = $match_id;
				} 

				update_term_meta( $ingredient_id, 'wprmpn_previous_match', $new_match );
			}
		}

		return rest_ensure_response( true );
	}

	/**
	 * Handle get calculated nutrition call to the REST API.
	 *
	 * @since 5.3.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_calculated_nutrition( $request ) {
		// Parameters.
		$params = $request->get_params();

		$nutrition = isset( $params['nutrition'] ) ? $params['nutrition'] : array();

		return rest_ensure_response( array(
			'calculated' => WPRMPN_Calculated_Nutrition::get_calculated_nutrition_fields( $nutrition ),
		) );
	}
}

WPRMPN_Api::init();
