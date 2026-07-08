<?php
/**
 * Handle the AI Assistant API for premium features.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the AI Assistant API for premium features.
 *
 * @since      10.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_AI_Assistant {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.5.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    10.5.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) {
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/generate-ideas', array(
				'callback' => array( __CLASS__, 'api_generate_ideas' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches', array(
				'callback' => array( __CLASS__, 'api_start_nutrition_review_batch' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/current', array(
				'callback' => array( __CLASS__, 'api_get_current_nutrition_review_batch' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/(?P<id>[\w-]+)', array(
				'callback' => array( __CLASS__, 'api_get_nutrition_review_batch' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/(?P<id>[\w-]+)/results', array(
				'callback' => array( __CLASS__, 'api_get_nutrition_review_results' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/(?P<id>[\w-]+)/recipe/(?P<recipe_id>\d+)/ingredient/(?P<ingredient_index>\d+)', array(
				'callback' => array( __CLASS__, 'api_save_nutrition_review_decision' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/(?P<id>[\w-]+)/recipe/(?P<recipe_id>\d+)/apply', array(
				'callback' => array( __CLASS__, 'api_apply_nutrition_review_recipe' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/nutrition-review/batches/(?P<id>[\w-]+)/apply-ready', array(
				'callback' => array( __CLASS__, 'api_apply_nutrition_review_ready' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches', array(
				'callback' => array( __CLASS__, 'api_start_unit_conversion_review_batch' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/current', array(
				'callback' => array( __CLASS__, 'api_get_current_unit_conversion_review_batch' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/(?P<id>[\w-]+)', array(
				'callback' => array( __CLASS__, 'api_get_unit_conversion_review_batch' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/(?P<id>[\w-]+)/results', array(
				'callback' => array( __CLASS__, 'api_get_unit_conversion_review_results' ),
				'methods' => 'GET',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/(?P<id>[\w-]+)/recipe/(?P<recipe_id>\d+)/ingredient/(?P<ingredient_index>\d+)', array(
				'callback' => array( __CLASS__, 'api_save_unit_conversion_review_decision' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/(?P<id>[\w-]+)/recipe/(?P<recipe_id>\d+)/apply', array(
				'callback' => array( __CLASS__, 'api_apply_unit_conversion_review_recipe' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
			register_rest_route( 'wp-recipe-maker/v1', '/ai-assistant/unit-conversion-review/batches/(?P<id>[\w-]+)/apply-ready', array(
				'callback' => array( __CLASS__, 'api_apply_unit_conversion_review_ready' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since    10.5.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check if Elite AI features are available.
	 *
	 * @since    10.5.0
	 *
	 * @return   boolean
	 */
	private static function has_elite_ai_access() {
		return class_exists( 'WPRM_Addons' ) && WPRM_Addons::is_active( 'elite' );
	}

	/**
	 * Require Elite access for Elite-only AI tools.
	 *
	 * @since    10.5.0
	 *
	 * @return   true|WP_Error
	 */
	private static function maybe_require_elite_ai_access() {
		if ( self::has_elite_ai_access() ) {
			return true;
		}

		return new WP_Error( 'wprm_ai_elite_required', __( 'This AI Assistant feature is only available in the Elite Bundle.', 'wp-recipe-maker-premium' ), array( 'status' => 403 ) );
	}

	/**
	 * Ensure current user can edit a specific recipe.
	 *
	 * @since    10.5.1
	 * @param    int $recipe_id Recipe ID.
	 *
	 * @return   true|WP_Error
	 */
	private static function maybe_require_recipe_edit_access( $recipe_id ) {
		$recipe_id = intval( $recipe_id );
		if ( $recipe_id && current_user_can( 'edit_post', $recipe_id ) ) {
			return true;
		}

		return new WP_Error( 'forbidden_recipe_edit', __( 'You do not have permission to edit this recipe.', 'wp-recipe-maker-premium' ), array( 'status' => 403 ) );
	}

	/**
	 * Handle AI generate ideas call to the REST API.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_generate_ideas( $request ) {
		$params = $request->get_params();

		$type    = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'recipe';
		$context = isset( $params['context'] ) ? sanitize_text_field( $params['context'] ) : 'popular';
		$prompt  = isset( $params['prompt'] ) ? sanitize_textarea_field( $params['prompt'] ) : '';
		$count   = isset( $params['count'] ) ? max( 1, min( 20, intval( $params['count'] ) ) ) : 15;

		// For list ideas, always use the full recipe catalog since lists are built from existing recipes.
		// For recipe ideas, use the selected context strategy.
		if ( 'list' === $type ) {
			$recipes = self::get_all_recipes_summary();
			$context = 'all';
		} else {
			$recipes = self::get_context_recipes( $context );
		}

		// Always include all recipe names for duplicate checking.
		$all_recipe_names = self::get_all_recipe_names();

		// For list ideas, include existing lists as inspiration and for duplicate avoidance.
		$existing_lists = array();
		if ( 'list' === $type ) {
			$existing_lists = self::get_all_existing_lists();
		}

		$data = array(
			'type'                => $type,
			'context'             => $context,
			'prompt'              => $prompt,
			'count'               => $count,
			'recipes'             => $recipes,
			'all_recipe_names'    => $all_recipe_names,
			'existing_lists'      => $existing_lists,
			'site_name'           => get_bloginfo( 'name' ),
			'site_description'    => get_bloginfo( 'description' ),
		);

		$response = WPRMP_Proxy::call( 'ai_generate_ideas', $data );

		if ( false === $response ) {
			return new WP_Error( 'proxy_error', __( 'Failed to generate ideas. Please try again.', 'wp-recipe-maker-premium' ), array( 'status' => 500 ) );
		}

		// Include the context recipe names so the frontend can show what data was used.
		if ( 'all' !== $context ) {
			$context_recipe_names = array_map( function( $recipe ) {
				return $recipe['name'];
			}, $recipes );
			$response['context_recipes'] = $context_recipe_names;
		}

		// For list ideas, resolve existing_recipes names to {id, name} objects.
		if ( 'list' === $type && ! empty( $response['ideas'] ) ) {
			foreach ( $response['ideas'] as &$idea ) {
				if ( ! empty( $idea['existing_recipes'] ) && is_array( $idea['existing_recipes'] ) ) {
					$idea['existing_recipes'] = self::resolve_recipe_names_to_ids( $idea['existing_recipes'] );
				}
			}
			unset( $idea );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Start a new nutrition review batch.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_start_nutrition_review_batch( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$params = $request->get_params();
		$scope = isset( $params['scope'] ) ? sanitize_key( $params['scope'] ) : 'missing_only';
		$recipe_ids = isset( $params['recipe_ids'] ) && is_array( $params['recipe_ids'] ) ? array_map( 'intval', $params['recipe_ids'] ) : array();
		$force_review = ! empty( $params['force_review'] );

		$result = WPRMP_AI_Nutrition_Review::start_batch(
			array(
				'scope' => $scope,
				'recipe_ids' => $recipe_ids,
				'force_review' => $force_review,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get the current nutrition review batch.
	 *
	 * @since    10.5.0
	 *
	 * @return   WP_REST_Response
	 */
	public static function api_get_current_nutrition_review_batch() {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		return rest_ensure_response(
			array(
				'batch' => WPRMP_AI_Nutrition_Review::prepare_batch_for_response( WPRMP_AI_Nutrition_Review::get_current_batch() ),
			)
		);
	}

	/**
	 * Get a nutrition review batch.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_get_nutrition_review_batch( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		WPRMP_AI_Nutrition_Review::maybe_process_batch_from_poll( $request['id'] );

		$batch = WPRMP_AI_Nutrition_Review::prepare_batch_for_response( WPRMP_AI_Nutrition_Review::get_batch( $request['id'] ) );

		if ( ! $batch ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that nutrition review batch.', 'wp-recipe-maker-premium' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'batch' => $batch,
			)
		);
	}

	/**
	 * Get filtered nutrition review results.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response
	 */
	public static function api_get_nutrition_review_results( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$status = isset( $request['status'] ) ? sanitize_key( $request['status'] ) : '';

		return rest_ensure_response(
			array(
				'results' => WPRMP_AI_Nutrition_Review::get_batch_results( $request['id'], $status ),
			)
		);
	}

	/**
	 * Save a nutrition review decision.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_save_nutrition_review_decision( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$params = $request->get_params();
		$decision = array(
			'candidateId' => isset( $params['candidateId'] ) ? intval( $params['candidateId'] ) : 0,
			'candidate' => isset( $params['candidate'] ) && is_array( $params['candidate'] ) ? $params['candidate'] : false,
			'amount' => isset( $params['amount'] ) ? sanitize_text_field( $params['amount'] ) : '',
			'unit' => isset( $params['unit'] ) ? sanitize_text_field( $params['unit'] ) : '',
			'exclude' => ! empty( $params['exclude'] ),
		);

		$result = WPRMP_AI_Nutrition_Review::save_review_decision(
			$request['id'],
			intval( $request['recipe_id'] ),
			intval( $request['ingredient_index'] ),
			$decision
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'result' => $result,
			)
		);
	}

	/**
	 * Apply proposed nutrition to one recipe.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_apply_nutrition_review_recipe( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$result = WPRMP_AI_Nutrition_Review::apply_recipe( $request['id'], intval( $request['recipe_id'] ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'result' => $result,
			)
		);
	}

	/**
	 * Apply all ready recipes in a nutrition review batch.
	 *
	 * @since    10.5.0
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_apply_nutrition_review_ready( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$result = WPRMP_AI_Nutrition_Review::apply_ready_recipes( $request['id'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Start a new unit conversion review batch.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_start_unit_conversion_review_batch( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$params = $request->get_params();
		$scope = isset( $params['scope'] ) ? sanitize_key( $params['scope'] ) : 'missing_only';
		$recipe_ids = isset( $params['recipe_ids'] ) && is_array( $params['recipe_ids'] ) ? array_map( 'intval', $params['recipe_ids'] ) : array();

		$result = WPRMP_AI_Unit_Conversion_Review::start_batch(
			array(
				'scope' => $scope,
				'recipe_ids' => $recipe_ids,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get the current unit conversion review batch.
	 *
	 * @since    10.5.1
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_get_current_unit_conversion_review_batch() {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		return rest_ensure_response(
			array(
				'batch' => WPRMP_AI_Unit_Conversion_Review::prepare_batch_for_response( WPRMP_AI_Unit_Conversion_Review::get_current_batch() ),
			)
		);
	}

	/**
	 * Get a unit conversion review batch.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_get_unit_conversion_review_batch( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		WPRMP_AI_Unit_Conversion_Review::maybe_process_batch_from_poll( $request['id'] );

		$batch = WPRMP_AI_Unit_Conversion_Review::prepare_batch_for_response( WPRMP_AI_Unit_Conversion_Review::get_batch( $request['id'] ) );

		if ( ! $batch ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that unit conversion review batch.', 'wp-recipe-maker-premium' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'batch' => $batch,
			)
		);
	}

	/**
	 * Get filtered unit conversion review results.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_get_unit_conversion_review_results( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$status = isset( $request['status'] ) ? sanitize_key( $request['status'] ) : '';

		return rest_ensure_response(
			array(
				'results' => WPRMP_AI_Unit_Conversion_Review::get_batch_results( $request['id'], $status ),
			)
		);
	}

	/**
	 * Save a unit conversion review decision.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_save_unit_conversion_review_decision( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}
		$recipe_access = self::maybe_require_recipe_edit_access( intval( $request['recipe_id'] ) );
		if ( is_wp_error( $recipe_access ) ) {
			return $recipe_access;
		}

		$params = $request->get_params();
		$decision = array(
			'decision' => isset( $params['decision'] ) ? sanitize_key( $params['decision'] ) : 'proposed',
			'amount' => isset( $params['amount'] ) ? sanitize_text_field( $params['amount'] ) : '',
			'unit' => isset( $params['unit'] ) ? sanitize_text_field( $params['unit'] ) : '',
		);

		$result = WPRMP_AI_Unit_Conversion_Review::save_review_decision(
			$request['id'],
			intval( $request['recipe_id'] ),
			intval( $request['ingredient_index'] ),
			$decision
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'result' => $result,
			)
		);
	}

	/**
	 * Apply proposed unit conversions to one recipe.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_apply_unit_conversion_review_recipe( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}
		$recipe_access = self::maybe_require_recipe_edit_access( intval( $request['recipe_id'] ) );
		if ( is_wp_error( $recipe_access ) ) {
			return $recipe_access;
		}

		$result = WPRMP_AI_Unit_Conversion_Review::apply_recipe( $request['id'], intval( $request['recipe_id'] ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'result' => $result,
			)
		);
	}

	/**
	 * Apply all ready recipes in a unit conversion review batch.
	 *
	 * @since    10.5.1
	 * @param    WP_REST_Request $request Current request.
	 *
	 * @return   WP_REST_Response|WP_Error
	 */
	public static function api_apply_unit_conversion_review_ready( $request ) {
		$elite_access = self::maybe_require_elite_ai_access();
		if ( is_wp_error( $elite_access ) ) {
			return $elite_access;
		}

		$result = WPRMP_AI_Unit_Conversion_Review::apply_ready_recipes( $request['id'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Get recipe data for the AI context based on the selected strategy.
	 *
	 * @since    10.5.0
	 * @param    string $context Context strategy: popular, highest_rated, or recent.
	 * @return   array Array of recipe data for AI context.
	 */
	private static function get_context_recipes( $context ) {
		// For "all" context, send a condensed catalog so the AI can find gaps.
		if ( 'all' === $context ) {
			return self::get_all_recipes_summary();
		}

		$args = array(
			'post_type'      => WPRM_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 15,
		);

		switch ( $context ) {
			case 'highest_rated':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'wprm_rating_average';
				$args['order']    = 'DESC';
				break;

			case 'recent':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;

			case 'popular':
			default:
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'wprm_rating_count';
				$args['order']    = 'DESC';
				break;
		}

		$query = new WP_Query( $args );
		$recipes = array();

		foreach ( $query->posts as $post ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $post );

			if ( ! $recipe ) {
				continue;
			}

			$rating = WPRM_Rating::get_ratings_summary_for( $recipe->id() );

			$recipes[] = self::get_recipe_context( $recipe, $rating );
		}

		return $recipes;
	}

	/**
	 * Get all existing lists with their names and recipe items.
	 * Used as inspiration and for duplicate avoidance.
	 *
	 * @since    10.5.0
	 * @return   array Array of list data with name and recipe names.
	 */
	private static function get_all_existing_lists() {
		$args = array(
			'post_type'      => WPRM_LIST_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		$lists = array();

		foreach ( $query->posts as $post_id ) {
			$list = WPRM_List_Manager::get_list( $post_id );

			if ( ! $list ) {
				continue;
			}

			$recipe_names = array();
			$items = $list->items();

			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( isset( $item['type'] ) && 'roundup' === $item['type'] && ! empty( $item['data'] ) ) {
						// Internal items have an ID we can look up.
						if ( ! empty( $item['data']['id'] ) ) {
							$recipe = WPRM_Recipe_Manager::get_recipe( intval( $item['data']['id'] ) );
							if ( $recipe ) {
								$recipe_names[] = $recipe->name();
								continue;
							}
						}

						// Fall back to the item name.
						if ( ! empty( $item['data']['name'] ) ) {
							$recipe_names[] = $item['data']['name'];
						}
					}
				}
			}

			$lists[] = array(
				'name'    => $list->name(),
				'recipes' => $recipe_names,
			);
		}

		return $lists;
	}

	/**
	 * Get all recipe names for duplicate checking.
	 *
	 * @since    10.5.0
	 * @return   array Array of recipe name strings.
	 */
	private static function get_all_recipe_names() {
		$map = self::get_recipe_name_to_id_map();
		return array_keys( $map );
	}

	/**
	 * Get a map of recipe names (lowercase) to their IDs.
	 * Used for duplicate checking and resolving AI-returned names to IDs.
	 *
	 * @since    10.5.0
	 * @return   array Associative array of lowercase recipe name => recipe ID.
	 */
	private static function get_recipe_name_to_id_map() {
		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$args = array(
			'post_type'      => WPRM_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		$map = array();

		foreach ( $query->posts as $post_id ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $post_id );

			if ( $recipe ) {
				$name = $recipe->name();
				// Store with original-case key for get_all_recipe_names().
				// Use lowercase key for lookups, but keep first match if duplicates.
				$map[ $name ] = $recipe->id();
			}
		}

		return $map;
	}

	/**
	 * Resolve an array of recipe names to {id, name} objects.
	 *
	 * @since    10.5.0
	 * @param    array $names Array of recipe name strings.
	 * @return   array Array of {id, name} objects for matched recipes.
	 */
	private static function resolve_recipe_names_to_ids( $names ) {
		if ( ! is_array( $names ) || empty( $names ) ) {
			return array();
		}

		$map = self::get_recipe_name_to_id_map();

		// Build a lowercase lookup for case-insensitive matching.
		$lowercase_map = array();
		foreach ( $map as $name => $id ) {
			$lower = strtolower( $name );
			if ( ! isset( $lowercase_map[ $lower ] ) ) {
				$lowercase_map[ $lower ] = array(
					'id'   => $id,
					'name' => $name,
				);
			}
		}

		$resolved = array();
		foreach ( $names as $name ) {
			$lower = strtolower( trim( $name ) );
			if ( isset( $lowercase_map[ $lower ] ) ) {
				$resolved[] = $lowercase_map[ $lower ];
			}
		}

		return $resolved;
	}

	/**
	 * Get a condensed summary of all recipes for gap analysis.
	 * Sends only name and tags to keep the payload manageable.
	 *
	 * @since    10.5.0
	 * @return   array Array of condensed recipe data.
	 */
	private static function get_all_recipes_summary() {
		$args = array(
			'post_type'      => WPRM_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		$recipes = array();

		foreach ( $query->posts as $post ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $post );

			if ( ! $recipe ) {
				continue;
			}

			$tags = array();
			$taxonomies = WPRM_Taxonomies::get_taxonomies();
			foreach ( $taxonomies as $taxonomy => $tax_options ) {
				$key = substr( $taxonomy, 5 );
				$tag_names = $recipe->tags( $key, true );

				if ( ! empty( $tag_names ) ) {
					$tags[ $key ] = $tag_names;
				}
			}

			$recipes[] = array(
				'name' => $recipe->name(),
				'tags' => $tags,
			);
		}

		return $recipes;
	}

	/**
	 * Build a concise recipe data array for AI context.
	 *
	 * @since    10.5.0
	 * @param    WPRM_Recipe $recipe Recipe object.
	 * @param    array       $rating Rating summary.
	 * @return   array
	 */
	private static function get_recipe_context( $recipe, $rating ) {
		$tags = array();
		$taxonomies = WPRM_Taxonomies::get_taxonomies();
		foreach ( $taxonomies as $taxonomy => $tax_options ) {
			$key = substr( $taxonomy, 5 );
			$tag_names = $recipe->tags( $key, true );

			if ( ! empty( $tag_names ) ) {
				$tags[ $key ] = $tag_names;
			}
		}

		return array(
			'name'            => $recipe->name(),
			'summary'         => wp_strip_all_tags( $recipe->summary() ),
			'tags'            => $tags,
			'ingredients_flat'=> $recipe->ingredients_flat(),
			'servings'        => $recipe->servings(),
			'prep_time'       => $recipe->prep_time(),
			'cook_time'       => $recipe->cook_time(),
			'total_time'      => $recipe->total_time(),
			'rating_average'  => isset( $rating['average'] ) ? $rating['average'] : 0,
			'rating_count'    => isset( $rating['count'] ) ? $rating['count'] : 0,
		);
	}
}

WPRMP_Api_AI_Assistant::init();
