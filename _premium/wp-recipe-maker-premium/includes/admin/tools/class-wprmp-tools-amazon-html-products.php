<?php
/**
 * Responsible for handling the convert Amazon HTML to products tool.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin/tools
 */

/**
 * Responsible for handling the convert Amazon HTML to products tool.
 *
 * @since      9.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin/tools
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRM_Tools_Amazon_Html_Products {

	/**
	 * Register actions and filters.
	 *
	 * @since	9.1.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
		add_action( 'wp_ajax_wprm_amazon_converting_html_to_products', array( __CLASS__, 'ajax_amazon_converting_html' ) );
	}

	/**
	 * Add the tools submenu to the WPRM menu.
	 *
	 * @since	9.1.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'Convert Amazon HTML to Products', 'wp-recipe-maker-premium' ), __( 'Convert Amazon HTML to Products', 'wp-recipe-maker-premium' ), WPRM_Settings::get( 'features_tools_access' ), 'wprm_amazon_html_products', array( __CLASS__, 'amazon_converting_html' ) );
	}

	/**
	 * Get the template for the converting HTML page.
	 *
	 * @since	9.1.0
	 */
	public static function amazon_converting_html() {
		// Get expiring products.
		$args = array(
			'taxonomy' => 'wprm_equipment',
			'hide_empty' => false,
			'order' => 'ASC',
			'orderby' => 'term_id',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => 'wprmp_equipment_affiliate_html',
					'compare' => '!=',
					'value' => '',
				),
			),
		);

		$query = new WP_Term_Query( $args );
		$posts = $query->terms ? array_values( $query->terms ) : array();

		// Only when debugging.
		if ( WPRM_Tools_Manager::$debugging ) {
			$result = self::amazon_convert_html( $posts ); // Input var okay.
			WPRM_Debug::log( $result );
			die();
		}

		// Handle via AJAX.
		wp_localize_script( 'wprm-admin', 'wprm_tools', array(
			'action' => 'amazon_converting_html_to_products',
			'posts' => $posts,
			'args' => array(),
		));

		require_once( WPRMP_DIR . 'templates/admin/menu/tools/amazon-conversion.php' );
	}

	/**
	 * Converting HTML through AJAX.
	 *
	 * @since	9.1.0
	 */
	public static function ajax_amazon_converting_html() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( WPRM_Settings::get( 'features_tools_access' ) ) ) {
				$posts = isset( $_POST['posts'] ) ? json_decode( wp_unslash( $_POST['posts'] ) ) : array(); // Input var okay.

				$posts_left = array();
				$posts_processed = array();

				if ( count( $posts ) > 0 ) {
					$posts_left = $posts;
					$posts_processed = array_map( 'intval', array_splice( $posts_left, 0, 10 ) );

					$result = self::amazon_convert_html( $posts_processed );

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( array(
							'redirect' => add_query_arg( array( 'sub' => 'advanced' ), admin_url( 'admin.php?page=wprm_tools' ) ),
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
	 * Convert affiliate HTML in terms to Amazon products.
	 *
	 * @since	9.1.0
	 * @param	array $terms IDs of terms to convert.
	 */
	public static function amazon_convert_html( $terms ) {
		$asins_found = array();

		foreach ( $terms as $term_id ) {
			$affiliate_html = get_term_meta( $term_id, 'wprmp_equipment_affiliate_html', true );
			
			$asin_found = false;
			if ( $affiliate_html ) {
				// Find ASIN in iframe.
				$regex = '/[&?]asins=([A-Z0-9]{10})/s';
				preg_match( $regex, $affiliate_html, $matches );

				if ( ! empty( $matches ) && isset( $matches[1] ) ) {
					$asin_found = $matches[1];
				}

				if ( ! $asin_found ) {
					// Find ASIN in link.
					$regex = '/(?:[\/dp\/]|$)([A-Z0-9]{10})/s';
					preg_match( $regex, $affiliate_html, $matches );

					if ( ! empty( $matches ) && isset( $matches[1] ) ) {
						$asin_found = $matches[1];
					}
				}
			}

			if ( $asin_found ) {
				$asins_found[ $term_id ] = $asin_found;
			}
		}

		// Bundle together for API call.
		if ( $asins_found ) {
			foreach ( $asins_found as $term_id => $asin ) {
				// Set NoAPI link temporarily, to make sure a link is present until Amazon Queue is processed.
				$link = WPRMP_Amazon::get_noapi_affiliate_link( $asin );

				if ( $link ) {
					update_term_meta( $term_id, 'wprmp_equipment_link', $link );
				}

				update_term_meta( $term_id, 'wprmp_equipment_affiliate_html', '' );
				update_term_meta( $term_id, 'wprmp_amazon_asin', $asin );
				update_term_meta( $term_id, 'wprmp_amazon_updated', 0 );
			}

			// Update products through Amazon API and wait to prevent API overloading.
			WPRMP_Amazon_Queue::update_terms( array_keys( $asins_found ) );
			sleep( 1 );
		}
	}
}

WPRM_Tools_Amazon_Html_Products::init();
