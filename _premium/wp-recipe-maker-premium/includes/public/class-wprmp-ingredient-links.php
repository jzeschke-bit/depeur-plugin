<?php
/**
 * Handle the ingredient links in the recipe modal.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the ingredient links in the recipe modal.
 *
 * @since      1.3.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Ingredient_Links {

	/**
	 * Get the ingredient link for a specific ingredient.
	 *
	 * @since    1.3.0
	 * @param    mixed $ingredient_name_or_id Name or ID of the ingredient to get the link for.
	 */
	public static function get_ingredient_link( $ingredient_name_or_id, $with_count = false ) {
		if ( is_int( $ingredient_name_or_id ) ) {
			$ingredient_id = $ingredient_name_or_id;
		} else {
			$ingredient_id = WPRM_Recipe_Sanitizer::get_ingredient_id( $ingredient_name_or_id );
		}

		$link = array(
			'url' => '',
			'nofollow' => 'default',
			'eafl' => '',
		);

		if ( $ingredient_id ) {
			// Easy Affiliate Links integration.
			if ( class_exists( 'EAFL_Link_Manager' ) ) {
				$eafl = get_term_meta( $ingredient_id, 'wprmp_ingredient_eafl', true );

				if ( $eafl ) {
					$eafl_link = EAFL_Link_Manager::get_link( $eafl );

					if ( $eafl_link ) {
						$link['eafl'] = $eafl;
					}
				}
			}

			// Regular links feature.
			$link['url'] = get_term_meta( $ingredient_id, 'wprmp_ingredient_link', true );

			$link_nofollow = get_term_meta( $ingredient_id, 'wprmp_ingredient_link_nofollow', true );
			$link['nofollow'] = in_array( $link_nofollow, array( 'default', 'nofollow', 'follow', 'sponsored' ), true ) ? $link_nofollow : 'default';

			if ( $with_count ) {
				$term_object = get_term( $ingredient_id, 'wprm_ingredient' );

				if ( ! is_wp_error( $term_object ) ) {
					$link['count'] = $term_object->count;
				}
			}
		}

		return $link;
	}

	/**
	 * Update links for a set of ingredients.
	 *
	 * @since    1.3.0
	 * @param    mixed $ingredients Ingredients with links to update to.
	 */
	public static function update_ingredient_links( $ingredients ) {
		foreach ( $ingredients as $index => $link ) {
			// Allow both associative and regular style.
			if ( isset( $link['name'] ) ) {
				$name = $link['name'];
			} else {
				$name = $index;
			}
			
			// Sanitize name before lookup.
			$name = WPRM_Recipe_Sanitizer::sanitize_html( $name );

			// Find or create term.
			$term = term_exists( $name, 'wprm_ingredient' );

			if ( 0 === $term || null === $term ) {
				$term = wp_insert_term( $name, 'wprm_ingredient' );
			}

			if ( is_wp_error( $term ) ) {
				if ( isset( $term->error_data['term_exists'] ) ) {
					$term_id = $term->error_data['term_exists'];
				} else {
					$term_id = 0;
				}
			} else {
				$term_id = $term['term_id'];
			}

			// Update term meta.
			if ( $term_id ) {
				if ( isset( $link['url'] ) ) {
					update_term_meta( $term_id, 'wprmp_ingredient_link', $link['url'] );
				}

				if ( isset( $link['nofollow'] ) ) {
					update_term_meta( $term_id, 'wprmp_ingredient_link_nofollow', $link['nofollow'] );
				}

				// Update term meta for Easy Affiliate Links integration.
				if ( isset( $link['eafl'] ) ) {
					$eafl = intval( $link['eafl'] );
					if ( 0 === $eafl ) {
						delete_term_meta( $term_id, 'wprmp_ingredient_eafl' );
					} else {
						update_term_meta( $term_id, 'wprmp_ingredient_eafl', $eafl );
					}
				}
			}
		}
	}
}
