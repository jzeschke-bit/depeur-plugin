<?php
/**
 * Handle the Recipe Collections API.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections API.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Api {

	/**
	 * Register actions and filters.
	 *
	 * @since    4.1.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
		add_action( 'rest_insert_' . WPRMPRC_POST_TYPE, array( __CLASS__, 'api_insert_update_recipe_collection' ), 10, 3 );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    4.1.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_field( WPRMPRC_POST_TYPE, 'collection', array(
				'get_callback'    => array( __CLASS__, 'api_get_recipe_collection_data' ),
				'update_callback' => null,
				'schema'          => null,
			));
			register_rest_route( 'wp-recipe-maker/v1', '/saved-collection/reload/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_reload_saved_collection' ),
				'methods' => 'POST',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/saved-collection/category/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_set_saved_collection_category' ),
				'methods' => 'POST',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/user/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_get_user_collections' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/user/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_save_user_collections' ),
				'methods' => 'POST',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/shared/(?P<userId>\d+)/(?P<collectionId>\d+|inbox)', array(
				'callback' => array( __CLASS__, 'api_get_shared_collection' ),
				'methods' => 'GET',
				'args' => array(
					'userId' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
					'collectionId' => array(
						'validate_callback' => function($param, $request, $key) {
							return is_numeric( $param ) || 'inbox' === $param;
						},
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/recipes', array(
				'callback' => array( __CLASS__, 'api_search_recipes' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/nutrition-ingredients', array(
				'callback' => array( __CLASS__, 'api_search_ingredients' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/recipe/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_get_recipe' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/recipe-data/(?P<id>\d+)', array(
				'callback' => array( __CLASS__, 'api_get_recipe_data' ),
				'methods' => 'GET',
				'args' => array(
					'id' => array(
						'validate_callback' => array( __CLASS__, 'api_validate_numeric' ),
					),
				),
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/ingredients', array(
				'callback' => array( __CLASS__, 'api_get_ingredients' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/nutrition', array(
				'callback' => array( __CLASS__, 'api_get_nutrition' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/inbox', array(
				'callback' => array( __CLASS__, 'api_save_to_inbox' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/add', array(
				'callback' => array( __CLASS__, 'api_add_to_collections' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/refresh-items', array(
				'callback' => array( __CLASS__, 'api_refresh_items' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/remove', array(
				'callback' => array( __CLASS__, 'api_remove_from_collections' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/save', array(
				'callback' => array( __CLASS__, 'api_save_to_collections' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/shopping-list/(?P<uid>\w+)', array(
				'callback' => array( __CLASS__, 'api_get_shopping_list' ),
				'methods' => 'GET',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/shopping-list/(?P<uid>\w+)', array(
				'callback' => array( __CLASS__, 'api_save_shopping_list' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/shopping-list', array(
				'callback' => array( __CLASS__, 'api_shopping_list_generate' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
			register_rest_route( 'wp-recipe-maker/v1', '/recipe-collections/shop-shopping-list', array(
				'callback' => array( __CLASS__, 'api_shop_shopping_list' ),
				'methods' => 'POST',
				'permission_callback' => '__return_true',
			));
		}
	}

	/**
	 * Validate ID in API call.
	 *
	 * @since 4.1.0
	 * @param mixed           $param Parameter to validate.
	 * @param WP_REST_Request $request Current request.
	 * @param mixed           $key Key.
	 */
	public static function api_validate_numeric( $param, $request, $key ) {
		return is_numeric( $param );
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 9.6.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle recipe collection calls to the REST API.
	 *
	 * @since 4.1.0
	 * @param array           $object Details of current post.
	 * @param mixed           $field_name Name of field.
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_recipe_collection_data( $object, $field_name, $request ) {
		$collection = WPRMPRC_Manager::get_collection( $object['id'] );

		if ( ! $collection ) {
			return false;
		}

		return $collection->get_data();
	}

	/**
	 * Handle recipe collection calls to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a post, false when updating.
	 */
	public static function api_insert_update_recipe_collection( $post, $request, $creating ) {
		$params = $request->get_params();
		$collection = isset( $params['collection'] ) ? WPRMPRC_Manager::sanitize_collection( $params['collection'] ) : false;
		$collection_id = $post->ID;

		if ( false !== $collection ) {
			WPRMPRC_Manager::update_collection( $collection_id, $collection );
		}
	}

	/**
	 * Handle reload saved collection call to the REST API.
	 *
	 * @since 8.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_reload_saved_collection( $request ) {
		$id = intval( $request['id'] );

		$collection = WPRMPRC_Manager::get_collection( $id );

		if ( $collection ) {
			$collection->reload();
			return rest_ensure_response( true );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle set category call to the REST API.
	 *
	 * @since 9.6.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_set_saved_collection_category( $request ) {
		$id = intval( $request['id'] );

		$params = $request->get_params();
		$category = isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : false;

		$collection = WPRMPRC_Manager::get_collection( $id );

		if ( $collection && false !== $category ) {
			update_post_meta( $id, 'wprm_category', $category );
			return rest_ensure_response( true );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle get user collections call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_user_collections( $request ) {
		$user_id = intval( $request['id'] );

		if ( $user_id !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return false;
		}

		return rest_ensure_response( WPRMPRC_Manager::get_user_collections( $user_id ) );
	}

	/**
	 * Handle save user collections call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_user_collections( $request ) {
		$user_id = intval( $request['id'] );

		$params = $request->get_params();
		$collections = isset( $params['collections'] ) ? $params['collections'] : false;

		if ( $user_id !== get_current_user_id() && ! current_user_can( 'edit_others_posts' ) ) {
			return false;
		}

		if ( $collections ) {
			WPRMPRC_Manager::save_user_collections( $collections, $user_id );
		}

		return rest_ensure_response( $collections );
	}

	/**
	 * Handle get shared collections call to the REST API.
	 *
	 * @since	9.7.0
	 * @param	WP_REST_Request $request Current request.
	 */
	public static function api_get_shared_collection( $request ) {
		$user_id = intval( $request['userId'] );
		$collection_id = sanitize_key( $request['collectionId'] );

		$collection = WPRMPRC_Manager::get_shared_collection( $user_id, $collection_id );

		return rest_ensure_response( $collection );
	}

	/**
	 * Handle get search recipes call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_search_recipes( $request ) {
		$recipes = array();

		// Parameters.
		$params = $request->get_params();
		$search = isset( $params['search'] ) ? $params['search'] : '';

		// Show all recipes for editors.
		$current_user_is_editor = get_current_user_id() && current_user_can( 'edit_others_posts' );

		// Search query.
		$args = array(
			'post_type' => WPRM_POST_TYPE,
			'post_status' => $current_user_is_editor ? 'any' : 'publish',
			'posts_per_page' => 100,
			's' => $search,
			'meta_query' => array(
				'relation' => 'AND',
			),
		);

		// Check if we should limit by type.
		if ( ! $current_user_is_editor && 'limit' === WPRM_Settings::get( 'recipe_collections_items_allow_recipe_search_by_types' ) ) {
			$types = WPRM_Settings::get( 'recipe_collections_items_allow_recipe_search_types' );

			if ( $types ) {
				// Backwards compatibility.
				if ( in_array( 'other', $types ) ) {
					$types[] = 'non-food';
				}
				
				$args['meta_query'][] = array(
					'key' => 'wprm_type',
					'compare' => 'IN',
					'value' => $types,
				);
			}
		}

		// Query recipes.
		$query = new WP_Query( $args );

		// Loop over posts.
		$posts = $query->posts;
		foreach ( $posts as $post ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $post );
			
			if ( $recipe ) {
				// Check if access to this recipe is restricted for the current user.
				if ( ! WPRM_Access::can_access( $recipe->id() ) ) {
					continue;
				}

				$recipes[] = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );
			}
		}

		return rest_ensure_response( $recipes );
	}

	/**
	 * Handle search ingredients call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_search_ingredients( $request ) {
		$ingredients = array();

		// Parameters.
		$params = $request->get_params();
		$search = isset( $params['search'] ) ? $params['search'] : '';

		// Search query.
		$args = array(
			'taxonomy' => 'wprm_nutrition_ingredient',
			'hide_empty' => false,
			'name__like' => $search,
		);

		$terms = get_terms( $args );

		foreach ( $terms as $term ) {
			$ingredients[] = WPRMPRC_Manager::get_collections_data_for_ingredient( $term );
		}

		return rest_ensure_response( $ingredients );
	}

	/**
	 * Handle get recipe call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_recipe( $request ) {
		$recipe = WPRM_Recipe_Manager::get_recipe( $request['id'] );

		if ( $recipe ) {
			$template_mode = WPRM_Settings::get( 'recipe_template_mode' );

			// Get template slug.
			$template_slug = WPRM_Settings::get( 'recipe_collections_template_' . $template_mode );
			if ( 'default_recipe_template' === $template_slug ) {
				$template_slug = WPRM_Settings::get( 'default_recipe_template_modern' );
			}

			if ( 'modern' === $template_mode ) {
				switch( $recipe->type() ) {
					case 'howto':
						$template_slug = WPRM_Settings::get( 'recipe_collections_howto_template_modern' );
						if ( 'default_recipe_template' === $template_slug ) {
							$template_slug = WPRM_Settings::get( 'default_howto_recipe_template_modern' );
						}
						break;
					case 'other':
						$template_slug = WPRM_Settings::get( 'recipe_collections_other_template_modern' );
						if ( 'default_recipe_template' === $template_slug ) {
							$template_slug = WPRM_Settings::get( 'default_other_recipe_template_modern' );
						}
						break;
				}
			}

			$html = '';
			$template = WPRM_Template_Manager::get_template_by_slug( $template_slug );
			$style = WPRM_Template_Manager::get_template_css( $template );

			if ( $style ) {
				$html .= '<style type="text/css">' . $style . '</style>';
			}
			$html .= '<div id="wprm-recipe-container-' . esc_attr( $recipe->id() ) . '" class="wprm-recipe-container" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-servings="' . esc_attr( $recipe->servings() ) . '">';
			$html .= do_shortcode( WPRM_Template_Manager::get_template( $recipe, 'single', $template_slug ) );
			$html .= '</div>';
			$html .= WPRM_Popup::get_html_for_all_modals( false, true );

			return rest_ensure_response( array(
				'html' => $html,
				'javascript' => array(),
			) );
		} else {
			return rest_ensure_response( array(
				'html' => false,
				'javascript' => false,
			) );
		}
	}

	/**
	 * Handle get recipe data call to the REST API.
	 *
	 * @since 9.6.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_recipe_data( $request ) {
		$recipe = WPRM_Recipe_Manager::get_recipe( $request['id'] );

		if ( $recipe ) {
			return rest_ensure_response( WPRMPRC_Manager::get_collections_data_for_recipe( $recipe ) );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle get ingredients call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_ingredients( $request ) {
		$recipe_data = array();
		$ingredients_data = array();

		// Parameters.
		$params = $request->get_params();
		$ingredients = isset( $params['ingredients'] ) ? array_map( 'sanitize_text_field', $params['ingredients'] ) : array();
		$recipes = isset( $params['recipes'] ) ? array_map( 'intval', $params['recipes'] ) : array();

		// Ingredients Data.
		foreach ( $ingredients as $ingredient_name ) {
			if ( $ingredient_name ) {
				$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient_name );

				if ( $ingredient_id ) {
					$ingredient = array(
						'group' => WPRMPRC_Ingredient_Groups::get_group( $ingredient_id ),
					);

					if ( WPRM_Settings::get( 'recipe_collections_shopping_list_links') ) {
						$ingredient['link'] = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient_id );
					}

					$ingredients_data[ $ingredient_name ] = $ingredient;
				}
			}
		}

		// Recipe Data.
		foreach ( $recipes as $recipe_id ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			if ( ! $recipe ) {
				continue;
			}

			$servings = 0 < floatval( $recipe->servings() ) ? floatval( $recipe->servings() ) : 1;
			$ingredients = $recipe->ingredients_without_groups();

			foreach ( $ingredients as $index => $ingredient ) {
				// Strip HTML and shortcodes.
				$ingredients[ $index ]['amount'] = wp_strip_all_tags( strip_shortcodes( $ingredients[ $index ]['amount'] ) );
				$ingredients[ $index ]['unit'] = wp_strip_all_tags( strip_shortcodes( $ingredients[ $index ]['unit'] ) );
				$ingredients[ $index ]['name'] = wp_strip_all_tags( strip_shortcodes( $ingredients[ $index ]['name'] ) );
				$ingredients[ $index ]['notes'] = wp_strip_all_tags( strip_shortcodes( $ingredients[ $index ]['notes'] ) );

				if ( WPRM_Settings::get( 'recipe_collections_shopping_list_links') ) {
					$ingredients[ $index ]['link'] = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient['id'] );
				}

				$ingredients[ $index ]['group'] = WPRMPRC_Ingredient_Groups::get_group( $ingredient['id'] );
			}

			$recipe_data[ $recipe_id ] = array(
				'servings' => $servings,
				'ingredients' => $ingredients,
			);
		}
		
		return rest_ensure_response( array(
			'recipes' => $recipe_data,
			'ingredients' => $ingredients_data,
		) );
	}

	/**
	 * Handle get nutrition call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_nutrition( $request ) {
		$recipe_data = array();

		// Parameters.
		$params = $request->get_params();
		$recipes = isset( $params['recipes'] ) ? array_map( 'intval', $params['recipes'] ) : array();

		foreach ( $recipes as $recipe_id ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			$nutrition = array();
			if ( $recipe ) {
				$nutrition = $recipe->nutrition();
			}

			$recipe_data[ $recipe_id ] = array(
				'nutrition' => $nutrition,
			);
		}

		return rest_ensure_response( $recipe_data );
	}

	/**
	 * Handle save to inbox call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_to_inbox( $request ) {
		// Parameters.
		$params = $request->get_params();
		$recipe_id = isset( $params['recipeId'] ) ? intval( $params['recipeId'] ) : 0;
		$servings = isset( $params['servings'] ) && false !== $params['servings'] ? floatval( $params['servings'] ) : false;
		$collection = isset( $params['collection'] ) ? sanitize_key( $params['collection'] ) : 'inbox';
		$column = isset( $params['column'] ) ? intval( $params['column'] ) : 0;
		$group = isset( $params['group'] ) ? intval( $params['group'] ) : 0;

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

		if ( $recipe ) {
			$collections = WPRMPRC_Manager::get_user_collections();

			$recipe_data = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );

			// Optionally set specific serving size.
			if ( false !== $servings ) {
				$recipe_data['servings'] = $servings;
			}

			if ( 'inbox' === $collection ) {
				// Get unique ID.
				$max_id = 0 < count( $collections['inbox']['items']['0-0'] ) ? max( array_map( function( $item ) { return intval( $item['id'] ); }, $collections['inbox']['items']['0-0'] ) ) : false;
				$recipe_data['id'] = false === $max_id ? 0 : $max_id + 1;

				$collections['inbox']['nbrItems']++;
				
				// Put recipe at top or bottom of inbox.
				if ( 'top' === WPRM_Settings::get( 'recipe_collections_add_button_placement' ) ) {
					array_unshift( $collections['inbox']['items']['0-0'], $recipe_data );
				} else {
					$collections['inbox']['items']['0-0'][] = $recipe_data;
				}
			} else {
				$collection_id = intval( $collection );
				
				$collection_index = false;
				foreach ( $collections['user'] as $index => $user_collection ) {
					if ( $collection_id === $user_collection['id'] ) {
						$collection_index = $index;
						break;
					}
				}

				if ( false !== $collection_index ) {
					// Make sure column/group exists.
					$column_group = $column . '-' . $group;

					if ( ! isset( $collections['user'][ $collection_index ]['items'][ $column_group ] ) ) {
						$collections['user'][ $collection_index ]['items'][ $column_group ] = array();
					}

					// Get unique ID.
					$all_items = array_reduce( $collections['user']['items'], function( $all_items, $group_items ) { return array_merge( $all_items, $group_items ); }, array() );
					$max_id = 0 < count( $all_items ) ? max( array_map( function( $item ) { return intval( $item['id'] ); }, $all_items ) ) : false;
					$recipe_data['id'] = false === $max_id ? 0 : $max_id + 1;

					$collections['user'][ $collection_index ]['nbrItems']++;

					// Put recipe at top or bottom of collection.
					if ( 'top' === WPRM_Settings::get( 'recipe_collections_add_button_placement' ) ) {
						array_unshift( $collections['user'][ $collection_index ]['items'][ $column_group ], $recipe_data );
					} else {
						$collections['user'][ $collection_index ]['items'][ $column_group ][] = $recipe_data;
					}
				}
			}

			WPRMPRC_Manager::save_user_collections( $collections );
		}

		return rest_ensure_response( true );
	}

	/**
	 * Handle add to collections call to the REST API.
	 *
	 * @since 8.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_add_to_collections( $request ) {
		// Parameters.
		$params = $request->get_params();

		$local_collections = isset( $params['localCollections'] ) ? $params['localCollections'] : false;
		$recipes_to_save = isset( $params['recipes'] ) ? $params['recipes'] : false;
		$collection = isset( $params['collection'] ) ? $params['collection'] : false;

		if ( $recipes_to_save && $collection ) {
			$recipes_data = array();

			foreach ( $recipes_to_save as $recipe_to_save ) {
				$recipe_id = intval( $recipe_to_save['id'] );
				$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

				if ( $recipe ) {
					$recipe_data = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );

					$recipe_servings = isset( $recipe_to_save['servings'] ) && $recipe_to_save['servings'] ? intval( $recipe_to_save['servings'] ) : false;
					if ( $recipe_servings ) {
						$recipe_data['servings'] = $recipe_servings;
					}

					$recipes_data[] = $recipe_data;
				}
			}

			// If we found recipes to add.
			if ( $recipes_data ) {
				$user_id = get_current_user_id();

				// Use logged in user's collections, local collections or default ones (in that order).
				if ( $user_id ) {
					$collections = WPRMPRC_Manager::get_user_collections();	
				} else {
					if ( $local_collections ) {
						$collections = $local_collections;
					} else {
						$collections = WPRMPRC_Manager::get_default_collections();
					}
				}

				// Make sure temp exists.
				if ( ! isset( $collections['temp'] ) ) {
					$collections['temp'] = array(
						'id' => 'temp',
						'columns' => array( array( 'id' => 0, 'name' => '') ),
						'groups' => array( array( 'id' => 0, 'name' => '') ),
						'items' => array( '0-0' => array() ),
						'nbrItems' => 0,
					);
				}

				// Add to collections.
				$collection_id = isset( $collection['id'] ) ? $collection['id'] : 'inbox';
				$collection_column = isset( $collection['column'] ) ? intval( $collection['column'] ) : 0;
				$collection_group = isset( $collection['group'] ) ? intval( $collection['group'] ) : 0;

				$collection = false;
				if ( 'inbox' === $collection_id ) {
					$collection = &$collections['inbox'];
					$column_group = '0-0';
				} else if ( 'temp' === $collection_id ) {
					$collection = &$collections['temp'];
					$column_group = '0-0';
				} else {
					$collection_id = str_replace( 'user-', '', $collection_id );
					$collection_id = intval( $collection_id );

					$collection_index = false;
					foreach ( $collections['user'] as $index => $user_collection ) {
						if ( $collection_id === $user_collection['id'] ) {
							$collection_index = $index;
							break;
						}
					}

					if ( false !== $collection_index ) {
						$collection = &$collections['user'][ $collection_index ];
					}
					$column_group = $collection_column . '-' . $collection_group;
				}

				// If we found the collection, add the recipes to it.
				if ( $collection ) {

					if ( ! isset( $collection['items'][ $column_group ] ) ) {
						 $collection['items'][ $column_group ] = array();
					}

					// Get unique ID.
					$all_items = array_reduce( $collection['items'], function( $all_items, $group_items ) { return array_merge( $all_items, $group_items ); }, array() );
					$max_id = 0 < count( $all_items ) ? max( array_map( function( $item ) { return intval( $item['id'] ); }, $all_items ) ) : false;

					foreach ( $recipes_data as $recipe_data ) {
						// Make sure ID is unique.
						$recipe_data['id'] = false === $max_id ? 0 : $max_id + 1;
						$max_id = $recipe_data['id'] + 1;
						$collection['nbrItems']++;

						// Put recipe at top or bottom of inbox.
						if ( 'top' === WPRM_Settings::get( 'recipe_collections_add_button_placement' ) ) {
							array_unshift( $collection['items'][ $column_group ], $recipe_data );
						} else {
							$collection['items'][ $column_group ][] = $recipe_data;
						}
					}

					if ( $user_id ) {
						WPRMPRC_Manager::save_user_collections( $collections );
						return rest_ensure_response( true );
					} else {
						// Not logged in, return collections to store in local storage.
						return rest_ensure_response( array(
							'collections' => $collections,
						) );
					}
				}
			}
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle remove from collections call to the REST API.
	 *
	 * @since 8.10.1
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_remove_from_collections( $request ) {
		// Parameters.
		$params = $request->get_params();

		$local_collections = isset( $params['localCollections'] ) ? $params['localCollections'] : false;
		$recipes_to_remove = isset( $params['recipes'] ) ? $params['recipes'] : false;
		$collection = isset( $params['collection'] ) ? $params['collection'] : false;

		if ( $recipes_to_remove && $collection ) {
			$recipes_to_remove = array_map( 'intval', $recipes_to_remove );
			$user_id = get_current_user_id();

			// Use logged in user's collections, local collections or default ones (in that order).
			if ( $user_id ) {
				$collections = WPRMPRC_Manager::get_user_collections();	
			} else {
				if ( $local_collections ) {
					$collections = $local_collections;
				} else {
					$collections = WPRMPRC_Manager::get_default_collections();
				}
			}

			// Make sure temp exists.
			if ( ! isset( $collections['temp'] ) ) {
				$collections['temp'] = array(
					'id' => 'temp',
					'columns' => array( array( 'id' => 0, 'name' => '') ),
					'groups' => array( array( 'id' => 0, 'name' => '') ),
					'items' => array( '0-0' => array() ),
					'nbrItems' => 0,
				);
			}

			// Remove from collections.
			$collection_id = isset( $collection['id'] ) ? $collection['id'] : 'inbox';
			$collection_column = isset( $collection['column'] ) ? intval( $collection['column'] ) : 0;
			$collection_group = isset( $collection['group'] ) ? intval( $collection['group'] ) : 0;

			$collection = false;
			if ( 'inbox' === $collection_id ) {
				$collection = &$collections['inbox'];
				$column_group = '0-0';
			} else if ( 'temp' === $collection_id ) {
				$collection = &$collections['temp'];
				$column_group = '0-0';
			} else {
				$collection_id = str_replace( 'user-', '', $collection_id );
				$collection_id = intval( $collection_id );

				$collection_index = false;
				foreach ( $collections['user'] as $index => $user_collection ) {
					if ( $collection_id === $user_collection['id'] ) {
						$collection_index = $index;
						break;
					}
				}

				if ( false !== $collection_index ) {
					$collection = &$collections['user'][ $collection_index ];
				}
				$column_group = $collection_column . '-' . $collection_group;
			}

			// If we found the collection, remove the recipes from it.
			if ( $collection && isset( $collection['items'][ $column_group ] ) ) {
				foreach ( $collection['items'][ $column_group ] as $index => $item ) {
					if ( 'recipe' === $item['type'] ) {
						if ( in_array( intval( $item['recipeId'] ), $recipes_to_remove ) ) {
							array_splice( $collection['items'][ $column_group ], $index, 1 ); // Use instead of unset to make sure array gets reindexed (becomes object in JS otherwise).
						} 
					}
				}

				if ( $user_id ) {
					WPRMPRC_Manager::save_user_collections( $collections );
					return rest_ensure_response( true );
				} else {
					// Not logged in, return collections to store in local storage.
					return rest_ensure_response( array(
						'collections' => $collections,
					) );
				}
			}
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle save to collections call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_to_collections( $request ) {
		// Parameters.
		$params = $request->get_params();
		$type = isset( $params['type'] ) ? sanitize_key( $params['type'] ) : 'saved';
		$user_id = isset( $params['userId'] ) ? intval( $params['userId'] ) : 0;
		$collection_id = isset( $params['collectionId'] ) ? sanitize_key( $params['collectionId'] ) : 0;

		if ( 'shared' === $type ) {
			$collection = WPRMPRC_Manager::get_shared_collection( $user_id, $collection_id );
		} else {
			// Default to saved collection.
			$collection = WPRMPRC_Manager::get_collection( $collection_id );
		}

		if ( $collection ) {
			$collection_data = 'shared' === $type ? $collection : $collection->get_data();
			$collections = WPRMPRC_Manager::get_user_collections();

			// Clean up shared collection data.
			$collection_data['shared'] = false;
			unset( $collection_data['sharedEncoded'] );

			// Get unique ID.
			$max_id = 0 < count( $collections['user'] ) ? max( array_map( function( $c ) { return intval( $c['id'] ); }, $collections['user'] ) ) : false;
			$collection_data['id'] = false === $max_id ? 0 : $max_id + 1;

			$collections['user'][] = $collection_data;

			WPRMPRC_Manager::save_user_collections( $collections );
		}

		return rest_ensure_response( true );
	}

	/**
	 * Handle refresh collection items call to the REST API.
	 *
	 * @since 8.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_refresh_items( $request ) {
		$params = $request->get_params();
		$items = isset( $params['items'] ) ? $params['items'] : array();

		if ( ! is_array( $items ) || empty( $items ) ) {
			return rest_ensure_response( array() );
		}

		$refreshed_items = array();
		$processed_items = array(); // Track processed recipe/ingredient IDs to avoid duplicates
		$unique_refreshed_items = array(); // Track unique items to return (one per recipe/ingredient)

		foreach ( $items as $item ) {
			// Validate item structure.
			if ( ! is_array( $item ) || ! isset( $item['type'] ) ) {
				continue;
			}

			$refreshed_item = false;

			// Get cached timestamp from original item.
			$cached_at = isset( $item['cachedAt'] ) ? intval( $item['cachedAt'] ) : 0;

			switch ( $item['type'] ) {
				case 'recipe':
					if ( isset( $item['recipeId'] ) ) {
						$recipe_id = intval( $item['recipeId'] );
						$missing_servings_data = ! isset( $item['servingsUnitRaw'] ) || ! isset( $item['originalServingsParsed'] );
						
						// Skip invalid recipe IDs.
						if ( $recipe_id <= 0 ) {
							continue 2; // Continue outer foreach loop.
						}

						// Check if we've already processed this recipe in this request.
						$item_key = 'recipe_' . $recipe_id;
						if ( isset( $processed_items[ $item_key ] ) ) {
							// Already processed - skip processing but check if we should include it.
							// If it's in unique_refreshed_items, it will be returned (already handled).
							// If processed_items[$item_key] is false, it means it wasn't stale or doesn't exist.
							$refreshed_item = false;
						} else {
							$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

							if ( $recipe ) {
								// Get current modification time.
								$modified_date = $recipe->date_modified();
								$current_modified_at = $modified_date ? strtotime( $modified_date ) : time();

								// Only refresh if item is missing cachedAt, missing servings baseline data,
								// or current modification time is newer than cachedAt.
								if ( ! $cached_at || $missing_servings_data || $current_modified_at > $cached_at ) {
									$refreshed_item = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );
									
									// Validate refreshed item was created successfully.
									if ( ! is_array( $refreshed_item ) || empty( $refreshed_item ) ) {
										// Mark as processed even if refresh failed (to avoid retrying).
										$processed_items[ $item_key ] = false;
										$refreshed_item = false;
									} else {
										// Store the base refreshed item (without ID/servings) for this recipe ID.
										// This base item will be returned once and applied to all occurrences.
										$base_refreshed_item = $refreshed_item;
										$processed_items[ $item_key ] = $base_refreshed_item;
										
										// Store in unique items map (one per recipe/ingredient).
										// Store base item without ID/servings so client can merge with each original item's ID/servings.
										if ( ! isset( $unique_refreshed_items[ $item_key ] ) ) {
											$unique_refreshed_items[ $item_key ] = $base_refreshed_item;
										}
									}
								} else {
									// Not stale, mark as processed but don't return anything.
									$processed_items[ $item_key ] = false;
									$refreshed_item = false;
								}
							} else {
								// Recipe doesn't exist, mark as processed to avoid retrying.
								$processed_items[ $item_key ] = false;
								$refreshed_item = false;
							}
						}
					}
					break;

				case 'nutrition-ingredient':
					if ( isset( $item['ingredientId'] ) ) {
						$ingredient_id = intval( $item['ingredientId'] );
						
						// Skip invalid ingredient IDs.
						if ( $ingredient_id <= 0 ) {
							continue 2; // Continue outer foreach loop.
						}

						// Check if we've already processed this ingredient in this request.
						$item_key = 'ingredient_' . $ingredient_id;
						if ( isset( $processed_items[ $item_key ] ) ) {
							// Already processed - skip processing but check if we should include it.
							// If it's in unique_refreshed_items, it will be returned (already handled).
							// If processed_items[$item_key] is false, it means it wasn't stale or doesn't exist.
							$refreshed_item = false;
						} else {
							$ingredient = get_term( $ingredient_id, 'wprm_nutrition_ingredient' );

							if ( $ingredient && ! is_wp_error( $ingredient ) ) {
								// Get current modification time.
								$current_modified_at = get_term_meta( $ingredient_id, 'wprm_modified_at', true );
								if ( ! $current_modified_at ) {
									// If no modification time stored, assume it's current (new ingredient).
									$current_modified_at = time();
								} else {
									$current_modified_at = intval( $current_modified_at );
								}

								// Only refresh if item is missing cachedAt OR current modification time is newer than cachedAt.
								if ( ! $cached_at || $current_modified_at > $cached_at ) {
									$refreshed_item = WPRMPRC_Manager::get_collections_data_for_ingredient( $ingredient );
									
									// Validate refreshed item was created successfully.
									if ( ! is_array( $refreshed_item ) || empty( $refreshed_item ) ) {
										// Mark as processed even if refresh failed (to avoid retrying).
										$processed_items[ $item_key ] = false;
										$refreshed_item = false;
									} else {
										// Store the base refreshed item (without ID/servings) for this ingredient ID.
										// This base item will be returned once and applied to all occurrences.
										$base_refreshed_item = $refreshed_item;
										$processed_items[ $item_key ] = $base_refreshed_item;
										
										// Store in unique items map (one per recipe/ingredient).
										// Store base item without ID/servings so client can merge with each original item's ID/servings.
										if ( ! isset( $unique_refreshed_items[ $item_key ] ) ) {
											$unique_refreshed_items[ $item_key ] = $base_refreshed_item;
										}
									}
								} else {
									// Not stale, mark as processed but don't return anything.
									$processed_items[ $item_key ] = false;
									$refreshed_item = false;
								}
							} else {
								// Ingredient doesn't exist, mark as processed to avoid retrying.
								$processed_items[ $item_key ] = false;
								$refreshed_item = false;
							}
						}
					}
					break;
			}

			// Note: We don't add items here anymore - we'll add unique items at the end.
			// This prevents returning the same recipe/ingredient multiple times.
		}

		// Return only unique refreshed items (one per recipe/ingredient).
		// The client will apply each unique item to all matching locations.
		return rest_ensure_response( array_values( $unique_refreshed_items ) );
	}

	/**
	 * Handle get shopping list call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_shopping_list( $request ) {
		return rest_ensure_response( WPRMPRC_Shopping_List::get( $request['uid'] ) );
	}

	/**
	 * Handle save shopping list call to the REST API.
	 *
	 * @since 4.1.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_shopping_list( $request ) {
		$uid = $request['uid'];
		
		$params = $request->get_params();
		$data = isset( $params['data'] ) ? $params['data'] : false;

		if ( $data ) {
			return rest_ensure_response( WPRMPRC_Shopping_List::save( $uid, $data ) );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Handle generate shopping list call to the REST API.
	 *
	 * @since 6.3.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_shopping_list_generate( $request ) {
		// Parameters.
		$params = $request->get_params();
		$type = isset( $params['type'] ) ? $params['type'] : '';
		$collection = isset( $params['collection'] ) ? $params['collection'] : false;
		$options = isset( $params['options'] ) ? $params['options'] : false;

		return rest_ensure_response( WPRMPRC_Shopping_List::generate( $type, $collection, $options ) );
	}

	/**
	 * Handle shop shopping list call to the REST API.
	 *
	 * @since 9.8.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_shop_shopping_list( $request ) {
		// Parameters.
		$params = $request->get_params();
		$uid = isset( $params['uid'] ) ? $params['uid'] : '';
		$integration = isset( $params['integration'] ) ? sanitize_key( $params['integration'] ) : false;

		if ( $uid && $integration ) {
			$shopping_list = WPRMPRC_Shopping_List::get( $uid );

			if ( $shopping_list ) {
				$shopping_list['uid'] = $uid;
				$link = false;

				switch( $integration ) {
					case 'instacart':
						$link = WPRM_Instacart::get_link_for_list( $shopping_list );
						break;
				}

				return rest_ensure_response( $link );
			}
		}

		return rest_ensure_response( false );
	}
}

WPRMPRC_Api::init();
