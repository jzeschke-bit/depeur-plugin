<?php
/**
 * Handles products.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 */

/**
 * Handles products.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Product_Manager {
	/**
	 * Search for products by keyword.
	 *
	 * @since	10.2.0
	 */
	public static function search( $search ) {
		$products = array();

		// WooCommerce only at the moment.
		if ( class_exists( 'WC_Data_Store' ) ) {
			$data_store = WC_Data_Store::load( 'product' );
			$search_results = $data_store->search_products( $search );
		
			foreach( $search_results as $result ) {
				$product = wc_get_product( $result );
				
				if ( $product ) {
					$image_id = $product->get_image_id();
					$image_url = '';
					if ( $image_id ) {
						$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					}
					
					$products[] = array(
						'id' => $product->get_id(),
						'text' => $product->get_formatted_name(),
						'name' => $product->get_name(),
						'has_variations' => $product->is_type( 'variable' ),
						'image_url' => $image_url,
					);
				}
			}
		}

		return $products;
	}

	/**
	 * Get product by ID.
	 *
	 * @since	10.2.0
	 */
	public static function get( $id, $plugin = 'woocommerce' ) {
		$product = false;

		// WooCommerce only at the moment.
		if ( 'woocommerce' === $plugin && class_exists( 'WC_Data_Store' ) ) {
			$wc_product = wc_get_product( $id );
		
			if ( $wc_product ) {
				$image_id = $wc_product->get_image_id();
				$image_url = '';
				if ( $image_id ) {
					$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
				}
				
				$product = array(
					'plugin' => 'woocommerce',
					'id' => $wc_product->get_id(),
					'name' => $wc_product->get_name(),
					'url' => get_permalink( $id ),
					'has_variations' => $wc_product->is_type( 'variable' ),
					'image_url' => $image_url,
					'price' => $wc_product->get_price_html(),
				);
			}
		}

		return $product;
	}

	/**
	 * Get product variations for a variable product.
	 *
	 * @since	10.2.0
	 */
	public static function get_variations( $product_id ) {
		$variations = array();

		if ( class_exists( 'WC_Data_Store' ) ) {
			$wc_product = wc_get_product( $product_id );
			
			if ( $wc_product && $wc_product->is_type( 'variable' ) ) {
				$variation_ids = $wc_product->get_children();
				
				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					
					if ( $variation && $variation->is_purchasable() ) {
						$variation_name = $variation->get_name();
						
						// Get variation image
						$image_id = $variation->get_image_id();
						$image_url = '';
						if ( $image_id ) {
							$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
						}
						
						// Get variation attributes for display
						$attributes = $variation->get_variation_attributes();
						$attribute_strings = array();
						
						foreach ( $attributes as $attribute_name => $attribute_value ) {
							if ( ! empty( $attribute_value ) ) {
								$taxonomy = str_replace( 'attribute_', '', $attribute_name );
								$taxonomy = str_replace( 'pa_', '', $taxonomy );
								$attribute_strings[] = ucfirst( $taxonomy ) . ': ' . $attribute_value;
							}
						}
						
						$display_name = $variation_name;
						if ( ! empty( $attribute_strings ) ) {
							$display_name .= ' (' . implode( ', ', $attribute_strings ) . ')';
						}
						
						$variations[] = array(
							'id' => $variation->get_id(),
							'name' => $variation_name,
							'display_name' => $display_name,
							'attributes' => $attributes,
							'image_url' => $image_url,
							'price' => $variation->get_price_html(),
						);
					}
				}
			}
		}

		return $variations;
	}
}
