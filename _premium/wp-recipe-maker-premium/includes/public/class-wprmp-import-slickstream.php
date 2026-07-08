<?php
/**
 * Handle the import of recipes from SlickStream.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.10.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the import of recipes from SlickStream.
 *
 * @since      8.10.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import_Slickstream {
	/**
	 *  Number of useres to import at a time.
	 *
	 * @since    8.10.0
	 * @access   private
	 * @var      int $import_limit Number of users to import at a time.
	 */
	private static $import_limit = 3;

	/**
	 * Register actions and filters.
	 *
	 * @since    8.10.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_import_slickstream', array( __CLASS__, 'ajax_import_slickstream' ) );
	}

	/**
	 * Add the JSON import page.
	 *
	 * @since	8.10.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'WPRM Import from SlickStream', 'wp-recipe-maker-premium' ), __( 'WPRM Import from SlickStream', 'wp-recipe-maker-premium' ), WPRM_Settings::get( 'features_import_access' ), 'wprm_import_slickstream', array( __CLASS__, 'import_slickstream_page_template' ) );
	}

	/**
	 * Get the template for the edit saved collection page.
	 *
	 * @since	8.10.0
	 */
	public static function import_slickstream_page_template() {
		$importing = false;

		if ( isset( $_POST['wprm_import_slickstream'] ) && wp_verify_nonce( $_POST['wprm_import_slickstream'], 'wprm_import_slickstream' ) ) { // Input var okay.
			$filename = $_FILES['csv']['tmp_name'];
			if ( $filename ) {
				$csv = array_map( 'str_getcsv', file( $filename ) );

				if ( ! $csv || ! is_array( $csv ) || ! count( $csv ) ) {
					echo '<p>We were not able to read this file or find anything in it. Is it using the correct CSV format?</p>';
				} else {
					$importing = true;
					$import_type = isset( $_POST['wprm-import-type'] ) ? $_POST['wprm-import-type'] : 'create';

					// Group by email address.
					$users = array();
					foreach ( $csv as $line ) {
						$email = $line[0];

						if ( $email ) {
							if ( ! isset( $users[ $email ] ) ) {
								$users[ $email ] = array(
									'email' => $email,
									'name' => $line[2],
									'permalinks' => array(),
								);
							}

							$users[ $email ]['permalinks'][] = $line[1];
						}
					}

					// Remove key, no need to store. Don't use array_values for memory reasons.
					$i = 0;
					foreach ( $users as $key => $user ) {
						$users[ $i ] = $user;
						unset( $users[ $key ] );
						$i++;
					}

					// // Debugging.
					// foreach ( $users as $user ) {
					// 	self::import_slickstream_user( $user );
					// }
					// die();

					delete_transient( 'wprm_import_users_slickstream' );
					$transient = json_encode( $users );
					set_transient( 'wprm_import_users_slickstream', $transient, 60 * 60 * 24 );

					$nbr_users = count( $users );
					$pages = ceil( $nbr_users / self::$import_limit );

					// Handle via AJAX.
					wp_localize_script( 'wprmp-admin', 'wprm_import_slickstream', array(
						'pages' => $pages,
					));

					echo '<p>Importing ' . $nbr_users . ' users.</p>';
					$progress_bar_type = 'tools';
					include WPRM_DIR . 'templates/admin/progress-bar.php';
					echo '<p id="wprm-tools-finished">Import finished!. <a href="' . admin_url( 'admin.php?page=wprm_manage#user-collections' ) . '">View on the manage page</a>.</p>';
				}
			} else {
				echo '<p>No file selected.</p>';
			}
		}
		
		if ( ! $importing ) {
			include WPRMP_DIR . 'templates/admin/import-slickstream.php';
		}
	}

	/**
	 * Import recipes through AJAX.
	 *
	 * @since	8.10.0
	 */
	public static function ajax_import_slickstream() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_import_access' ) ) ) {
				$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : false; // Input var okay.

				if ( false !== $page ) {
					$users = get_transient( 'wprm_import_users_slickstream' );
					$users = json_decode( $users, true );

					if ( $users && is_array( $users ) ) {
						$start = $page * self::$import_limit;
						$end = $start + self::$import_limit;

						for ( $i = $start; $i < $end; $i++ ) {
							if ( isset( $users[ $i ] ) ) {
								self::import_slickstream_user( $users[ $i ] );
							}
						}

						wp_send_json_success();
					}
				}

				wp_send_json_error();
			}
		}
		wp_die();
	}

	/**
	 * Import a single user from CSV.
	 *
	 * @since	8.10.0
	 * @param	mixed $user  User to import from CSV.
	 */
	public static function import_slickstream_user( $user ) {
		// Check if user exists.
		$user_id = false;
		$userdata = get_user_by( 'email', $user['email'] );

		if ( $userdata ) {
			$user_id = $userdata->ID;
		} else {
			// Create user.
			$user_id = wp_insert_user(
				array(
					'user_login' => $user['email'],
					'user_pass' => wp_generate_password(),
					'user_email' => $user['email'],
					'display_name' => $user['name'],
				)
			);
		}

		if ( $user_id && ! is_wp_error( $user_id ) ) {
			$recipes = self::get_recipe_ids_from_urls( $user['permalinks'] );

			if ( $recipes ) {
				self::add_recipes_to_user_inbox( $user_id, $recipes );
			}
		}
	}

	/**
	 * Get recipe IDs from list of URLS.
	 *
	 * @since	8.10.0
	 * @param	array $urls  Urls to get the recipe IDs from.
	 */
	public static function get_recipe_ids_from_urls( $urls ) {
		$recipes = array();

		foreach ( $urls as $url ) {
			$post_id = url_to_postid( $url );

			if ( $post_id ) {
				$recipe_ids = WPRM_Recipe_Manager::get_recipe_ids_from_post( $post_id );

				$recipes = array_merge( $recipes, $recipe_ids );
			}
		}

		return array_unique( $recipes );
	}

	/**
	 * Add recipes to the inbox of a specific user.
	 *
	 * @since	8.10.0
	 * @param	int		$user_id  User to add the recipes to.
	 * @param	array	$recipes  Recipes to add.
	 */
	public static function add_recipes_to_user_inbox( $user_id, $recipes ) {
		$collections = WPRMPRC_Manager::get_user_collections( $user_id, true );

		if ( $collections && isset( $collections['inbox'] ) ) {
			// Make sure inbox exists.
			if ( ! isset( $collections['inbox']['items']['0-0'] ) ) {
				$collections['inbox']['items']['0-0'] = array();
			}

			// Check for any existing recipes in the inbox (we won't add duplicates).
			$existing_recipes_in_inbox = array_column( $collections['inbox']['items']['0-0'], 'recipeId' );

			// Get unique ID.
			$max_id = 0 < count( $collections['inbox']['items']['0-0'] ) ? max( array_map( function( $item ) { return intval( $item['id'] ); }, $collections['inbox']['items']['0-0'] ) ) : false;
			$uid = false === $max_id ? 0 : $max_id + 1;

			foreach ( $recipes as $recipe_id ) {
				// Skip if already in inbox.
				if ( in_array( $recipe_id, $existing_recipes_in_inbox ) ) {
					continue;
				}

				// Get recipe to add.
				$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

				if ( $recipes ) {
					$recipe_data = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );

					if ( $recipe_data ) {
						$recipe_data['id'] = $uid;

						$collections['inbox']['items']['0-0'][] = $recipe_data;
						$collections['inbox']['nbrItems']++;
					}
				}

				// Increase UID.
				$uid++;
			}

			// Save changes.
			WPRMPRC_Manager::save_user_collections( $collections, $user_id );
		}
	}
}
WPRMP_Import_Slickstream::init();
