<?php
/**
 * Handle the Premium recipe equipment shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium recipe equipment shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Equipment {
	public static function init() {
		add_filter( 'wprm_recipe_equipment_shortcode_checkbox', array( __CLASS__, 'checkbox' ) );
		add_filter( 'wprm_recipe_equipment_shortcode_link', array( __CLASS__, 'link' ), 10, 2 );
		add_filter( 'wprm_recipe_equipment_shortcode_display', array( __CLASS__, 'display' ), 10, 3 );
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
	 * Add equipment links.
	 *
	 * @since	5.6.0
	 * @param	mixed $output 	Current output.
	 * @param	array $equiment Equipment we're outputting.
	 */
	public static function link( $output, $equipment ) {
		if ( false === WPRM_Settings::get( 'equipment_links_enabled' ) ) {
			return $output;
		}

		if ( isset( $equipment['id'] ) && $equipment['id'] ) {
			// Easy Affiliate Links integration.
			if ( class_exists( 'EAFL_Link_Manager' ) ) {
				$eafl = get_term_meta( $equipment['id'], 'wprmp_equipment_eafl', true );

				if ( $eafl ) {
					$eafl_link = EAFL_Link_Manager::get_link( $eafl );

					if ( $eafl_link ) {
						return do_shortcode( '[eafl id="' .  $eafl . '"]' . $output . '[/eafl]' );
					}
				}
			}

			// Regular link.
			$link = get_term_meta( $equipment['id'], 'wprmp_equipment_link', true );
			$link_nofollow = get_term_meta( $equipment['id'], 'wprmp_equipment_link_nofollow', true );

			if ( $link ) {
				$link_output = WPRMP_Links::get( $link, $link_nofollow, $output, 'equipment' );

				if ( $link_output ) {
					return $link_output;
				}
			}
		}

		return $output;
	}

	/**
	 * Change the equipment display.
	 *
	 * @since	5.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function display( $output, $atts, $recipe ) {
		switch( $atts['display_style'] ) {
			case 'images':
				$output .= self::display_images( $atts, $recipe );
				break;
			case 'grid':
				$output .= self::display_grid( $atts, $recipe );
				break;
		}

		return $output;
	}

	/**
	 * Get the output for the images display.
	 *
	 * @since	8.0.0
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function display_images( $atts, $recipe ) {
		$output = '';

		$classes = array(
			'wprm-recipe-equipment',
			'wprm-recipe-equipment-images',
			'wprm-recipe-equipment-images-align-' . esc_attr( $atts['image_alignment'] ),
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		foreach ( $recipe->equipment() as $equipment ) {
			$output .= self::get_equipment_item_image_output( $equipment, $atts, $recipe );
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the output for the images display.
	 *
	 * @since	8.0.0
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function display_grid( $atts, $recipe ) {
		$output = '';


		$grid_columns = intval( $atts['grid_columns'] );
		$classes = array(
			'wprm-recipe-equipment',
			'wprm-recipe-equipment-grid',
			'wprm-recipe-equipment-grid-columns-' . $grid_columns,
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		$output .= '<div class="wprm-recipe-equipment-grid-row">';

		$item_nbr = 1;
		foreach ( $recipe->equipment() as $equipment ) {
			$output .= self::get_equipment_item_image_output( $equipment, $atts, $recipe );

			if ( 0 === $item_nbr % $grid_columns && $item_nbr < count( $recipe->equipment() ) ) {
				$output .= '</div>';
				$output .= '<div class="wprm-recipe-equipment-grid-row">';
			}
			$item_nbr++;
		}

		$output .= '</div>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the output for an equipment item with image.
	 *
	 * @since	8.0.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 */
	public static function get_equipment_item_image_output( $equipment, $atts, $recipe ) {
		$output = '';

		// Equipment Image.
		$image_id = intval( get_term_meta( $equipment['id'], 'wprmp_equipment_image_id', true ) );

		// No manual image? Check for Amazon image.
		$amazon_image_url = false;
		$amazon_image_width = 0;
		$amazon_image_height = 0;
		if ( ! $image_id ) {
			$amazon_image_url = get_term_meta( $equipment['id'], 'wprmp_amazon_image', true );

			if ( $amazon_image_url ) {
				// Make sure this image hasn't expired.
				$amazon_updated = get_term_meta( $equipment['id'], 'wprmp_amazon_updated', true );

				if ( intval( $amazon_updated ) < ( ( time() * 1000 ) - ( 1000 * 60 * 60 * 24 ) ) ) {
					// Expired. Cron job not running? Manually force an update before loading the image.
					WPRMP_Amazon_Queue::update_terms( array( $equipment['id'] ) );
					$amazon_image_url = get_term_meta( $equipment['id'], 'wprmp_amazon_image', true );
				}

				$amazon_image_width = intval( get_term_meta( $equipment['id'], 'wprmp_amazon_image_width', true ) );
				$amazon_image_height = intval( get_term_meta( $equipment['id'], 'wprmp_amazon_image_height', true ) );
			}
		}

		$has_image = $image_id || $amazon_image_url;
		$class = $has_image ? 'wprm-recipe-equipment-item-has-image' : 'wprm-recipe-equipment-item-no-image';

		$output .= '<div class="wprm-recipe-equipment-item ' . $class . '">';
		
		$equipment_output = '';

		if ( $has_image ) {
			$size = $atts['image_size'];
			$force_size = false;

			preg_match( '/^(\d+)x(\d+)(\!?)$/i', $atts['image_size'], $match );
			if ( ! empty( $match ) ) {
				$size = array( intval( $match[1] ), intval( $match[2] ) );
				$force_size = isset( $match[3] ) && '!' === $match[3];
			}

			if ( $image_id ) {
				$thumbnail_size = WPRM_Shortcode_Helper::get_thumbnail_image_size( $image_id, $size, $force_size );
				$img = wp_get_attachment_image( $image_id, $thumbnail_size );
			} else {
				// Get thumbnail size.
				if ( ! is_array( $size ) ) {
					$thumbnail_sizes = wp_get_registered_image_subsizes();

					if ( isset( $thumbnail_sizes[ $size ] ) ) {
						$size = array(
							$thumbnail_sizes[ $size ]['width'],
							$thumbnail_sizes[ $size ]['height'],
						);
					} else {
						$size = array( 500, 500 );
					}
				}
				
				// Width shouldn't be more than	500px.
				$size[0] = $size[0] > 500 ? 500 : $size[0];
				
				$target_width = isset( $size[0] ) ? intval( $size[0] ) : 0;
				$calculated_height = 0;

				if ( $target_width > 0 && $amazon_image_width > 0 && $amazon_image_height > 0 ) {
					$ratio = $amazon_image_height / $amazon_image_width;
					$calculated_height = intval( round( $target_width * $ratio ) );
				}

				if ( $calculated_height <= 0 && isset( $size[1] ) && $size[1] > 0 ) {
					$calculated_height = intval( $size[1] );
				}

				$height_attr = $calculated_height > 0 ? ' height="' . $calculated_height . '"' : '';

				$img = '<img class="wprm-recipe-equipment-image-amazon" src="' . esc_url( $amazon_image_url ) . '" width="' . $target_width . '"' . $height_attr . ' alt="' . esc_attr( $equipment['name'] ) . '" />';
			}

			// Maybe add border radius.
			$style = '';
			if ( false !== $atts['image_border_radius'] && '0px' !== $atts['image_border_radius'] ) {
				$style = 'border-radius: ' . $atts['image_border_radius'] . ';';
			}

			// Maybe force image size.
			if ( $force_size ) {
				$style .= WPRM_Shortcode_Helper::get_force_image_size_style( $size );	
			}

			// Add inline CSS to img.
			$img = WPRM_Shortcode_Helper::add_inline_style( $img, $style );

			// Disable equipment image pinning.
			if ( WPRM_Settings::get( 'pinterest_nopin_equipment_image' ) ) {
				$img = str_ireplace( '<img ', '<img data-pin-nopin="true" ', $img );
			}

			$image_output = '<div class="wprm-recipe-equipment-image">' . $img . '</div>';
			$equipment_output .= self::link( $image_output, $equipment );
		}

		// Equipment Affiliate HTML.
		$affiliate_html = get_term_meta( $equipment['id'], 'wprmp_equipment_affiliate_html', true );

		if ( $affiliate_html ) {
			$output .= '<div class="wprm-recipe-equipment-affiliate-html">' . do_shortcode( $affiliate_html ) . '</div>';
		}

		// Maybe add amount or notes.
		$name = self::link( $equipment['name'], $equipment );
		if ( isset( $equipment['amount'] ) && $equipment['amount'] ) {
			$name = $equipment['amount'] . ' ' . $name;
		}
		if ( isset( $equipment['notes'] ) && $equipment['notes'] ) {
			$notes = $equipment['notes'];

			switch ( $atts['equipment_notes_separator'] ) {
				case 'comma':
					$separator = ',&#32;';
					break;
				case 'dash':
					$separator = '&#32;-&#32;';
					break;
				case 'parentheses':
					$notes = '(' . $notes . ')';
					// Fall through to default separator.
				default:
					$separator = '&#32;';
			}

			$name = $name . $separator . '<span class="wprm-recipe-equipment-notes wprm-recipe-equipment-notes-' . esc_attr( $atts['notes_style'] ) . '">' . $notes . '</span>';
		}

		// Equipment Name.
		$equipment_output .= '<div class="wprm-recipe-equipment-name">' . $name . '</div>';

		$output .= $equipment_output;
		$output .= '</div>';

		// Apply filter to the complete equipment item output
		$output = apply_filters( 'wprm_recipe_equipment_shortcode_equipment', $output, $atts, $equipment, $recipe );

		return $output;
	}
}

WPRMP_SC_Equipment::init();