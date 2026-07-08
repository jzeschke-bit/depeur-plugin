<?php
/**
 * AI-assisted nutrition review batches.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * AI-assisted nutrition review batches.
 *
 * @since      10.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_AI_Nutrition_Review {
	/**
	 * Current batch option.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	const OPTION_CURRENT_BATCH = 'wprmp_ai_nutrition_review_current_batch';

	/**
	 * Meta key for stored review status.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	const RECIPE_META_STATUS = 'wprmp_ai_nutrition_review_status';

	/**
	 * Meta key for stored review timestamp.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	const RECIPE_META_REVIEWED_AT = 'wprmp_ai_nutrition_reviewed_at';

	/**
	 * Transient prefix for synchronous poll locks.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	const POLL_LOCK_PREFIX = 'wprmp_ai_nutrition_review_poll_lock_';

	/**
	 * Batch processor.
	 *
	 * @since    10.5.0
	 * @var      WPRMP_AI_Nutrition_Review_Background_Process
	 */
	private static $background_process;

	/**
	 * Direct units that Spoonacular handles well for nutrition lookups.
	 *
	 * @since    10.5.0
	 * @var      array
	 */
	private static $direct_units = array(
		'gram',
		'kilogram',
		'milligram',
		'ounce',
		'pound',
		'milliliter',
		'liter',
		'deciliter',
		'centiliter',
		'fluid_ounce',
		'quart',
		'pint',
		'gallon',
		'cup',
		'tablespoon',
		'teaspoon',
	);

	/**
	 * Ambiguous count-style units that usually need size confirmation.
	 *
	 * @since    10.5.0
	 * @var      array
	 */
	private static $ambiguous_units = array(
		'piece',
		'pieces',
		'pc',
		'pcs',
		'fillet',
		'fillets',
		'breast',
		'breasts',
		'slice',
		'slices',
		'clove',
		'cloves',
		'can',
		'cans',
		'package',
		'packages',
		'pkg',
		'container',
		'containers',
		'bunch',
		'bunches',
		'head',
		'heads',
		'stick',
		'sticks',
		'ear',
		'ears',
		'link',
		'links',
		'scoop',
		'scoops',
	);

	/**
	 * Status order for batch result grouping.
	 *
	 * @since    10.5.0
	 * @var      array
	 */
	private static $status_order = array(
		'needs_review',
		'no_match',
		'suggest_update',
		'ready_to_apply',
		'no_change',
		'already_handled',
		'error',
	);

	/**
	 * Register actions and filters.
	 *
	 * @since    10.5.0
	 */
	public static function init() {
		self::get_background_process();
	}

	/**
	 * Reset background processor instance.
	 *
	 * @since    10.5.0
	 */
	public static function reset_batch_processor() {
		self::$background_process = null;
	}

	/**
	 * Get batch page URL.
	 *
	 * @since    10.5.0
	 * @param    string $batch_id Optional batch ID.
	 *
	 * @return   string
	 */
	public static function get_batch_url( $batch_id = '' ) {
		$url = admin_url( 'admin.php?page=wprm_ai_assistant&tool=nutrition_review' );

		if ( $batch_id ) {
			$url = add_query_arg( 'batch', $batch_id, $url );
		}

		return $url;
	}

	/**
	 * Get batch option name.
	 *
	 * @since    10.5.0
	 * @param    string $batch_id Batch ID.
	 *
	 * @return   string
	 */
	public static function get_batch_option_name( $batch_id ) {
		return 'wprmp_ai_nutrition_review_batch_' . sanitize_key( $batch_id );
	}

	/**
	 * Get background processor.
	 *
	 * @since    10.5.0
	 *
	 * @return   WPRMP_AI_Nutrition_Review_Background_Process
	 */
	private static function get_background_process() {
		if ( ! self::$background_process ) {
			self::$background_process = new WPRMP_AI_Nutrition_Review_Background_Process();
		}

		return self::$background_process;
	}

	/**
	 * Start a new review batch.
	 *
	 * @since    10.5.0
	 * @param    array $args Batch arguments.
	 *
	 * @return   array|WP_Error
	 */
	public static function start_batch( $args = array() ) {
		$scope = isset( $args['scope'] ) ? sanitize_key( $args['scope'] ) : 'missing_only';
		$recipe_ids = isset( $args['recipe_ids'] ) && is_array( $args['recipe_ids'] ) ? array_values( array_filter( array_map( 'intval', $args['recipe_ids'] ) ) ) : array();
		$force_review = ! empty( $args['force_review'] ) || 'all_recipes' === $scope;

		$recipe_ids = self::resolve_recipe_ids_for_scope( $scope, $recipe_ids );

		if ( empty( $recipe_ids ) ) {
			return new WP_Error( 'no_recipes', __( 'No recipes found for this nutrition review run.', 'wp-recipe-maker-premium' ) );
		}

		$batch_id = wp_generate_uuid4();
		$batch = array(
			'id' => $batch_id,
			'created_at' => time(),
			'updated_at' => time(),
			'scope' => $scope,
			'force_review' => $force_review,
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
					'force_review' => $force_review,
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
	 * @since    10.5.0
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
	 * @since    10.5.0
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
	 * @since    10.5.0
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
	 * @since    10.5.0
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
			foreach ( $batch['results'] as $recipe_id => $result ) {
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
	 * @since    10.5.0
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
	 * @since    10.5.0
	 * @param    array $item Queue item.
	 */
	public static function process_batch_item( $item ) {
		$batch_id = isset( $item['batch_id'] ) ? sanitize_key( $item['batch_id'] ) : '';
		$recipe_id = isset( $item['recipe_id'] ) ? intval( $item['recipe_id'] ) : 0;
		$force_review = ! empty( $item['force_review'] );

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
			$result = self::review_recipe( $recipe_id, $force_review );
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
	 * @since    10.5.0
	 * @param    int     $recipe_id     Recipe ID.
	 * @param    boolean $force_review  Whether to force AI review even when a previous match exists.
	 *
	 * @return   array|false
	 */
	public static function review_recipe( $recipe_id, $force_review = false ) {
		self::ensure_nutrition_api_loaded();
		require_once( WPRMPUC_DIR . 'includes/admin/class-wprmpuc-conversion-api.php' );

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe ) {
			return false;
		}

		$recipe_context = self::get_recipe_context( $recipe );
		$ingredient_reviews = array();
		$items_for_ai = array();

		foreach ( $recipe_context['ingredients'] as $index => $ingredient ) {
			$review = self::build_ingredient_review( $ingredient, $recipe_context, $index, $force_review );
			$ingredient_reviews[] = $review;

			if ( isset( $review['needs_ai_review'] ) && $review['needs_ai_review'] ) {
				$items_for_ai[] = self::get_ai_ingredient_item( $recipe_context, $review );
			}
		}

		if ( ! empty( $items_for_ai ) ) {
			$ai_results = self::call_ai_review( $items_for_ai );
			$ingredient_reviews = self::apply_ai_ingredient_results( $recipe_context, $ingredient_reviews, $ai_results );
		}

		$result = self::build_recipe_result_from_reviews( $recipe_context, $ingredient_reviews );

		if ( ! empty( $result['flags'] ) ) {
			$totals_ai_result = self::call_ai_review(
				array(
					self::get_ai_totals_item( $recipe_context, $result ),
				)
			);
			$result = self::apply_ai_totals_result( $result, $totals_ai_result );
		}

		$result['recipe']['id'] = $recipe->id();
		$result['recipe']['name'] = $recipe->name();

		return $result;
	}

	/**
	 * Ensure the nutrition API dependency is available.
	 *
	 * The nutrition review save flow can run in REST requests where the
	 * advanced nutrition addon didn't load its admin-only dependency yet.
	 *
	 * @since    10.5.0
	 */
	private static function ensure_nutrition_api_loaded() {
		if ( class_exists( 'WPRMPN_Nutrition_Api' ) ) {
			return;
		}

		if ( defined( 'WPRMPN_DIR' ) ) {
			require_once( WPRMPN_DIR . 'includes/admin/class-wprmpn-nutrition-api.php' );
		}
	}

	/**
	 * Save a manual review decision for one ingredient and recalculate the recipe result.
	 *
	 * @since    10.5.0
	 * @param    string $batch_id          Batch ID.
	 * @param    int    $recipe_id         Recipe ID.
	 * @param    int    $ingredient_index  Ingredient index.
	 * @param    array  $decision          Review decision.
	 *
	 * @return   array|WP_Error
	 */
	public static function save_review_decision( $batch_id, $recipe_id, $ingredient_index, $decision ) {
		$batch = self::get_batch( $batch_id );
		if ( ! $batch || ! isset( $batch['results'][ $recipe_id ] ) ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that nutrition review batch.', 'wp-recipe-maker-premium' ) );
		}

		$result = $batch['results'][ $recipe_id ];
		if ( ! isset( $result['ingredients'][ $ingredient_index ] ) ) {
			return new WP_Error( 'invalid_ingredient', __( 'Could not find that ingredient review.', 'wp-recipe-maker-premium' ) );
		}

		$ingredient = $result['ingredients'][ $ingredient_index ];
		$exclude = ! empty( $decision['exclude'] );
		$candidate_id = isset( $decision['candidateId'] ) ? intval( $decision['candidateId'] ) : 0;
		$manual_candidate = ! empty( $decision['candidate'] ) && is_array( $decision['candidate'] ) ? self::normalize_candidate( $decision['candidate'] ) : false;
		$amount = isset( $decision['amount'] ) ? self::sanitize_resolved_amount( $decision['amount'], '' ) : $ingredient['resolved_amount'];
		$unit = isset( $decision['unit'] ) ? sanitize_text_field( $decision['unit'] ) : $ingredient['resolved_unit'];

		if ( $manual_candidate && ! $manual_candidate['id'] ) {
			$manual_candidate = false;
		}

		$ingredient['resolved_amount'] = $amount;
		$ingredient['resolved_unit'] = $unit;
		$ingredient['excluded'] = $exclude;
		$ingredient['manual_override'] = true;
		$ingredient['needs_ai_review'] = false;
		$ingredient['ai_review'] = array(
			'confidence' => 1,
			'reason' => __( 'Manually reviewed.', 'wp-recipe-maker-premium' ),
		);

		if ( $candidate_id ) {
			$selected_candidate = false;

			if ( $manual_candidate && intval( $manual_candidate['id'] ) === $candidate_id ) {
				$selected_candidate = $manual_candidate;
			}

			foreach ( $ingredient['candidate_options'] as $candidate ) {
				if ( intval( $candidate['id'] ) === $candidate_id ) {
					$selected_candidate = $candidate;
					break;
				}
			}

			if ( ! $selected_candidate && isset( $ingredient['selected_candidate']['id'] ) && intval( $ingredient['selected_candidate']['id'] ) === $candidate_id ) {
				$selected_candidate = $ingredient['selected_candidate'];
			}

			if ( $selected_candidate ) {
				$ingredient['selected_candidate'] = $selected_candidate;

				$found_candidate_option = false;
				foreach ( $ingredient['candidate_options'] as $index => $candidate ) {
					if ( intval( $candidate['id'] ) === $candidate_id ) {
						$ingredient['candidate_options'][ $index ] = $selected_candidate;
						$found_candidate_option = true;
						break;
					}
				}

				if ( ! $found_candidate_option ) {
					$ingredient['candidate_options'][] = $selected_candidate;
				}
			}
		}

		if ( $exclude ) {
			$ingredient['status'] = 'excluded';
			$ingredient['facts'] = false;
		} elseif ( ! empty( $ingredient['selected_candidate'] ) ) {
			$ingredient['status'] = 'approved';
		} elseif ( ! empty( $decision['confirmed'] ) ) {
			$ingredient['status'] = 'approved';
		}

		$result['ingredients'][ $ingredient_index ] = $ingredient;
		$result = self::recalculate_stored_recipe_result( $result );

		$batch['results'][ $recipe_id ] = $result;
		$batch['counts'] = self::calculate_counts( $batch['results'] );
		self::save_batch( $batch );

		return $result;
	}

	/**
	 * Apply proposed nutrition to a recipe.
	 *
	 * @since    10.5.0
	 * @param    string $batch_id  Batch ID.
	 * @param    int    $recipe_id Recipe ID.
	 *
	 * @return   array|WP_Error
	 */
	public static function apply_recipe( $batch_id, $recipe_id ) {
		$batch = self::get_batch( $batch_id );
		if ( ! $batch || ! isset( $batch['results'][ $recipe_id ] ) ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that nutrition review batch.', 'wp-recipe-maker-premium' ) );
		}

		$result = $batch['results'][ $recipe_id ];
		if ( empty( $result['proposed_nutrition'] ) ) {
			return new WP_Error( 'no_nutrition', __( 'No proposed nutrition facts are available for this recipe.', 'wp-recipe-maker-premium' ) );
		}

		$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe ) {
			return new WP_Error( 'missing_recipe', __( 'Could not load that recipe.', 'wp-recipe-maker-premium' ) );
		}

		$applied_nutrition = self::get_applied_nutrition( $recipe->nutrition(), $result['proposed_nutrition'] );
		$sanitized_recipe = WPRM_Recipe_Sanitizer::sanitize(
			array(
				'nutrition' => $applied_nutrition,
			)
		);
		WPRM_Recipe_Saver::update_recipe( $recipe_id, $sanitized_recipe );

		$result['existing_nutrition'] = $applied_nutrition;
		$result['comparison'] = array(
			'status' => 'already_handled',
			'changed' => false,
		);
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
	 * @since    10.5.0
	 * @param    string $batch_id Batch ID.
	 *
	 * @return   array|WP_Error
	 */
	public static function apply_ready_recipes( $batch_id ) {
		$batch = self::get_batch( $batch_id );
		if ( ! $batch ) {
			return new WP_Error( 'invalid_batch', __( 'Could not find that nutrition review batch.', 'wp-recipe-maker-premium' ) );
		}

		$applied = 0;
		foreach ( $batch['results'] as $recipe_id => $result ) {
			if ( isset( $result['status'] ) && in_array( $result['status'], array( 'ready_to_apply', 'suggest_update' ), true ) ) {
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
	 * @since    10.5.0
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

						return ! self::recipe_has_existing_nutrition( $recipe );
					}
				)
			);
		}

		return array_values( array_map( 'intval', $ids ) );
	}

	/**
	 * Try to process one queued recipe while polling a batch.
	 *
	 * This acts as a fallback when the async loopback request does not run.
	 *
	 * @since    10.5.0
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
					'force_review' => ! empty( $batch['force_review'] ),
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
	 * Check if a recipe already has stored nutrition.
	 *
	 * @since    10.5.0
	 * @param    WPRM_Recipe $recipe Recipe.
	 *
	 * @return   boolean
	 */
	private static function recipe_has_existing_nutrition( $recipe ) {
		$nutrition = $recipe->nutrition();
		unset( $nutrition['serving_size'], $nutrition['serving_unit'] );

		foreach ( $nutrition as $value ) {
			if ( false !== $value && '' !== $value && 0 !== floatval( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get normalized recipe context for review.
	 *
	 * @since    10.5.0
	 * @param    WPRM_Recipe $recipe Recipe.
	 *
	 * @return   array
	 */
	private static function get_recipe_context( $recipe ) {
		$flat_ingredients = array();
		$neighbor_names = array();

		foreach ( $recipe->ingredients() as $group ) {
			foreach ( $group['ingredients'] as $ingredient ) {
				if ( empty( $ingredient['name'] ) ) {
					continue;
				}

				$flat_ingredients[] = array(
					'amount' => isset( $ingredient['amount'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['amount'] ) ) : '',
					'unit' => isset( $ingredient['unit'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['unit'] ) ) : '',
					'name' => isset( $ingredient['name'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) ) : '',
					'notes' => isset( $ingredient['notes'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['notes'] ) ) : '',
				);
				$neighbor_names[] = isset( $ingredient['name'] ) ? wp_strip_all_tags( strip_shortcodes( $ingredient['name'] ) ) : '';
			}
		}

		foreach ( $flat_ingredients as $index => $ingredient ) {
			$context = array();
			if ( isset( $neighbor_names[ $index - 1 ] ) && $neighbor_names[ $index - 1 ] ) {
				$context[] = $neighbor_names[ $index - 1 ];
			}
			if ( isset( $neighbor_names[ $index + 1 ] ) && $neighbor_names[ $index + 1 ] ) {
				$context[] = $neighbor_names[ $index + 1 ];
			}
			$flat_ingredients[ $index ]['nearby_ingredients'] = $context;
		}

		return array(
			'id' => $recipe->id(),
			'name' => $recipe->name(),
			'servings' => $recipe->servings(),
			'ingredients' => $flat_ingredients,
			'existing_nutrition' => $recipe->nutrition(),
			'has_existing_nutrition' => self::recipe_has_existing_nutrition( $recipe ),
		);
	}

	/**
	 * Build review data for one ingredient.
	 *
	 * @since    10.5.0
	 * @param    array   $ingredient     Ingredient data.
	 * @param    array   $recipe_context Recipe context.
	 * @param    int     $index          Ingredient index.
	 * @param    boolean $force_review   Force AI review.
	 *
	 * @return   array
	 */
	private static function build_ingredient_review( $ingredient, $recipe_context, $index, $force_review ) {
		$review = array(
			'index' => $index,
			'display' => self::get_ingredient_display_text( $ingredient ),
			'original' => $ingredient,
			'resolved_amount' => self::sanitize_resolved_amount( $ingredient['amount'] ),
			'resolved_unit' => $ingredient['unit'],
			'unit_analysis' => self::analyze_unit( $ingredient ),
			'candidate_options' => array(),
			'selected_candidate' => false,
			'status' => 'needs_review',
			'needs_ai_review' => true,
			'reused_previous_match' => false,
			'facts' => false,
			'excluded' => false,
			'flags' => array(),
			'ai_review' => false,
		);

		$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient['name'] );
		$previous_match = $ingredient_id ? get_term_meta( $ingredient_id, 'wprmpn_previous_match', true ) : false;

		$search_name = WPRMPT_Translate::translate_or_keep( $ingredient['name'] );
		$candidate_options = WPRMPN_Nutrition_Api::search_ingredient( $search_name, 3 );
		if ( empty( $candidate_options ) && $search_name !== $ingredient['name'] ) {
			$candidate_options = WPRMPN_Nutrition_Api::search_ingredient( $ingredient['name'], 3 );
		}

		$review['candidate_options'] = is_array( $candidate_options ) ? array_values( array_map( array( __CLASS__, 'normalize_candidate' ), $candidate_options ) ) : array();

		if ( $previous_match && ! $force_review ) {
			$review = self::apply_previous_match_to_review( $review, $previous_match );
		}

		if ( ! $review['selected_candidate'] && ! empty( $review['candidate_options'] ) ) {
			$review['selected_candidate'] = $review['candidate_options'][0];
		}

		if ( empty( $review['candidate_options'] ) && ! $review['selected_candidate'] ) {
			$review['status'] = 'no_match';
			$review['needs_ai_review'] = false;
			$review['flags'][] = 'no_match';
		}

		if (
			! $force_review
			&& $review['reused_previous_match']
			&& isset( $review['unit_analysis']['quality'] )
			&& in_array( $review['unit_analysis']['quality'], array( 'direct_ok', 'convertible' ), true )
		) {
			$review['status'] = 'approved';
			$review['needs_ai_review'] = false;
			$review['ai_review'] = array(
				'confidence' => 1,
				'reason' => __( 'Reused a previously confirmed ingredient match.', 'wp-recipe-maker-premium' ),
			);
		}

		return self::ensure_ingredient_review_reason( $review );
	}

	/**
	 * Apply previous saved match to the current ingredient review.
	 *
	 * @since    10.5.0
	 * @param    array $review         Ingredient review.
	 * @param    array $previous_match Previous match data.
	 *
	 * @return   array
	 */
	private static function apply_previous_match_to_review( $review, $previous_match ) {
		if ( ! is_array( $previous_match ) ) {
			return $review;
		}

		if (
			isset( $previous_match['amount_original'], $previous_match['unit_original'], $previous_match['amount'], $previous_match['unit'] )
			&& $previous_match['amount_original'] === $review['original']['amount']
			&& $previous_match['unit_original'] === $review['original']['unit']
		) {
			$review['resolved_amount'] = self::sanitize_resolved_amount( $previous_match['amount'], $review['resolved_amount'] );
			$review['resolved_unit'] = $previous_match['unit'];
		}

		if ( isset( $previous_match['source'] ) && 'custom' === $previous_match['source'] ) {
			$review['flags'][] = 'custom_match_needs_review';
			return $review;
		}

		if ( isset( $previous_match['id'] ) && intval( $previous_match['id'] ) ) {
			$review['selected_candidate'] = array(
				'id' => intval( $previous_match['id'] ),
				'name' => isset( $previous_match['name'] ) ? $previous_match['name'] : __( 'Saved match', 'wp-recipe-maker-premium' ),
				'possibleUnits' => isset( $previous_match['possibleUnits'] ) && is_array( $previous_match['possibleUnits'] ) ? $previous_match['possibleUnits'] : array(),
				'source' => isset( $previous_match['source'] ) ? $previous_match['source'] : 'api',
			);
			$review['reused_previous_match'] = true;
		}

		return $review;
	}

	/**
	 * Analyze the ingredient unit quality.
	 *
	 * @since    10.5.0
	 * @param    array $ingredient Ingredient data.
	 *
	 * @return   array
	 */
	private static function analyze_unit( $ingredient ) {
		$amount = WPRM_Recipe_Parser::parse_quantity( $ingredient['amount'] );
		$unit = trim( $ingredient['unit'] );
		$normalized_unit = $unit ? WPRMPUC_Manager::get_unit_from_alias( $unit ) : false;
		$analysis = array(
			'quality' => 'unknown',
			'normalized_unit' => $normalized_unit ? str_replace( '_', ' ', $normalized_unit ) : '',
			'can_auto_approve' => false,
			'preferred_amount' => is_numeric( $amount ) ? floatval( $amount ) : 0,
			'preferred_unit' => $unit,
			'parse_result' => false,
			'conversion_attempts' => array(),
			'reasons' => array(),
		);

		$unit_lower = strtolower( $unit );

		if ( $normalized_unit && in_array( $normalized_unit, self::$direct_units, true ) ) {
			$analysis['quality'] = $unit === $normalized_unit ? 'direct_ok' : 'convertible';
			$analysis['preferred_unit'] = str_replace( '_', ' ', $normalized_unit );
			$analysis['can_auto_approve'] = true;
			$analysis['reasons'][] = __( 'This unit is already a good fit for Spoonacular nutrition lookups.', 'wp-recipe-maker-premium' );
		} elseif ( $unit && in_array( $unit_lower, self::$ambiguous_units, true ) ) {
			$analysis['quality'] = 'ambiguous_size';
			$analysis['reasons'][] = __( 'This count-style unit is ambiguous and may need a weight or volume to get reliable nutrition facts.', 'wp-recipe-maker-premium' );
		}

		$parsed_display = self::get_ingredient_display_text( $ingredient );
		$parse_result = WPRMPUC_Conversion_Api::parse_ingredient( $parsed_display );
		if ( is_array( $parse_result ) && isset( $parse_result[0] ) && is_array( $parse_result[0] ) ) {
			$parsed = $parse_result[0];
			$analysis['parse_result'] = array(
				'name' => isset( $parsed['name'] ) ? $parsed['name'] : '',
				'amount' => isset( $parsed['amount'] ) ? $parsed['amount'] : '',
				'unit' => isset( $parsed['unit'] ) ? $parsed['unit'] : '',
			);

			if ( 'unknown' === $analysis['quality'] && ! empty( $analysis['parse_result']['unit'] ) ) {
				$parsed_unit = sanitize_text_field( $analysis['parse_result']['unit'] );
				$parsed_normalized_unit = WPRMPUC_Manager::get_unit_from_alias( $parsed_unit );

				if ( $parsed_normalized_unit && in_array( $parsed_normalized_unit, self::$direct_units, true ) ) {
					$analysis['quality'] = 'convertible';
					$analysis['preferred_unit'] = str_replace( '_', ' ', $parsed_normalized_unit );
					$analysis['can_auto_approve'] = true;
					$analysis['reasons'][] = __( 'Spoonacular could parse this ingredient into a clearer nutrition-friendly unit.', 'wp-recipe-maker-premium' );
				}
			}
		}

		if ( $amount && $unit && $ingredient['name'] ) {
			$targets = array( 'gram', 'milliliter' );
			foreach ( $targets as $target_unit ) {
				$conversion = WPRMPUC_Conversion_Api::convert_ingredient( $amount, $unit, $ingredient['name'], $target_unit );
				if ( is_array( $conversion ) && isset( $conversion['targetAmount'] ) && is_numeric( $conversion['targetAmount'] ) ) {
					$analysis['conversion_attempts'][ $target_unit ] = array(
						'amount' => floatval( $conversion['targetAmount'] ),
						'unit' => $target_unit,
					);
				}
			}
		}

		if (
			in_array( $analysis['quality'], array( 'ambiguous_size', 'unknown' ), true )
			&& ! empty( $analysis['conversion_attempts'] )
		) {
			$preferred_target = isset( $analysis['conversion_attempts']['gram'] ) ? 'gram' : 'milliliter';
			$analysis['quality'] = 'convertible';
			$analysis['preferred_amount'] = $analysis['conversion_attempts'][ $preferred_target ]['amount'];
			$analysis['preferred_unit'] = $preferred_target;
			$analysis['can_auto_approve'] = true;
			$analysis['reasons'][] = __( 'We could convert this unit to a more precise nutrition-friendly value.', 'wp-recipe-maker-premium' );
		}

		if ( ! $analysis['preferred_unit'] && $analysis['normalized_unit'] ) {
			$analysis['preferred_unit'] = $analysis['normalized_unit'];
		}

		return $analysis;
	}

	/**
	 * Normalize a Spoonacular candidate.
	 *
	 * @since    10.5.0
	 * @param    array|object $candidate Candidate data.
	 *
	 * @return   array
	 */
	private static function normalize_candidate( $candidate ) {
		$candidate = (array) $candidate;

		return array(
			'id' => isset( $candidate['id'] ) ? intval( $candidate['id'] ) : 0,
			'name' => isset( $candidate['name'] ) ? $candidate['name'] : '',
			'aisle' => isset( $candidate['aisle'] ) ? $candidate['aisle'] : '',
			'image' => isset( $candidate['image'] ) ? $candidate['image'] : '',
			'possibleUnits' => isset( $candidate['possibleUnits'] ) && is_array( $candidate['possibleUnits'] ) ? array_values( $candidate['possibleUnits'] ) : array(),
			'source' => isset( $candidate['source'] ) ? $candidate['source'] : 'api',
		);
	}

	/**
	 * Get AI request item for an ingredient.
	 *
	 * @since    10.5.0
	 * @param    array $recipe_context Recipe context.
	 * @param    array $review         Ingredient review.
	 *
	 * @return   array
	 */
	private static function get_ai_ingredient_item( $recipe_context, $review ) {
		return array(
			'type' => 'ingredient_review',
			'client_id' => self::get_ai_ingredient_client_id( $recipe_context['id'], $review['index'] ),
			'recipe' => array(
				'id' => $recipe_context['id'],
				'name' => $recipe_context['name'],
				'servings' => $recipe_context['servings'],
			),
			'ingredient' => array(
				'index' => $review['index'],
				'amount' => $review['original']['amount'],
				'unit' => $review['original']['unit'],
				'name' => $review['original']['name'],
				'notes' => $review['original']['notes'],
				'nearby_ingredients' => $review['original']['nearby_ingredients'],
			),
			'unit_analysis' => $review['unit_analysis'],
			'candidate_options' => $review['candidate_options'],
			'selected_candidate' => $review['selected_candidate'],
		);
	}

	/**
	 * Get AI request item for totals review.
	 *
	 * @since    10.5.0
	 * @param    array $recipe_context Recipe context.
	 * @param    array $result         Recipe review result.
	 *
	 * @return   array
	 */
	private static function get_ai_totals_item( $recipe_context, $result ) {
		return array(
			'type' => 'totals_review',
			'client_id' => 'totals-' . $recipe_context['id'],
			'recipe' => array(
				'id' => $recipe_context['id'],
				'name' => $recipe_context['name'],
				'servings' => $recipe_context['servings'],
			),
			'ingredients' => array_map(
				function( $ingredient ) {
					return array(
						'display' => $ingredient['display'],
						'status' => $ingredient['status'],
						'resolved_amount' => $ingredient['resolved_amount'],
						'resolved_unit' => $ingredient['resolved_unit'],
						'match' => $ingredient['selected_candidate'],
						'unit_analysis' => $ingredient['unit_analysis'],
						'excluded' => ! empty( $ingredient['excluded'] ),
					);
				},
				$result['ingredients']
			),
			'proposed_nutrition' => $result['proposed_nutrition'],
			'flags' => $result['flags'],
		);
	}

	/**
	 * Call the AI nutrition review proxy.
	 *
	 * @since    10.5.0
	 * @param    array $items Review items.
	 *
	 * @return   array
	 */
	private static function call_ai_review( $items ) {
		if ( empty( $items ) ) {
			return array();
		}

		$response = WPRMP_Proxy::call(
			'ai_review_nutrition_matches',
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
	 * Get the exact client ID used for an ingredient review item.
	 *
	 * @since    10.5.0
	 * @param    int $recipe_id Recipe ID.
	 * @param    int $index     Ingredient index.
	 *
	 * @return   string
	 */
	private static function get_ai_ingredient_client_id( $recipe_id, $index ) {
		return 'ingredient-' . intval( $recipe_id ) . '-' . intval( $index );
	}

	/**
	 * Apply AI ingredient results to ingredient reviews.
	 *
	 * @since    10.5.0
	 * @param    array $recipe_context     Recipe context.
	 * @param    array $ingredient_reviews Ingredient reviews.
	 * @param    array $ai_results         AI results keyed by client ID.
	 *
	 * @return   array
	 */
	private static function apply_ai_ingredient_results( $recipe_context, $ingredient_reviews, $ai_results ) {
		foreach ( $ingredient_reviews as $index => $review ) {
			$client_id = self::get_ai_ingredient_client_id( $recipe_context['id'], $review['index'] );

			if ( ! isset( $ai_results[ $client_id ] ) ) {
				continue;
			}

			$ai_result = $ai_results[ $client_id ];
			$review['ai_review'] = array(
				'confidence' => isset( $ai_result['confidence'] ) ? floatval( $ai_result['confidence'] ) : 0,
				'reason' => isset( $ai_result['reason'] ) ? $ai_result['reason'] : '',
				'alternatives' => isset( $ai_result['alternatives'] ) && is_array( $ai_result['alternatives'] ) ? $ai_result['alternatives'] : array(),
			);

			if ( isset( $ai_result['approved_candidate_id'] ) ) {
				$approved_id = intval( $ai_result['approved_candidate_id'] );
				foreach ( $review['candidate_options'] as $candidate ) {
					if ( intval( $candidate['id'] ) === $approved_id ) {
						$review['selected_candidate'] = $candidate;
						break;
					}
				}
			}

			$unit_verdict = isset( $ai_result['unit_verdict'] ) ? sanitize_key( $ai_result['unit_verdict'] ) : 'needs_better_unit';
			$review['unit_verdict'] = $unit_verdict;
			if ( isset( $ai_result['recommended_amount'] ) && is_numeric( $ai_result['recommended_amount'] ) ) {
				$review['resolved_amount'] = self::sanitize_resolved_amount( $ai_result['recommended_amount'], $review['resolved_amount'] );
			}
			if ( isset( $ai_result['recommended_unit'] ) && $ai_result['recommended_unit'] ) {
				$review['resolved_unit'] = sanitize_text_field( $ai_result['recommended_unit'] );
			}

			$confidence = isset( $review['ai_review']['confidence'] ) ? floatval( $review['ai_review']['confidence'] ) : 0;

			if ( 'exclude' === $unit_verdict ) {
				$review['status'] = 'excluded';
				$review['excluded'] = true;
				$review['needs_ai_review'] = false;
			} elseif ( $review['selected_candidate'] && in_array( $unit_verdict, array( 'unit_ok', 'use_normalized_unit' ), true ) && 0.9 <= $confidence ) {
				$review['status'] = 'approved';
				$review['needs_ai_review'] = false;
			} elseif ( ! $review['selected_candidate'] ) {
				$review['status'] = 'no_match';
				$review['flags'][] = 'no_match';
				$review['needs_ai_review'] = false;
			} else {
				$review['status'] = 'needs_review';
				$review['flags'][] = 'needs_review';
				$review['needs_ai_review'] = false;
			}

			$ingredient_reviews[ $index ] = self::ensure_ingredient_review_reason( $review );
		}

		return $ingredient_reviews;
	}

	/**
	 * Build a recipe result from ingredient reviews.
	 *
	 * @since    10.5.0
	 * @param    array $recipe_context     Recipe context.
	 * @param    array $ingredient_reviews Ingredient reviews.
	 *
	 * @return   array
	 */
	private static function build_recipe_result_from_reviews( $recipe_context, $ingredient_reviews ) {
		$result = array(
			'recipe' => array(
				'id' => $recipe_context['id'],
				'name' => $recipe_context['name'],
				'servings' => $recipe_context['servings'],
			),
			'status' => 'needs_review',
			'ingredients' => $ingredient_reviews,
			'flags' => array(),
			'totals_review' => false,
			'proposed_nutrition' => array(),
			'comparison' => array(
				'status' => '',
				'changed' => false,
			),
			'existing_nutrition' => $recipe_context['existing_nutrition'],
		);

		$result = self::recalculate_stored_recipe_result( $result );

		return $result;
	}

	/**
	 * Recalculate facts and totals for a stored recipe result.
	 *
	 * @since    10.5.0
	 * @param    array $result Stored recipe result.
	 *
	 * @return   array
	 */
	private static function recalculate_stored_recipe_result( $result ) {
		$flags = array();

		foreach ( $result['ingredients'] as $index => $ingredient ) {
			if ( ! empty( $ingredient['excluded'] ) ) {
				$ingredient['facts'] = false;
				$ingredient['status'] = 'excluded';
				$result['ingredients'][ $index ] = self::ensure_ingredient_review_reason( $ingredient );
				continue;
			}

			if ( 'approved' !== $ingredient['status'] || empty( $ingredient['selected_candidate']['id'] ) ) {
				$ingredient['facts'] = false;
				if ( 'no_match' === $ingredient['status'] ) {
					$flags[] = 'no_match';
				} else {
					$flags[] = 'needs_review';
				}
				$result['ingredients'][ $index ] = self::ensure_ingredient_review_reason( $ingredient );
				continue;
			}

			$ingredient['facts'] = self::get_facts_for_reviewed_ingredient( $ingredient );
			if ( ! $ingredient['facts'] ) {
				$ingredient['status'] = 'needs_review';
				$flags[] = 'facts_missing';
			}

			$result['ingredients'][ $index ] = self::ensure_ingredient_review_reason( $ingredient );
		}

		$result['proposed_nutrition'] = self::get_proposed_recipe_nutrition(
			$result['ingredients'],
			isset( $result['recipe']['servings'] ) ? $result['recipe']['servings'] : 1
		);

		$deterministic_flags = self::get_totals_flags( $result );
		$flags = array_values( array_unique( array_merge( $flags, $deterministic_flags ) ) );
		$result['flags'] = $flags;

		$has_existing_nutrition = ! empty( $result['existing_nutrition'] ) && is_array( $result['existing_nutrition'] );
		if ( ! empty( $flags ) ) {
			$result['status'] = in_array( 'no_match', $flags, true ) ? 'no_match' : 'needs_review';
		} elseif ( empty( $result['proposed_nutrition'] ) ) {
			$result['status'] = 'needs_review';
		} elseif ( $has_existing_nutrition && self::nutrition_values_match( $result['existing_nutrition'], $result['proposed_nutrition'] ) ) {
			$result['status'] = 'no_change';
			$result['comparison'] = array(
				'status' => 'no_change',
				'changed' => false,
			);
		} elseif ( $has_existing_nutrition ) {
			$result['status'] = 'suggest_update';
			$result['comparison'] = array(
				'status' => 'suggest_update',
				'changed' => true,
			);
		} else {
			$result['status'] = 'ready_to_apply';
			$result['comparison'] = array(
				'status' => 'ready_to_apply',
				'changed' => true,
			);
		}

		return $result;
	}

	/**
	 * Apply AI totals result.
	 *
	 * @since    10.5.0
	 * @param    array $result     Recipe result.
	 * @param    array $ai_results AI results.
	 *
	 * @return   array
	 */
	private static function apply_ai_totals_result( $result, $ai_results ) {
		$client_id = 'totals-' . $result['recipe']['id'];
		if ( ! isset( $ai_results[ $client_id ] ) ) {
			$result['status'] = 'needs_review';
			$result['totals_review'] = array(
				'status' => 'needs_review',
				'reason' => __( 'AI totals review failed, so this recipe still needs review.', 'wp-recipe-maker-premium' ),
			);
			return $result;
		}

		$ai_result = $ai_results[ $client_id ];
		$status = isset( $ai_result['status'] ) ? sanitize_key( $ai_result['status'] ) : 'needs_review';

		$result['totals_review'] = array(
			'status' => $status,
			'reason' => isset( $ai_result['reason'] ) ? $ai_result['reason'] : '',
		);

		if ( 'looks_reasonable' !== $status ) {
			$result['status'] = 'needs_review';
		}

		return $result;
	}

	/**
	 * Get facts for a reviewed ingredient.
	 *
	 * @since    10.5.0
	 * @param    array $ingredient Reviewed ingredient.
	 *
	 * @return   array|false
	 */
	private static function get_facts_for_reviewed_ingredient( $ingredient ) {
		self::ensure_nutrition_api_loaded();

		$match_id = isset( $ingredient['selected_candidate']['id'] ) ? intval( $ingredient['selected_candidate']['id'] ) : 0;
		$amount = isset( $ingredient['resolved_amount'] ) ? floatval( $ingredient['resolved_amount'] ) : 0;
		$unit = isset( $ingredient['resolved_unit'] ) ? sanitize_text_field( $ingredient['resolved_unit'] ) : '';

		if ( ! $match_id ) {
			return false;
		}

		if ( isset( $ingredient['selected_candidate']['source'] ) && 'custom' === $ingredient['selected_candidate']['source'] ) {
			return false;
		}

		$api_facts = (array) WPRMPN_Nutrition_Api::get_nutrition_for( $amount, $unit, $match_id );
		if ( ! isset( $api_facts['nutrition']['nutrients'] ) ) {
			return false;
		}

		$api_nutrients = (array) $api_facts['nutrition']['nutrients'];
		$facts = array();

		foreach ( WPRM_Nutrition::get_fields() as $field => $options ) {
			if ( 'serving_size' === $field ) {
				continue;
			}

			$api_search = isset( $options['api'] ) ? $options['api'] : false;
			$api_value = false;

			if ( $api_search ) {
				foreach ( $api_nutrients as $api_nutrient ) {
					$api_nutrient = (array) $api_nutrient;
					$api_name = isset( $api_nutrient['title'] ) ? $api_nutrient['title'] : ( isset( $api_nutrient['name'] ) ? $api_nutrient['name'] : false );

					if ( $api_search === $api_name ) {
						$api_value = isset( $api_nutrient['amount'] ) ? floatval( $api_nutrient['amount'] ) : false;
						break;
					}
				}
			}

			$facts[ $field ] = $api_value;
		}

		return $facts;
	}

	/**
	 * Get proposed recipe nutrition for approved ingredients.
	 *
	 * @since    10.5.0
	 * @param    array $ingredients Ingredient reviews.
	 * @param    mixed $servings    Recipe servings.
	 *
	 * @return   array
	 */
	private static function get_proposed_recipe_nutrition( $ingredients, $servings ) {
		$fields = WPRM_Nutrition::get_fields();
		unset( $fields['serving_size'] );

		$servings = WPRM_Recipe_Parser::parse_quantity( $servings );
		$servings = $servings && 0 < $servings ? floatval( $servings ) : 1;

		$nutrition = array();

		foreach ( $fields as $field => $options ) {
			if ( 'calculated' === $options['type'] ) {
				continue;
			}

			$value = 0;
			$has_value = false;

			foreach ( $ingredients as $ingredient ) {
				if ( ! empty( $ingredient['excluded'] ) || empty( $ingredient['facts'] ) ) {
					continue;
				}

				if ( isset( $ingredient['facts'][ $field ] ) && false !== $ingredient['facts'][ $field ] && '' !== $ingredient['facts'][ $field ] ) {
					$value += floatval( $ingredient['facts'][ $field ] );
					$has_value = true;
				}
			}

			if ( $has_value ) {
				$value = $value / $servings;

				$ignore_quantity = WPRM_Settings::get( 'nutrition_facts_calculation_ignore_small_quantity' );
				if ( $ignore_quantity && $value < floatval( $ignore_quantity ) ) {
					$nutrition[ $field ] = false;
				} else {
					$nutrition[ $field ] = round( $value, intval( WPRM_Settings::get( 'nutrition_facts_calculation_round_to_decimals' ) ) );
				}
			} else {
				$nutrition[ $field ] = false;
			}
		}

		$nutrition = array_replace( $nutrition, WPRMPN_Calculated_Nutrition::get_calculated_nutrition_fields( $nutrition ) );

		return $nutrition;
	}

	/**
	 * Get deterministic totals flags.
	 *
	 * @since    10.5.0
	 * @param    array $result Recipe result.
	 *
	 * @return   array
	 */
	private static function get_totals_flags( $result ) {
		$flags = array();
		$nutrition = isset( $result['proposed_nutrition'] ) ? $result['proposed_nutrition'] : array();
		$ingredient_count = count( isset( $result['ingredients'] ) ? $result['ingredients'] : array() );
		$resolved_count = count(
			array_filter(
				$result['ingredients'],
				function( $ingredient ) {
					return 'approved' === $ingredient['status'] && ! empty( $ingredient['facts'] );
				}
			)
		);

		$calories = isset( $nutrition['calories'] ) && false !== $nutrition['calories'] ? floatval( $nutrition['calories'] ) : 0;
		$fat = isset( $nutrition['fat'] ) && false !== $nutrition['fat'] ? floatval( $nutrition['fat'] ) : 0;
		$carbs = isset( $nutrition['carbohydrates'] ) && false !== $nutrition['carbohydrates'] ? floatval( $nutrition['carbohydrates'] ) : 0;
		$protein = isset( $nutrition['protein'] ) && false !== $nutrition['protein'] ? floatval( $nutrition['protein'] ) : 0;

		if ( 0 === $resolved_count && 0 < $ingredient_count ) {
			$flags[] = 'no_resolved_ingredients';
		}

		if ( 2 < $ingredient_count && $calories < 5 ) {
			$flags[] = 'calories_too_low';
		}

		if ( 2000 < $calories ) {
			$flags[] = 'calories_too_high';
		}

		$macro_calories = ( $fat * 9 ) + ( $carbs * 4 ) + ( $protein * 4 );
		if ( $calories && abs( $macro_calories - $calories ) > max( 50, $calories * 0.35 ) ) {
			$flags[] = 'macro_calories_mismatch';
		}

		foreach ( $result['ingredients'] as $ingredient ) {
			if ( ! empty( $ingredient['excluded'] ) ) {
				continue;
			}

			if ( 'no_match' === $ingredient['status'] ) {
				$flags[] = 'incomplete_ingredients';
				break;
			}

			if (
				isset( $ingredient['unit_analysis']['quality'] )
				&& 'ambiguous_size' === $ingredient['unit_analysis']['quality']
				&& 'approved' !== $ingredient['status']
			) {
				$flags[] = 'ambiguous_units';
				break;
			}
		}

		return array_values( array_unique( $flags ) );
	}

	/**
	 * Check if two nutrition sets effectively match.
	 *
	 * @since    10.5.0
	 * @param    array $existing Existing nutrition.
	 * @param    array $proposed Proposed nutrition.
	 *
	 * @return   boolean
	 */
	private static function nutrition_values_match( $existing, $proposed ) {
		foreach ( WPRM_Nutrition::get_fields() as $field => $options ) {
			if ( in_array( $field, array( 'serving_size', 'serving_unit' ), true ) ) {
				continue;
			}

			$existing_value = isset( $existing[ $field ] ) && false !== $existing[ $field ] ? floatval( $existing[ $field ] ) : 0;
			$proposed_value = isset( $proposed[ $field ] ) && false !== $proposed[ $field ] ? floatval( $proposed[ $field ] ) : 0;

			if ( abs( $existing_value - $proposed_value ) > max( 0.5, $existing_value * 0.1 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Prepare nutrition values to apply to a recipe.
	 *
	 * @since    10.5.0
	 * @param    array $existing Existing recipe nutrition.
	 * @param    array $proposed Proposed nutrition.
	 *
	 * @return   array
	 */
	private static function get_applied_nutrition( $existing, $proposed ) {
		$nutrition = array();

		foreach ( WPRM_Nutrition::get_fields() as $field => $options ) {
			if ( 'serving_size' === $field ) {
				$nutrition[ $field ] = isset( $existing[ $field ] ) ? $existing[ $field ] : false;
			} elseif ( 'serving_unit' === $field ) {
				$nutrition[ $field ] = isset( $existing[ $field ] ) ? $existing[ $field ] : false;
			} else {
				$nutrition[ $field ] = isset( $proposed[ $field ] ) ? $proposed[ $field ] : false;
			}
		}

		return $nutrition;
	}

	/**
	 * Calculate status counts for a batch.
	 *
	 * @since    10.5.0
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
	 * @since    10.5.0
	 *
	 * @return   array
	 */
	private static function empty_counts() {
		return array(
			'ready_to_apply' => 0,
			'needs_review' => 0,
			'no_match' => 0,
			'no_change' => 0,
			'already_handled' => 0,
			'suggest_update' => 0,
			'error' => 0,
		);
	}

	/**
	 * Get display text for an ingredient.
	 *
	 * @since    10.5.0
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
	 * @since    10.5.0
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
		$unit_verdict = isset( $review['unit_verdict'] ) ? $review['unit_verdict'] : '';
		$unit_quality = isset( $review['unit_analysis']['quality'] ) ? $review['unit_analysis']['quality'] : '';

		if ( 'excluded' === $status ) {
			$reason = __( 'This ingredient is excluded from the nutrition calculation.', 'wp-recipe-maker-premium' );
		} elseif ( in_array( 'facts_missing', $flags, true ) ) {
			$reason = __( 'Spoonacular could not return nutrition facts for the selected match and unit, so this ingredient still needs review.', 'wp-recipe-maker-premium' );
		} elseif ( 'no_match' === $status || in_array( 'no_match', $flags, true ) ) {
			$reason = __( 'We could not confidently find a Spoonacular match for this ingredient yet.', 'wp-recipe-maker-premium' );
		} elseif ( in_array( 'custom_match_needs_review', $flags, true ) ) {
			$reason = __( 'This ingredient is using a saved custom match and still needs a manual review before nutrition can be calculated reliably.', 'wp-recipe-maker-premium' );
		} elseif ( 'needs_better_unit' === $unit_verdict ) {
			$reason = __( 'The ingredient match looks possible, but the unit still needs review before we can trust the nutrition facts.', 'wp-recipe-maker-premium' );
		} elseif ( 'ambiguous_size' === $unit_quality ) {
			$reason = __( 'This ingredient needs review because the unit is ambiguous and may need a weight or volume for reliable nutrition facts.', 'wp-recipe-maker-premium' );
		} elseif ( 'needs_review' === $status ) {
			$reason = __( 'This ingredient still needs a manual review before the nutrition facts can be calculated reliably.', 'wp-recipe-maker-premium' );
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
	 * @since    10.5.0
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
 * Background processor for AI nutrition review batches.
 *
 * @since      10.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_AI_Nutrition_Review_Background_Process extends WPRM_WP_Background_Process {
	/**
	 * Prefix.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	protected $prefix = 'wprmp';

	/**
	 * Action.
	 *
	 * @since    10.5.0
	 * @var      string
	 */
	protected $action = 'ai_nutrition_review_batch';

	/**
	 * Process one queue item.
	 *
	 * @since    10.5.0
	 * @param    array $item Queue item.
	 *
	 * @return   false
	 */
	protected function task( $item ) {
		WPRMP_AI_Nutrition_Review::process_batch_item( $item );

		return false;
	}
}

WPRMP_AI_Nutrition_Review::init();
