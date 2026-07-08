<?php
/**
 * Handle the import of taxonomy terms from JSON.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the import of taxonomy terms from JSON.
 *
 * @since      6.8.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import_Taxonomies {

	/**
	 *  Number of terms to import at a time.
	 *
	 * @since    6.8.0
	 * @access   private
	 * @var      int $import_limit Number of terms to import at a time.
	 */
	private static $import_limit = 3;

	/**
	 * Register actions and filters.
	 *
	 * @since    6.8.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_import_taxonomies', array( __CLASS__, 'ajax_import_taxonomies' ) );
	}

	/**
	 * Add the JSON import page.
	 *
	 * @since	6.8.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'WPRM Import from JSON', 'wp-recipe-maker' ), __( 'WPRM Import from JSON', 'wp-recipe-maker' ), WPRM_Settings::get( 'features_import_access' ), 'wprm_import_taxonomies', array( __CLASS__, 'import_taxonomies_page_template' ) );
	}

	/**
	 * Get the template for the import taxonomies page.
	 *
	 * @since	6.8.0
	 */
	public static function import_taxonomies_page_template() {
		$importing = false;

		if ( isset( $_POST['wprm_import_taxonomies'] ) && wp_verify_nonce( $_POST['wprm_import_taxonomies'], 'wprm_import_taxonomies' ) ) { // Input var okay.
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
					$json = json_decode( $str, true );
				}

				if ( ! $json || ! is_array( $json ) || ! count( $json ) ) {
					echo '<p>We were not able to read this file or find any recipes. Is it using the correct JSON format?</p>';
				} else {
					$importing = true;
					delete_transient( 'wprm_import_taxonomies_json' );
					$transient = json_encode( $json );
					set_transient( 'wprm_import_taxonomies_json', $transient, 60 * 60 * 24 );

					$terms = count ( $json );
					$pages = ceil( $terms / self::$import_limit );

					// Handle via AJAX.
					wp_localize_script( 'wprmp-admin', 'wprm_import_taxonomies', array(
						'pages' => $pages,
					));

					echo '<p>Importing ' . $terms . ' terms.</p>';
					$progress_bar_type = 'tools';
					include WPRM_DIR . 'templates/admin/progress-bar.php';
					echo '<p id="wprm-tools-finished">Import finished!. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';
					
					// foreach ( $json as $json_recipe ) {
					// 	self::import_json_recipe( $json_recipe );
					// }

					// echo '<p>Imported ' . count( $json ) . ' terms. <a href="' . admin_url( 'admin.php?page=wprm_manage' ) . '">View on the manage page</a>.</p>';
				}
			} else {
				echo '<p>No file selected.</p>';
			}
		}
		
		if ( ! $importing ) {
			include WPRMP_DIR . 'templates/admin/import-taxonomies.php';
		}
	}

	/**
	 * Import taxonomy terms through AJAX.
	 *
	 * @since	6.8.0
	 */
	public static function ajax_import_taxonomies() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_import_access' ) ) ) {
				$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : false; // Input var okay.

				if ( false !== $page ) {
					$transient = get_transient( 'wprm_import_taxonomies_json' );
					$json = json_decode( $transient, true );

					if ( $json && is_array( $json ) ) {
						$start = $page * self::$import_limit;
						$end = $start + self::$import_limit;

						for ( $i = $start; $i < $end; $i++ ) {
							if ( isset( $json[ $i ] ) ) {
								self::import_json_term( $json[ $i ] );
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
	 * Import a single term from JSON.
	 *
	 * @since	6.8.0
	 * @param	mixed $json  Term to import from JSON.
	 */
	public static function import_json_term( $json ) {
		// Only for WPRM taxonomies.
		if ( 'wprm_' !== substr( $json['taxonomy'], 0, 5 ) ) {
			return;
		}

		// Get or create term by name.
		// Sanitize name before lookup.
		$name = WPRM_Recipe_Sanitizer::sanitize_html( $json['name'] );

		// Find or create term.
		$term = term_exists( $name, $json['taxonomy'] );

		if ( 0 === $term || null === $term ) {
			$term = wp_insert_term( $name, $json['taxonomy'] );
		}

		if ( is_wp_error( $term ) ) {
			if ( isset( $term->error_data['term_exists'] ) ) {
				$term_id = $term->error_data['term_exists'];
			} else {
				$term_id = 0;
			}
		} else {
			$term_id = $term['term_id'];
		}

		$term_id = intval( $term_id );

		// Created or found a term, add metadata to it.
		if ( $term_id ) {
			// Optionally sideload any images.
			foreach ( $json['meta'] as $key => $value ) {
				if ( 'image_id' === substr( $key, -8 ) || 'wpupg_custom_image' === $key ) {

					// Check if there's a _url helper.
					if ( isset( $json['meta'][ $key . '_url' ] ) ) {
						$image_url = $json['meta'][ $key . '_url' ];
						$image_id = WPRM_Import_Helper::get_or_upload_attachment( 0, $image_url );

						// Found or sideloaded image, replace or unset image id.
						if ( $image_id ) {
							$json['meta'][ $key ] = $image_id;
						} else {
							unset( $json['meta'][ $key ] );
						}

						// Image URL should not actually get stored as meta.
						unset( $json['meta'][ $key . '_url' ] );
					}

				}
			}

			// Update term meta.
			foreach ( $json['meta'] as $key => $value ) {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}
}
WPRMP_Import_Taxonomies::init();
