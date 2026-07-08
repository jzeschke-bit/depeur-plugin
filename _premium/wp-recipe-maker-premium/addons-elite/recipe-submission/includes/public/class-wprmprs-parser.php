<?php
/**
 * Parse the Recipe Submission form data.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 */

/**
 * Parse the Recipe Submission form data.
 *
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Parser {
	/**
	 * Process data submitted via the recipe submission form.
	 *
	 * @since	2.1.0
	 * @param	array $data Data passed in through the recipe submission form.
	 */
	public static function process_recipe_submission_form( $data ) {
		// User facts.
		$user = array();

		$user['id'] = get_current_user_id();
		$user['name'] = isset( $data['user_name'] ) ? sanitize_text_field( $data['user_name'] ) : '';
		$user['email'] = isset( $data['user_email'] ) ? sanitize_text_field( $data['user_email'] ) : '';

		$recipe = self::get_recipe( $data );

		WPRMPRS_Saver::save_recipe( $user, $recipe );
	}

	/**
	 * Get recipe from recipe submission data.
	 *
	 * @since	2.1.0
	 * @param	array $data Recipe submission data.
	 */
	private static function get_recipe( $data ) {
		$recipe = array();

		// Simple matching.
		$recipe['name'] = isset( $data['recipe_name'] ) ? $data['recipe_name'] : '';
		$recipe['summary'] = isset( $data['recipe_summary'] ) ? $data['recipe_summary'] : '';
		$recipe['notes'] = isset( $data['recipe_notes'] ) ? $data['recipe_notes'] : '';

		// Get maximum sizes for file uploads.
		$wp_max_size = wp_max_upload_size();
		$maybe_image_max_size = floatval( WPRM_Settings::get( 'recipe_submission_max_file_size_image' ) ) * 1024 * 1024;
		$maybe_video_max_size = floatval( WPRM_Settings::get( 'recipe_submission_max_file_size_video' ) ) * 1024 * 1024;

		if ( 0 < $maybe_image_max_size && $maybe_image_max_size < $wp_max_size ) {
			$image_max_size = $maybe_image_max_size;
		} else { 
			$image_max_size = $wp_max_size;
		}

		if ( 0 < $maybe_video_max_size && $maybe_video_max_size < $wp_max_size ) {
			$video_max_size = $maybe_video_max_size;
		} else { 
			$video_max_size = $wp_max_size;
		}

		// Image.
		if ( $_FILES && isset( $_FILES['recipe_image'] ) ) {
			if ( $_FILES['recipe_image']['size'] <= $image_max_size ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );

				$attachment_id = media_handle_upload( 'recipe_image', 0 );

				if ( ! is_wp_error( $attachment_id ) ) {
					$recipe['image_id'] = $attachment_id;
				}
			}
		}

		// Video.
		if ( $_FILES && isset( $_FILES['recipe_video_upload'] ) ) {
			if ( $_FILES['recipe_video_upload']['size'] <= $video_max_size ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );

				$attachment_id = media_handle_upload( 'recipe_video_upload', 0 );

				if ( ! is_wp_error( $attachment_id ) ) {
					$recipe['video_id'] = $attachment_id;
				}
			}
		}
		$recipe['video_embed'] = isset( $data['recipe_video_embed'] ) ? $data['recipe_video_embed'] : '';

		// Author.
		$recipe['author_name'] = isset( $data['user_name'] ) ? $data['user_name'] : '';

		if ( '' !== trim( $recipe['author_name'] ) ) {
			$recipe['author_display'] = 'custom';
		}

		// Servings.
		$data_servings = isset( $data['recipe_servings'] ) ? $data['recipe_servings'] : '';
		$match = preg_match( '/^\s*\d+/', $data_servings, $servings_array );
		if ( 1 === $match ) {
				$servings = str_replace( ' ','', $servings_array[0] );
		} else {
				$servings = '';
		}

		$servings_unit = preg_replace( '/^\s*\d+\s*/', '', $data_servings );

		$recipe['servings'] = $servings;
		$recipe['servings_unit'] = $servings_unit;

		// Times.
		$recipe['prep_time'] = isset( $data['recipe_prep_time'] ) ? self::parse_time( $data['recipe_prep_time'] ) : 0;
		$recipe['cook_time'] = isset( $data['recipe_cook_time'] ) ? self::parse_time( $data['recipe_cook_time'] ) : 0;
		$recipe['total_time'] = isset( $data['recipe_total_time'] ) ? self::parse_time( $data['recipe_total_time'] ) : 0;

		// Cost.
		$recipe['cost'] = isset( $data['recipe_cost'] ) ? $data['recipe_cost'] : '';

		// Tags.
		$recipe['tags'] = array();

		$data_courses = isset( $data['recipe_courses'] ) ? str_replace( ';', ',', $data['recipe_courses'] ) : '';
		$recipe['tags']['course'] = array_map( 'trim', explode( ',', $data_courses ) );

		$data_cuisines = isset( $data['recipe_courses'] ) ? str_replace( ';', ',', $data['recipe_cuisines'] ) : '';
		$recipe['tags']['cuisine'] = array_map( 'trim', explode( ',', $data_cuisines ) );

		// Custom Taxonomies.
		$wprm_taxonomies = WPRM_Taxonomies::get_taxonomies();
		foreach ( $wprm_taxonomies as $wprm_taxonomy => $options ) {
			$wprm_key = substr( $wprm_taxonomy, 5 );

			if ( isset( $data[ 'recipe_custom_taxonomy_' . $wprm_key ] ) ) {
				$data_taxonomy = str_replace( ';', ',', $data[ 'recipe_custom_taxonomy_' . $wprm_key ] );
				$recipe['tags'][ $wprm_key ] = array_map( 'trim', explode( ',', $data_taxonomy ) );
			}

			if ( isset( $data[ 'recipe_custom_taxonomy_id_' . $wprm_key ] ) ) {
				$data_taxonomy = is_array( $data[ 'recipe_custom_taxonomy_id_' . $wprm_key ] ) ? $data[ 'recipe_custom_taxonomy_id_' . $wprm_key ] : array( $data[ 'recipe_custom_taxonomy_id_' . $wprm_key ] );
				$recipe['tags'][ $wprm_key ] = array_map( 'intval', $data_taxonomy );
			}
		}

		// Equipment.
		$data_equipment = isset( $data['recipe_equipment'] ) ? $data['recipe_equipment'] : '';
		$equipment = array();

		$items = explode( PHP_EOL, $data_equipment );
		foreach ( $items as $item ) {
			$name = trim( strip_tags( $item ) );

			if ( ! empty( $name ) ) {
				$equipment[] = array(
					'id' => WPRM_Recipe_Sanitizer::get_equipment_id( $name ),
					'name' => $name,
				);
			}
		}
		$recipe['equipment'] = $equipment;

		// Ingredients.
		$data_ingredients = isset( $data['recipe_ingredients'] ) ? self::parse_blob( $data['recipe_ingredients'] ) : array();
		$ingredients = array();

		foreach ( $data_ingredients as $data_group ) {
			$group = array(
				'name' => $data_group['name'],
				'ingredients' => array(),
			);

			foreach ( $data_group['items'] as $data_item ) {
				$text = trim( strip_tags( $data_item ) );

				if ( ! empty( $text ) ) {
					$group['ingredients'][] = array(
						'raw' => $text,
					);
				}
			}

			$ingredients[] = $group;
		}
		$recipe['ingredients'] = $ingredients;

		// Instructions.
		$data_instructions = isset( $data['recipe_instructions'] ) ? self::parse_blob( $data['recipe_instructions'] ) : array();
		$instructions = array();

		foreach ( $data_instructions as $data_group ) {
			$group = array(
				'name' => $data_group['name'],
				'instructions' => array(),
			);

			foreach ( $data_group['items'] as $data_item ) {
				$text = trim( strip_tags( $data_item ) );

				if ( ! empty( $text ) ) {
					$group['instructions'][] = array(
						'text' => $text,
					);
				}
			}

			$instructions[] = $group;
		}
		$recipe['instructions'] = $instructions;

		// Custom fields.
		$recipe['custom_fields'] = array();

		$custom_fields = WPRMPCF_Manager::get_custom_fields();
		foreach ( $custom_fields as $key => $options ) {
			if ( 'image' === $options['type'] ) {
				if ( $_FILES && isset( $_FILES[ 'recipe_custom_field_' . $key ] ) ) {
					if ( $_FILES[ 'recipe_custom_field_' . $key ]['size'] <= $image_max_size ) {
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						require_once( ABSPATH . 'wp-admin/includes/media.php' );
			
						$attachment_id = media_handle_upload( 'recipe_custom_field_' . $key, 0 );
			
						if ( ! is_wp_error( $attachment_id ) ) {
							$recipe['custom_fields'][ $key ] = array(
								'id' => $attachment_id,
							);
						}
					}
				}
			} else {
				if ( isset( $data[ 'recipe_custom_field_' . $key ] ) ) {
					$recipe['custom_fields'][ $key ] = $data[ 'recipe_custom_field_' . $key ];
				}
			}
		}

		return $recipe;
	}

	/**
	 * Parse recipe submission time field to minutes.
	 *
	 * @since	2.1.0
	 * @param	array $time_string Full text time field to parse.
	 */
	private static function parse_time( $time_string ) {
		// Add space between numbers and letters.
		$time_string = preg_replace( "/([[:alpha:]])([[:digit:]])/", "\\1 \\2", $time_string );
		$time_string = preg_replace( "/([[:digit:]])([[:alpha:]])/", "\\1 \\2", $time_string );

		// Replace common abbreviations.
		$time_string = preg_replace( '/\bd\b/i' , 'days' , $time_string );
		$time_string = preg_replace( '/\bhr\b/i' , 'hours' , $time_string );
		$time_string = preg_replace( '/\bh\b/i' , 'hours' , $time_string );
		$time_string = preg_replace( '/\bmin\b/i' , 'minutes' , $time_string );
		$time_string = preg_replace( '/\bmn\b/i' , 'minutes' , $time_string );
		$time_string = preg_replace( '/\bm\b/i' , 'minutes' , $time_string );

		// Replace common translations causing issues.
		$time_string = str_ireplace( 'minuten', 'minutes', $time_string );
		$time_string = str_ireplace( 'minuut', 'minute', $time_string );

		// Replace translations.
		$time_string = str_ireplace( __( 'days', 'wp-recipe-maker' ), 'days', $time_string );
		$time_string = str_ireplace( __( 'day', 'wp-recipe-maker' ), 'day', $time_string );
		$time_string = str_ireplace( __( 'hours', 'wp-recipe-maker' ), 'hours', $time_string );
		$time_string = str_ireplace( __( 'hour', 'wp-recipe-maker' ), 'hour', $time_string );
		$time_string = str_ireplace( __( 'minutes', 'wp-recipe-maker' ), 'minutes', $time_string );
		$time_string = str_ireplace( __( 'minute', 'wp-recipe-maker' ), 'minute', $time_string );

		// Remove parentheses.
		$time_string = preg_replace( '/\([^\)]+\)/' , '' , $time_string );

		// Calculate time.
		$now = time();
		$time = strtotime( $time_string, $now );

		$minutes = $time ? ( $time - $now ) / 60 : 0;

		return $minutes > 0 ? $minutes : intval( $time_string );
	}

	/**
	 * Ingredient or instructions blob to array.
	 *
	 * @since	2.1.0
	 * @param	mixed $component Blob to parse.
	 */
	private static function parse_blob( $component ) {
		$component_list = array();
		$component_group = array(
			'name' => '',
			'items' => array(),
		);

		$bits = explode( PHP_EOL, $component );
		foreach ( $bits as $bit ) {

			$test_bit = trim( $bit );
			if ( empty( $test_bit ) ) {
				continue;
			}
			if ( self::is_heading( $bit ) ) {
				$component_list[] = $component_group;

				$component_group = array(
					'name' => strip_tags( trim( $bit ) ),
					'items' => array(),
				);
			} else {
				$component_group['items'][] = trim( $bit );
			}
		}

		$component_list[] = $component_group;

		return $component_list;
	}

	/**
	 * Check if line is a heading.
	 *
	 * @since	2.1.0
	 * @param	mixed $string String to parse.
	 */
	private static function is_heading( $string ) {
		$string = trim( $string );
		// For The Red Beans:.
		if ( ':' === substr( $string, -1, 1 ) ) {
			return true;
		}

		return false;
	}
}
