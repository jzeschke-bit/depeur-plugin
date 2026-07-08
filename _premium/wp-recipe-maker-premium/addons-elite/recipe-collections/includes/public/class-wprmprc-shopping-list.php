<?php
/**
 * Handle the shopping list for collections.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.3.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the shopping list for collections.
 *
 * @since      6.3.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Shopping_List {

	/**
	 * Get a shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $uid	UID of the shopping list to get.
	 */
	public static function get( $uid ) {
		$shopping_list = false;

		$data = WPRMPRC_Shopping_List_Database::get( $uid );	

		if ( is_array( $data ) ) {
			return array(
				'id' => $data['id'],
				'uid' => $data['uid'],
				'created' => $data['created'],
				'updated' => $data['updated'],
				'collection' => maybe_unserialize( $data['collection'] ),
				'groups' => maybe_unserialize( $data['groups'] ),
				'meta' => maybe_unserialize( $data['meta'] ),
			);
		}

		return $shopping_list;
	}

	/**
	 * Get a shopping list by collection and user.
	 *
	 * @since    6.3.0
	 * @param    int $user_id			ID of the user.
	 * @param    int $collection_id		ID of the collection.
	 * @param    mixed $collection_type	Type of the collection.
	 */
	public static function get_uid_by_collection_and_user( $user_id, $collection_id, $collection_type ) {
		$data = WPRMPRC_Shopping_List_Database::get_by_collection_and_user( $user_id, $collection_id, $collection_type );

		if ( is_array( $data ) ) {
			return $data['uid'];
		}

		return false;
	}

	/**
	 * Save a shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $uid		UID of the shopping list to get.
	 * @param    mixed $data	Data to save in the shopping list.
	 */
	public static function save( $uid, $data ) {
		// Make sure the data we need is actually set.
		if ( isset( $data['groups'] ) ) {
			$old_data = self::get( $uid );

			if ( is_array( $old_data ) ) {
				// Clear Instacart link if shopping list has been updated.
				$data['meta'] = is_array( $old_data['meta'] ) ? $old_data['meta'] : array();
				unset( $data['meta']['instacart'] );
				
				return WPRMPRC_Shopping_List_Database::update( $old_data['id'], $data );
			}
		}

		return false;
	}

	/**
	 * Update shopping list meta.
	 *
	 * @since    9.8.0
	 * @param    mixed $uid		UID of the shopping list.
	 * @param    mixed $meta	Meta to save
	 */
	public static function save_meta( $uid, $meta ) {
		$old_data = self::get( $uid );

		if ( is_array( $old_data ) ) {
			$data = array(
				'meta' => is_array( $old_data['meta'] ) ? $old_data['meta'] : array(),
			);
			
			// Merge new meta with old meta.
			$data['meta'] = array_merge( $data['meta'], $meta );
			
			return WPRMPRC_Shopping_List_Database::update( $old_data['id'], $data );
		}
	}

	/**
	 * Generate a shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $type		Type of collection to generate the shopping list for.
	 * @param    mixed $collection	Collection to generate the shopping list for.
	 * @param    mixed $options		Options for the shopping list.
	 */
	public static function generate( $type, $collection, $options ) {
		$items = 		apply_filters( 'wprmrc_shopping_list_generate_items', 		self::get_items( $collection ), $collection );
		$ingredients = 	apply_filters( 'wprmrc_shopping_list_generate_ingredients', self::get_ingredients( $items, $options ), $items, $options, $collection );
		$groups = 		apply_filters( 'wprmrc_shopping_list_generate_groups',		self::get_groups( $ingredients, $options ), $ingredients, $options, $collection );

		$data = array(
			'collection_id' => $collection['id'],
			'collection_type' => $type,
			'collection' => $collection,
			'groups' => $groups,
		);
		
		return WPRMPRC_Shopping_List_Database::create( $data );
	}

	/**
	 * Get items to show in shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $collection	Collection to get the items from.
	 */
	public static function get_items( $collection ) {
		$items = array();

		foreach ( $collection['columns'] as $column ) {
			// Check if this column is part of the shopping list.
			$in_shopping_list = false;

			if ( isset( $column['inShoppingList'] ) ) {
				$in_shopping_list = $column['inShoppingList'];
			} else {
				$in_shopping_list = 1 === count( $collection['columns'] );
			}
			if ( ! $in_shopping_list ) { continue; }

			foreach ( $collection['groups'] as $group ) {
				$group_items = isset( $collection['items'][ $column['id'] . '-' . $group['id'] ] ) ? $collection['items'][ $column['id'] . '-' . $group['id'] ] : array();

				// Only items with more than 0 servings that are not marked as leftovers.
				$group_items = array_filter( $group_items, function( $item ) {
					$is_leftovers = WPRM_Settings::get( 'recipe_collections_items_leftovers' ) && isset( $item['leftovers'] ) ? $item['leftovers'] : false;
					return ! $is_leftovers && 0 < floatval( $item['servings'] );
				} );

				$items = array_merge( $items, $group_items );
			}
		}

		return $items;
	}

	/**
	 * Get ingredients to show in shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $items	Items to get the ingredients from.
	 * @param    mixed $options	Options for the shopping list.
	 */
	public static function get_ingredients( $items, $options ) {
		$ingredients = array();

		$unit_system = isset( $options['system'] ) ? max( array( 1, intval( $options['system'] ) ) ) : 1;

		foreach ( $items as $item ) {
			$item_servings = floatval( $item['servings'] );

			switch ( $item['type'] ) {
				case 'recipe':
					$recipe = WPRM_Recipe_Manager::get_recipe( $item['recipeId'] );

					if ( $recipe ) {
						$recipe_servings = 0 < floatval( $recipe->servings() ) ? floatval( $recipe->servings() ) : 1;
						$recipe_ingredients = $recipe->ingredients_without_groups();

						foreach ( $recipe_ingredients as $ingredient ) {
							$amount = WPRM_Recipe_Parser::parse_quantity( do_shortcode( $ingredient['amount'] ) );
							$unit = wp_strip_all_tags( strip_shortcodes( $ingredient['unit'] ) );
							$name = wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) );
							$notes = wp_strip_all_tags( strip_shortcodes( $ingredient['notes'] ) );

							// Use term name to combine differently capitalized ingredients.
							$ingredient_term = $ingredient['id'] ? get_term( $ingredient['id'] ) : false;

							if ( $ingredient_term ) {
								$name = $ingredient_term->name;
							}

							// Make sure we're getting the correct unit system for this recipe.
							$recipe_unit_system = intval( $recipe->unit_system() );
							$unit_system_to_use = 1 === $recipe_unit_system ? $unit_system : 3 - $unit_system;

							// Use different unit system.
							if ( 1 !== $unit_system_to_use && isset( $ingredient['converted'] ) && isset( $ingredient['converted'][ $unit_system_to_use ] ) ) {
								$converted_amount = isset( $ingredient['converted'][ $unit_system_to_use ]['amount'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['converted'][ $unit_system_to_use ]['amount'] ) ) : '';
								$converted_unit = isset( $ingredient['converted'][ $unit_system_to_use ]['unit'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['converted'][ $unit_system_to_use ]['unit'] ) ) : '';

								// Only if at least amount or unit was set.
								if ( $converted_amount || $converted_amount ) {
									$amount = WPRM_Recipe_Parser::parse_quantity( $converted_amount );
									$unit = $converted_unit;
								}
							}

							// Adjust amount with serving size.
							if ( $item_servings !== $recipe_servings ) {
								$amount = $amount / $recipe_servings * $item_servings;
							}

							$ingredients[] = array(
								'id' => $ingredient['id'],
								'amount' => $amount,
								'unit' => $unit,
								'name' => $name,
								'notes' => $notes,
							);
						}
					}
					break;
				case 'ingredient':
					foreach ( $item['ingredients'] as $ingredient ) {
						$amount = WPRM_Recipe_Parser::parse_quantity( $ingredient['amount'] );
						$unit = trim( $ingredient['unit'] );
						$name = trim( $ingredient['name'] );

						$amount = $item_servings * $amount;

						$ingredients[] = array(
							'id' => WPRM_Recipe_Sanitizer::get_ingredient_id( $name ),
							'amount' => $amount,
							'unit' => $unit,
							'name' => $name,
							'notes' => '',
						);
					}
					break;
				case 'nutrition-ingredient':
					$amount = WPRM_Recipe_Parser::parse_quantity( $item['amount'] );
					$unit = trim( $item['unit'] );
					$name = trim( $item['name'] );

					$amount = $item_servings * $amount;

					$ingredients[] = array(
						'id' => WPRM_Recipe_Sanitizer::get_ingredient_id( $name ),
						'amount' => $amount,
						'unit' => $unit,
						'name' => $name,
						'notes' => '',
					);
					break;
			}
		}

		return $ingredients;
	}

	/**
	 * Get groups to show in shopping list.
	 *
	 * @since    6.3.0
	 * @param    mixed $ingredients	Ingredients to get the groups for.
	 * @param    mixed $options	Options for the shopping list.
	 */
	public static function get_groups( $ingredients, $options ) {
		$groups = array();
		$ingredient_id = 0;

		// Group all ingredients.
		foreach ( $ingredients as $ingredient ) {
			$group_name = WPRMPRC_Ingredient_Groups::get_group( $ingredient['id'] );

			// Make sure group exists.
			$group_index = array_search( $group_name, array_column( $groups, 'name' ) );
			if ( false === $group_index ) {
				$group_index = count( $groups );
				$groups[] = array(
					'id' => $group_index,
					'name' => $group_name,
					'ingredients' => array(),
				);
			}

			// Make sure ingredient line exists.
			$ingredient_name = $ingredient['name'];

			if ( isset( $options['notes'] ) && $options['notes'] ) {
				$notes = trim( $ingredient['notes'] );

				if ( $notes ) {
					$ingredient_name .= ' (' . $notes . ')';
				}
			}

			$ingredient_index = array_search( $ingredient_name, array_column( $groups[ $group_index ]['ingredients'], 'name' ) );
			if ( false === $ingredient_index ) {
				// Get link for this ingredient if enabled.
				if ( WPRM_Settings::get( 'recipe_collections_shopping_list_links') ) {
					$link = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient['id'] );
				}

				$ingredient_index = count( $groups[ $group_index ]['ingredients'] );
				$groups[ $group_index ]['ingredients'][] = array(
					'id' => $ingredient_id,
					'checked' => false,
					'name' => $ingredient_name,
					'link' => $link,
					'variations' => array(),
				);

				$ingredient_id++;
			}

			// Optionally replace unit with standardized one.
			$standard_unit = WPRMPUC_Manager::get_unit_from_alias( $ingredient['unit'] );
			if ( false !== $standard_unit ) {
				$ingredient['unit'] = $standard_unit;
			} else {
				// No standard unit found, check for unit term and use singular for now.
				$unit_id = WPRM_Recipe_Sanitizer::get_ingredient_unit_id( $ingredient['unit'] );

				if ( $unit_id ) {
					$unit_term = get_term( $unit_id, 'wprm_ingredient_unit' );
					if ( $unit_term && ! is_wp_error( $unit_term ) ) {
						$ingredient['unit'] = $unit_term->name;
					}
				}
			}

			// Check if unit exists.
			$unit_index = array_search( $ingredient['unit'], array_column( $groups[ $group_index ]['ingredients'][ $ingredient_index ]['variations'], 'unit' ) );

			if ( false === $unit_index ) {
				$groups[ $group_index ]['ingredients'][ $ingredient_index ]['variations'][] = array(
					'amount' => $ingredient['amount'],
					'unit' => $ingredient['unit'],
				);
			} else {
				$groups[ $group_index ]['ingredients'][ $ingredient_index ]['variations'][ $unit_index ]['amount'] += $ingredient['amount'];
			}
		}

		// Check if fractions should be used.
		$use_fractions = WPRM_Settings::get( 'fractions_enabled' );

		if ( WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
			$unit_system = isset( $options['system'] ) ? max( array( 1, intval( $options['system'] ) ) ) : 1;
			$use_fractions = WPRM_Settings::get( 'unit_conversion_system_' . $unit_system . '_fractions' );
		}

		// Format all amounts.
		foreach ( $groups as $g => $group ) {

			// Order ingredients by name.
			usort( $groups[ $g ]['ingredients'], function($a, $b) {
				return strcasecmp( $a['name'], $b['name'] );
			} );

			foreach ( $groups[ $g ]['ingredients'] as $i => $ingredient ) {
				$variations = $ingredient['variations'];

				// Maybe smart combine units.
				if ( WPRM_Settings::get( 'recipe_collections_shopping_list_smart_combine' ) && 1 < count( $variations ) ) {
					$groups[ $g ]['ingredients'][ $i ]['variations'] = WPRMPUC_Manager::maybe_combine_multiple_units( $variations );
				}

				foreach ( $groups[ $g ]['ingredients'][ $i ]['variations'] as $v => $variation ) {
					// If standard unit, use correct singular/plural version.
					$groups[ $g ]['ingredients'][ $i ]['variations'][ $v ]['unit'] = WPRMPUC_Manager::get_alias_for( $groups[ $g ]['ingredients'][ $i ]['variations'][ $v ]['amount'], $groups[ $g ]['ingredients'][ $i ]['variations'][ $v ]['unit'] );

					// Format number.
					$decimals = WPRM_Settings::get( 'recipe_collections_shopping_list_round_to_decimals' );
					$groups[ $g ]['ingredients'][ $i ]['variations'][ $v ]['amount'] = WPRM_Recipe_Parser::format_quantity( $groups[ $g ]['ingredients'][ $i ]['variations'][ $v ]['amount'], $decimals, $use_fractions );
				}
			}
		}

		// Order groups by name (with empty first).
		usort( $groups, function($a, $b) {
			if ( '' === $a['name'] ) { return -1; }
			if ( '' === $b['name'] ) { return 1; }

			return strcasecmp( $a['name'], $b['name'] );
		} );

		return $groups;
	}
}
