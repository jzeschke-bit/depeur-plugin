<?php
/**
 * Responsible for handling the recipe collections report.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Responsible for handling the recipe collections report.
 *
 * @since      9.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Reports_Recipe_Collections {

	/**
	 * Register actions and filters.
	 *
	 * @since	9.5.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_report_recipe_collections', array( __CLASS__, 'ajax_report_recipe_collections' ) );
	}

	/**
	 * Add the reports submenu to the WPRM menu.
	 *
	 * @since	9.5.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'Recipe Collections Report', 'wp-recipe-maker-premium' ), __( 'Recipe Collections Report', 'wp-recipe-maker-premium' ), WPRM_Settings::get( 'features_reports_access' ), 'wprm_report_recipe_collections', array( __CLASS__, 'report_template' ) );
	}

	/**
	 * Get the template for the report.
	 *
	 * @since    9.5.0
	 */
	public static function report_template() {
		$report_finished = isset( $_GET['wprm_report_finished'] ) ? (bool) sanitize_key( $_GET['wprm_report_finished'] ) : false;

		if ( $report_finished ) {
			$data = self::get_report_data();
			wp_localize_script( 'wprmp-admin', 'wprm_reports_data', $data );
		} else {
			WPRM_Reports_Manager::clear_data();

			$args = array(
				'number' => -1,
				'count_total' => false,
				'fields' => 'ID',
			);
	
			$users = get_users( $args );
	
			// Only when debugging.
			if ( WPRM_Reports_Manager::$debugging ) {
				$result = self::report_recipe_collections( $users ); // Input var okay.
				WPRM_Debug::log( $result );
				die();
			}
	
			// Handle via AJAX.
			wp_localize_script( 'wprm-admin', 'wprm_reports', array(
				'action' => 'report_recipe_collections',
				'posts' => $users,
				'args' => array(),
			));
		}

		require_once( WPRMP_DIR . 'templates/admin/menu/reports/recipe-collections.php' );
	}

	/**
	 * Generate the report through AJAX.
	 *
	 * @since    9.5.0
	 */
	public static function ajax_report_recipe_collections() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_reports_access' ) ) ) {
				$posts = isset( $_POST['posts'] ) ? json_decode( wp_unslash( $_POST['posts'] ) ) : array(); // Input var okay.

				$posts_left = array();
				$posts_processed = array();

				if ( count( $posts ) > 0 ) {
					$posts_left = $posts;
					$posts_processed = array_map( 'intval', array_splice( $posts_left, 0, 10 ) );

					$result = self::report_recipe_collections( $posts_processed );

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( array(
							'redirect' => add_query_arg( array( 'sub' => 'advanced' ), admin_url( 'admin.php?page=wprm_reports' ) ),
						) );
					}
				}

				wp_send_json_success( array(
					'posts_processed' => $posts_processed,
					'posts_left' => $posts_left,
				) );
			}
		}

		wp_die();
	}

	/**
	 * Generate the report.
	 *
	 * @since	9.5.0
	 * @param	array $users IDs of users to search.
	 */
	public static function report_recipe_collections( $users ) {
		$timeframes = array(
			'365_days' => date( 'Y-m-d H:i:s', strtotime( '-365 days' ) ),
			'31_days' => date( 'Y-m-d H:i:s', strtotime( '-31 days' ) ),
			'7_days' => date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
		);

		foreach ( $users as $user_id ) {
			$user_id = intval( $user_id );
			$user = get_user_by( 'ID', $user_id );

			if ( $user ) {
				$data = array();
				$collections = WPRMPRC_Manager::get_user_collections( $user_id, false );

				$using_feature = false;
				$nbr_collections = 0;
				$nbr_items = array(
					'recipe' => 0,
					'ingredient' => 0,
					'nutrition-ingredient' => 0,
					'note' => 0,
				);
				$recipes = array();

				if ( $collections ) {
					// Check items in inbox first.
					if ( $collections['inbox'] ) {
						if ( $collections['inbox']['items'] ) {
							foreach ( $collections['inbox']['items'] as $column_group => $items ) {
								foreach ( $items as $item ) {
									$using_feature = true;
									if ( $item['type'] && isset( $nbr_items[ $item['type'] ] ) ) {
										$nbr_items[ $item['type'] ]++;
									}

									if ( 'recipe' === $item['type'] ) {
										$recipe_id = $item['recipeId'];

										if ( ! isset( $recipes[ $recipe_id ] ) ) {
											$recipes[ $recipe_id ] = array(
												'name' => $item['name'],
												'lifetime' => 0,
												'365_days' => 0,
												'31_days' => 0,
												'7_days' => 0,
											);
										}

										$recipes[ $recipe_id ]['lifetime']++;

										if ( isset( $item['added'] ) ) {
											$date = DateTimeImmutable::createFromFormat('U', (string) $item['added'] );
											$date->setTimezone( new DateTimeZone( 'UTC' ) );
											$date = $date->format( 'Y-m-d H:i:s' );

											if ( $date >= $timeframes['365_days'] ) {
												$recipes[ $recipe_id ]['365_days']++;
											}
											if ( $date >= $timeframes['31_days'] ) {
												$recipes[ $recipe_id ]['31_days']++;
											}
											if ( $date >= $timeframes['7_days'] ) {
												$recipes[ $recipe_id ]['7_days']++;
											}
										}
									}
								}
							}
						}
					}

					// Check collections the user made themselves.
					if ( $collections['user'] ) {
						foreach ( $collections['user'] as $collection ) {
							$nbr_collections++;

							if ( $collection['items'] ) {
								foreach ( $collection['items'] as $column_group => $items ) {
									foreach ( $items as $item ) {
										$using_feature = true;
										if ( $item['type'] && isset( $nbr_items[ $item['type'] ] ) ) {
											$nbr_items[ $item['type'] ]++;
										}
	
										if ( 'recipe' === $item['type'] ) {
											$recipe_id = $item['recipeId'];
	
											if ( ! isset( $recipes[ $recipe_id ] ) ) {
												$recipes[ $recipe_id ] = array(
													'name' => $item['name'],
													'lifetime' => 0,
													'365_days' => 0,
													'31_days' => 0,
													'7_days' => 0,
												);
											}
	
											$recipes[ $recipe_id ]['lifetime']++;
										
											if ( isset( $item['added'] ) ) {
												$date = DateTimeImmutable::createFromFormat('U', (string) $item['added'] );
												$date->setTimezone( new DateTimeZone( 'UTC' ) );
												$date = $date->format( 'Y-m-d H:i:s' );

												if ( $date >= $timeframes['365_days'] ) {
													$recipes[ $recipe_id ]['365_days']++;
												}
												if ( $date >= $timeframes['31_days'] ) {
													$recipes[ $recipe_id ]['31_days']++;
												}
												if ( $date >= $timeframes['7_days'] ) {
													$recipes[ $recipe_id ]['7_days']++;
												}
											}
										}
									}
								}
							}
						}
					}
				}

				$data = array(
					'using_feature' => $using_feature,
					'nbr_collections' => $nbr_collections,
					'nbr_items' => array_sum( $nbr_items ),
					'nbr_recipes' => $nbr_items['recipe'],
					'nbr_custom' => $nbr_items['ingredient'],
					'nbr_ingredients' => $nbr_items['nutrition-ingredient'],
					'nbr_notes' => $nbr_items['note'],
					'recipes' => $recipes,
				);
			}

			// Store in reports database.
			WPRM_Reports_Manager::save_data( $user_id, $data );
		}
	}

	/**
	 * Get data to use in the report.
	 *
	 * @since	9.5.0
	 */
	public static function get_report_data() {
		$user_report = array(
			'users_using_feature' => 0,
			'users_total' => 0,
		);
		$recipe_report = array();

		$items_report = array(
			'nbr_collections' => array(
				'name' => __( 'Collections (excludes inbox)', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
			'nbr_items' => array(
				'name' => __( 'All Items used in Collections', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
			'nbr_recipes' => array(
				'name' => __( 'All Items', 'wp-recipe-maker-premium' ) . ' - ' . __( 'Recipes', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
			'nbr_custom' => array(
				'name' => __( 'All Items', 'wp-recipe-maker-premium' ) . ' - ' . __( 'Custom Recipes', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
			'nbr_ingredients' => array(
				'name' => __( 'All Items', 'wp-recipe-maker-premium' ) . ' - ' . __( 'Nutrition Ingredients', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
			'nbr_notes' => array(
				'name' => __( 'All Items', 'wp-recipe-maker-premium' ) . ' - ' . __( 'Notes', 'wp-recipe-maker-premium' ),
				'values' => array(),
			),
		);

		// Loop over all users.
		$users_data = WPRM_Reports_Manager::get_data();
		foreach ( $users_data as $user_id => $user_data ) {
			$user_report['users_total']++;

			// Usage.
			if ( $user_data['using_feature'] ) {
				$user_report['users_using_feature']++;

				foreach ( $items_report as $item_type => $item_data ) {
					if ( isset( $user_data[ $item_type ] ) ) {
						$items_report[ $item_type ]['values'][] = $user_data[ $item_type ];
					}
				}

				foreach ( $user_data['recipes'] as $recipe_id => $recipe ) {
					if ( ! isset( $recipe_report[ $recipe_id ] ) ) {
						$recipe_report[ $recipe_id ] = array(
							'id' => $recipe_id,
							'name' => $recipe['name'],
							'lifetime' => 0,
							'365_days' => 0,
							'31_days' => 0,
							'7_days' => 0,
							'users' => 0,
						);
					}

					$recipe_report[ $recipe_id ]['lifetime'] += $recipe['lifetime'];
					$recipe_report[ $recipe_id ]['365_days'] += $recipe['365_days'];
					$recipe_report[ $recipe_id ]['31_days'] += $recipe['31_days'];
					$recipe_report[ $recipe_id ]['7_days'] += $recipe['7_days'];
					$recipe_report[ $recipe_id ]['users'] += 1;
				}
			}
		}

		// Calculate value statistics.
		foreach ( $items_report as $item_type => $item_data ) {			
			$values = $item_data['values'];

			$sum = array_sum( $values );
			$count = count( $values );

			$items_report[ $item_type ][ 'nbr_total' ] = $sum;
			$items_report[ $item_type ][ 'nbr_average' ] = $count ? $sum / $count : 0;
			$items_report[ $item_type ][ 'nbr_max' ] = $count ? max( $values ) : 0;

			// Get the median value.
			sort( $values );
			$middle = floor( ( $count - 1 ) / 2 );
			if ( $count % 2 == 0 ) {
				$median = ($values[$middle] + $values[$middle + 1]) / 2;
			} else {
				$median = $values[$middle];
			}
			$items_report[ $item_type ]['nbr_median'] = $median;

			// Do not need individual values anymore.
			unset( $items_report[ $item_type ]['values'] );
		}

		$user_report['users_using_feature_percentage'] = $user_report['users_total'] ? $user_report['users_using_feature'] / $user_report['users_total'] * 100 : 0;

		return array(
			'user_report' => $user_report,
			'items_report' => $items_report,
			'recipe_report' => array_values( $recipe_report ),
		);
	}
}

WPRMP_Reports_Recipe_Collections::init();