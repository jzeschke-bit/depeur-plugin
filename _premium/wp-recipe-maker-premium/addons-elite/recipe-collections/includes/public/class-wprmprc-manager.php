<?php
/**
 * Manage the Recipe Collections posts.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Manage the Recipe Collections posts.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Manager {

	/**
	 * Collections that have already been requested for easy subsequent access.
	 *
	 * @since	4.1.0
	 * @access	private
	 * @var		array $collections Array containing collections that have already been requested for easy access.
	 */
	private static $collections = array();

	/**
	 * Clean servings unit text for collection views that don't process inline shortcodes.
	 *
	 * @param	string $servings_unit Servings unit to sanitize.
	 */
	private static function sanitize_servings_unit( $servings_unit ) {
		return trim( preg_replace( '/\[\/?adjustable\]/i', '', $servings_unit ) );
	}

	/**
	 * Get servings unit data for collection contexts.
	 *
	 * @param	WPRM_Recipe $recipe Recipe to get the servings unit data for.
	 */
	private static function get_recipe_servings_unit_data( $recipe ) {
		$original_servings = $recipe->servings();
		$original_servings_parsed = WPRM_Recipe_Parser::parse_quantity( $original_servings );
		$original_servings_parsed = is_numeric( $original_servings_parsed ) && 0 < $original_servings_parsed ? floatval( $original_servings_parsed ) : 1;

		return array(
			'servingsUnit' => self::sanitize_servings_unit( $recipe->servings_unit() ),
			'servingsUnitRaw' => $recipe->servings_unit(),
			'originalServings' => $original_servings,
			'originalServingsParsed' => $original_servings_parsed,
		);
	}

	/**
	 * Backfill servings unit data for recipe collection items.
	 *
	 * @param	array $item Collection item data.
	 */
	public static function maybe_backfill_recipe_item_servings_data( $item ) {
		if ( ! is_array( $item ) || ! isset( $item['type'] ) || 'recipe' !== $item['type'] ) {
			return $item;
		}

		$item['servingsUnit'] = isset( $item['servingsUnit'] ) ? self::sanitize_servings_unit( $item['servingsUnit'] ) : '';

		$missing_fields = ! isset( $item['servingsUnitRaw'] ) || ! isset( $item['originalServings'] ) || ! isset( $item['originalServingsParsed'] );

		if ( ! $missing_fields ) {
			return $item;
		}

		$recipe_id = isset( $item['recipeId'] ) ? intval( $item['recipeId'] ) : 0;
		if ( $recipe_id <= 0 ) {
			return $item;
		}

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe ) {
			return $item;
		}

		return array_merge(
			$item,
			self::get_recipe_servings_unit_data( $recipe )
		);
	}

	/**
	 * Backfill recipe servings metadata throughout a collections structure.
	 *
	 * @param	array $collections Collections data.
	 */
	private static function maybe_backfill_recipe_servings_data_in_collections( $collections ) {
		if ( ! is_array( $collections ) ) {
			return array(
				'collections' => $collections,
				'changed' => false,
			);
		}

		$changed = false;
		$collection_types = array( 'inbox', 'user' );

		foreach ( $collection_types as $collection_type ) {
			if ( ! isset( $collections[ $collection_type ] ) ) {
				continue;
			}

			$collections_to_process = 'user' === $collection_type
				? $collections[ $collection_type ]
				: array( $collections[ $collection_type ] );

			foreach ( $collections_to_process as $collection_index => $collection ) {
				if ( ! isset( $collection['items'] ) || ! is_array( $collection['items'] ) ) {
					continue;
				}

				foreach ( $collection['items'] as $column_group => $items ) {
					if ( ! is_array( $items ) ) {
						continue;
					}

					foreach ( $items as $item_index => $item ) {
						$backfilled_item = self::maybe_backfill_recipe_item_servings_data( $item );

						if ( $backfilled_item !== $item ) {
							$changed = true;

							if ( 'user' === $collection_type ) {
								$collections[ $collection_type ][ $collection_index ]['items'][ $column_group ][ $item_index ] = $backfilled_item;
							} else {
								$collections[ $collection_type ]['items'][ $column_group ][ $item_index ] = $backfilled_item;
							}
						}
					}
				}
			}
		}

		return array(
			'collections' => $collections,
			'changed' => $changed,
		);
	}

	/**
	 * Register actions and filters.
	 *
	 * @since    9.5.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_frontend_data', array( __CLASS__, 'frontend_recipe_data' ), 10, 2 );
	}

	/**
	 * Add collection information to frontend recipe data.
	 *
	 * @since 	9.5.0
	 * @param	array			$data Frontend recipe data.
	 * @param	WPPRM_Recipe	$recipe Recipe to add the data for.
	 */
	public static function frontend_recipe_data( $data, $recipe ) {
		$data['collection'] = self::get_collections_data_for_recipe( $recipe );

		return $data;
	}

	/**
	 * Get collection object by ID.
	 *
	 * @since 	4.1.0
	 * @param	mixed $post_or_collection_id ID or Post Object for the collection we want.
	 */
	public static function get_collection( $post_or_collection_id ) {
		$collection_id = is_object( $post_or_collection_id ) && $post_or_collection_id instanceof WP_Post ? $post_or_collection_id->ID : intval( $post_or_collection_id );

		// Only get new collection object if it hasn't been retrieved before.
		if ( ! array_key_exists( $collection_id, self::$collections ) ) {
			$post = is_object( $post_or_collection_id ) && $post_or_collection_id instanceof WP_Post ? $post_or_collection_id : get_post( intval( $post_or_collection_id ) );

			if ( $post instanceof WP_Post && WPRMPRC_POST_TYPE === $post->post_type ) {
				$collection = new WPRMPRC_Collection( $post );
			} else {
				$collection = false;
			}

			self::$collections[ $collection_id ] = $collection;
		}

		return self::$collections[ $collection_id ];
	}

	/**
	 * Get defaults collections.
	 *
	 * @since 	4.1.0
	 */
	public static function get_default_collections() {
		// Empty collections with inbox.
		$default_collections = array(
			'inbox' => array(
				'id' => 0,
				'name' => WPRM_Settings::get('recipe_collections_inbox_name'),
				'nbrItems' => 0,
				'columns' => array(
					array(
						'id' => 0,
						'name' => __( 'Recipes', 'wp-recipe-maker-premium' ),
					),
				),
				'groups' => array(
					array(
						'id' => 0,
						'name' => '',
					),
				),
				'items' => array(
					'0-0' => array()
				),
				'created' => time(),
			),
			'user' => array(),
		);

		// Get default saved collections.
		$args = array(
			'post_type' => WPRMPRC_POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => 'wprm_default',
					'compare' => '=',
					'value' => '1',
				),
			),
		);

		$query = new WP_Query( $args );

		$collection_id = 0;
		$posts = $query->posts;
		foreach ( $posts as $post ) {
			$collection = self::get_collection( $post );

			if ( $collection ) {
				$collection_data = $collection->get_data();
				$collection_data['id'] = $collection_id;

				$default_collections['user'][] = $collection_data;

				$collection_id++;
			}
		}

		// Order default user collections.
		$default_collections['user'] = self::order_collections( $default_collections['user'] );

		return $default_collections;
	}

	/**
	 * Get starter template collections.
	 *
	 * @since 	8.1.0
	 */
	public static function get_collections_by_field( $field ) {
		$collections = array();

		// Get template saved collections.
		$args = array(
			'post_type' => WPRMPRC_POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => 'wprm_' . sanitize_key( $field ),
					'compare' => '=',
					'value' => '1',
				),
			),
		);

		$query = new WP_Query( $args );

		$posts = $query->posts;
		foreach ( $posts as $post ) {
			$collection = self::get_collection( $post );

			if ( $collection ) {
				$collections[] = $collection->get_data();
			}
		}
		
		// Order before returning.
		$collections = self::order_collections( $collections );

		return $collections;
	}

	/**
	 * Order an array of collections.
	 *
	 * @since 	8.3.0
	 * @param	array $collections Collections to order.
	 */
	public static function order_collections( $collections ) {
		usort( $collections, function($a, $b) {
			$a_order = isset( $a['order'] ) ? intval( $a['order'] ) : 0;
			$b_order = isset( $b['order'] ) ? intval( $b['order'] ) : 0;

			return $a_order - $b_order;
		});

		return $collections;
	}

	/**
	 * Get collections for the current user.
	 *
	 * @since 	4.1.0
	 * @param	mixed $user_id User ID to get the collections for.
	 * @param	mixed $use_default Whether to use default collections if none are found.
	 */
	public static function get_user_collections( $user_id = false, $use_default = true ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$collections = false;

		// If user is logged in, find their collections.
		if ( $user_id ) {
			$collections = get_user_meta( $user_id, 'wprm-recipe-collections', true );
		}

		// Set default if none found.
		if ( ! $collections && $use_default ) {
			$collections = self::get_default_collections();
		}

		$backfilled = self::maybe_backfill_recipe_servings_data_in_collections( $collections );
		$collections = $backfilled['collections'];

		if ( $user_id && $backfilled['changed'] ) {
			update_user_meta( $user_id, 'wprm-recipe-collections', $collections );
		}

		return $collections;
	}

	/**
	 * Save collections for the current user.
	 *
	 * @since 	4.1.0
	 * @param	mixed $collections Collections to save.
	 * @param	mixed $user_id User ID to save the collections for.
	 */
	public static function save_user_collections( $collections, $user_id = false ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id ) {
			update_user_meta( $user_id, 'wprm-recipe-collections', $collections );
		}

		return $collections;
	}

	/**
	 * Get data to use in collections for a particular recipe.
	 *
	 * @since 	4.1.0
	 * @param	mixed $recipe Recipe to get the collections data for.
	 */
	public static function get_collections_data_for_recipe( $recipe ) {
		switch ( WPRM_Settings::get( 'recipe_collections_items_recipe_servings' ) ) {
			case 'one':
				$servings = 1;
				break;
			case 'two':
				$servings = 2;
				break;
			case 'three':
				$servings = 3;
				break;
			case 'four':
				$servings = 4;
				break;
			default:
			$servings = floatval( $recipe->servings() ) ? floatval( $recipe->servings() ) : 1;
		}

		$modified_date = $recipe->date_modified();
		$modified_timestamp = $modified_date ? strtotime( $modified_date ) : time();

		return array_merge( self::get_recipe_servings_unit_data( $recipe ), array(
			'type' => 'recipe',
			'recipeId' => $recipe->id(),
			'name' => $recipe->name(),
			'image' => $recipe->image_url( array( 300, 300 ) ),
			'servings' => $servings,
			'parent_id' => $recipe->parent_post_id(),
			'parent_url' => $recipe->permalink(),
			'cachedAt' => time(),
			'modifiedAt' => $modified_timestamp,
		) );
	}

	/**
	 * Get data to use in collections for a particular ingredient.
	 *
	 * @since 	5.6.0
	 * @param	mixed $ingredient Ingredient to get the collections data for.
	 */
	public static function get_collections_data_for_ingredient( $ingredient ) {
		$nutrition = WPRMPN_Ingredient_Manager::get_nutrition( $ingredient->term_id );
		$amount = isset( $nutrition['amount'] ) && 0 < floatval( $nutrition['amount'] ) ? floatval( $nutrition['amount'] ) : 1;

		// Get ingredient modification time (stored in term meta or use term update time).
		$modified_timestamp = get_term_meta( $ingredient->term_id, 'wprm_modified_at', true );
		if ( ! $modified_timestamp ) {
			// Fallback to current time if not set yet.
			$modified_timestamp = time();
		} else {
			$modified_timestamp = intval( $modified_timestamp );
		}

		return array(
			'type' => 'nutrition-ingredient',
			'ingredientId' => $ingredient->term_id,
			'name' => $ingredient->name,
			'servings' => 1,
			'servingsUnit' => '',
			'amount' => $amount,
			'amountOriginal' => $amount,
			'unit' => isset( $nutrition['unit'] ) ? $nutrition['unit'] : '',
			'nutrition' => isset( $nutrition['nutrients'] ) ? $nutrition['nutrients'] : array(),
			'cachedAt' => time(),
			'modifiedAt' => $modified_timestamp,
		);
	}

	/**
	 * Invalidate cached collection.
	 *
	 * @since	4.1.0
	 * @param	int $collection_id ID of the collection to invalidate.
	 */
	public static function invalidate_collection( $collection_id ) {
		if ( array_key_exists( $collection_id, self::$collections ) ) {
			unset( self::$collections[ $collection_id ] );
		}
	}

	/**
	 * Sanitize collection array.
	 *
	 * @since	4.1.0
	 * @param	array $collection Array containing all collection input data.
	 */
	public static function sanitize_collection( $collection ) {
		$sanitized_collection = array();

		// Boolean fields.
		$sanitized_collection['default'] = isset( $collection['default'] ) && $collection['default'] ? true : false;
		$sanitized_collection['push'] = isset( $collection['push'] ) && $collection['push'] ? true : false;
		$sanitized_collection['fixed'] = isset( $collection['fixed'] ) && $collection['fixed'] ? true : false;
		$sanitized_collection['template'] = isset( $collection['template'] ) && $collection['template'] ? true : false;
		$sanitized_collection['quick_add'] = isset( $collection['quick_add'] ) && $collection['quick_add'] ? true : false;

		// Text fields.
		$sanitized_collection['name'] = isset( $collection['name'] ) ? sanitize_text_field( $collection['name'] ) : '';
		$sanitized_collection['description'] = isset( $collection['description'] ) ? WPRM_Recipe_Sanitizer::sanitize_html( $collection['description'] ) : '';
		
		// Numbers.
		$sanitized_collection['order'] = isset( $collection['order'] ) ? intval( $collection['order'] ) : 0;
		$sanitized_collection['nbrItems'] = isset( $collection['nbrItems'] ) ? intval( $collection['nbrItems'] ) : 0;
		$sanitized_collection['created'] = isset( $collection['created'] ) ? intval( $collection['created'] ) : time();

		// Arrays.
		$sanitized_collection['columns'] = isset( $collection['columns'] ) ? $collection['columns'] : array( array( 'id' => 0, 'name' => __( 'Recipes', 'wp-recipe-maker-premium' ) ) );
		$sanitized_collection['groups'] = isset( $collection['groups'] ) ? $collection['groups'] : array( array( 'id' => 0, 'name' => '' ) );
		$sanitized_collection['items'] = isset( $collection['items'] ) ? $collection['items'] : array( '0-0' => array() );

		return $sanitized_collection;
	}

	/**
	 * Create a new collection.
	 *
	 * @since	4.1.0
	 * @param	array $recipe Recipe fields to save.
	 */
	public static function create_collection( $collection = array() ) {
		$post = array(
			'post_type' => WPRMPRC_POST_TYPE,
			'post_status' => 'publish',
		);

		$collection_id = wp_insert_post( $post );
		self::update_collection( $collection_id, self::sanitize_collection( $collection ) );

		return $collection_id;
	}

	/**
	 * Save collection fields.
	 *
	 * @since	4.1.0
	 * @param	int   $id Post ID of the collection.
	 * @param	array $collection Collection fields to save.
	 */
	public static function update_collection( $id, $collection ) {
		// Post Fields.
		$post = array(
			'ID'	      => $id,
			'post_title'  => $collection['name'],
			'post_name'	  => 'wprm-collection-' . sanitize_title( $collection['name'] ),
		);
		wp_update_post( $post );

		// Meta Fields.
		update_post_meta( $id, 'wprm_description', $collection['description'] );
		update_post_meta( $id, 'wprm_default', $collection['default'] );
		update_post_meta( $id, 'wprm_push', $collection['push'] );
		update_post_meta( $id, 'wprm_fixed', $collection['fixed'] );
		update_post_meta( $id, 'wprm_template', $collection['template'] );
		update_post_meta( $id, 'wprm_quick_add', $collection['quick_add'] );
		update_post_meta( $id, 'wprm_nbr_items', $collection['nbrItems'] );
		update_post_meta( $id, 'wprm_columns', $collection['columns'] );
		update_post_meta( $id, 'wprm_groups', $collection['groups'] );
		update_post_meta( $id, 'wprm_order', $collection['order'] );
		update_post_meta( $id, 'wprm_items', $collection['items'] );

		self::invalidate_collection( $id );
	}

	/**
	 * Get shared collection.
	 *
	 * @since	9.7.0
	 * @param	int   $id User ID to get the collection from.
	 * @param	mixed $collection_id Collection ID to get.
	 */
	public static function get_shared_collection( $user_id, $collection_id ) {
		$collection = false;

		if ( $user_id && WPRM_Settings::get( 'recipe_collections_share_collection' ) ) {
			$collections = self::get_user_collections( $user_id );

			if ( $collections ) {
				if ( 'inbox' === $collection_id ) {
					$potential_collection = $collections['inbox'];

					if ( $potential_collection && isset( $potential_collection['shared'] ) && $potential_collection['shared'] ) {
						$collection = $potential_collection;
					}
				} else {
					$collection_id = intval( $collection_id );

					foreach ( $collections['user'] as $potential_collection ) {
						if ( $potential_collection['id'] === $collection_id && isset( $potential_collection['shared'] ) && $potential_collection['shared'] ) {
							$collection = $potential_collection;
							break;
						}
					}
				}
			}
		}

		// Do not pass along shopping list.
		if ( $collection ) {
			$collection['shoppingList'] = false;
		}

		return $collection;
	}
}

WPRMPRC_Manager::init();
