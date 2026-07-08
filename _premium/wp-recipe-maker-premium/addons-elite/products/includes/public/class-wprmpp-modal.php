<?php
/**
 * Handle the product modal functionality.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Modal {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_add_products_to_cart_shortcode', array( __CLASS__, 'add_products_to_cart_shortcode' ), 10, 3 );
	}

	/**
	 * Handle the add products to cart shortcode output.
	 *
	 * @since    10.2.0
	 * @param    string $output Current output.
	 * @param    array  $atts   Shortcode attributes.
	 * @param    object $recipe Recipe object.
	 */
	public static function add_products_to_cart_shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ! $recipe->id() ) {
			return $output;
		}

		// Get products from recipe
		$products_data = self::get_recipe_products( $recipe );
		
		// Create modal content
		$modal_content = self::generate_modal_content( $products_data );
		
		$cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

		// Create modal for this recipe.
		$modal_uid = WPRM_Popup::add( array(
			'type' => 'products',
			'title' => __( 'Add Products to Cart', 'wp-recipe-maker-premium' ),
			'content' => $modal_content,
			'buttons' => array(
				array(
					'text' => __( 'Add 0 Products to Cart', 'wp-recipe-maker-premium' ),
					'primary' => true,
					'class' => 'wprmp-add-products-to-cart-btn',
				),
				array(
					'text' => __( 'Go to Cart', 'wp-recipe-maker-premium' ),
					'class' => 'wprmp-go-to-cart-btn',
					'attributes' => array(
						'type' => 'button',
						'data-cart-url' => $cart_url,
						'disabled' => 'disabled',
						'hidden' => 'hidden',
					),
				),
			),
			'reuse' => false,
			'recipe_id' => $recipe->id(),
		) );

		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-add-products-to-cart-icon">' . $icon . '</span> ';
			}
		}

		// Output.
		$classes = array(
			'wprm-recipe-add-products-to-cart',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-add-products-to-cart-' . $atts['style'];
			$classes[] = 'wprm-recipe-link-' . $atts['style'];
			$classes[] = 'wprm-color-accent';

			$style .= 'background-color: ' . $atts['button_color'] . ';';
			$style .= 'border-color: ' . $atts['border_color'] . ';';
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
			$style .= 'padding: ' . $atts['vertical_padding'] . ' ' . $atts['horizontal_padding'] . ';';
		}

		// Backwards compatibility.
		if ( 'legacy' === WPRM_Settings::get( 'recipe_template_mode' ) ) {
			$style = '';
		}

		// Text and optional aria-label.
		$text = WPRM_i18n::maybe_translate( $atts['text'] );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . __( 'Add Products to Cart', 'wp-recipe-maker-premium' ) . '"';
		}

		// Button that opens the modal
		$output = '<a href="#" style="' . esc_attr( $style ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-modal-uid="' . esc_attr( $modal_uid ) . '"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';

		return $output;
	}

	/**
	 * Get all products from recipe ingredients and equipment.
	 *
	 * @since    10.2.0
	 * @param    object $recipe Recipe object.
	 * @return   array  Products data organized by type.
	 */
	private static function get_recipe_products( $recipe ) {
		$products = array(
			'equipment' => array(),
			'ingredients' => array(),
		);

		// Get equipment with products
		$equipment = $recipe->equipment();
		foreach ( $equipment as $item ) {
			if ( isset( $item['product'] ) && $item['product'] && isset( $item['product_amount'] ) && $item['product_amount'] > 0 ) {
				$products['equipment'][] = array(
					'id' => $item['id'],
					'name' => $item['name'],
					'product' => $item['product'],
					'product_amount' => $item['product_amount'],
				);
			}
		}

		// Get ingredients with products
		$ingredients = $recipe->ingredients();
		foreach ( $ingredients as $group ) {
			if ( isset( $group['ingredients'] ) && is_array( $group['ingredients'] ) ) {
				foreach ( $group['ingredients'] as $ingredient ) {
					if ( isset( $ingredient['product'] ) && $ingredient['product'] && isset( $ingredient['product_amount'] ) && $ingredient['product_amount'] > 0 ) {
						$products['ingredients'][] = array(
							'id' => $ingredient['id'],
							'name' => $ingredient['name'],
							'product' => $ingredient['product'],
							'product_amount' => $ingredient['product_amount'],
						);
					}
				}
			}
		}

		return $products;
	}

	/**
	 * Generate modal content with product lists.
	 *
	 * @since    10.2.0
	 * @param    array $products_data Products organized by type.
	 * @return   string Modal content HTML.
	 */
	private static function generate_modal_content( $products_data ) {
		$content = '<div class="wprmp-products-modal">';

		$showing_both = ! empty( $products_data['equipment'] ) && ! empty( $products_data['ingredients'] );

		// Equipment section
		if ( ! empty( $products_data['equipment'] ) ) {
			$content .= '<div class="wprmp-products-section">';
			if ( $showing_both ) {
				$content .= '<div class="wprmp-products-section-title">' . __( 'Equipment', 'wp-recipe-maker' ) . '</div>';
			}
			$content .= '<div class="wprmp-products-list">';
			
			foreach ( $products_data['equipment'] as $item ) {
				$content .= self::generate_product_item_html( $item, 'equipment' );
			}
			
			$content .= '</div>';
			$content .= '</div>';
		}

		// Ingredients section
		if ( ! empty( $products_data['ingredients'] ) ) {
			$content .= '<div class="wprmp-products-section">';
			if ( $showing_both ) {
				$content .= '<div class="wprmp-products-section-title">' . __( 'Ingredients', 'wp-recipe-maker' ) . '</div>';
			}
			$content .= '<div class="wprmp-products-list">';
			
			foreach ( $products_data['ingredients'] as $item ) {
				$content .= self::generate_product_item_html( $item, 'ingredient' );
			}
			
			$content .= '</div>';
			$content .= '</div>';
		}

		// No products message
		if ( empty( $products_data['equipment'] ) && empty( $products_data['ingredients'] ) ) {
			$content .= '<p class="wprmp-no-products">' . __( 'No products are available for this recipe.', 'wp-recipe-maker-premium' ) . '</p>';
		}

		$content .= '</div>';

		return $content;
	}

	/**
	 * Generate HTML for a single product item.
	 *
	 * @since    10.2.0
	 * @param    array  $item Product item data.
	 * @param    string $type Item type (equipment or ingredient).
	 * @return   string Product item HTML.
	 */
	private static function generate_product_item_html( $item, $type ) {
		$product = $item['product'];
		$product_id = $product['id'];
		$variation_id = isset( $product['variation_id'] ) ? $product['variation_id'] : '';
		$variation_name = isset( $product['variation_name'] ) ? $product['variation_name'] : '';
		$image_url = isset( $product['image_url'] ) ? $product['image_url'] : '';
		$price = isset( $product['price'] ) ? $product['price'] : '';
		$quantity_value = max( 1, ceil( floatval( $item['product_amount'] ) ) );

		$html = '<div class="wprmp-product-item" data-product-id="' . esc_attr( $product_id ) . '" data-variation-id="' . esc_attr( $variation_id ) . '" data-type="' . esc_attr( $type ) . '" data-original-amount="' . esc_attr( $item['product_amount'] ) . '">';
		
		// Checkbox
		$html .= '<div class="wprmp-product-checkbox">';
		$html .= '<input type="checkbox" class="wprmp-product-select" checked />';
		$html .= '</div>';

		// Quantity input
		$html .= '<div class="wprmp-product-quantity">';
		$html .= '<input type="number" class="wprmp-quantity-input" value="' . esc_attr( $quantity_value ) . '" min="1" />';
		$html .= '</div>';

		// Product image
		if ( $image_url ) {
			$html .= '<div class="wprmp-product-image">';
			$html .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $item['name'] ) . '" />';
			$html .= '</div>';
		}

		// Product details
		$html .= '<div class="wprmp-product-details">';
		$html .= '<div class="wprmp-product-name">' . esc_html( $product['name'] );
		
		if ( $variation_name ) {
			$html .= '<span class="wprmp-product-variation">' . esc_html( $variation_name ) . '</span>';
		}
		
		$html .= '</div>';

		if ( $price ) {
			// Strip HTML tags from price to show only text
			$html .= '<div class="wprmp-product-price">' . esc_html( strip_tags( $price ) ) . '</div>';
		}
	
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}

WPRMPP_Modal::init();