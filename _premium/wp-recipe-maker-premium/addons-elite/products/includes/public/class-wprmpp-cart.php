<?php
/**
 * Handle the product cart functionality.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Cart {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_action( 'wp_ajax_wprmp_add_product_to_cart', array( __CLASS__, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_wprmp_add_product_to_cart', array( __CLASS__, 'add_to_cart' ) );
		add_action( 'wp_ajax_wprmp_get_cart_data', array( __CLASS__, 'get_cart_data' ) );
		add_action( 'wp_ajax_nopriv_wprmp_get_cart_data', array( __CLASS__, 'get_cart_data' ) );
	}

	/**
	 * Handle AJAX add to cart request.
	 *
	 * @since	10.2.0
	 */
	public static function add_to_cart() {
		// Check if WooCommerce is available
		if ( ! WPRMPP_Integrations::is_available() ) {
			wp_send_json_error( array( 'message' => 'WooCommerce not available' ) );
		}

		// Get and validate request data
		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$quantity = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : 1;
		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;

		// Validate product ID
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
		}

		// Validate quantity
		if ( $quantity <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid quantity' ) );
		}

		// Get product
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'Product not found' ) );
		}

		// Check if product is purchasable
		if ( ! $product->is_purchasable() ) {
			wp_send_json_error( array( 'message' => 'Product not purchasable' ) );
		}

		// Handle variation products
		if ( $variation_id > 0 ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || $variation->get_parent_id() !== $product_id ) {
				wp_send_json_error( array( 'message' => 'Invalid variation' ) );
			}
			$product = $variation;
		}

		// Add to cart
		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );

		if ( $cart_item_key ) {
			// Get updated cart count
			$cart_count = WC()->cart->get_cart_contents_count();
			
			wp_send_json_success( array(
				'message' => 'Product added to cart',
				'cart_count' => $cart_count,
				'cart_item_key' => $cart_item_key,
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to add product to cart' ) );
		}
	}

	/**
	 * Handle AJAX get cart data request.
	 *
	 * @since	10.2.0
	 */
	public static function get_cart_data() {
		// Check if WooCommerce is available
		if ( ! WPRMPP_Integrations::is_available() ) {
			wp_send_json_error( array( 'message' => 'WooCommerce not available' ) );
		}

		$cart = WC()->cart;
		$item_count = $cart->get_cart_contents_count();
		$total = $cart->get_cart_total();
		$cart_url = wc_get_cart_url();

		// Strip HTML tags and decode entities from total
		$total_clean = html_entity_decode( wp_strip_all_tags( $total ), ENT_QUOTES, 'UTF-8' );

		wp_send_json_success( array(
			'item_count' => $item_count,
			'total' => $total_clean,
			'cart_url' => $cart_url
		) );
	}
}

WPRMPP_Cart::init();
