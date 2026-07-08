<?php
/**
 * Handle the Premium rating shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium rating shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Rating {
	private static $uid = 0;

	public static function init() {
		add_filter( 'wprm_recipe_rating_shortcode_stars', array( __CLASS__, 'stars' ), 10, 8 );
	}

	/**
	 * Stars shortcode.
	 *
	 * @since	5.6.0
	 * @param	mixed 	$output		Current output.
	 * @param   mixed 	$recipe   	Recipe to display the rating for.
	 * @param   array 	$rating   	Rating to display.
	 * @param   mixed	$icon 	   	Icon to use for the rating.
	 * @param   boolean $voteable 	Whether the user is allowed to vote.
	 * @param   mixed	$color 	   	Color for the stars.
	 * @param   mixed	$atts		Options passed along with the shortcode.
	 */
	public static function stars( $output, $recipe, $rating, $voteable, $icon, $color, $atts ) {
		if ( WPRM_Settings::get( 'features_user_ratings' ) ) {
			$recipe_id = 'summary' === $recipe ? 'summary' : $recipe->id();
			$rating_value = $rating['average'];

			// UID for these stars.
			$id = 'wprm-recipe-user-rating-' . self::$uid;
			self::$uid++;

			// Only output when there is an actual rating or users can rate.
			if ( ! $voteable && ! $rating_value ) {
				return false;
			}

			// Output style for star color.
			$output = WPRM_SC_Rating::get_stars_style( $id, $atts );

			if ( WPRM_Settings::get( 'user_ratings_indicate_not_voted' ) ) {
				$output .= '<style>';
				$output .= '#' . $id . '.wprm-user-rating-allowed.wprm-user-rating-not-voted:not(.wprm-user-rating-voting) svg * { fill-opacity: 0.3; }';
				$output .= '</style>';
			}

			// Get correct class.
			$classes = array(
				'wprm-recipe-rating',
				'wprm-recipe-rating-recipe-' . $recipe_id,
				'wprm-user-rating',
			);

			if ( 'stars-details' === $atts['display'] ) {
				$classes[] = 'wprm-recipe-rating-' . $atts['style'];
			}

			// Maybe not voteable if comments are disabled.
			if ( 'close' === WPRM_Settings::get( 'user_ratings_comments_closed' ) ) {
				$parent_post_id = is_a( $recipe, 'WPRM_Recipe' ) ? $recipe->parent_post_id() : false;

				if ( ! $parent_post_id ) {
					$parent_post_id = get_the_ID();
				}

				if ( ! $parent_post_id || ! comments_open( $parent_post_id ) ) {
					$voteable = false;
				}
			}

			$functions = '';
			if ( $voteable && WPRMP_User_Rating::is_user_allowed_to_vote() ) {
				$decimals = isset( $atts['average_decimals'] ) ? $atts['average_decimals'] : 2;
				$user_rating = isset( $rating['user'] ) ? $rating['user'] : 0;

				if ( 0 < $user_rating ) {
					$classes[] = 'wprm-user-rating-has-voted';
				} else {
					$classes[] = 'wprm-user-rating-not-voted';
				}

				$classes[] = 'wprm-user-rating-allowed';

				$average = isset( $rating['average'] ) ? $rating['average'] : 0;
				$count = isset( $rating['count'] ) ? $rating['count'] : 0;
				$total = isset( $rating['total'] ) ? $rating['total'] : 0;
				$data = ' data-recipe="' . $recipe_id . '" data-average="' . $average . '" data-count="' . $count . '" data-total="' . $total . '" data-user="' . $user_rating . '" data-decimals="' . $decimals .'"';
				
				$functions .= ' onmouseenter="window.WPRecipeMaker.userRating.enter(this)"';
				$functions .= ' onfocus="window.WPRecipeMaker.userRating.enter(this)"';
				$functions .= ' onmouseleave="window.WPRecipeMaker.userRating.leave(this)"';
				$functions .= ' onblur="window.WPRecipeMaker.userRating.leave(this)"';
				$functions .= ' onclick="window.WPRecipeMaker.userRating.click(this, event)"';
				$functions .= ' onkeypress="window.WPRecipeMaker.userRating.click(this, event)"';

				// Add Modal details.
				$modal_uid = WPRMP_User_Rating::get_modal_uid();
				$data .= 'data-modal-uid="' . esc_attr( $modal_uid ) . '"';
			} else {
				$data = '';
			}

			// Output stars.
			$output .= '<div id="' . $id . '" class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $data . '>';

			$stars = array(
				1 => __( 'Rate this recipe 1 out of 5 stars', 'wp-recipe-maker' ),
				2 => __( 'Rate this recipe 2 out of 5 stars', 'wp-recipe-maker' ),
				3 => __( 'Rate this recipe 3 out of 5 stars', 'wp-recipe-maker' ),
				4 => __( 'Rate this recipe 4 out of 5 stars', 'wp-recipe-maker' ),
				5 => __( 'Rate this recipe 5 out of 5 stars', 'wp-recipe-maker' ),
			);

			foreach ( $stars as $i => $label ) {
				$star_classes = array(
					'wprm-rating-star',
					'wprm-rating-star-' . $i,
				);

				// Get star class.
				if ( $i <= $rating_value ) {
					$star_classes[] = 'wprm-rating-star-full';
				} else {
					$difference = $rating_value - $i + 1;
					if ( 0 < $difference && $difference <= 0.33 ) {
						$star_classes[] = 'wprm-rating-star-33';
					} elseif ( 0 < $difference && $difference <= 0.5 ) {
						$star_classes[] = 'wprm-rating-star-50';
					} elseif( 0 < $difference && $difference <= 0.66 ) {
						$star_classes[] = 'wprm-rating-star-66';
					} elseif( 0 < $difference && $difference <= 1 ) {
						$star_classes[] = 'wprm-rating-star-full';
					} else {
						$star_classes[] = 'wprm-rating-star-empty';
					}
				}

				$accessibility = '';
				if ( $voteable && WPRMP_User_Rating::is_user_allowed_to_vote() ) {
					$accessibility = ' role="button" tabindex="0" aria-label="' . $label . '"';
				}

				// Style.
				$style = WPRM_SC_Rating::get_star_style( $i, $atts );

				$output .= '<span class="' . esc_attr( implode( ' ', $star_classes ) ) . '" data-rating="' . $i . '" data-color="' . $color . '"' . $accessibility . $functions . $style . '>';
				$output .= apply_filters( 'wprm_recipe_rating_star_icon', WPRM_Icon::get( $icon, $color ) );
				$output .= '</span>';
			}
		}

		return $output;
	}
}

WPRMP_SC_Rating::init();