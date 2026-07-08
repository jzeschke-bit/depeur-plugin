<?php
/**
 * Handle the import of recipes from JSON.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the import of recipes from JSON.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import_JSON {

	/**
	 *  Number of recipes to import at a time.
	 *
	 * @since    5.3.0
	 * @access   private
	 * @var      int $import_limit Number of recipes to import at a time.
	 */
	private static $import_limit = 1;
	private static $background_process_user_ratings = false;
	private static $background_process_images = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    5.2.0
	 */
	public static function init() {
		self::$background_process_user_ratings = new WPRM_Import_User_Ratings_Background_Process();
		self::$background_process_images = new WPRM_Import_Images_Background_Process();

		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_import_json', array( __CLASS__, 'ajax_import_json' ) );
	}

	/**
	 * Add the JSON import page.
	 *
	 * @since	5.2.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'WPRM Import from JSON', 'wp-recipe-maker' ), __( 'WPRM Import from JSON', 'wp-recipe-maker' ), WPRM_Settings::get( 'features_import_access' ), 'wprm_import_json', array( __CLASS__, 'import_json_page_template' ) );
	}

	/**
	 * Get the template for the edit saved collection page.
	 *
	 * @since	5.2.0
	 */
	public static function import_json_page_template() {
		$importing = false;

		if ( isset( $_POST['wprm_import_json'] ) && wp_verify_nonce( $_POST['wprm_import_json'], 'wprm_import_json' ) ) { // Input var okay.
			$filename = $_FILES['json']['tmp_name'];
			if ( $filename ) {
				$json = false;

				$str = file_get_contents(
					$filename,
					false,
					stream_context_create( array(
						'http' => array(
							'ignore_errors' => true,
						),
					))
				);
				if ( $str ) {
					// Make sure the JSON only contains UTF-8 encoded text before decoding.
					$str = mb_convert_encoding( $str, 'UTF-8', 'UTF-8' );
					$json = json_decode( $str, true );
				}

				if ( ! $json || ! is_array( $json ) || ! count( $json ) ) {
					echo '<p>We were not able to read this file or find any recipes. Is it using the correct JSON format?</p>';
					echo '<p>JSON: ' . json_last_error_msg() . '</p><br/><br/>';
				} else {
					$importing = true;
					$import_type = isset( $_POST['wprm-import-type'] ) ? $_POST['wprm-import-type'] : 'create';

					delete_transient( 'wprm_import_recipes_json' );
					delete_transient( 'wprm_import_recipes_type' );
					$transient = json_encode( $json );
					set_transient( 'wprm_import_recipes_json', $transient, 60 * 60 * 24 );
					set_transient( 'wprm_import_recipes_type', $import_type, 60 * 60 * 24 );

					$recipes = count ( $json );
					$pages = ceil( $recipes / self::$import_limit );

					// Handle via AJAX.
					wp_localize_script( 'wprmp-admin', 'wprm_import_json', array(
						'pages' => $pages,
					));

					echo '<p>Importing ' . $recipes . ' recipes.</p>';
					$progress_bar_type = 'tools';
					include WPRM_DIR . 'templates/admin/progress-bar.php';
					echo '<p id="wprm-tools-finished">Import finished!. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';
					
					// foreach ( $json as $json_recipe ) {
					// 	self::import_json_recipe( $json_recipe );
					// }

					// echo '<p>Imported ' . count( $json ) . ' recipes. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';
				}
			} else {
				echo '<p>No file selected.</p>';
			}
		}
		
		if ( ! $importing ) {
			include WPRMP_DIR . 'templates/admin/import-json.php';
		}
	}

	/**
	 * Import recipes through AJAX.
	 *
	 * @since	5.3.0
	 */
	public static function ajax_import_json() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_import_access' ) ) ) {
				$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : false; // Input var okay.

				if ( false !== $page ) {
					$import_type = get_transient( 'wprm_import_recipes_type' );
					$transient = get_transient( 'wprm_import_recipes_json' );
					$json = json_decode( $transient, true );

					if ( $json && is_array( $json ) ) {
						$start = $page * self::$import_limit;
						$end = $start + self::$import_limit;

						for ( $i = $start; $i < $end; $i++ ) {
							if ( isset( $json[ $i ] ) ) {
								self::import_json_recipe( $json[ $i ], $import_type );
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
	 * Import a single recipe from JSON.
	 *
	 * @since	5.2.0
	 * @param	mixed $json_recipe  Recipe to import from JSON.
	 * @param	mixed $import_type  Import type to use.
	 */
	public static function import_json_recipe( $json_recipe, $import_type = 'create' ) {
		$old_recipe_id = false;
		if ( isset ( $json_recipe['id'] ) && $json_recipe['id'] ) {
			$old_recipe_id = intval( $json_recipe['id'] );
		}

		// Do not generate thumbnail sizes during JSON import.
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		$images_to_regenerate = array();

		// Check import type.
		$recipe_id = false;
		$create_new_otherwise = true;
		if ( 'create' !== $import_type ) {
			if ( 'edit' === substr( $import_type, 0, 4 ) ) {
				$create_new_otherwise = false;
			}

			// Find existing recipe.
			$import_type_parts = explode( '-', $import_type );
			if ( 'id' === $import_type_parts[1] ) {
				if ( $old_recipe_id && WPRM_POST_TYPE === get_post_type( $old_recipe_id ) ) {
					$recipe_id = $old_recipe_id;
				}
			} elseif ( 'slug' === $import_type_parts[1] ) {
				$json_recipe_slug = isset( $json_recipe['slug'] ) ? trim( $json_recipe['slug'] ) : false;

				if ( $json_recipe_slug ) {
					$existing_recipe = get_page_by_path( $json_recipe_slug, OBJECT, WPRM_POST_TYPE );

					if ( $existing_recipe ) {
						$recipe_id = $existing_recipe->ID;	
					}
				}
			}
		}

		// Maybe create new recipe.
		$created_new_recipe = false;
		if ( ! $recipe_id ) {
			if ( ! $create_new_otherwise ) {
				return;
			}

			// Create new recipe.
			$post = array(
				'post_type' => WPRM_POST_TYPE,
				'post_status' => 'draft',
			);

			// Try to reuse the ID if set.
			if ( $old_recipe_id ) {
				$post['import_id'] = $old_recipe_id;
			}

			$recipe_id = wp_insert_post( $post );
			$created_new_recipe = true;
		} else {
			// Found existing recipe, maybe ignore it.
			if ( 'ignore' === substr( $import_type, 0, 6 ) ) {
				return;
			}
		}

		// Import recipe images.
		if ( isset( $json_recipe['image_url'] ) && $json_recipe['image_url'] ) {
			$json_recipe['image_id'] = WPRM_Import_Helper::get_or_upload_attachment( $recipe_id, $json_recipe['image_url'] );
			$images_to_regenerate[] = $json_recipe['image_id'];
		}
		if ( isset( $json_recipe['pin_image_url'] ) && $json_recipe['pin_image_url'] ) {
			$json_recipe['pin_image_id'] = WPRM_Import_Helper::get_or_upload_attachment( $recipe_id, $json_recipe['pin_image_url'] );
			$images_to_regenerate[] = $json_recipe['pin_image_id'];
		}

		// Import instruction images.
		if ( isset( $json_recipe['instructions_flat'] ) ) {
			foreach ( $json_recipe['instructions_flat'] as $index => $instruction ) {
				if ( isset( $instruction['image_url'] ) && $instruction['image_url'] ) {
					$json_recipe['instructions_flat'][ $index ]['image'] = WPRM_Import_Helper::get_or_upload_attachment( $recipe_id, $instruction['image_url'] );
					$images_to_regenerate[] = $json_recipe['instructions_flat'][ $index ]['image'];
				} 
			}
		}

		// Import custom field images.
		if ( isset( $json_recipe['custom_fields'] ) ) {
			foreach ( $json_recipe['custom_fields'] as $index => $custom_field ) {
				if ( is_array( $custom_field ) && isset( $custom_field['url'] ) && $custom_field['url'] ) {
					$json_recipe['custom_fields'][ $index ]['id'] = WPRM_Import_Helper::get_or_upload_attachment( $recipe_id, $custom_field['url'] );
					$images_to_regenerate[] = $json_recipe['custom_fields'][ $index ]['id'];
				}
			}
		}

		// Sanitize and save recipe.
		$recipe = WPRM_Recipe_Sanitizer::sanitize( $json_recipe );
		WPRM_Recipe_Saver::update_recipe( $recipe_id, $recipe );

		// Maybe import user ratings.
		if ( isset( $json_recipe['user_ratings'] ) ) {
			foreach ( $json_recipe['user_ratings'] as $user_rating ) {
				$rating = array(
					'recipe_id' => $recipe_id, // This uses the new recipe ID.
					'date' => isset( $user_rating['date'] ) ? $user_rating['date'] : '',
					'user_id' => 0,
					'ip' => isset( $user_rating['ip'] ) ? $user_rating['ip'] : '',
					'rating' => isset( $user_rating['rating'] ) ? intval( $user_rating['rating'] ) : 0,
				);

				// Try to match old user ID, if set.
				$old_user_id = isset( $user_rating['user_id'] ) ? intval( $user_rating['user_id'] ) : 0;

				if ( 0 < $old_user_id ) {
					$found_by_email = false;
					if ( isset( $user_rating['user_email'] ) ) {
						$user = get_user_by( 'email', $user_rating['user_email'] );
						if ( $user ) {
							$rating['user_id'] = $user->ID;
							$found_by_email = true;
						}
					}

					// If user is not found by email, and we also don't have an IP, use a custom import IP.
					if ( ! $found_by_email && 'unknown' === $rating['ip'] ) {
						$rating['ip'] = 'wprm-import-json-unknown-user-' . $old_user_id;
					}
				}
	
				// Add saving of this rating to background queue.
				self::$background_process_user_ratings->push_to_queue( $rating );
			}

			// All ratings added, save and dispatch queue.
			self::$background_process_user_ratings->save()->dispatch();
		}

		// Maybe import parent post for recipe. Only when we created a new recipe.
		if ( $created_new_recipe && isset( $json_recipe['parent'] ) && $json_recipe['parent'] ) {
			$parent_post_id = self::import_json_parent( $json_recipe['parent'], $old_recipe_id, $recipe_id );

			if ( $parent_post_id && $parent_post_id !== $recipe_id ) {
				update_post_meta( $recipe_id, 'wprm_parent_post_id', $parent_post_id );

				// Get parent post featured image.
				$images_to_regenerate[] = get_post_thumbnail_id( $parent_post_id );
			}
		}

		// Regenerate thumbnail sizes.
		remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
		foreach( $images_to_regenerate as $image_id ) {
			self::$background_process_images->push_to_queue( $image_id );
		}
		self::$background_process_images->save()->dispatch();
	}

	/**
	 * Import a parent post from JSON.
	 *
	 * @since	7.1.0
	 * @param	mixed $json_parent  	Parent post to import from JSON.
	 * @param	int   $old_recipe_id  	Old ID of the imported recipe.
	 * @param	int   $new_recipe_id  	New ID of the imported recipe.
	 */
	public static function import_json_parent( $json_parent, $old_recipe_id, $new_recipe_id ) {
		// Default to new draft post.
		$parent = array(
			'post_type' => 'post',
			'post_status' => 'draft',
		);

		// Maybe update recipe ID in post content.
		$content = isset( $json_parent['post_content'] ) ? $json_parent['post_content'] : '';
		if ( $old_recipe_id && $old_recipe_id !== $new_recipe_id ) {
			// Gutenberg.
			$gutenberg_matches = array();
			$gutenberg_patern = '/<!--\s+wp:(wp-recipe-maker\/recipe)(\s+(\{.*?\}))?\s+(\/)?-->.*?<!--\s+\/wp:wp-recipe-maker\/recipe\s+(\/)?-->/mis';
			preg_match_all( $gutenberg_patern, $content, $matches );

			if ( isset( $matches[3] ) ) {
				foreach ( $matches[3] as $index => $block_attributes_json ) {
					if ( ! empty( $block_attributes_json ) ) {
						$attributes = json_decode( $block_attributes_json, true );

						if ( ! is_null( $attributes ) ) {
							if ( isset( $attributes['id'] ) && $old_recipe_id === intval( $attributes['id'] ) ) {
								$content = str_ireplace( $matches[0][ $index ], '<!-- wp:wp-recipe-maker/recipe {"id":' . $new_recipe_id . ',"updated":' . time() . '} -->[wprm-recipe id="' . $new_recipe_id . '"]<!-- /wp:wp-recipe-maker/recipe -->', $content );
							}
						}
					}
				}
			}

			// Classic Editor.
			$content = WPRM_Fallback_Recipe::replace_fallback_with_shortcode( $content );

			$classic_pattern = '/\[wprm-recipe\s.*?id=\"?\'?(\d+)\"?\'?.*?\]/mi';
			preg_match_all( $classic_pattern, $content, $classic_matches );

			if ( isset( $classic_matches[1] ) ) {
				foreach ( $classic_matches[1] as $index => $id ) {
					if ( $old_recipe_id === intval( $id ) ) {
						$content = str_ireplace( $classic_matches[0][ $index ], '[wprm-recipe id="' . $new_recipe_id . '"]', $content );
					}
				}
			}
		}
		$parent['post_content'] = $content;

		// Regular post fields.
		if ( isset( $json_parent['ID'] ) ) { $parent['import_id'] = $json_parent['ID']; }
		if ( isset( $json_parent['post_date'] ) ) { $parent['post_date'] = $json_parent['post_date']; }
		if ( isset( $json_parent['post_name'] ) ) { $parent['post_name'] = $json_parent['post_name']; }
		if ( isset( $json_parent['post_title'] ) ) { $parent['post_title'] = $json_parent['post_title']; }
		if ( isset( $json_parent['post_excerpt'] ) ) { $parent['post_excerpt'] = $json_parent['post_excerpt']; }
		if ( isset( $json_parent['post_status'] ) ) { $parent['post_status'] = $json_parent['post_status']; }
		if ( isset( $json_parent['post_type'] ) ) { $parent['post_type'] = $json_parent['post_type']; }

		// Insert parent post.
		$parent_post_id = wp_insert_post( $parent );

		// Featured Image.
		if ( isset( $json_parent['image_url'] ) && $json_parent['image_url'] ) {
			$image_id = WPRM_Import_Helper::get_or_upload_attachment( $parent_post_id, $json_parent['image_url'] );
			set_post_thumbnail( $parent_post_id, $image_id );
		}

		// Taxonomies.
		if ( isset( $json_parent['tags'] ) ) {
			foreach ( $json_parent['tags'] as $taxonomy => $terms ) {
				wp_set_object_terms( $parent_post_id, $terms, $taxonomy, false );
			}
		}

		return $parent_post_id;
	}
}

require_once( WPRM_DIR . 'vendor/wp-background-processing/classes/wp-async-request.php' );
require_once( WPRM_DIR . 'vendor/wp-background-processing/classes/wp-background-process.php' );

class WPRM_Import_User_Ratings_Background_Process extends WPRM_WP_Background_Process {

	protected $prefix = 'wprm';
	protected $action = 'import_json_user_ratings';

	protected function task( $rating ) {
		WPRM_Rating_Database::add_or_update_rating( $rating );
		return false; // False means task is finished.
	}
}

class WPRM_Import_Images_Background_Process extends WPRM_WP_Background_Process {

	protected $prefix = 'wprm';
	protected $action = 'import_json_regenerate_images';

	protected function task( $image_id ) {
		// Get image file path.
		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$file = wp_get_original_image_path( $image_id );
		} else {
			$file = get_attached_file( $image_id );
		}

		if ( $file ) {
			// Generate new metadata.
			$new_metadata = wp_generate_attachment_metadata( $image_id, $file );

			if ( $new_metadata ) {
				wp_update_attachment_metadata( $image_id, $new_metadata );
			}
		}

		return false; // False means task is finished.
	}
}

WPRMP_Import_JSON::init();
