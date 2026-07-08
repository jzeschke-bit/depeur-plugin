<?php
/**
 * AI-assisted unit conversion review batches.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.5.1
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * AI-assisted unit conversion review batches.
 *
 * @since      10.5.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_AI_Unit_Conversion_Review {
	/**
	 * Current batch option.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	const OPTION_CURRENT_BATCH = 'wprmp_ai_unit_conversion_review_current_batch';

	/**
	 * Meta key for stored review status.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	const RECIPE_META_STATUS = 'wprmp_ai_unit_conversion_review_status';

	/**
	 * Meta key for stored review timestamp.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	const RECIPE_META_REVIEWED_AT = 'wprmp_ai_unit_conversion_reviewed_at';

	/**
	 * Transient prefix for synchronous poll locks.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	const POLL_LOCK_PREFIX = 'wprmp_ai_unit_conversion_review_poll_lock_';

	/**
	 * Batch processor.
	 *
	 * @since    10.5.1
	 * @var      WPRMP_AI_Unit_Conversion_Review_Background_Process
	 */
	private static $background_process;

	/**
	 * Status order for batch result grouping.
	 *
	 * @since    10.5.1
	 * @var      array
	 */
	private static $status_order = array(
		'needs_review',
		'ready_to_apply',
		'no_change',
		'already_handled',
		'error',
	);

	/**
	 * Register actions and filters.
	 *
	 * @since    10.5.1
	 */
	public static function init() {
		self::get_background_process();
	}

	/**
	 * Reset background processor instance.
	 *
	 * @since    10.5.1
	 */
	public static function reset_batch_processor() {
		self::$background_process = null;
	}

	/**
	 * Get batch page URL.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Optional batch ID.
	 *
	 * @return   string
	 */
	public static function get_batch_url( $batch_id = '' ) {
		$url = admin_url( 'admin.php?page=wprm_ai_assistant&tool=unit_conversion_review' );

		if ( $batch_id ) {
			$url = add_query_arg( 'batch', $batch_id, $url );
		}

		return $url;
	}

	/**
	 * Get batch option name.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Batch ID.
	 *
	 * @return   string
	 */
	public static function get_batch_option_name( $batch_id ) {
		return 'wprmp_ai_unit_conversion_review_batch_' . sanitize_key( $batch_id );
	}

	/**
	 * Get background processor.
	 *
	 * @since    10.5.1
	 *
	 * @return   WPRMP_AI_Unit_Conversion_Review_Background_Process
	 */
	private static function get_background_process() {
		if ( ! self::$background_process ) {
			self::$background_process = new WPRMP_AI_Unit_Conversion_Review_Background_Process();
		}

		return self::$background_process;
	}

	/**
	 * Start a new review batch.
	 *
	 * @since    10.5.1
	 * @param    array $args Batch arguments.
	 *
	 * @return   array|WP_Error
	 */
	public static function start_batch( $args = array() ) {
		$scope = isset( $args['scope'] ) ? sanitize_key( $args['scope'] ) : 'missing_only';
		$recipe_ids = isset( $args['recipe_ids'] ) && is_array( $args['recipe_ids'] ) ? array_values( array_filter( array_map( 'intval', $args['recipe_ids'] ) ) ) : array();

		$recipe_ids = self::resolve_recipe_ids_for_scope( $scope, $recipe_ids );

		if ( empty( $recipe_ids ) ) {
			return new WP_Error( 'no_recipes', __( 'No recipes found for this unit conversion review run.', 'wp-recipe-maker-premium' ) );
		}

		$batch_id = wp_generate_uuid4();
		$batch = array(
			'id' => $batch_id,
			'created_at' => time(),
			'updated_at' => time(),
			'scope' => $scope,
			'status' => 'queued',
			'total' => count( $recipe_ids ),
			'processed' => 0,
			'counts' => self::empty_counts(),
			'recipe_ids' => $recipe_ids,
			'results' => array(),
		);

		self::save_batch( $batch );
		update_option( self::OPTION_CURRENT_BATCH, $batch_id, false );

		$processor = self::get_background_process();
		foreach ( $recipe_ids as $recipe_id ) {
			$processor->push_to_queue(
				array(
					'batch_id' => $batch_id,
					'recipe_id' => $recipe_id,
				)
			);
		}
		$processor->save()->dispatch();

		return array(
			'batch_id' => $batch_id,
			'url' => self::get_batch_url( $batch_id ),
			'batch' => self::prepare_batch_for_response( self::get_batch( $batch_id ) ),
		);
	}

	/**
	 * Get current batch.
	 *
	 * @since    10.5.1
	 *
	 * @return   array|false
	 */
	public static function get_current_batch() {
		$current_batch_id = get_option( self::OPTION_CURRENT_BATCH, false );
		if ( ! $current_batch_id ) {
			return false;
		}

		self::maybe_process_batch_from_poll( $current_batch_id );

		return self::get_batch( $current_batch_id );
	}

	/**
	 * Get a stored batch.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Batch ID.
	 *
	 * @return   array|false
	 */
	public static function get_batch( $batch_id ) {
		if ( ! $batch_id ) {
			return false;
		}

		$batch = get_option( self::get_batch_option_name( $batch_id ), false );

		return is_array( $batch ) ? $batch : false;
	}

	/**
	 * Save batch.
	 *
	 * @since    10.5.1
	 * @param    array $batch Batch data.
	 *
	 * @return   bool
	 */
	public static function save_batch( $batch ) {
		if ( ! isset( $batch['id'] ) ) {
			return false;
		}

		$batch['updated_at'] = time();

		return update_option( self::get_batch_option_name( $batch['id'] ), $batch, false );
	}

	/**
	 * Prepare batch data for REST responses.
	 *
	 * @since    10.5.1
	 * @param    array|false $batch Batch data.
	 *
	 * @return   array|false
	 */
	public static function prepare_batch_for_response( $batch ) {
		if ( ! $batch ) {
			return false;
		}

		$prepared_results = array();
		if ( isset( $batch['results'] ) && is_array( $batch['results'] ) ) {
			foreach ( $batch['results'] as $result ) {
				$prepared_results[] = $result;
			}

			usort(
				$prepared_results,
				function( $a, $b ) {
					$status_a = isset( $a['status'] ) ? array_search( $a['status'], self::$status_order, true ) : 999;
					$status_b = isset( $b['status'] ) ? array_search( $b['status'], self::$status_order, true ) : 999;

					if ( false === $status_a ) {
						$status_a = 999;
					}
					if ( false === $status_b ) {
						$status_b = 999;
					}

					if ( $status_a === $status_b ) {
						return strcmp(
							isset( $a['recipe']['name'] ) ? $a['recipe']['name'] : '',
							isset( $b['recipe']['name'] ) ? $b['recipe']['name'] : ''
						);
					}

					return $status_a - $status_b;
				}
			);
		}

		$batch['results'] = $prepared_results;
		$batch['url'] = self::get_batch_url( $batch['id'] );

		return $batch;
	}

	/**
	 * Get filtered batch results.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Batch ID.
	 * @param    string $status   Optional status filter.
	 *
	 * @return   array
	 */
	public static function get_batch_results( $batch_id, $status = '' ) {
		self::maybe_process_batch_from_poll( $batch_id );

		$batch = self::prepare_batch_for_response( self::get_batch( $batch_id ) );
		if ( ! $batch ) {
			return array();
		}

		$results = isset( $batch['results'] ) ? $batch['results'] : array();

		if ( $status ) {
			$results = array_values(
				array_filter(
					$results,
					function( $result ) use ( $status ) {
						return isset( $result['status'] ) && $status === $result['status'];
					}
				)
			);
		}

		return $results;
	}

	/**
	 * Process a queued recipe item.
	 *
	 * @since    10.5.1
	 * @param    array $item Queue item.
	 */
	public static function process_batch_item( $item ) {
		require_once( WPRMPUC_DIR . 'includes/admin/class-wprmpuc-conversion-api.php' );

		$batch_id = isset( $item['batch_id'] ) ? sanitize_key( $item['batch_id'] ) : '';
		$recipe_id = isset( $item['recipe_id'] ) ? intval( $item['recipe_id'] ) : 0;

		if ( ! $batch_id || ! $recipe_id ) {
			return;
		}

		$batch = self::get_batch( $batch_id );
		if ( ! $batch ) {
			return;
		}

		$batch['status'] = 'processing';
		self::save_batch( $batch );

		try {
			$result = self::review_recipe( $recipe_id );
		} catch ( Throwable $throwable ) {
			$result = array(
				'recipe' => array(
					'id' => $recipe_id,
					'name' => sprintf( __( 'Recipe %d', 'wp-recipe-maker-premium' ), $recipe_id ),
				),
				'status' => 'error',
				'errors' => array( $throwable->getMessage() ),
			);
		}

		if ( ! $result ) {
			$result = array(
				'recipe' => array(
					'id' => $recipe_id,
					'name' => sprintf( __( 'Recipe %d', 'wp-recipe-maker-premium' ), $recipe_id ),
				),
				'status' => 'error',
				'errors' => array( __( 'Recipe review failed unexpectedly.', 'wp-recipe-maker-premium' ) ),
			);
		}

		$batch = self::get_batch( $batch_id );
		if ( ! $batch ) {
			return;
		}

		$batch['results'][ $recipe_id ] = $result;
		$batch['processed'] = min( intval( $batch['total'] ), intval( $batch['processed'] ) + 1 );
		$batch['counts'] = self::calculate_counts( $batch['results'] );
		$batch['status'] = intval( $batch['processed'] ) >= intval( $batch['total'] ) ? 'completed' : 'processing';
		self::save_batch( $batch );

		update_post_meta( $recipe_id, self::RECIPE_META_STATUS, $result['status'] );
		update_post_meta( $recipe_id, self::RECIPE_META_REVIEWED_AT, time() );
	}

	/**
	 * Review a single recipe.
	 *
	 * @since    10.5.1
	 * @param    int $recipe_id Recipe ID.
	 *
	 * @return   array|false
	 */
	public static function review_recipe( $recipe_id ) {
		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe ) {
			return false;
		}

		$recipe_context = self::get_recipe_context( $recipe );
		if ( empty( $recipe_context['ingredients'] ) ) {
			return array(
				'recipe' => array(
					'id' => $recipe->id(),
					'name' => $recipe->name(),
					'original_system' => $recipe_context['original_system'],
					'target_system' => $recipe_context['target_system'],
				),
				'status' => 'no_change',
				'ingredients' => array(),
				'counts' => self::empty_ingredient_counts(),
				'proposed_changes' => 0,
				'errors' => array(),
			);
		}

		$ingredient_reviews = array();
		$items_for_ai = array();

		foreach ( $recipe_context['ingredients'] as $index => $ingredient ) {
			$review = self::build_ingredient_review( $ingredient, $recipe_context, $index );
			$ingredient_reviews[] = $review;

			if ( ! empty( $review['needs_ai_review'] ) ) {
				$items_for_ai[] = self::get_ai_ingredient_item( $recipe_context, $review );
			}
		}

		if ( ! empty( $items_for_ai ) ) {
			$ingredient_reviews = self::apply_ai_results( $ingredient_reviews, self::call_ai_review( $items_for_ai ) );
		}

		$result = self::build_recipe_result( $recipe_context, $ingredient_reviews );
		$result['recipe']['id'] = $recipe->id();
		$result['recipe']['name'] = $recipe->name();

		return $result;
	}

	/**
	 * Save a manual review decision for one ingredient and recalculate the recipe result.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id          Batch ID.
	 * @param    int    $recipe_id         Recipe ID.
	 * @param    int    $ingredient_index  Ingredient index.
	 * @param    array  $decision          Review decision.
	 *
	 * @return   array|WP_Error
	 */
	public static function save_review_decision( $batch_id, $recipe_id, $ingredient_index, $decision ) {
		if ( ! current_user_can( 'edit_post', $recipe_id ) ) {
			return new WP_Error( 'forbidden_recipe_edit', __( 'You do not have permission to edit this recipe.', 'wp-recipe-maker-premium' ), array( 'status' => 403 ) );
		}

		$batch = self::get_batch( $batch_id );
		if ( ! $batch || ! isset( $batch['results'][ $recipe_id ] ) ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that unit conversion review batch.', 'wp-recipe-maker-premium' ) );
		}

		$result = $batch['results'][ $recipe_id ];
		if ( ! isset( $result['ingredients'][ $ingredient_index ] ) ) {
			return new WP_Error( 'invalid_ingredient', __( 'Could not find that ingredient review.', 'wp-recipe-maker-premium' ) );
		}

		$ingredient = $result['ingredients'][ $ingredient_index ];
		$decision_source = isset( $decision['decision'] ) ? sanitize_key( $decision['decision'] ) : 'proposed';
		$amount = isset( $decision['amount'] ) ? self::sanitize_resolved_amount( $decision['amount'], '' ) : $ingredient['resolved_amount'];
		$unit = isset( $decision['unit'] ) ? sanitize_text_field( $decision['unit'] ) : $ingredient['resolved_unit'];

		if ( 'existing' === $decision_source ) {
			if ( empty( $ingredient['existing_conversion']['has_conversion'] ) ) {
				return new WP_Error( 'missing_existing_conversion', __( 'There is no existing conversion to keep for this ingredient.', 'wp-recipe-maker-premium' ) );
			}

			$ingredient['resolved_amount'] = $ingredient['existing_conversion']['amount'];
			$ingredient['resolved_unit'] = $ingredient['existing_conversion']['unit'];
			$ingredient['decision_source'] = 'existing';
			$ingredient['requires_confirmation'] = false;
			$ingredient['status'] = 'approved';
		} elseif ( 'skip' === $decision_source ) {
			$ingredient['resolved_amount'] = '';
			$ingredient['resolved_unit'] = '';
			$ingredient['decision_source'] = 'skip';
			$ingredient['requires_confirmation'] = false;
			$ingredient['status'] = empty( $ingredient['existing_conversion']['has_conversion'] ) ? 'skipped' : 'approved';
		} else {
			$ingredient['resolved_amount'] = $amount;
			$ingredient['resolved_unit'] = $unit;
			$ingredient['decision_source'] = 'proposed';
			$ingredient['requires_confirmation'] = false;
			$ingredient['status'] = ( '' !== $unit || '' !== $amount ) ? 'approved' : 'needs_review';
		}

		$ingredient['manual_override'] = true;
		if ( ! isset( $ingredient['ai_review'] ) || ! is_array( $ingredient['ai_review'] ) ) {
			$ingredient['ai_review'] = array();
		}
		$ingredient['ai_review']['reason'] = __( 'Manually reviewed.', 'wp-recipe-maker-premium' );

		$result['ingredients'][ $ingredient_index ] = $ingredient;
		$result = self::recalculate_stored_recipe_result( $result );

		$batch['results'][ $recipe_id ] = $result;
		$batch['counts'] = self::calculate_counts( $batch['results'] );
		self::save_batch( $batch );

		return $result;
	}

	/**
	 * Apply proposed unit conversions to a recipe.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id  Batch ID.
	 * @param    int    $recipe_id Recipe ID.
	 *
	 * @return   array|WP_Error
	 */
	public static function apply_recipe( $batch_id, $recipe_id ) {
		if ( ! current_user_can( 'edit_post', $recipe_id ) ) {
			return new WP_Error( 'forbidden_recipe_edit', __( 'You do not have permission to edit this recipe.', 'wp-recipe-maker-premium' ), array( 'status' => 403 ) );
		}

		$batch = self::get_batch( $batch_id );
		if ( ! $batch || ! isset( $batch['results'][ $recipe_id ] ) ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that unit conversion review batch.', 'wp-recipe-maker-premium' ) );
		}

		$result = $batch['results'][ $recipe_id ];
		if ( empty( $result['ingredients'] ) ) {
			return new WP_Error( 'no_ingredients', __( 'No reviewed ingredient conversions are available for this recipe.', 'wp-recipe-maker-premium' ) );
		}

		if ( ! in_array( $result['status'], array( 'ready_to_apply', 'no_change' ), true ) ) {
			return new WP_Error( 'recipe_not_ready', __( 'This recipe still needs review before it can be applied.', 'wp-recipe-maker-premium' ) );
		}

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe ) {
			return new WP_Error( 'missing_recipe', __( 'Could not load that recipe.', 'wp-recipe-maker-premium' ) );
		}

		$ingredients = $recipe->ingredients();
		$stale_review_entries = array();

		foreach ( $result['ingredients'] as $ingredient_result ) {
			$group_index = isset( $ingredient_result['original']['group_index'] ) ? intval( $ingredient_result['original']['group_index'] ) : -1;
			$ingredient_index = isset( $ingredient_result['original']['ingredient_index'] ) ? intval( $ingredient_result['original']['ingredient_index'] ) : -1;

			if (
				$group_index < 0
				|| $ingredient_index < 0
				|| ! isset( $ingredients[ $group_index ]['ingredients'][ $ingredient_index ] )
			) {
				$stale_review_entries[] = isset( $ingredient_result['index'] ) ? intval( $ingredient_result['index'] ) : false;
				continue;
			}

			if ( ! self::ingredient_matches_reviewed_original( $ingredients[ $group_index ]['ingredients'][ $ingredient_index ], $ingredient_result['original'] ) ) {
				$stale_review_entries[] = isset( $ingredient_result['index'] ) ? intval( $ingredient_result['index'] ) : false;
			}
		}

		if ( ! empty( $stale_review_entries ) ) {
			foreach ( $result['ingredients'] as $index => $ingredient_result ) {
				if ( in_array( isset( $ingredient_result['index'] ) ? intval( $ingredient_result['index'] ) : -1, $stale_review_entries, true ) ) {
					$result['ingredients'][ $index ]['status'] = 'needs_review';
					$result['ingredients'][ $index ]['requires_confirmation'] = true;
					$result['ingredients'][ $index ]['manual_override'] = false;
					if ( ! isset( $result['ingredients'][ $index ]['ai_review'] ) || ! is_array( $result['ingredients'][ $index ]['ai_review'] ) ) {
						$result['ingredients'][ $index ]['ai_review'] = array();
					}
					$result['ingredients'][ $index ]['ai_review']['reason'] = __( 'Recipe ingredients changed after this review. Run this recipe through review again before applying.', 'wp-recipe-maker-premium' );
				}
			}

			$result['errors'] = isset( $result['errors'] ) && is_array( $result['errors'] ) ? $result['errors'] : array();
			$result['errors'][] = __( 'Recipe ingredients changed after this review. Run this recipe through review again before applying.', 'wp-recipe-maker-premium' );
			$result = self::recalculate_stored_recipe_result( $result );

			$batch['results'][ $recipe_id ] = $result;
			$batch['counts'] = self::calculate_counts( $batch['results'] );
			self::save_batch( $batch );

			return new WP_Error( 'stale_recipe_review', __( 'Recipe ingredients changed after this review. Run this recipe through review again before applying.', 'wp-recipe-maker-premium' ), array( 'status' => 409 ) );
		}

		foreach ( $result['ingredients'] as $ingredient_result ) {
			$group_index = isset( $ingredient_result['original']['group_index'] ) ? intval( $ingredient_result['original']['group_index'] ) : -1;
			$ingredient_index = isset( $ingredient_result['original']['ingredient_index'] ) ? intval( $ingredient_result['original']['ingredient_index'] ) : -1;

			if (
				$group_index < 0
				|| $ingredient_index < 0
				|| ! isset( $ingredients[ $group_index ]['ingredients'][ $ingredient_index ] )
			) {
				continue;
			}

			$ingredient = $ingredients[ $group_index ]['ingredients'][ $ingredient_index ];
			$decision_source = isset( $ingredient_result['decision_source'] ) ? $ingredient_result['decision_source'] : 'skip';

			if ( 'skip' === $decision_source ) {
				if ( ! isset( $ingredient['converted'] ) || ! is_array( $ingredient['converted'] ) ) {
					$ingredient['converted'] = array();
				}
				$ingredient['converted'][2] = array(
					'amount' => '',
					'unit' => '',
				);
				unset( $ingredient['conversion_item_snapshot'] );
			} else {
				$amount = isset( $ingredient_result['resolved_amount'] ) ? $ingredient_result['resolved_amount'] : '';
				$unit = isset( $ingredient_result['resolved_unit'] ) ? $ingredient_result['resolved_unit'] : '';

				if ( 'existing' === $decision_source && ! empty( $ingredient_result['existing_conversion']['has_conversion'] ) ) {
					$amount = $ingredient_result['existing_conversion']['amount'];
					$unit = $ingredient_result['existing_conversion']['unit'];
				}

				if ( ! isset( $ingredient['converted'] ) || ! is_array( $ingredient['converted'] ) ) {
					$ingredient['converted'] = array();
				}
				$ingredient['converted'][2] = array(
					'amount' => self::format_converted_amount( $amount, $result['recipe']['target_system'] ),
					'unit' => $unit,
				);
				$ingredient['conversion_item_snapshot'] = array(
					'amount' => isset( $ingredient['amount'] ) ? $ingredient['amount'] : '',
					'unit' => isset( $ingredient['unit'] ) ? $ingredient['unit'] : '',
					'timestamp' => time() * 1000,
				);
			}

			$ingredients[ $group_index ]['ingredients'][ $ingredient_index ] = $ingredient;
		}

		$sanitized_recipe = WPRM_Recipe_Sanitizer::sanitize(
			array(
				'ingredients' => $ingredients,
			)
		);
		WPRM_Recipe_Saver::update_recipe( $recipe_id, $sanitized_recipe );

		$result['status'] = 'already_handled';
		$result['applied'] = true;
		$result['applied_at'] = time();
		$batch['results'][ $recipe_id ] = $result;
		$batch['counts'] = self::calculate_counts( $batch['results'] );
		self::save_batch( $batch );

		return $result;
	}

	/**
	 * Apply all ready results in a batch.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Batch ID.
	 *
	 * @return   array|WP_Error
	 */
	public static function apply_ready_recipes( $batch_id ) {
		$batch = self::get_batch( $batch_id );
		if ( ! $batch ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that unit conversion review batch.', 'wp-recipe-maker-premium' ) );
		}

		$applied = 0;
		foreach ( $batch['results'] as $recipe_id => $result ) {
			if ( isset( $result['status'] ) && 'ready_to_apply' === $result['status'] ) {
				$apply_result = self::apply_recipe( $batch_id, $recipe_id );
				if ( ! is_wp_error( $apply_result ) ) {
					$applied++;
				}
			}
		}

		$batch = self::get_batch( $batch_id );

		return array(
			'applied' => $applied,
			'batch' => self::prepare_batch_for_response( $batch ),
		);
	}

	/**
	 * Resolve recipe IDs for a selected scope.
	 *
	 * @since    10.5.1
	 * @param    string $scope      Scope to resolve.
	 * @param    array  $recipe_ids Optional selected recipe IDs.
	 *
	 * @return   array
	 */
	private static function resolve_recipe_ids_for_scope( $scope, $recipe_ids = array() ) {
		if ( ! empty( $recipe_ids ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $recipe_ids ) ) ) );
		}

		$args = array(
			'post_type' => WPRM_POST_TYPE,
			'post_status' => 'any',
			'fields' => 'ids',
			'posts_per_page' => -1,
			'suppress_filters' => true,
			'lang' => '',
		);

		$ids = get_posts( $args );

		if ( 'missing_only' === $scope ) {
			$ids = array_values(
				array_filter(
					$ids,
					function( $recipe_id ) {
						$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
						if ( ! $recipe ) {
							return false;
						}

						return self::recipe_needs_missing_unit_conversion_review( $recipe );
					}
				)
			);
		} else {
			$ids = array_values(
				array_filter(
					$ids,
					function( $recipe_id ) {
						$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
						if ( ! $recipe ) {
							return false;
						}

						return self::recipe_has_actionable_ingredients( $recipe );
					}
				)
			);
		}

		return array_values( array_map( 'intval', $ids ) );
	}

	/**
	 * Check if current ingredient data still matches reviewed original data.
	 *
	 * @since    10.5.1
	 * @param    array $current_ingredient  Current recipe ingredient data.
	 * @param    array $reviewed_ingredient Reviewed ingredient data stored in batch.
	 *
	 * @return   boolean
	 */
	private static function ingredient_matches_reviewed_original( $current_ingredient, $reviewed_ingredient ) {
		$current_amount = isset( $current_ingredient['amount'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $current_ingredient['amount'] ) ) ) : '';
		$current_unit = isset( $current_ingredient['unit'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $current_ingredient['unit'] ) ) ) : '';
		$current_name = isset( $current_ingredient['name'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $current_ingredient['name'] ) ) ) : '';
		$current_notes = isset( $current_ingredient['notes'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $current_ingredient['notes'] ) ) ) : '';

		$reviewed_amount = isset( $reviewed_ingredient['amount'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $reviewed_ingredient['amount'] ) ) ) : '';
		$reviewed_unit = isset( $reviewed_ingredient['unit'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $reviewed_ingredient['unit'] ) ) ) : '';
		$reviewed_name = isset( $reviewed_ingredient['name'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $reviewed_ingredient['name'] ) ) ) : '';
		$reviewed_notes = isset( $reviewed_ingredient['notes'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $reviewed_ingredient['notes'] ) ) ) : '';

		return $current_amount === $reviewed_amount
			&& $current_unit === $reviewed_unit
			&& $current_name === $reviewed_name
			&& $current_notes === $reviewed_notes;
	}

	/**
	 * Try to process one queued recipe while polling a batch.
	 *
	 * This acts as a fallback when the async loopback request does not run.
	 *
	 * @since    10.5.1
	 * @param    string $batch_id Batch ID.
	 */
	public static function maybe_process_batch_from_poll( $batch_id ) {
		$batch = self::get_batch( $batch_id );
		if ( ! $batch ) {
			return;
		}

		if ( ! isset( $batch['status'] ) || ! in_array( $batch['status'], array( 'queued', 'processing' ), true ) ) {
			return;
		}

		if ( intval( $batch['processed'] ) >= intval( $batch['total'] ) ) {
			return;
		}

		if ( self::get_background_process()->is_processing() ) {
			return;
		}

		$lock_key = self::POLL_LOCK_PREFIX . sanitize_key( $batch_id );
		if ( get_transient( $lock_key ) ) {
			return;
		}

		$pending_recipe_id = 0;
		$processed_recipe_ids = isset( $batch['results'] ) && is_array( $batch['results'] ) ? array_map( 'intval', array_keys( $batch['results'] ) ) : array();
		$recipe_ids = isset( $batch['recipe_ids'] ) && is_array( $batch['recipe_ids'] ) ? array_map( 'intval', $batch['recipe_ids'] ) : array();

		foreach ( $recipe_ids as $recipe_id ) {
			if ( ! in_array( $recipe_id, $processed_recipe_ids, true ) ) {
				$pending_recipe_id = $recipe_id;
				break;
			}
		}

		if ( ! $pending_recipe_id ) {
			return;
		}

		set_transient( $lock_key, time(), 30 );

		try {
			self::process_batch_item(
				array(
					'batch_id' => $batch_id,
					'recipe_id' => $pending_recipe_id,
				)
			);
		} catch ( Throwable $throwable ) {
			$batch = self::get_batch( $batch_id );

			if ( $batch ) {
				$batch['results'][ $pending_recipe_id ] = array(
					'recipe' => array(
						'id' => $pending_recipe_id,
						'name' => sprintf( __( 'Recipe %d', 'wp-recipe-maker-premium' ), $pending_recipe_id ),
					),
					'status' => 'error',
					'errors' => array( $throwable->getMessage() ),
				);
				$batch['processed'] = min( intval( $batch['total'] ), intval( $batch['processed'] ) + 1 );
				$batch['counts'] = self::calculate_counts( $batch['results'] );
				$batch['status'] = intval( $batch['processed'] ) >= intval( $batch['total'] ) ? 'completed' : 'processing';
				self::save_batch( $batch );
			}
		}

		delete_transient( $lock_key );
	}

	/**
	 * Check if a recipe has actionable ingredients.
	 *
	 * @since    10.5.1
	 * @param    WPRM_Recipe $recipe Recipe.
	 *
	 * @return   boolean
	 */
	private static function recipe_has_actionable_ingredients( $recipe ) {
		foreach ( $recipe->ingredients() as $group ) {
			foreach ( $group['ingredients'] as $ingredient ) {
				if ( empty( $ingredient['name'] ) ) {
					continue;
				}

				$amount = isset( $ingredient['amount'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) ) ) : '';
				if ( $amount ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a recipe needs missing-unit-conversion review.
	 *
	 * @since    10.5.1
	 * @param    WPRM_Recipe $recipe Recipe.
	 *
	 * @return   boolean
	 */
	private static function recipe_needs_missing_unit_conversion_review( $recipe ) {
		foreach ( $recipe->ingredients() as $group ) {
			foreach ( $group['ingredients'] as $ingredient ) {
				if ( empty( $ingredient['name'] ) ) {
					continue;
				}

				$amount = isset( $ingredient['amount'] ) ? trim( wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) ) ) : '';
				if ( ! $amount ) {
					continue;
				}

				$existing = isset( $ingredient['converted'][2] ) && is_array( $ingredient['converted'][2] ) ? $ingredient['converted'][2] : array();
				$existing_amount = isset( $existing['amount'] ) ? trim( $existing['amount'] ) : '';
				$existing_unit = isset( $existing['unit'] ) ? trim( $existing['unit'] ) : '';

				if ( '' === $existing_amount && '' === $existing_unit ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get normalized recipe context for review.
	 *
	 * @since    10.5.1
	 * @param    WPRM_Recipe $recipe Recipe.
	 *
	 * @return   array
	 */
	private static function get_recipe_context( $recipe ) {
		$original_system = intval( $recipe->unit_system() );
		$original_system = in_array( $original_system, array( 1, 2 ), true ) ? $original_system : 1;
		$target_system = 2 === $original_system ? 1 : 2;
		$ingredients = array();

		foreach ( $recipe->ingredients() as $group_index => $group ) {
			if ( ! isset( $group['ingredients'] ) || ! is_array( $group['ingredients'] ) ) {
				continue;
			}

			foreach ( $group['ingredients'] as $ingredient_index => $ingredient ) {
				if ( empty( $ingredient['name'] ) ) {
					continue;
				}

				$existing_conversion = isset( $ingredient['converted'][2] ) && is_array( $ingredient['converted'][2] ) ? $ingredient['converted'][2] : array();
				$snapshot = isset( $ingredient['conversion_item_snapshot'] ) && is_array( $ingredient['conversion_item_snapshot'] ) ? $ingredient['conversion_item_snapshot'] : false;

				$ingredients[] = array(
					'amount' => isset( $ingredient['amount'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) ) : '',
					'unit' => isset( $ingredient['unit'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['unit'] ) ) : '',
					'name' => isset( $ingredient['name'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) ) : '',
					'notes' => isset( $ingredient['notes'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['notes'] ) ) : '',
					'group_index' => $group_index,
					'ingredient_index' => $ingredient_index,
					'existing_conversion' => array(
						'amount' => isset( $existing_conversion['amount'] ) ? $existing_conversion['amount'] : '',
						'unit' => isset( $existing_conversion['unit'] ) ? $existing_conversion['unit'] : '',
						'has_conversion' => (
							( isset( $existing_conversion['amount'] ) && '' !== trim( $existing_conversion['amount'] ) )
							|| ( isset( $existing_conversion['unit'] ) && '' !== trim( $existing_conversion['unit'] ) )
						),
					),
					'conversion_snapshot' => $snapshot,
				);
			}
		}

		return array(
			'id' => $recipe->id(),
			'name' => $recipe->name(),
			'original_system' => $original_system,
			'target_system' => $target_system,
			'ingredients' => $ingredients,
		);
	}

	/**
	 * Build review data for one ingredient.
	 *
	 * @since    10.5.1
	 * @param    array $ingredient     Ingredient data.
	 * @param    array $recipe_context Recipe context.
	 * @param    int   $index          Ingredient index.
	 *
	 * @return   array
	 */
	private static function build_ingredient_review( $ingredient, $recipe_context, $index ) {
		$existing_conversion = isset( $ingredient['existing_conversion'] ) ? $ingredient['existing_conversion'] : array(
			'amount' => '',
			'unit' => '',
			'has_conversion' => false,
		);
		$review = array(
			'index' => $index,
			'client_id' => 'ingredient-' . $recipe_context['id'] . '-' . $index,
			'display' => self::get_ingredient_display_text( $ingredient ),
			'original' => $ingredient,
			'target_system' => $recipe_context['target_system'],
			'existing_conversion' => $existing_conversion,
			'rule_conversion' => false,
			'resolved_amount' => '',
			'resolved_unit' => '',
			'decision_source' => 'skip',
			'status' => 'needs_review',
			'needs_ai_review' => false,
			'requires_confirmation' => false,
			'manual_override' => false,
			'ai_review' => false,
			'flags' => array(),
		);

		$can_convert = self::ingredient_can_be_converted( $ingredient );
		if ( ! $can_convert ) {
			if ( ! empty( $existing_conversion['has_conversion'] ) ) {
				$review['resolved_amount'] = $existing_conversion['amount'];
				$review['resolved_unit'] = $existing_conversion['unit'];
				$review['decision_source'] = 'existing';
				$review['status'] = 'approved';
			} elseif ( ! empty( $ingredient['amount'] ) ) {
				// Ingredient has amount but no unit — carry over as-is (like "Keep Unit").
				$review['resolved_amount'] = $ingredient['amount'];
				$review['resolved_unit'] = '';
				$review['decision_source'] = 'proposed';
				$review['status'] = 'approved';
			} else {
				$review['status'] = 'skipped';
			}

			return self::ensure_ingredient_review_reason( $review );
		}

		$rule_conversion = WPRMPUC_Manager::calculate_unit_conversion(
			array(
				'amount' => $ingredient['amount'],
				'unit' => $ingredient['unit'],
				'name' => $ingredient['name'],
			),
			$recipe_context['target_system']
		);
		$review['rule_conversion'] = self::normalize_conversion( $rule_conversion, $recipe_context['target_system'] );

		// Don't trust rule conversions that cross the volume/weight boundary (e.g. cups to grams).
		if ( $review['rule_conversion'] && self::is_cross_type_conversion( $ingredient['unit'], $review['rule_conversion']['normalized_unit'] ) ) {
			$review['rule_conversion'] = false;
		}

		if ( ( $review['rule_conversion'] && ! empty( $review['rule_conversion']['amount'] ) ) || ( $review['rule_conversion'] && '' !== $review['rule_conversion']['unit'] ) ) {
			$review['resolved_amount'] = $review['rule_conversion']['amount'];
			$review['resolved_unit'] = $review['rule_conversion']['unit'];
			$review['decision_source'] = 'proposed';

			if ( ! empty( $existing_conversion['has_conversion'] ) ) {
				if ( self::conversion_values_match( $existing_conversion, $review['rule_conversion'], $recipe_context['target_system'] ) ) {
					$review['resolved_amount'] = $existing_conversion['amount'];
					$review['resolved_unit'] = $existing_conversion['unit'];
					$review['decision_source'] = 'existing';
					$review['status'] = 'approved';
					$review['flags'][] = 'matches_existing';
				} else {
					$review['status'] = 'needs_review';
					$review['requires_confirmation'] = true;
					$review['flags'][] = 'existing_mismatch';
					$review['needs_ai_review'] = true;
				}
			} else {
				$review['status'] = 'approved';
				$review['flags'][] = 'new_conversion';
			}

			return self::ensure_ingredient_review_reason( $review );
		}

		if ( ! empty( $existing_conversion['has_conversion'] ) ) {
			$review['resolved_amount'] = $existing_conversion['amount'];
			$review['resolved_unit'] = $existing_conversion['unit'];
			$review['decision_source'] = 'existing';
		}

		$review['needs_ai_review'] = true;
		$review['status'] = 'needs_review';
		$review['flags'][] = 'needs_ai_review';

		return self::ensure_ingredient_review_reason( $review );
	}

	/**
	 * Check if an ingredient can be converted.
	 *
	 * @since    10.5.1
	 * @param    array $ingredient Ingredient data.
	 *
	 * @return   boolean
	 */
	private static function ingredient_can_be_converted( $ingredient ) {
		$amount = isset( $ingredient['amount'] ) ? trim( $ingredient['amount'] ) : '';
		$unit = isset( $ingredient['unit'] ) ? trim( $ingredient['unit'] ) : '';
		$name = isset( $ingredient['name'] ) ? trim( $ingredient['name'] ) : '';

		return (bool) ( $amount && $unit && $name );
	}

	/**
	 * Check if a conversion crosses the volume/weight boundary.
	 *
	 * @since    10.5.1
	 * @param    string $unit_from_alias Original unit alias (e.g. "cups").
	 * @param    string $unit_to         Converted unit (e.g. "gram").
	 *
	 * @return   boolean
	 */
	private static function is_cross_type_conversion( $unit_from_alias, $unit_to ) {
		$weight_units = array( 'pound', 'ounce', 'kilogram', 'gram', 'milligram' );
		$volume_units = array( 'cup', 'gallon', 'quart', 'pint', 'fluid_ounce', 'liter', 'deciliter', 'centiliter', 'milliliter', 'tablespoon', 'teaspoon' );

		$unit_from = WPRMPUC_Manager::get_unit_from_alias( $unit_from_alias );
		if ( ! $unit_from ) {
			return false;
		}

		$from_is_weight = in_array( $unit_from, $weight_units, true );
		$from_is_volume = in_array( $unit_from, $volume_units, true );
		$to_is_weight = in_array( $unit_to, $weight_units, true );
		$to_is_volume = in_array( $unit_to, $volume_units, true );

		if ( $from_is_weight && $to_is_volume ) {
			return true;
		}
		if ( $from_is_volume && $to_is_weight ) {
			return true;
		}

		return false;
	}

	/**
	 * Normalize a conversion result for review use.
	 *
	 * @since    10.5.1
	 * @param    array|false $conversion    Conversion data.
	 * @param    int         $target_system Target system.
	 *
	 * @return   array|false
	 */
	private static function normalize_conversion( $conversion, $target_system ) {
		if ( ! is_array( $conversion ) || empty( $conversion['unit'] ) && '' === trim( isset( $conversion['amount'] ) ? $conversion['amount'] : '' ) ) {
			return false;
		}

		if ( isset( $conversion['type'] ) && 'failed' === $conversion['type'] ) {
			return false;
		}

		$amount = isset( $conversion['amount'] ) ? floatval( $conversion['amount'] ) : 0;
		$unit = isset( $conversion['unit'] ) ? sanitize_text_field( $conversion['unit'] ) : '';
		$alias = isset( $conversion['alias'] ) && $conversion['alias'] ? sanitize_text_field( $conversion['alias'] ) : WPRMPUC_Manager::get_alias_for( $amount, $unit );

		return array(
			'amount' => self::format_converted_amount( $amount, $target_system ),
			'amount_raw' => $amount,
			'unit' => $alias,
			'normalized_unit' => $unit,
		);
	}

	/**
	 * Get AI request item for an ingredient.
	 *
	 * @since    10.5.1
	 * @param    array $recipe_context Recipe context.
	 * @param    array $review         Ingredient review.
	 *
	 * @return   array
	 */
	private static function get_ai_ingredient_item( $recipe_context, $review ) {
		return array(
			'client_id' => $review['client_id'],
			'recipe' => array(
				'id' => $recipe_context['id'],
				'name' => $recipe_context['name'],
				'original_system' => $recipe_context['original_system'],
				'target_system' => $recipe_context['target_system'],
			),
			'ingredient' => array(
				'index' => $review['index'],
				'amount' => $review['original']['amount'],
				'unit' => $review['original']['unit'],
				'name' => $review['original']['name'],
				'notes' => $review['original']['notes'],
			),
			'existing_conversion' => $review['existing_conversion'],
			'rule_conversion' => $review['rule_conversion'],
			'flags' => $review['flags'],
		);
	}

	/**
	 * Call the AI unit conversion review proxy.
	 *
	 * @since    10.5.1
	 * @param    array $items Review items.
	 *
	 * @return   array
	 */
	private static function call_ai_review( $items ) {
		if ( empty( $items ) ) {
			return array();
		}

		$response = WPRMP_Proxy::call(
			'ai_review_unit_conversions',
			array(
				'items' => $items,
			)
		);

		if ( ! is_array( $response ) || ! isset( $response['results'] ) || ! is_array( $response['results'] ) ) {
			return array();
		}

		$results = array();
		foreach ( $response['results'] as $result ) {
			if ( isset( $result['client_id'] ) ) {
				$results[ $result['client_id'] ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Apply AI ingredient results to ingredient reviews.
	 *
	 * @since    10.5.1
	 * @param    array $ingredient_reviews Ingredient reviews.
	 * @param    array $ai_results         AI results keyed by client ID.
	 *
	 * @return   array
	 */
	private static function apply_ai_results( $ingredient_reviews, $ai_results ) {
		foreach ( $ingredient_reviews as $index => $review ) {
			if ( empty( $review['needs_ai_review'] ) ) {
				continue;
			}

			$client_id = isset( $review['client_id'] ) ? $review['client_id'] : '';
			$ai_result = isset( $ai_results[ $client_id ] ) ? $ai_results[ $client_id ] : false;
			if ( ! $ai_result ) {
				$review['status'] = 'needs_review';
				$review['flags'][] = 'ai_missing';
				$ingredient_reviews[ $index ] = self::ensure_ingredient_review_reason( $review );
				continue;
			}

			$review['needs_ai_review'] = false;
			$review['ai_review'] = array(
				'confidence' => isset( $ai_result['confidence'] ) ? floatval( $ai_result['confidence'] ) : 0,
				'reason' => isset( $ai_result['reason'] ) ? $ai_result['reason'] : '',
				'status' => isset( $ai_result['status'] ) ? sanitize_key( $ai_result['status'] ) : 'needs_review',
			);

			$status = isset( $review['ai_review']['status'] ) ? $review['ai_review']['status'] : 'needs_review';

			switch ( $status ) {
				case 'approve_rule_result':
					if ( $review['rule_conversion'] ) {
						$review['resolved_amount'] = $review['rule_conversion']['amount'];
						$review['resolved_unit'] = $review['rule_conversion']['unit'];
						$review['decision_source'] = 'proposed';
						$review['status'] = 'approved';
					} else {
						$review['status'] = 'needs_review';
					}
					break;
				case 'approve_ai_result':
					$recommended_amount = isset( $ai_result['recommended_amount'] ) ? self::sanitize_resolved_amount( $ai_result['recommended_amount'], '' ) : '';
					$recommended_unit = isset( $ai_result['recommended_unit'] ) ? sanitize_text_field( $ai_result['recommended_unit'] ) : '';
					$normalized_unit = $recommended_unit ? WPRMPUC_Manager::get_unit_from_alias( $recommended_unit ) : false;
					if ( $normalized_unit ) {
						$recommended_unit = WPRMPUC_Manager::get_alias_for(
							is_numeric( $recommended_amount ) ? floatval( $recommended_amount ) : 0,
							$normalized_unit
						);
					}

					if ( '' !== $recommended_unit || '' !== $recommended_amount ) {
						$review['resolved_amount'] = self::format_converted_amount( $recommended_amount, $review['target_system'] );
						$review['resolved_unit'] = $recommended_unit;
						$review['decision_source'] = 'proposed';
						$review['status'] = 'approved';
					} else {
						$review['status'] = 'needs_review';
					}
					break;
				case 'keep_existing':
					if ( ! empty( $review['existing_conversion']['has_conversion'] ) ) {
						$review['resolved_amount'] = $review['existing_conversion']['amount'];
						$review['resolved_unit'] = $review['existing_conversion']['unit'];
						$review['decision_source'] = 'existing';
						$review['status'] = 'approved';
					} else {
						$review['status'] = 'needs_review';
					}
					break;
				case 'skip':
					$review['resolved_amount'] = '';
					$review['resolved_unit'] = '';
					$review['decision_source'] = 'skip';
					$review['status'] = empty( $review['existing_conversion']['has_conversion'] ) ? 'skipped' : 'approved';
					break;
				case 'needs_review':
				default:
					$recommended_amount = isset( $ai_result['recommended_amount'] ) ? self::sanitize_resolved_amount( $ai_result['recommended_amount'], '' ) : '';
					$recommended_unit = isset( $ai_result['recommended_unit'] ) ? sanitize_text_field( $ai_result['recommended_unit'] ) : '';
					$normalized_unit = $recommended_unit ? WPRMPUC_Manager::get_unit_from_alias( $recommended_unit ) : false;
					if ( $normalized_unit ) {
						$recommended_unit = WPRMPUC_Manager::get_alias_for(
							is_numeric( $recommended_amount ) ? floatval( $recommended_amount ) : 0,
							$normalized_unit
						);
					}

					if ( '' !== $recommended_unit || '' !== $recommended_amount ) {
						$review['resolved_amount'] = self::format_converted_amount( $recommended_amount, $review['target_system'] );
						$review['resolved_unit'] = $recommended_unit;
						$review['decision_source'] = 'proposed';
					}
					$review['status'] = 'needs_review';
					break;
			}

			if (
				'approved' === $review['status']
				&& 'proposed' === $review['decision_source']
				&& ! empty( $review['existing_conversion']['has_conversion'] )
			) {
				$proposed = array(
					'amount' => $review['resolved_amount'],
					'unit' => $review['resolved_unit'],
				);

				if ( ! self::conversion_values_match( $review['existing_conversion'], $proposed, $review['target_system'] ) ) {
					$review['requires_confirmation'] = true;
					$review['status'] = 'needs_review';
					$review['flags'][] = 'existing_mismatch';
				}
			}

			if (
				'approved' === $review['status']
				&& 'skip' === $review['decision_source']
				&& ! empty( $review['existing_conversion']['has_conversion'] )
			) {
				$review['requires_confirmation'] = true;
				$review['status'] = 'needs_review';
				$review['flags'][] = 'existing_mismatch';
			}

			$ingredient_reviews[ $index ] = self::ensure_ingredient_review_reason( $review );
		}

		return $ingredient_reviews;
	}

	/**
	 * Build a recipe result from ingredient reviews.
	 *
	 * @since    10.5.1
	 * @param    array $recipe_context     Recipe context.
	 * @param    array $ingredient_reviews Ingredient reviews.
	 *
	 * @return   array
	 */
	private static function build_recipe_result( $recipe_context, $ingredient_reviews ) {
		$result = array(
			'recipe' => array(
				'id' => $recipe_context['id'],
				'name' => $recipe_context['name'],
				'original_system' => $recipe_context['original_system'],
				'target_system' => $recipe_context['target_system'],
			),
			'status' => 'needs_review',
			'ingredients' => $ingredient_reviews,
			'counts' => self::empty_ingredient_counts(),
			'proposed_changes' => 0,
			'errors' => array(),
		);

		return self::recalculate_stored_recipe_result( $result );
	}

	/**
	 * Recalculate a stored recipe result.
	 *
	 * @since    10.5.1
	 * @param    array $result Stored recipe result.
	 *
	 * @return   array
	 */
	private static function recalculate_stored_recipe_result( $result ) {
		$counts = self::empty_ingredient_counts();
		$proposed_changes = 0;
		$has_review_blockers = false;
		$has_errors = false;

		foreach ( $result['ingredients'] as $index => $ingredient ) {
			$status = isset( $ingredient['status'] ) ? $ingredient['status'] : 'error';

			if ( ! isset( $counts[ $status ] ) ) {
				$status = 'error';
				$ingredient['status'] = 'error';
			}

			$counts[ $status ]++;

			if ( 'error' === $status ) {
				$has_errors = true;
				$has_review_blockers = true;
			} elseif ( 'needs_review' === $status || ! empty( $ingredient['requires_confirmation'] ) ) {
				$has_review_blockers = true;
			}

			if ( 'approved' === $status ) {
				if ( 'proposed' === $ingredient['decision_source'] ) {
					$proposed = array(
						'amount' => $ingredient['resolved_amount'],
						'unit' => $ingredient['resolved_unit'],
					);

					if ( empty( $ingredient['existing_conversion']['has_conversion'] ) || ! self::conversion_values_match( $ingredient['existing_conversion'], $proposed, $result['recipe']['target_system'] ) ) {
						$proposed_changes++;
					}
				} elseif ( 'skip' === $ingredient['decision_source'] && ! empty( $ingredient['existing_conversion']['has_conversion'] ) ) {
					$proposed_changes++;
				}
			}

			$result['ingredients'][ $index ] = self::ensure_ingredient_review_reason( $ingredient );
		}

		$result['counts'] = $counts;
		$result['proposed_changes'] = $proposed_changes;

		if ( $has_errors ) {
			$result['status'] = 'error';
		} elseif ( $has_review_blockers ) {
			$result['status'] = 'needs_review';
		} elseif ( 0 < $proposed_changes ) {
			$result['status'] = 'ready_to_apply';
		} else {
			$result['status'] = 'no_change';
		}

		return $result;
	}

	/**
	 * Check if two conversion values effectively match.
	 *
	 * @since    10.5.1
	 * @param    array $existing Existing conversion.
	 * @param    array $proposed Proposed conversion.
	 *
	 * @return   boolean
	 */
	private static function conversion_values_match( $existing, $proposed, $target_system = 0 ) {
		$existing_amount = self::sanitize_resolved_amount( isset( $existing['amount'] ) ? $existing['amount'] : '', 0 );
		$proposed_amount = self::sanitize_resolved_amount( isset( $proposed['amount'] ) ? $proposed['amount'] : '', 0 );
		$existing_unit = self::normalize_unit_key( isset( $existing['unit'] ) ? $existing['unit'] : '' );
		$proposed_unit = self::normalize_unit_key( isset( $proposed['unit'] ) ? $proposed['unit'] : '' );

		if ( $existing_unit !== $proposed_unit ) {
			return false;
		}

		// Compare formatted display values so the match aligns with what the user sees.
		if ( $target_system > 0 ) {
			$existing_display = self::format_converted_amount( $existing_amount, $target_system );
			$proposed_display = self::format_converted_amount( $proposed_amount, $target_system );
			return $existing_display === $proposed_display;
		}

		return abs( floatval( $existing_amount ) - floatval( $proposed_amount ) ) < 0.01;
	}

	/**
	 * Normalize a unit value for comparison.
	 *
	 * @since    10.5.1
	 * @param    string $unit Unit to normalize.
	 *
	 * @return   string
	 */
	private static function normalize_unit_key( $unit ) {
		$unit = sanitize_text_field( $unit );
		$normalized = WPRMPUC_Manager::get_unit_from_alias( $unit );

		return $normalized ? $normalized : strtolower( trim( $unit ) );
	}

	/**
	 * Format a converted amount for storage and display.
	 *
	 * @since    10.5.1
	 * @param    mixed $amount        Amount to format.
	 * @param    int   $target_system Target unit system.
	 *
	 * @return   string
	 */
	private static function format_converted_amount( $amount, $target_system ) {
		$amount = self::sanitize_resolved_amount( $amount, '' );
		if ( '' === $amount ) {
			return '';
		}

		$allow_fractions = WPRM_Settings::get( 'unit_conversion_system_' . intval( $target_system ) . '_fractions' );
		$decimals = intval( WPRM_Settings::get( 'unit_conversion_round_to_decimals' ) );

		return WPRM_Recipe_Parser::format_quantity( $amount, $decimals, $allow_fractions, true );
	}

	/**
	 * Calculate status counts for a batch.
	 *
	 * @since    10.5.1
	 * @param    array $results Batch results keyed by recipe ID.
	 *
	 * @return   array
	 */
	private static function calculate_counts( $results ) {
		$counts = self::empty_counts();

		foreach ( $results as $result ) {
			$status = isset( $result['status'] ) ? $result['status'] : 'error';
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			} else {
				$counts['error']++;
			}
		}

		return $counts;
	}

	/**
	 * Get empty counts array.
	 *
	 * @since    10.5.1
	 *
	 * @return   array
	 */
	private static function empty_counts() {
		return array(
			'ready_to_apply' => 0,
			'needs_review' => 0,
			'no_change' => 0,
			'already_handled' => 0,
			'error' => 0,
		);
	}

	/**
	 * Get empty ingredient counts array.
	 *
	 * @since    10.5.1
	 *
	 * @return   array
	 */
	private static function empty_ingredient_counts() {
		return array(
			'approved' => 0,
			'needs_review' => 0,
			'skipped' => 0,
			'error' => 0,
		);
	}

	/**
	 * Get display text for an ingredient.
	 *
	 * @since    10.5.1
	 * @param    array $ingredient Ingredient data.
	 *
	 * @return   string
	 */
	private static function get_ingredient_display_text( $ingredient ) {
		$parts = array();

		if ( ! empty( $ingredient['amount'] ) ) {
			$parts[] = $ingredient['amount'];
		}
		if ( ! empty( $ingredient['unit'] ) ) {
			$parts[] = $ingredient['unit'];
		}
		if ( ! empty( $ingredient['name'] ) ) {
			$parts[] = $ingredient['name'];
		}
		if ( ! empty( $ingredient['notes'] ) ) {
			$parts[] = '(' . $ingredient['notes'] . ')';
		}

		return trim( implode( ' ', $parts ) );
	}

	/**
	 * Ensure an ingredient review has a user-facing reason.
	 *
	 * @since    10.5.1
	 * @param    array $review Ingredient review data.
	 *
	 * @return   array
	 */
	private static function ensure_ingredient_review_reason( $review ) {
		$existing_reason = '';

		if ( isset( $review['ai_review']['reason'] ) && $review['ai_review']['reason'] ) {
			$existing_reason = $review['ai_review']['reason'];
		}

		if ( $existing_reason ) {
			return $review;
		}

		$reason = '';
		$status = isset( $review['status'] ) ? $review['status'] : '';
		$flags = isset( $review['flags'] ) && is_array( $review['flags'] ) ? $review['flags'] : array();

		if ( 'skipped' === $status ) {
			$reason = __( 'This ingredient does not need a separate converted value.', 'wp-recipe-maker-premium' );
		} elseif ( in_array( 'matches_existing', $flags, true ) ) {
			$reason = __( 'The recalculated conversion matches the stored converted value.', 'wp-recipe-maker-premium' );
		} elseif ( in_array( 'existing_mismatch', $flags, true ) ) {
			$reason = __( 'The recalculated conversion differs from the stored converted value and needs confirmation.', 'wp-recipe-maker-premium' );
		} elseif ( in_array( 'new_conversion', $flags, true ) ) {
			$reason = __( 'A new converted value is ready for review and apply.', 'wp-recipe-maker-premium' );
		} elseif ( 'needs_review' === $status ) {
			$reason = __( 'This ingredient still needs manual review before the converted value can be applied.', 'wp-recipe-maker-premium' );
		}

		if ( $reason ) {
			if ( ! isset( $review['ai_review'] ) || ! is_array( $review['ai_review'] ) ) {
				$review['ai_review'] = array();
			}

			$review['ai_review']['reason'] = $reason;
		}

		return $review;
	}

	/**
	 * Sanitize a resolved amount for review and UI output.
	 *
	 * @since    10.5.1
	 * @param    mixed $amount   Amount value to sanitize.
	 * @param    mixed $fallback Fallback when the amount is empty or invalid.
	 *
	 * @return   float|string
	 */
	private static function sanitize_resolved_amount( $amount, $fallback = '' ) {
		if ( is_bool( $amount ) || null === $amount ) {
			return $fallback;
		}

		if ( is_numeric( $amount ) ) {
			return floatval( $amount );
		}

		$amount = wp_strip_all_tags( strip_shortcodes( '' . $amount ) );
		$amount = trim( $amount );

		if ( '' === $amount ) {
			return $fallback;
		}

		if ( ! preg_match( '/\d/', $amount ) ) {
			return $fallback;
		}

		$parsed_amount = WPRM_Recipe_Parser::parse_quantity( $amount );

		return is_numeric( $parsed_amount ) ? floatval( $parsed_amount ) : $fallback;
	}
}

/**
 * Background processor for AI unit conversion review batches.
 *
 * @since      10.5.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_AI_Unit_Conversion_Review_Background_Process extends WPRM_WP_Background_Process {
	/**
	 * Prefix.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	protected $prefix = 'wprmp';

	/**
	 * Action.
	 *
	 * @since    10.5.1
	 * @var      string
	 */
	protected $action = 'ai_unit_conversion_review_batch';

	/**
	 * Process one queue item.
	 *
	 * @since    10.5.1
	 * @param    array $item Queue item.
	 *
	 * @return   false
	 */
	protected function task( $item ) {
		WPRMP_AI_Unit_Conversion_Review::process_batch_item( $item );

		return false;
	}
}

WPRMP_AI_Unit_Conversion_Review::init();
