<?php
/**
 * Handle the product meta.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 */

/**
 * Handle the product meta.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Meta {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		apply_filters( 'wprm_get_term_meta', array( __CLASS__, 'get_term_meta' ), 10, 3 );
		add_action( 'wprm_update_term_meta', array( __CLASS__, 'update_term_meta' ), 10, 2 );
		add_filter( 'wprm_recipe_field', array( __CLASS__, 'add_product_information' ), 10, 3 );
	}

	/**
	 * Get product from term ID.
	 *
	 * @since	10.2.0
	 */
	public static function get_product_from_term_id( $term_id ) {
		$product = get_term_meta( $term_id, 'wprmp_product', true );

		if ( is_array( $product ) && isset( $product['id'] ) ) {
			// Get basic product info
			$basic_product = WPRMPP_Product_Manager::get( $product['id'] );
			
				if ( $basic_product ) {
					// Preserve variation data if it exists
					if ( isset( $product['variation_id'] ) ) {
						$basic_product['variation_id'] = $product['variation_id'];
					}
					if ( isset( $product['variation_name'] ) ) {
						$basic_product['variation_name'] = $product['variation_name'];
					}
					if ( isset( $product['variation_image_url'] ) ) {
						$basic_product['variation_image_url'] = $product['variation_image_url'];
					}
				}
			
			return $basic_product;
		}

		return false;
	}

	/**
	 * Get product term meta.
	 *
	 * @since	10.2.0
	 */
	public static function get_term_meta( $meta, $term, $term_meta ) {
		if ( 'wprm_equipment' === $term->taxonomy || 'wprm_ingredient' === $term->taxonomy ) {
			$product = isset( $term_meta['wprmp_product'] ) ? $term_meta['wprmp_product'] : false;

			if ( is_array( $product ) && isset( $product['id'] ) ) {
				// Get basic product info
				$basic_product = WPRMPP_Product_Manager::get( $product['id'] );
				
				if ( $basic_product ) {
					// Preserve variation data if it exists
					if ( isset( $product['variation_id'] ) ) {
						$basic_product['variation_id'] = $product['variation_id'];
					}
					if ( isset( $product['variation_name'] ) ) {
						$basic_product['variation_name'] = $product['variation_name'];
					}
					if ( isset( $product['variation_image_url'] ) ) {
						$basic_product['variation_image_url'] = $product['variation_image_url'];
					}
				}
				
				$meta['product'] = $basic_product;
			} else {
				$meta['product'] = false;
			}
		}

		return $meta;
	}

	/**
	 * Update product term meta.
	 *
	 * @since	10.2.0
	 */
	public static function update_term_meta( $term, $meta ) {
		if ( 'wprm_equipment' === $term->taxonomy || 'wprm_ingredient' === $term->taxonomy ) {
			if ( isset( $meta['product'] ) ) {
				if ( false === $meta['product'] ) {
					delete_term_meta( $term->term_id, 'wprmp_product' );
				} else {
					update_term_meta( $term->term_id, 'wprmp_product', $meta['product'] );
				}
			}
		}
	}

	/**
	 * Check if linked ingredient products should default to 1.
	 *
	 * @since	10.2.0
	 */
	private static function use_default_linked_ingredient_amount() {
		return WPRM_Settings::get( 'products_default_linked_ingredient_amount' );
	}

	/**
	 * Check if linked equipment products should default to 1.
	 *
	 * @since	10.2.0
	 */
	private static function use_default_linked_equipment_amount() {
		return WPRM_Settings::get( 'products_default_linked_equipment_amount' );
	}

	/**
	 * Get product amount data for an ingredient item.
	 *
	 * @since	10.2.0
	 */
	private static function get_ingredient_product_amount_data( $ingredient ) {
		if ( ! isset( $ingredient['id'] ) ) {
			return false;
		}

		$term_id = intval( $ingredient['id'] );
		if ( $term_id <= 0 ) {
			return false;
		}

		if ( array_key_exists( 'product_amount', $ingredient ) ) {
			$product_amount = floatval( $ingredient['product_amount'] );

			if ( $product_amount > 0 ) {
				return array(
					'term_id' => $term_id,
					'product_amount' => $product_amount,
					'product_amount_default' => ! empty( $ingredient['product_amount_default'] ),
				);
			}

			// Explicitly set to 0 (or empty) means do not use the global default.
			return false;
		}

		if ( self::use_default_linked_ingredient_amount() ) {
			return array(
				'term_id' => $term_id,
				'product_amount' => 1,
				'product_amount_default' => true,
			);
		}

		return false;
	}

	/**
	 * Get product amount data for an equipment item.
	 *
	 * @since	10.2.0
	 */
	private static function get_equipment_product_amount_data( $equipment ) {
		if ( ! isset( $equipment['id'] ) ) {
			return false;
		}

		$term_id = intval( $equipment['id'] );
		if ( $term_id <= 0 ) {
			return false;
		}

		if ( array_key_exists( 'product_amount', $equipment ) ) {
			$product_amount = floatval( $equipment['product_amount'] );

			if ( $product_amount > 0 ) {
				return array(
					'term_id' => $term_id,
					'product_amount' => $product_amount,
					'product_amount_default' => ! empty( $equipment['product_amount_default'] ),
				);
			}

			// Explicitly set to 0 (or empty) means do not use the global default.
			return false;
		}

		if ( self::use_default_linked_equipment_amount() ) {
			return array(
				'term_id' => $term_id,
				'product_amount' => 1,
				'product_amount_default' => true,
			);
		}

		return false;
	}

	/**
	 * Add product information to recipe fields when product_amount is set.
	 *
	 * @since	10.2.0
	 */
	public static function add_product_information( $field_data, $field_type, $recipe ) {
		// Return early if products integration is not available
		if ( ! WPRMPP_Integrations::is_available() ) {
			return $field_data;
		}

		// Only process ingredients and equipment fields
		if ( 'ingredients' !== $field_type && 'equipment' !== $field_type ) {
			return $field_data;
		}

		// Check if we have an array of items to process
		if ( ! is_array( $field_data ) ) {
			return $field_data;
		}

		// Collect all term IDs that need product information
		$term_ids_to_fetch = array();
		$items_to_update = array();

		// First pass: collect all term IDs and track items that need updating
		foreach ( $field_data as $group_index => $group ) {
			if ( 'ingredients' === $field_type && isset( $group['ingredients'] ) ) {
				// Process ingredients within groups
				foreach ( $group['ingredients'] as $ingredient_index => $ingredient ) {
					$product_amount_data = self::get_ingredient_product_amount_data( $ingredient );

					if ( $product_amount_data ) {
						$term_ids_to_fetch[] = $product_amount_data['term_id'];
						$items_to_update[] = array(
							'group_index' => $group_index,
							'ingredient_index' => $ingredient_index,
							'term_id' => $product_amount_data['term_id'],
							'type' => 'ingredient',
							'product_amount' => $product_amount_data['product_amount'],
							'product_amount_default' => $product_amount_data['product_amount_default'],
						);
					}
				}
			} elseif ( 'equipment' === $field_type ) {
				// Process equipment items directly
				$product_amount_data = self::get_equipment_product_amount_data( $group );

				if ( $product_amount_data ) {
					$term_ids_to_fetch[] = $product_amount_data['term_id'];
					$items_to_update[] = array(
						'group_index' => $group_index,
						'term_id' => $product_amount_data['term_id'],
						'type' => 'equipment',
						'product_amount' => $product_amount_data['product_amount'],
						'product_amount_default' => $product_amount_data['product_amount_default'],
					);
				}
			}
		}

		// If no items need product information, return early
		if ( empty( $term_ids_to_fetch ) ) {
			return $field_data;
		}

		// Remove duplicates and fetch all products in batch
		$term_ids_to_fetch = array_unique( $term_ids_to_fetch );
		$products_cache = self::get_products_batch( $term_ids_to_fetch, $field_type );

		// Second pass: apply product information to items
		foreach ( $items_to_update as $item ) {
			$term_id = $item['term_id'];
			if ( isset( $products_cache[ $term_id ] ) && $products_cache[ $term_id ] ) {
				if ( 'ingredient' === $item['type'] ) {
					$field_data[ $item['group_index'] ]['ingredients'][ $item['ingredient_index'] ]['product'] = $products_cache[ $term_id ];

					if ( ! array_key_exists( 'product_amount', $field_data[ $item['group_index'] ]['ingredients'][ $item['ingredient_index'] ] ) || ! empty( $item['product_amount_default'] ) ) {
						$field_data[ $item['group_index'] ]['ingredients'][ $item['ingredient_index'] ]['product_amount'] = $item['product_amount'];

						if ( ! empty( $item['product_amount_default'] ) ) {
							$field_data[ $item['group_index'] ]['ingredients'][ $item['ingredient_index'] ]['product_amount_default'] = true;
						}
					}
				} else {
					$field_data[ $item['group_index'] ]['product'] = $products_cache[ $term_id ];

					if ( ! array_key_exists( 'product_amount', $field_data[ $item['group_index'] ] ) || ! empty( $item['product_amount_default'] ) ) {
						$field_data[ $item['group_index'] ]['product_amount'] = $item['product_amount'];

						if ( ! empty( $item['product_amount_default'] ) ) {
							$field_data[ $item['group_index'] ]['product_amount_default'] = true;
						}
					}
				}
			}
		}

		return $field_data;
	}

	/**
	 * Get product information for multiple terms in batch.
	 *
	 * @since	10.2.0
	 */
	private static function get_products_batch( $term_ids, $field_type ) {
		$products_cache = array();
		
		if ( empty( $term_ids ) ) {
			return $products_cache;
		}

		// Determine the taxonomy based on field type
		$taxonomy = 'ingredients' === $field_type ? 'wprm_ingredient' : 'wprm_equipment';
		
		// Get all terms in one query
		$terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'include' => $term_ids,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $products_cache;
		}

		// Get all term meta in one query
		$term_meta_cache = array();
		foreach ( $terms as $term ) {
			$meta = get_term_meta( $term->term_id, 'wprmp_product', true );
			if ( is_array( $meta ) && isset( $meta['id'] ) ) {
				$term_meta_cache[ $term->term_id ] = $meta;
			}
		}

		// Process each term and get product information
		foreach ( $terms as $term ) {
			if ( isset( $term_meta_cache[ $term->term_id ] ) ) {
				$product_meta = $term_meta_cache[ $term->term_id ];
				
				// Get basic product info
				$basic_product = WPRMPP_Product_Manager::get( $product_meta['id'] );
				
				if ( $basic_product ) {
					// Preserve variation data if it exists
					if ( isset( $product_meta['variation_id'] ) ) {
						$basic_product['variation_id'] = $product_meta['variation_id'];
						
						// Get variation price if we have a variation ID
						$variation = wc_get_product( $product_meta['variation_id'] );
						if ( $variation ) {
							$basic_product['price'] = $variation->get_price_html();
						}
					}
					if ( isset( $product_meta['variation_name'] ) ) {
						$basic_product['variation_name'] = $product_meta['variation_name'];
					}
					if ( isset( $product_meta['variation_image_url'] ) ) {
						$basic_product['variation_image_url'] = $product_meta['variation_image_url'];
					}
					
					$products_cache[ $term->term_id ] = $basic_product;
				}
			}
		}

		return $products_cache;
	}

	/**
	 * Get product information for a specific item.
	 *
	 * @since	10.2.0
	 * @deprecated Use get_products_batch for better performance
	 */
	private static function get_product_info_for_item( $item, $field_type ) {
		// Check if the item has an ID (linked to a term)
		$item_id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;
		
		if ( $item_id > 0 ) {
			// Use batch method for single item
			$products_cache = self::get_products_batch( array( $item_id ), $field_type );
			return isset( $products_cache[ $item_id ] ) ? $products_cache[ $item_id ] : false;
		}

		return false;
	}
}
WPRMPP_Meta::init();
