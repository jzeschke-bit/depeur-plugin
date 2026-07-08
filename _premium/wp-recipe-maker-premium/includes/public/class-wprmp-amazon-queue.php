<?php
/**
	* Responsible for handling the Amazon queue.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for handling the Amazon queue.
 *
 * @since      9.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */

class WPRMP_Amazon_Queue {

	private static $background_process = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    9.1.0
	 */
	public static function init() {
		self::$background_process = new WPRM_Amazon_Background_Process();

		add_action( 'wprm_hourly_cron', array( __CLASS__, 'check_for_expiring_products' ) );
	}

	/**
	 * Check for expering Amazon products that need to be updated.
	 *
	 * @since    9.1.0
	 */
	public static function check_for_expiring_products() {
		// Don't queue updates if no Amazon API credentials are configured.
		if ( ! class_exists( 'WPRMP_Amazon_Api_Factory' ) || ! WPRMP_Amazon_Api_Factory::has_api_credentials() ) {
			return;
		}

		// Check when the last error was.
		$last_error = get_option( 'wprm_amazon_queue_error' );

		if ( $last_error && ( $last_error + 60 * 60 * 8 ) > time() ) {
			// Error occurred less than 8 hours ago, don't add more products to the queue to prevent overloading.
			return;
		}

		// Get expiring products.
		$terms = self::get_expiring_products();

		// Group terms per 10 to add to queue.
		$grouped_terms = array_chunk( $terms, 10 );

		if ( $grouped_terms ) {
			foreach ( $grouped_terms as $terms ) {
				self::$background_process->push_to_queue( $terms );
			}
			self::$background_process->save()->dispatch();
		}
	}

	/**
	 * Get expiring products that need an update.
	 *
	 * @since    9.1.0
	 */
	public static function get_expiring_products() {
		// Get expiring products.
		$args = array(
			'taxonomy' => 'wprm_equipment',
			'hide_empty' => false,
			'order' => 'ASC',
			'orderby' => 'meta_value_num',
			'meta_key' => 'wprmp_amazon_updated',
			'meta_query' => array(
				'key' => 'wprmp_amazon_updated',
				'value' => ( time() * 1000 ) - ( 1000 * 60 * 60 * 22 ), // Updated 22 hours or longer ago. In milliseconds, like JS Date.now().
				'compare' => '<=',
				'type' => 'NUMERIC',
			),
			'fields' => 'ids',
		);

		$query = new WP_Term_Query( $args );
		$terms = $query->terms ? array_values( $query->terms ) : array();

		return $terms;
	}

	/**
	 * Terms to update.
	 *
	 * @since    9.1.0
	 */
	public static function update_terms( $terms ) {
		$asins = array();

		foreach ( $terms as $term_id ) {
			$asin = get_term_meta( $term_id, 'wprmp_amazon_asin', true );

			if ( $asin ) {
				$asins[ $term_id ] = $asin;
			}
		}

		// Got the ASINs, get the products.
		if ( $asins ) {
			$products = WPRMP_Amazon::get_products( array_values( $asins ) );
			
			if ( ! $products['error'] ) {
				foreach ( $asins as $term_id => $asin ) {
					// Default values.
					$link = WPRMP_Amazon::get_noapi_affiliate_link( $asin );
					$name = '';
					$image = '';
					$image_width = 0;
					$image_height = 0;
					$updated = time() * 1000; // Use milliseconds, like JS Date.now(); 

					// Check if product data was found.
					$product = isset( $products['products'][ $asin ] ) ? $products['products'][ $asin ] : false;

					if ( $product ) {
						$link = $product['link'];
						$name = $product['name'];
						$image = $product['image'];
						$image_width = isset( $product['image_width'] ) ? intval( $product['image_width'] ) : 0;
						$image_height = isset( $product['image_height'] ) ? intval( $product['image_height'] ) : 0;
						
						// Store availability status if available.
						$availability_type = isset( $product['availability_type'] ) ? $product['availability_type'] : '';
						
						if ( $availability_type ) {
							// Always store just the type as a string.
							update_term_meta( $term_id, 'wprmp_amazon_status', $availability_type );
						} else {
							// Product found but no availability data - store as UNKNOWN.
							update_term_meta( $term_id, 'wprmp_amazon_status', 'UNKNOWN' );
						}
					} else {
						$name = __( 'ASIN not found', 'wp-recipe-maker-premium' );
						$image_width = 0;
						$image_height = 0;
						// Product not found - store as NOT_FOUND.
						update_term_meta( $term_id, 'wprmp_amazon_status', 'NOT_FOUND' );
					}
					
					update_term_meta( $term_id, 'wprmp_equipment_link', $link );
					update_term_meta( $term_id, 'wprmp_amazon_name', $name );
					update_term_meta( $term_id, 'wprmp_amazon_image', $image );
					if ( $image_width > 0 ) {
						update_term_meta( $term_id, 'wprmp_amazon_image_width', $image_width );
					} else {
						delete_term_meta( $term_id, 'wprmp_amazon_image_width' );
					}
					if ( $image_height > 0 ) {
						update_term_meta( $term_id, 'wprmp_amazon_image_height', $image_height );
					} else {
						delete_term_meta( $term_id, 'wprmp_amazon_image_height' );
					}
					update_term_meta( $term_id, 'wprmp_amazon_updated', $updated );
				}
			} else {
				// Error occurred during get products API call.
				// Check if it's a rate limit error.
				if ( isset( $products['error']['code'] ) && 'TooManyRequests' === $products['error']['code'] ) {
					// Return special value to indicate rate limit error.
					return 'rate_limit_error';
				}
				return false;
			}
		}

		return true; // Update was successful.
	}
}

require_once( WPRM_DIR . 'vendor/wp-background-processing/classes/wp-async-request.php' );
require_once( WPRM_DIR . 'vendor/wp-background-processing/classes/wp-background-process.php' );

class WPRM_Amazon_Background_Process extends WPRM_WP_Background_Process {

	protected $prefix = 'wprm';
	protected $action = 'amazon_queue_update_products';

	protected function task( $item ) {
		// Skip processing if no Amazon API credentials are configured.
		if ( ! class_exists( 'WPRMP_Amazon_Api_Factory' ) || ! WPRMP_Amazon_Api_Factory::has_api_credentials() ) {
			$this->cancel();
			return false;
		}

		$updated = WPRMP_Amazon_Queue::update_terms( $item );

		// Check for rate limit error.
		if ( 'rate_limit_error' === $updated ) {
			// Rate limit error occurred, stop processing and mark error.
			update_option( 'wprm_amazon_queue_error', time() );
			// Cancel the process to stop further API calls.
			$this->cancel();
			return false;
		}

		// Slow down the queue to prevent overloading of API - increased to 60 seconds.
		sleep(60);

		if ( ! $updated ) {
			update_option( 'wprm_amazon_queue_error', time() );
		}

		return false; // False means task is finished. Always finish so that items don't stay in the queue.
	}
}

WPRMP_Amazon_Queue::init();