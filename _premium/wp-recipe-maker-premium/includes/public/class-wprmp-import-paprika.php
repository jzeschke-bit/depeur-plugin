<?php
/**
 * Handle the import of recipes from Paprika.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the import of recipes from Paprika.
 *
 * @since      8.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import_Paprika {

	/**
	 *  Number of recipes to import at a time.
	 *
	 * @since    5.3.0
	 * @access   private
	 * @var      int $import_limit Number of recipes to import at a time.
	 */
	private static $import_limit = 1;

	/**
	 * Register actions and filters.
	 *
	 * @since    8.2.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_import_paprika', array( __CLASS__, 'ajax_import_paprika' ) );
	}

	/**
	 * Add the JSON import page.
	 *
	 * @since	8.2.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'WPRM Import from Paprika', 'wp-recipe-maker' ), __( 'WPRM Import from Paprika', 'wp-recipe-maker' ), WPRM_Settings::get( 'features_import_access' ), 'wprm_import_paprika', array( __CLASS__, 'import_paprika_page_template' ) );
	}

	/**
	 * Get the template for the edit saved collection page.
	 *
	 * @since	8.2.0
	 */
	public static function import_paprika_page_template() {
		$importing = false;

		if ( isset( $_POST['wprm_import_paprika'] ) && wp_verify_nonce( $_POST['wprm_import_paprika'], 'wprm_import_paprika' ) ) { // Input var okay.
			$file_system = WP_Filesystem();

			if ( false === $file_system ) {
				echo '<p>Problem accessing file system.</p>';
			} else {
				$filename = $_FILES['paprika']['tmp_name'];

				if ( $filename ) {
					// Create directory to extract zip file to.
					$upload_dir = wp_upload_dir();
					$dir = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . 'wprm-import-paprika' );
					wp_mkdir_p( $dir );

					// Clean directory
					$files = glob( $dir . '*' );
					foreach( $files as $file ) {
						if( is_file( $file ) ) unlink( $file );
					}

					// Move to directory.
					$zip_file = $dir . 'paprika.zip';
					$move = move_uploaded_file( $filename, $zip_file );

					// Unzip to same directory.
					$zip = new ZipArchive();

					if ( true === $zip->open( $zip_file ) ) {
						$zip->extractTo( $dir );
						$zip->close();
					} else {
						echo '<p>Problem unzipping the file.</p>';
					}

					// Array to store recipes found.
					$paprika_recipes = array();

					// Unzip any .paprikarecipe files inside.
					$files = glob( $dir . '*.paprikarecipe' );

					foreach( $files as $file ) {
						if( is_file( $file ) ) {
							$str = file_get_contents(
								$file,
								false,
								stream_context_create( array(
									'http' => array(
										'ignore_errors' => true,
									),
								))
							);
							if ( $str ) {
								$json_raw = gzdecode( $str );

								if ( $json_raw ) {
									$json = json_decode( $json_raw, true );

									if ( $json && is_array( $json ) ) {
										$paprika_recipes[] = $json;
									}
								}
							}
						};
					}

					if ( ! count( $paprika_recipes ) ) {
						echo '<p>We were not able to read this file or find any recipes.</p>';
					} else {
						$importing = true;

						delete_transient( 'wprm_import_paprika_recipes' );
						$transient = json_encode( $paprika_recipes );
						set_transient( 'wprm_import_paprika_recipes', $transient, 60 * 60 * 24 );

						$recipes = count ( $paprika_recipes );
						$pages = ceil( $recipes / self::$import_limit );

						// Handle via AJAX.
						wp_localize_script( 'wprmp-admin', 'wprm_import_paprika', array(
							'pages' => $pages,
						));

						echo '<p>Importing ' . $recipes . ' recipes.</p>';
						$progress_bar_type = 'tools';
						include WPRM_DIR . 'templates/admin/progress-bar.php';
						echo '<p id="wprm-tools-finished">Import finished!. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';

						// Debugging only.
						// foreach ( $paprika_recipes as $paprika_recipe ) {
						// 	self::import_paprika_recipe( $paprika_recipe );
						// }
						// echo '<p>Imported ' . count( $json ) . ' recipes. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';
					}
				} else {
					echo '<p>No file selected.</p>';
				}
			}
		}
		
		if ( ! $importing ) {
			include WPRMP_DIR . 'templates/admin/import-paprika.php';
		}
	}

	/**
	 * Import recipes through AJAX.
	 *
	 * @since	8.2.0
	 */
	public static function ajax_import_paprika() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_import_access' ) ) ) {
				$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : false; // Input var okay.

				if ( false !== $page ) {
					$transient = get_transient( 'wprm_import_paprika_recipes' );
					$paprika_recipes = json_decode( $transient, true );

					if ( $paprika_recipes && is_array( $paprika_recipes ) ) {
						$start = $page * self::$import_limit;
						$end = $start + self::$import_limit;

						for ( $i = $start; $i < $end; $i++ ) {
							if ( isset( $paprika_recipes[ $i ] ) ) {
								self::import_paprika_recipe( $paprika_recipes[ $i ] );
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
	 * Import a single recipe from Paprika.
	 *
	 * @since	8.2.0
	 * @param	mixed $paprika_recipe  Recipe to import from Paprika.
	 */
	public static function import_paprika_recipe( $paprika_recipe ) {
		$recipe = array();

		// Name.
		$recipe['name'] = $paprika_recipe['name'];

		// Recipe image.
		if ( $paprika_recipe['photo_data'] ) {
			$image_id = self::save_base64_image( $paprika_recipe['photo_data'], $recipe['name'] );

			if ( $image_id ) {
				$recipe['image_id'] = $image_id;
			}
		}

		// Other images.
		$photos = array();
		foreach ( $paprika_recipe['photos'] as $paprika_photo ) {
			$image_id = self::save_base64_image( $paprika_photo['data'], $recipe['name'] . '-' . $paprika_photo['name'] );

			if ( $image_id ) {
				$photos[ 'photo-' . $paprika_photo['name'] ] = array(
					'id' => $image_id,
					'img' => wp_get_attachment_image( $image_id, 'full' ),
				);
			}
		}

		// Rich text fields.
		$recipe['summary'] = self::richify( $paprika_recipe['description'], $photos );
		$recipe['notes'] = self::richify( $paprika_recipe['notes'], $photos );

		// Servings.
		$match = preg_match( '/^\s*\d+/', $paprika_recipe['servings'], $servings_array );
		if ( 1 === $match ) {
			$servings = str_replace( ' ','', $servings_array[0] );
		} else {
			$servings = '';
		}

		$servings_unit = preg_replace( '/^\s*\d+\s*/', '', $paprika_recipe['servings'] );

		$recipe['servings'] = $servings;
		$recipe['servings_unit'] = $servings_unit;

		// Times.
		if ( $paprika_recipe['prep_time'] ) {
			$prep_time = self::read_time( $paprika_recipe['prep_time'] );
			if ( $prep_time ) { $recipe['prep_time'] = $prep_time; }
		}
		if ( $paprika_recipe['cook_time'] ) {
			$cook_time = self::read_time( $paprika_recipe['cook_time'] );
			if ( $cook_time ) { $recipe['cook_time'] = $cook_time; }
		}
		if ( $paprika_recipe['total_time'] ) {
			$total_time = self::read_time( $paprika_recipe['total_time'] );
			if ( $total_time ) { $recipe['total_time'] = $total_time; }
		}

		// Ingredients.
		if ( $paprika_recipe['ingredients'] ) {
			$paprika_ingredients = self::parse_recipe_component_list( $paprika_recipe['ingredients'] );

			$ingredients = array();
			foreach ( $paprika_ingredients as $paprika_group ) {
				$group = array(
					'name' => $paprika_group['name'],
					'ingredients' => array(),
				);

				foreach ( $paprika_group['items'] as $paprika_item ) {
					$text = trim( $paprika_item );

					if ( ! empty( $text ) ) {
						$group['ingredients'][] = array(
							'raw' => self::derichify( $text ),
						);
					}
				}

				$ingredients[] = $group;
			}
			$recipe['ingredients'] = $ingredients;
		}

		// Instructions.
		if ( $paprika_recipe['directions'] ) {
			$paprika_instructions = self::parse_recipe_component_list( $paprika_recipe['directions'] );

			$instructions = array();
			foreach ( $paprika_instructions as $paprika_group ) {
				$group = array(
					'name' => $paprika_group['name'],
					'instructions' => array(),
				);
	
				foreach ( $paprika_group['items'] as $paprika_item ) {
					$text = trim( $paprika_item );
	
					// Prevent empty tag (because of linked image, for example).
					if ( '' === self::derichify( $text ) ) {
						$text = '';
					}

					// Find any photos.
					preg_match_all( '/\[photo:(.+)\]/mi', $paprika_item, $photos_found );

					foreach ( $photos_found[1] as $photo_found ) {
						if ( isset( $photos[ 'photo-' . $photo_found ] ) ) {
							$image_id = $photos[ 'photo-' . $photo_found ]['id'];
							$text = preg_replace( '/\[photo:(.*)\]/mi', '', $text );

							$prev_instruction_index = count( $group['instructions'] ) - 1;
							if ( ! $text && 0 <= $prev_instruction_index && ! $group['instructions'][ $prev_instruction_index ]['image'] ) {
								$group['instructions'][ $prev_instruction_index ]['image'] = $image_id;
							} else {
								$group['instructions'][] = array(
									'text' => $text,
									'image' => $image_id,
								);
								$text = ''; // Only add same text once.
							}
						}
					}
	
					if ( ! empty( $text ) ) {
						$group['instructions'][] = array(
							'text' => $text,
						);
					}
				}
	
				$instructions[] = $group;
			}
			$recipe['instructions'] = $instructions;
		}

		// Sanitize and save recipe.
		$sanitized_recipe = WPRM_Recipe_Sanitizer::sanitize( $recipe );
		WPRM_Recipe_Saver::create_recipe( $sanitized_recipe );
	}

	/**
	 * Richify text by adding links and styling.
	 *
	 * @since    8.2.0
	 * @param	 mixed $text	Text to richify.
	 * @param	 array $photos	Photos to replace with.
	 */
	private static function richify( $text, $photos = array() ) {
		$text = preg_replace( '/(^|\s)\*\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*\*(\W|$)/', '\\1<strong>\\2</strong>\\3', $text );
		$text = preg_replace( '/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<em>\\2</em>\\3', $text );
		$text = preg_replace( '/\[([^\[]+)\]\((.*)\)/', '<a href="\\2" target="_blank">\\1</a>', $text );
		$text = preg_replace( '/\[recipe:(.*)\]/mi', '\\1', $text );

		if ( $photos ) {
			preg_match_all( '/\[photo:(.+)\]/mi', $text, $photos_found );

			foreach ( $photos_found[1] as $photo_found ) {
				if ( isset( $photos[ 'photo-' . $photo_found ] ) ) {
					$text = str_ireplace( '[photo:' . $photo_found . ']', $photos[ 'photo-' . $photo_found ]['img'], $text );
				}
			}
		}

		return $text;
	}

	/**
	 * Derichify text by removing links and styling.
	 *
	 * @since    8.2.0
	 * @param	 mixed $text	Text to derichify.
	 */
	private static function derichify( $text ) {
		$text = preg_replace( '/(^|\s)\*\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*\*(\W|$)/', '\\1\\2\\3', $text );
		$text = preg_replace( '/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1\\2\\3', $text );
		$text = preg_replace( '/\[([^\[]+)\]\((.*)\)/', '\\1', $text );
		$text = preg_replace( '/\[recipe:(.*)\]/mi', '\\1', $text );
		$text = preg_replace( '/\[photo:(.*)\]/mi', '', $text );

		return $text;
	}

	/**
	 * Try to read time.
	 *
	 * @since    8.2.0
	 * @param	 mixed $time Time to parse.
	 */
	private static function read_time( $time ) {
		$time = str_replace( 'hrs', 'hour', $time );
		$time = str_replace( 'hr', 'hour', $time );
		$time = str_replace( 'minutes', 'min', $time );
		$time = str_replace( 'mins', 'min', $time );

		if ( is_numeric( $time ) ) {
			$time = "{$time} minutes";
		}

		return max( 0, strtotime( $time, 0 ) ) / 60;
	}

	/**
	 * Save base64 encoded image to the media library.
	 * Source: https://gist.github.com/cyberwani/ad5452b040001878d692c3165836ebff
	 *
	 * @since    8.2.0
	 * @param	 mixed $base64_img	Image to save.
	 * @param	 mixed $title		Title for the image.
	 */
	private static function save_base64_image( $base64_img, $title ) {
		$upload_dir  = wp_upload_dir();
		$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
	
		$img             = str_replace( 'data:image/jpeg;base64,', '', $base64_img );
		$img             = str_replace( ' ', '+', $img );
		$decoded         = base64_decode( $img );

		if ( $decoded ) {
			$filename        = $title . '.jpeg';
			$file_type       = 'image/jpeg';
			$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
		
			// Save the image in the uploads directory.
			$upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded );
		
			$attachment = array(
				'post_mime_type' => $file_type,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
			);
		
			$attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $hashed_filename );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			
			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['path'] . '/' . $hashed_filename );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			
			return $attach_id;
		}
	
		return false;
	}

	/**
	 * Blob to array.
	 *
	 * @since	8.2.0
	 * @param	mixed $component Component to parse.
	 */
	private static function parse_recipe_component_list( $component ) {
		$component_list = array();
		$component_group = array(
			'name' => '',
			'items' => array(),
		);

		$bits = explode( PHP_EOL, $component );
		foreach ( $bits as $bit ) {

			$test_bit = trim( $bit );
			if ( empty( $test_bit ) ) {
				continue;
			}
			if ( WPRM_Import_Helper::is_heading( $bit ) ) {
				$component_list[] = $component_group;

				$component_group = array(
					'name' => strip_tags( trim( $bit ) ),
					'items' => array(),
				);
			} else {
				$component_group['items'][] = trim( $bit );
			}
		}

		$component_list[] = $component_group;

		return $component_list;
	}
}
WPRMP_Import_Paprika::init();
