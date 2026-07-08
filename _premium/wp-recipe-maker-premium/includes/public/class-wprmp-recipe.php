<?php
/**
 * Handle the Premium recipe fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the Premium recipe fields.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Recipe {
	public static function init() {
		add_filter( 'wprm_recipe_data', array( __CLASS__, 'recipe_data' ), 10, 3 );
		add_filter( 'wprm_recipe_frontend_data', array( __CLASS__, 'frontend_data' ), 10, 2 );
		add_filter( 'wprm_recipe_manage_data', array( __CLASS__, 'manage_data' ), 10, 2 );
		add_filter( 'wprm_recipe_author', array( __CLASS__, 'author' ), 10, 2 );
		add_filter( 'wprm_recipe_custom_field', array( __CLASS__, 'custom_field' ), 10, 3 );
		add_filter( 'wprm_recipe_custom_fields', array( __CLASS__, 'custom_fields' ), 10, 2 );
		add_filter( 'wprm_recipe_pin_image_id', array( __CLASS__, 'pin_image_id' ), 10, 3 );
		add_filter( 'wprm_recipe_in_collection', array( __CLASS__, 'in_collection' ), 10, 3 );
		add_filter( 'wprm_recipe_rating', array( __CLASS__, 'rating' ), 10, 2 );
	}

	/**
	 * Change the recipe data.
	 *
	 * @since	10.0.0
	 * @param	mixed $data 	Current data.
	 * @param	array $recipe	Recipe we're getting the data for.
	 * @param	string $context	Context we're getting the data for.
	 */
	public static function recipe_data( $data, $recipe, $context ) {
		if ( 'api' === $context ) {
			// Maybe add ingredient links.
			if ( false !== WPRM_Settings::get( 'ingredient_links_enabled' ) ) {
				if ( 'global' === $recipe->ingredient_links_type() ) {
					$remember_links = array();

					if ( isset( $data['ingredients'] ) ) {
						foreach( $data['ingredients'] as $i => $group ) {
							foreach( $group['ingredients'] as $j => $ingredient ) {
								$find_link_by = isset( $ingredient['id'] ) ? 'id' : 'name';
								if ( isset( $ingredient[ $find_link_by ] ) ) {
									$link = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient[ $find_link_by ] );

									if ( $link ) {
										$remember_links[ $ingredient[ $find_link_by ] ] = $link;
										$data['ingredients'][ $i ]['ingredients'][ $j ]['link'] = $link;
									}
								}
							}
						}
					}

					if ( isset( $data['ingredients_flat'] ) ) {
						foreach( $data['ingredients_flat'] as $index => $ingredient ) {
							if ( isset( $ingredient['type'] ) && 'ingredient' === $ingredient['type'] ) {
								$find_link_by = isset( $ingredient['id'] ) ? 'id' : 'name';
								if ( isset( $ingredient[ $find_link_by ] ) ) {
									$link = isset( $remember_links[ $ingredient[ $find_link_by ] ] ) ? $remember_links[ $ingredient[ $find_link_by ] ] : WPRMP_Ingredient_Links::get_ingredient_link( $ingredient[ $find_link_by ] );

									if ( $link ) {
										$data['ingredients_flat'][ $index ]['link'] = $link;
									}
								}
							}
						}
					}
				}
			}

		}

		return $data;
	}

	/**
	 * Change the recipe frontend data.
	 *
	 * @since	9.5.0	 
	 * @param	mixed $data 	Current data.
	 * @param	array $recipe	Recipe we're getting the manage data for.
	 */
	public static function frontend_data( $data, $recipe ) {
		// Check what unit system this recipe was written in (usually 1, but could be 2).
		$recipe_unit_system = intval( $recipe->unit_system() );
		$recipe_unit_system = 1 === $recipe_unit_system || 2 === $recipe_unit_system ? $recipe_unit_system : 1;

		$data['originalSystem'] = $recipe_unit_system;
		$data['currentSystem'] = $recipe_unit_system;

		// Ingredients data, needed for adjustable servings and unit conversion.
		$ingredients = $recipe->ingredients_flat();
		$ingredients_only = array();
		$has_unit_systems = array( $recipe_unit_system );

		foreach ( $ingredients as $index => $ingredient ) {
			// Set singular and plural for name.
			if ( isset( $ingredient['id'] ) && $ingredient['id'] ) {
				$ingredient_term = get_term( $ingredient['id'], 'wprm_ingredient' );

				if ( $ingredient_term && ! is_wp_error( $ingredient_term ) ) {
					$singular = $ingredient_term->name;
					$plural = get_term_meta( $ingredient['id'], 'wprm_ingredient_plural', true );

					if ( $singular && $plural ) {
						$ingredients[ $index ]['name_singular'] = $singular;
						$ingredients[ $index ]['name_plural'] = $plural;
					}
					
				}
			}

			// Combine unit systems.
			$unit_systems = array(
				'unit-system-' . $recipe_unit_system => array(
					'amount' => isset( $ingredient['amount'] ) ? $ingredient['amount'] : '',
					'unit' => isset( $ingredient['unit'] ) ? $ingredient['unit'] : '',
					'unitParsed' => isset( $ingredient['unit'] ) ? do_shortcode( $ingredient['unit'] ) : '',
					'unit_id' => isset( $ingredient['unit_id'] ) ? $ingredient['unit_id'] : 0,
				),
			);

			if ( isset( $ingredient['converted'] ) ) {
				foreach ( $ingredient['converted'] as $system => $values ) {
					$fixed_system = 2 === $recipe_unit_system ? 1 : $system;

					$amount = isset( $values['amount'] ) ? trim( $values['amount'] ) : '';
					$unit = isset( $values['unit'] ) ? trim( $values['unit'] ) : '';

					if ( $amount || $unit ) {
						$unit_systems[ 'unit-system-' . $fixed_system ] = array(
							'amount' => $amount,
							'unit' => $unit,
							'unitParsed' => do_shortcode( $unit ),
							'unit_id' => isset( $values['unit_id'] ) ? $values['unit_id'] : 0,
						);

						if ( ! in_array( $fixed_system, $has_unit_systems ) ) {
							$has_unit_systems[] = $fixed_system;
						}
					}
				}
			}

			// Check for unit plurals.
			foreach( $unit_systems as $system => $values ) {
				if ( $values['unit_id'] ) {
					$unit_term = get_term( $values['unit_id'], 'wprm_ingredient_unit' );

					if ( $unit_term && ! is_wp_error( $unit_term ) ) {
						$singular = $unit_term->name;
						$plural = get_term_meta( $values['unit_id'], 'wprm_ingredient_unit_plural', true );
	
						if ( $singular && $plural ) {
							$unit_systems[ $system ]['unit_singular'] = $singular;
							$unit_systems[ $system ]['unit_plural'] = $plural;
						}
						
					}
				}

				unset( $unit_systems[ $system ]['unit_id'] );
			}

			$ingredients[ $index ]['unit_systems'] = $unit_systems;

			if ( 'ingredient' === $ingredient['type'] ) {
				$ingredients_only[] = $ingredients[ $index ];
			}
		}

		$data['ingredients'] = $ingredients_only;
		$data['unitSystems'] = $has_unit_systems;

		// Advanced adjustable servings data.
		$servings = $recipe->servings_advanced();

		if ( $servings ) {
			// Smart defaults depending on shape.
			if ( 'round' === $servings['shape'] ) {
				$servings['width'] = $servings['diameter'];
				$servings['length'] = $servings['diameter'];
			} else {
				$servings['diameter'] = $servings['width'];
			}
		}

		$data['originalAdvancedServings'] = $servings;
		$data['currentAdvancedServings'] = $servings;

		return $data;
	}

	/**
	 * Change the recipe manage data.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $data 	Current data.
	 * @param	array $recipe	Recipe we're getting the manage data for.
	 */
	public static function manage_data( $data, $recipe ) {
		if ( WPRM_Addons::is_active( 'unit-conversion' ) ) {
			$data['unit_conversion'] = __( 'Not enabled', 'wp-recipe-maker' );

			if ( WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
				$ingredients = $recipe->ingredients_without_groups();
				$converted_ingredients = array();

				foreach ( $ingredients as $ingredient ) {
					if ( isset( $ingredient['converted'] ) ) {
						foreach ( $ingredient['converted'] as $system => $values ) {
							$converted = $values['amount'] ? $values['amount'] : '';
							$converted .= $values['unit'] ? ' ' . $values['unit'] : '';
							$converted = trim( $converted );

							if ( $converted ) {
								$converted .= ' ' . $ingredient['name'] . ' ' . $ingredient['notes'];
								$converted_ingredients[] = trim( $converted );
							}
						}
					}
				}

				$data['unit_conversion'] = $converted_ingredients;
			}
		}

		return $data;
	}

	/**
	 * Get the recipe author.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $author 	Current author.
	 * @param	array $recipe	Recipe we're getting the author for.
	 */
	public static function author( $author, $recipe ) {
		if ( $author ) {
			switch ( $recipe->author_display() ) {
				case 'post_author':
					if ( WPRM_Settings::get( 'post_author_link' ) ) {
						$author_id = $recipe->post_author();

						if ( $author_id ) {
							if ( 'archive' === WPRM_Settings::get( 'post_author_link_use' ) ) {
								$link = get_author_posts_url( $author_id );
							} else {
								$author_data = get_userdata( $author_id );
								$link = $author_data->data->user_url;
							}

							if ( $link ) {
								$target = WPRM_Settings::get( 'post_author_link_new_tab' ) ? '_blank' : '_self';
								$author = '<a href="' . esc_attr( $link ) . '" target="' . $target . '">' . $author . '</a>';
							}
						}
					}
					break;
				case 'custom':
					$link = $recipe->custom_author_link();
	
					if ( $link ) {
						$target = WPRM_Settings::get( 'custom_author_link_new_tab' ) ? '_blank' : '_self';
						$author = '<a href="' . esc_attr( $link ) . '" target="' . $target . '">' . $author . '</a>';
					}
					break;
				case 'same':
					$link = WPRM_Settings::get( 'recipe_author_same_link' );
	
					if ( $link ) {
						$target = WPRM_Settings::get( 'recipe_author_same_link_new_tab' ) ? '_blank' : '_self';
						$author = '<a href="' . esc_attr( $link ) . '" target="' . $target . '">' . $author . '</a>';
					}
					break;
			}
		}

		return $author;
	}

	/**
	 * Get the recipe custom field.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $custom_field Current custom field.
	 * @param	array $field		Field to get.
	 * @param	array $recipe		Recipe to get the field for.
	 */
	public static function custom_field( $custom_field, $field, $recipe ) {
		if ( WPRM_Addons::is_active( 'custom-fields' ) ) {
			$custom_field = WPRMPCF_Fields::get( $recipe, $field );
		}

		return $custom_field;
	}

	/**
	 * Get the recipe custom fields.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $custom_fields Current custom fields.
	 * @param	array $recipe		Recipe to get the fields for.
	 */
	public static function custom_fields( $custom_fields, $recipe ) {
		// Prevent StoreCustomizer compatibility problem.
		if ( is_string( $recipe ) ) {
			return $custom_fields;
		}

		if ( WPRM_Addons::is_active( 'custom-fields' ) ) {
			$custom_fields = WPRMPCF_Fields::get_all( $recipe );
		}

		return $custom_fields;
	}

	/**
	 * Get the recipe pin image ID.
	 *
	 * @since	5.6.0
	 * @param	mixed 	$pin_image_id 	Current pin image ID.
	 * @param   boolean $for_editing 	Whether or not we're retrieving the value for editing.
	 * @param	array 	$recipe			Recipe to get the ID for.
	 */
	public static function pin_image_id( $pin_image_id, $for_editing, $recipe ) {
		switch ( WPRM_Settings::get( 'pinterest_use_for_image' ) ) {
			case 'custom':
				$pin_image_id = $recipe->meta( 'wprm_pin_image_id', 0 );
				break;
			case 'custom_or_recipe_image':
				$custom_image_id = $recipe->meta( 'wprm_pin_image_id', 0 );
				if ( 0 < $custom_image_id ) {
					$pin_image_id = $custom_image_id;
				}
				break;
		}

		return max( array( intval( $pin_image_id ), 0 ) ); // Make sure to return 0 when set to -1.
	}

	/**
	 * Check if a recipe is in a collection.
	 *
	 * @since	5.6.0
	 * @param   boolean $in_collection 	Whether or not the recipe is in the collection.
	 * @param	mixed 	$collection_id 	Collection to check.
	 * @param	array 	$recipe			Recipe to check.
	 */
	public static function in_collection( $in_collection, $collection_id, $recipe ) {
		if ( WPRM_Addons::is_active( 'recipe-collections' ) ) {
			$collections = WPRMPRC_Manager::get_user_collections();

			if ( 'inbox' === $collection_id ) {
				$collection = $collections['inbox'];
			} elseif ( 'temp' === $collection_id ) {
				if ( isset( $collections['temp'] ) ) {
					$collection = $collections['temp'];
				} else {
					return false;
				}
			} else {
				// Not implemented yet. Needed?
			}

			if ( $collections && isset( $collection['items'] ) && isset( $collection['items']['0-0'] ) && $collection['items']['0-0'] ) {
				$recipe_id = $recipe->id();
				$filtered_items = array_filter( $collection['items']['0-0'], function( $item ) use ( $recipe_id ) {
					if ( isset( $item['recipeId'] ) ) {
						return $recipe_id === $item['recipeId'];	
					}
					return false;
				});

				if ( $filtered_items ) {
					$in_collection = true;
				}
			}
		}

		return $in_collection;
	}

	/**
	 * Get the recipe rating.
	 *
	 * @since	5.6.0
	 * @param   boolean $rating Current rating.
	 * @param	array 	$recipe	Recipe to get the rating for.
	 */
	public static function rating( $rating, $recipe ) {
		$rating['user'] = WPRMP_User_Rating::get_user_rating_for( $recipe->id() );
		return $rating;
	}
}

WPRMP_Recipe::init();