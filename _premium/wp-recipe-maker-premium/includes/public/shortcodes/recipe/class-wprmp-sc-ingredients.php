<?php
/**
 * Handle the Premium recipe ingredients shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium recipe ingredients shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Ingredients {
	public static function init() {
		add_filter( 'wprm_recipe_ingredients_shortcode_checkbox', array( __CLASS__, 'checkbox' ) );
		add_filter( 'wprm_recipe_ingredients_shortcode_image', array( __CLASS__, 'image' ), 10, 3 );
		add_filter( 'wprm_recipe_ingredients_shortcode', array( __CLASS__, 'check_for_images' ) );

		add_filter( 'wprm_recipe_ingredients_shortcode_amount_unit', array( __CLASS__, 'amount_unit' ), 10, 3 );
		add_filter( 'wprm_recipe_ingredients_shortcode_link', array( __CLASS__, 'link' ), 10, 3 );
	}

	/**
	 * Add checkboxes.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 */
	public static function checkbox( $output ) {
		return WPRMP_Checkboxes::checkbox( $output );
	}

	/**
	 * Display ingredient image.
	 *
	 * @since	8.5.0
	 * @param	mixed $output 		Current output.
	 * @param	array $atts 		Shortcode attributes.
	 * @param	array $ingredient 	Ingredient we're outputting.
	 */
	public static function image( $output, $atts, $ingredient ) {
		if ( '' !== $atts['image_position'] ) {
			if ( isset( $ingredient['id'] ) && $ingredient['id'] ) {
				$image_id = intval( get_term_meta( $ingredient['id'], 'wprmp_ingredient_image_id', true ) );

				// Get classes for image container.
				$classes = array(
					'wprm-recipe-ingredient-image',
					'wprm-block-image-' . $atts['image_style'],
				);

				// Make sure this class is added last.
				if ( $image_id ) {
					$classes[] = 'wprm-recipe-ingredient-has-image';
				} else {
					$classes[] = 'wprm-recipe-ingredient-no-image';
				}
		
				$output .= '<span class="' . esc_attr( implode( ' ', $classes ) ) . '">';
						
				if ( $image_id ) {
					$size = $atts['image_size'];
					$force_size = false;

					preg_match( '/^(\d+)x(\d+)(\!?)$/i', $atts['image_size'], $match );
					if ( ! empty( $match ) ) {
						$size = array( intval( $match[1] ), intval( $match[2] ) );
						$force_size = isset( $match[3] ) && '!' === $match[3];
					}
		
					$thumbnail_size = WPRM_Shortcode_Helper::get_thumbnail_image_size( $image_id, $size, $force_size );
					$img = wp_get_attachment_image( $image_id, $thumbnail_size );
		
					// Disable ingredient image pinning.
					if ( WPRM_Settings::get( 'pinterest_nopin_ingredient_image' ) ) {
						$img = str_ireplace( '<img ', '<img data-pin-nopin="true" ', $img );
					}

					// Image Style.
					$style = '';
					$style .= 'border-width: ' . $atts['image_border_width'] . ';';
					$style .= 'border-style: ' . $atts['image_border_style'] . ';';
					$style .= 'border-color: ' . $atts['image_border_color'] . ';';
					$style .= 'margin: ' . $atts['image_margin'] . ' 0;';

					if ( 'rounded' === $atts['image_style'] ) {
						$style .= 'border-radius: ' . $atts['image_rounded_radius'] . ';';
					}

					// Maybe force image size.
					if ( $force_size ) {
						$style .= WPRM_Shortcode_Helper::get_force_image_size_style( $size );
					}

					$img = WPRM_Shortcode_Helper::add_inline_style( $img, $style );

					$output .= $img;
				} else {
					// No image, set minimum width and height.
					$minimum_width = intval( $atts['no_image_width'] );
					$minimum_height = intval( $atts['no_image_height'] );

					$style = '';

					if ( 0 < $minimum_width ) {
						$style .= 'min-width: ' . $minimum_width . 'px;';
					}
					if ( 0 < $minimum_height ) {
						$style .= 'min-height: ' . $minimum_height . 'px;';
					}

					if ( $style ) {
						$output .= '<div class="wprm-recipe-ingredient-no-image-placeholder" style="' . esc_attr( $style ) . '">&nbsp;</div>';
					}
				}

				$output .= '</span>';
			}
		}

		return $output;
	}

	/**
	 * Check if there were any ingredient images.
	 *
	 * @since	8.5.0
	 * @param	mixed $output 		Current output.
	 */
	public static function check_for_images( $output ) {
		$has_images = false !== stripos( $output, 'wprm-recipe-ingredient-has-image' );

		// Add correct class.
		if ( $has_images ) {
			$output = str_ireplace( 'wprm-recipe-ingredients-container', 'wprm-recipe-ingredients-container wprm-recipe-ingredients-has-images', $output );
		} else {
			$output = str_ireplace( 'wprm-recipe-ingredients-container', 'wprm-recipe-ingredients-container wprm-recipe-ingredients-no-images', $output );

			// Remove placeholders to prevent no-image from taking up space.
			$output = preg_replace( '/<span class="wprm-recipe-ingredient-image [^"]*wprm-recipe-ingredient-no-image">.*?<\/span>/', '', $output );
		}

		return $output;
	}

	/**
	 * Add unit conversion.
	 *
	 * @since	5.6.0
	 * @param	mixed $amount_unit 	Current output.
	 * @param	array $atts 		Shortcode attributes.
	 * @param	array $ingredient 	Ingredient we're outputting.
	 */
	public static function amount_unit( $amount_unit, $atts, $ingredient ) {
		if ( 'both' === $atts['unit_conversion'] && WPRM_Addons::is_active( 'unit-conversion' ) && WPRM_Settings::get( 'unit_conversion_enabled' ) ) {

			// Surround first unit system with span.
			$amount_unit = '<span class="wprm-recipe-ingredient-unit-system wprm-recipe-ingredient-unit-system-1">' . trim( $amount_unit ) . '</span>';

			// Add second unit system.
			$second_system = '';
			if ( isset( $ingredient['converted'] ) && isset( $ingredient['converted'][2] ) ) {

				// Maybe replace fractions in amount.
				if ( WPRM_Settings::get( 'automatic_amount_fraction_symbols' ) ) {
					$ingredient['converted'][2]['amount'] = WPRM_Recipe_Parser::replace_any_fractions_with_symbol( $ingredient['converted'][2]['amount'] );
				}

				// Check if identical if we're not showing them.
				$skip_second_system = false;
				if ( ! $atts['unit_conversion_show_identical'] ) {
					if ( $ingredient['amount'] === $ingredient['converted'][2]['amount'] && $ingredient['unit'] === $ingredient['converted'][2]['unit'] ) {
						$skip_second_system = true;
					}
				}

				// Make sure amount value is not NaN.
				if ( 'NaN' === $ingredient['converted'][2]['amount'] ) {
					$skip_second_system = true;
				}

				// Add second unit system to output.
				if ( ! $skip_second_system ) {
					if ( $ingredient['converted'][2]['amount'] ) {
						$second_system .= '<span class="wprm-recipe-ingredient-amount">' . $ingredient['converted'][2]['amount'] . '</span> ';
					}
					if ( $ingredient['converted'][2]['unit'] ) {
						$second_system .= '<span class="wprm-recipe-ingredient-unit">' . $ingredient['converted'][2]['unit'] . '</span>';
					}
					$second_system = trim( $second_system );
				}
			}

			if ( $second_system ) {
				switch ( $atts['unit_conversion_both_style'] ) {
					case 'parentheses':
						$second_system = '(' . $second_system . ')';
						break;
					case 'slash':
						$second_system = '/ ' . $second_system;
						break;
				}
			}

			$amount_unit .= ' <span class="wprm-recipe-ingredient-unit-system wprm-recipe-ingredient-unit-system-2">' . $second_system . '</span> ';
		}

		return $amount_unit;
	}

	/**
	 * Add ingredient links.
	 *
	 * @since	5.6.0
	 * @param	mixed $output 		Current output.
	 * @param	array $ingredient 	Ingredient we're outputting.
	 * @param	mixed $recipe 		Recipe the shortcode is getting output for.
	 */
	public static function link( $output, $ingredient, $recipe ) {
		$link = array();

		if ( false === WPRM_Settings::get( 'ingredient_links_enabled' ) ) {
			return $output;
		}
		
		if ( 'global' === $recipe->ingredient_links_type() ) {
			$link = WPRMP_Ingredient_Links::get_ingredient_link( $ingredient['id'] );
		} elseif ( isset( $ingredient['link'] ) ) {
			$link = $ingredient['link'];
		}

		// Easy Affiliate Links integration.
		if ( class_exists( 'EAFL_Link_Manager' ) ) {
			if ( isset( $link['eafl'] ) && $link['eafl'] ) {
				return do_shortcode( '[eafl id="' . $link['eafl'] .'"]' . $output . '[/eafl]' );
			}
		}

		if ( isset( $link['url'] ) && $link['url'] ) {
			$link_output = WPRMP_Links::get( $link['url'], $link['nofollow'], $output, 'ingredient' );

			if ( $link_output ) {
				return $link_output;
			}
		} else {
			return $output;
		}

		return $output;
	}
}

WPRMP_SC_Ingredients::init();