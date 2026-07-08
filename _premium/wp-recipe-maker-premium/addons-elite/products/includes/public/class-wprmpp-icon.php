<?php
/**
 * Handle the product icon display.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPP_Icon {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_ingredients_shortcode_ingredient', array( __CLASS__, 'add_product_icon' ), 20, 4 );
		add_filter( 'wprm_recipe_equipment_shortcode_equipment', array( __CLASS__, 'add_product_icon' ), 20, 4 );
	}

	/**
	 * Add product icon to ingredient or equipment line.
	 *
	 * @since	10.2.0
	 * @param	string $line Current line HTML.
	 * @param	array $atts Shortcode attributes.
	 * @param	array $item Ingredient or equipment item.
	 * @param	object $recipe Recipe object.
	 * @return	string Modified line with product icon.
	 */
	public static function add_product_icon( $line, $atts, $item, $recipe ) {
		// Check if products integration is available
		if ( ! WPRMPP_Integrations::is_available() ) {
			return $line;
		}

		// Check if show individual products is enabled
		if ( ! WPRM_Settings::get( 'products_show_individual' ) ) {
			return $line;
		}

		// Check if item has product and product_amount > 0
		if ( ! isset( $item['product'] ) || ! $item['product'] || ! isset( $item['product_amount'] ) || $item['product_amount'] <= 0 ) {
			return $line;
		}

		$product = $item['product'];
		$product_amount = $item['product_amount'];

		// Get product icon from settings with fallback to cart
		$icon_type = WPRM_Settings::get( 'products_show_individual_icon' );
		$icon_color = WPRM_Settings::get( 'products_show_individual_icon_color' );

		if ( 'custom' === $icon_type ) {
			$custom_url = WPRM_Settings::get( 'products_show_individual_icon_custom_url' );
			$icon = WPRM_Icon::get( $custom_url );
		} else {
			$icon = WPRM_Icon::get( $icon_type, $icon_color );
		}
		
		if ( ! $icon ) {
			// If the selected icon doesn't exist, fallback to cart
			$icon = WPRM_Icon::get( 'cart', $icon_color );
			if ( ! $icon ) {
				return $line;
			}
		}

		// Determine if this is an equipment item
		$is_equipment = strpos( $line, 'wprm-recipe-equipment-name' ) !== false;
		
		// Build data attributes
		$data_attributes = array(
			'data-product-id="' . esc_attr( $product['id'] ) . '"',
			'data-product-name="' . esc_attr( $product['name'] ) . '"',
			'data-product-amount="' . esc_attr( $product_amount ) . '"',
			'data-product-type="' . ( $is_equipment ? 'equipment' : 'ingredient' ) . '"',
		);

		// Add optional data attributes
		if ( isset( $product['price'] ) && $product['price'] ) {
			// Strip HTML tags from price to show only text
			$data_attributes[] = 'data-product-price="' . esc_attr( strip_tags( $product['price'] ) ) . '"';
		}

		if ( isset( $product['image_url'] ) && $product['image_url'] ) {
			$data_attributes[] = 'data-product-image="' . esc_attr( $product['image_url'] ) . '"';
		}

		if ( isset( $product['url'] ) && $product['url'] ) {
			$data_attributes[] = 'data-product-url="' . esc_attr( $product['url'] ) . '"';
		}

		if ( isset( $product['variation_id'] ) && $product['variation_id'] ) {
			$data_attributes[] = 'data-variation-id="' . esc_attr( $product['variation_id'] ) . '"';
		}

		if ( isset( $product['variation_name'] ) && $product['variation_name'] ) {
			$data_attributes[] = 'data-variation-name="' . esc_attr( $product['variation_name'] ) . '"';
		}

		// Build product icon HTML
		$product_icon = '<span class="wprm-recipe-product-icon-container" ' . implode( ' ', $data_attributes ) . '>' . $icon . '</span>';

		// Check if this is an equipment item by looking for the equipment name div
		if ( strpos( $line, 'wprm-recipe-equipment-name' ) !== false ) {
			// For equipment items, insert icon before the very last </div> tag
			$last_div_pos = strrpos( $line, '</div>' );
			if ( $last_div_pos !== false ) {
				$line = substr_replace( $line, $product_icon . '</div>', $last_div_pos, 6 );
			}
		} else {
			// For ingredient items, insert icon before closing </li> tag
			$line = str_replace( '</li>', $product_icon . '</li>', $line );
		}

		return $line;
	}
}

WPRMPP_Icon::init();