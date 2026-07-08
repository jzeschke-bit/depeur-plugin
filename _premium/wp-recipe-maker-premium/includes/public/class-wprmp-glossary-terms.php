<?php
/**
 * Automatically show glossary terms.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.9.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Automatically show glossary terms.
 *
 * @since      8.9.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Glossary_Terms {

	private static $glossary_terms = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    8.9.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_shortcode_output', array( __CLASS__, 'recipe_shortcode_output' ) );
		add_filter( 'wprm_recipe_field', array( __CLASS__, 'recipe_field_overrides' ), 10, 2 );
	}

	/**
	 * Filter recipe shortcode output.
	 *
	 * @since    8.9.0
	 * @param    mixed $output	Current recipe shortcode output.
	 */
	public static function recipe_shortcode_output( $output ) {
		// Make sure shortcodes for added glossary terms are actually being processed.
		return do_shortcode( $output );
	}

	/**
	 * Maybe override to recipe field.
	 *
	 * @since    8.9.0
	 * @param    mixed $output	Current recipe field output.
	 * @param    mixed $field	Current recipe field getting output.
	 */
	public static function recipe_field_overrides( $output, $field ) {
		if ( ! is_admin() && ! wp_doing_ajax() && ! isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			if (
				( 'summary' === $field && WPRM_Settings::get( 'glossary_terms_automatic_summary' ) )
				|| ( 'notes' === $field && WPRM_Settings::get( 'glossary_terms_automatic_notes' ) )
			) {
				$output = self::add_glossary_terms( $output );
			}

			if ( 'equipment' === $field ) {
				foreach ( $output as $index => $equipment ) {
					if ( WPRM_Settings::get( 'glossary_terms_automatic_equipment' ) ) {
						if ( $equipment['name'] ) {
							$output[ $index ]['name'] = self::add_glossary_terms( $equipment['name'] );
						}
						if ( isset( $equipment['notes'] ) && $equipment['notes'] ) {
							$output[ $index ]['notes'] = self::add_glossary_terms( $equipment['notes'] );
						}
					}
				}
			}

			if ( 'ingredients' === $field ) {
				foreach ( $output as $group_index => $group ) {
					if ( isset( $group['name'] ) && $group['name'] && WPRM_Settings::get( 'glossary_terms_automatic_ingredient_headers' ) ) {
						$output[ $group_index ]['name'] = self::add_glossary_terms( $group['name'] );
					}

					if ( isset( $group['ingredients'] ) && $group['ingredients'] && WPRM_Settings::get( 'glossary_terms_automatic_ingredients' ) ) {
						foreach ( $group['ingredients'] as $ingredient_index => $ingredient ) {
							$output[ $group_index ]['ingredients'][ $ingredient_index ]['unit'] = self::add_glossary_terms( $ingredient['unit'] );
							$output[ $group_index ]['ingredients'][ $ingredient_index ]['name'] = self::add_glossary_terms( $ingredient['name'] );
							$output[ $group_index ]['ingredients'][ $ingredient_index ]['notes'] = self::add_glossary_terms( $ingredient['notes'] );
						}
					}
				}
			}

			if ( 'instructions' === $field ) {
				foreach ( $output as $group_index => $group ) {
					if ( isset( $group['name'] ) && $group['name'] && WPRM_Settings::get( 'glossary_terms_automatic_instruction_headers' ) ) {
						$output[ $group_index ]['name'] = self::add_glossary_terms( $group['name'] );
					}

					if ( isset( $group['instructions'] ) && $group['instructions'] && WPRM_Settings::get( 'glossary_terms_automatic_instruction_steps' ) ) {
						foreach ( $group['instructions'] as $instruction_index => $instruction ) {
							$output[ $group_index ]['instructions'][ $instruction_index ]['text'] = self::add_glossary_terms( $instruction['text'] );
						}
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Add glossary terms to text.
	 *
	 * @since    8.9.0
	 * @param    string $text	Text to add glossary terms to.
	 */
	public static function add_glossary_terms( $text ) {
		$terms = self::get_glossary_terms();

		foreach ( $terms as $term ) {
			$match = trim( $term['name'] );

			if ( $match ) {
				$match = preg_quote( $match, '/' );
				$match = str_replace( '\*', '.*', $match );
				$match = str_replace( '\?', '.', $match );

				$regex = '/(?<!\w)' . $match . '(?!\w)/';

				if ( 'insensitive' === WPRM_Settings::get( 'glossary_terms_automatic_matching' ) ) {
					$regex .= 'i';
				}

				$replace = '[wprm-glossary id=' . $term['id'] . ']$0[/wprm-glossary]';

				$text = preg_replace( $regex, $replace, $text );

				// Fix problems with glossary terms inside of inline ingredients.
				if ( strpos( $text, '[wprm-ingredient' ) !== false ) {
					// Find glossary terms inside of ingredient shortcode.
					preg_match_all( '/\[wprm-ingredient text=".*?"/', $text, $issues );

					foreach ( $issues[0] as $issue ) {
						$fixed_issue = preg_replace('/\[wprm-glossary id=\d+\](.*?)\[\/wprm-glossary\]/', '$1', $issue);
						$text = str_replace( $issue, $fixed_issue, $text );
					}
				}
			}
		}

		// Make sure there are no nested glossary term shortcodes. Keep outermost one.
		$fixed_text = $text;
		$search_pos = 0;

		while ( $search_pos <= strlen( $text ) ) {
			$outermost_glossary_pos = strpos( $text, '[wprm-glossary id=', $search_pos );
			
			// Stop if no more glossary term shortcodes found.
			if ( $outermost_glossary_pos === false ) {
				break;
			}

			// Need to start searching after this glossary shortcode.
			$search_pos = $outermost_glossary_pos;

			// We're inside a glossary term now. Check if there are nested glossary shortcodes.
			$search_pos++;
			$glossary_inside = 1;
			$loop_prevention = 0;

			do {
				$next_close = strpos( $text, '[/wprm-glossary]', $search_pos + 1 );
				$next_open = strpos( $text, '[wprm-glossary id=', $search_pos + 1 );

				$next_close = $next_close === false ? strlen( $text ) : $next_close;
				$next_open = $next_open === false ? strlen( $text ) : $next_open;

				if ( $next_open < $next_close ) {
					$glossary_inside++;
					$search_pos = $next_open + 1;
				} else {
					$glossary_inside--;
					$search_pos = $next_close + 1;
				}
			} while ( $glossary_inside > 0 && $loop_prevention < 100 );

			$outermost_shortcode = substr( $text, $outermost_glossary_pos, $search_pos - 1 - $outermost_glossary_pos );
			$outermost_shortcode_content_start = strpos( $outermost_shortcode, ']' ) + 1;
			$outermost_shortcode_content = substr( $outermost_shortcode, $outermost_shortcode_content_start );

			// Remove any glossary shortcodes inside of outermost content.
			$outermost_shortcode_content = preg_replace( '/\[wprm-glossary id=\d+\]/', '', $outermost_shortcode_content );
			$outermost_shortcode_content = str_replace( '[/wprm-glossary]', '', $outermost_shortcode_content );

			// Reconstruct outermost shortcode.
			$outermost_shortcode_fixed = substr( $outermost_shortcode, 0, $outermost_shortcode_content_start ) . $outermost_shortcode_content;
			
			// Replace in text.
			$fixed_text = str_replace( $outermost_shortcode, $outermost_shortcode_fixed, $fixed_text );
		}

		return $fixed_text;
	}

	/**
	 * Get glossary terms to look for.
	 *
	 * @since    8.9.0
	 * @param    string $text	Text to add glossary terms to.
	 */
	public static function get_glossary_terms() {
		if ( false === self::$glossary_terms ) {
			$terms = get_terms( array(
				'taxonomy'   => 'wprm_glossary_term',
				'hide_empty' => false,
			) );

			$glossary_terms = array();

			foreach ( $terms as $term ) {
				if ( $term->description ) {
					$glossary_terms[] = array(
						'id' => $term->term_id,
						'name' => $term->name,
						'tooltip' => $term->description,
					);
				}
			}

			// Sort by name length, longest first. Needed to remove nested glossary terms later on.
			usort( $glossary_terms, function( $a, $b ) {
				return strlen( $b['name'] ) - strlen( $a['name'] );
			} );
			
			self::$glossary_terms = $glossary_terms;
		}

		return self::$glossary_terms;
	}
}

WPRMP_Glossary_Terms::init();
