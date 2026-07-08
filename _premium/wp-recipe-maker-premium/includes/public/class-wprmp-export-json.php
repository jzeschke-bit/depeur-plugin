<?php
/**
 * Handle the export of recipes to JSON.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the export of recipes to JSON.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Export_JSON {

	/**
	 * Export recipes to JSON.
	 *
	 * @since	5.2.0
	 * @param	int 	$recipe_ids IDs of the recipes to export.
	 * @param	mixed 	$options	Export options to use.
	 */
	public static function bulk_edit_export( $recipe_ids, $options ) {
		$export = array();

		foreach ( $recipe_ids as $recipe_id ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			if ( $recipe ) {
				$export[] = self::get_data_for_export( $recipe, $options );
			}
		}

		$json = json_encode( $export, JSON_PRETTY_PRINT );
		
		// Create file.
		$upload_dir = wp_upload_dir();
		$slug = 'wprm';
		$dir = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $slug );
		$url = $upload_dir['baseurl'] . '/' . $slug . '/';

		wp_mkdir_p( $dir );

		$filename = 'WPRM Recipe Export.json';
		$filepath = $dir . $filename;

		$f = fopen( $filepath, 'wb' );
		if ( ! $f ) {
			wp_die( 'Unable to create recipe export file. Check file permissions' );
		}

		fwrite( $f, $json );
		fclose( $f );

		return array(
			'result' => __( 'Your recipes have been exported to:', 'wp-recipe-maker' ) . '<br/><a href="' . esc_url( $url . $filename ) . '?t=' . time() . '" target="_blank">' . $url . $filename . '</a>',
		);
	}

	/**
	 * Get recipe data for export.
	 *
	 * @since	7.1.0
	 * @param	mixed $recipe Recipe to export.
	 * @param	mixed $options Export options to use.
	 */
	public static function get_data_for_export( $recipe, $options ) {
		$data = $recipe->get_data( 'export' );
		$data = self::clean_up_recipe_for_export( $data );

		$type = isset( $options['type'] ) ? $options['type'] : 'recipe';
		$with_user_ratings = isset( $options['user_ratings'] ) && $options['user_ratings'];

		if ( 'with_parent' === $type ) {
			$parent = $recipe->parent_post();
			$data['parent'] = self::get_post_for_export( $parent );
		}

		if ( $with_user_ratings ) {
			$data['user_ratings'] = self::get_user_ratings_for_export( $recipe );
		}

		return $data;
	}

	/**
	 * Get post data for export.
	 *
	 * @since	9.1.0
	 * @param	mixed $post Post to export.
	 */
	public static function get_post_for_export( $post ) {
		$data = false;

		if ( $post ) {
			$data = array();

			$data['ID'] = $post->ID;
			$data['post_date'] = $post->post_date;
			$data['post_name'] = $post->post_name;
			$data['post_title'] = $post->post_title;
			$data['post_content'] = $post->post_content;
			$data['post_excerpt'] = $post->post_excerpt;
			$data['post_status'] = $post->post_status;
			$data['post_type'] = $post->post_type;

			// Featured image.
			$post_image_id = get_post_thumbnail_id( $post->ID );

			if ( $post_image_id ) {
				$thumb = wp_get_attachment_image_src( $post_image_id, 'full' );
				$post_image_url = $thumb && isset( $thumb[0] ) ? $thumb[0] : false;

				if ( $post_image_url ) {
					$data['image_url'] = $post_image_url;
				}
			}
			
			// Taxonomies.
			$data['tags'] = array();

			$taxonomies = get_taxonomies( '', 'names' );
			$terms = wp_get_object_terms( $post->ID, $taxonomies );

			foreach ( $terms as $term ) {
				if ( ! array_key_exists( $term->taxonomy, $data['tags'] ) ) {
					$data['tags'][ $term->taxonomy ] = array();
				}

				$data['tags'][ $term->taxonomy ][] = $term->name;
			}
		}

		return $data;
	}

	/**
	 * Get user ratings data for export.
	 *
	 * @since	9.1.0
	 * @param	mixed $recipe Recipe to export the ratings for.
	 */
	public static function get_user_ratings_for_export( $recipe ) {
		$data = array();

		if ( $recipe ) {
			$user_ratings = WPRMP_User_Rating::get_ratings_for( $recipe->id() );

			$users = array();
			foreach( $user_ratings as $user_rating ) {
				$user_rating = (array) $user_rating;

				$rating_data = array(
					'date' => $user_rating['date'],
					'ip' => $user_rating['ip'],
					'rating' => $user_rating['rating'],
				);

				// If related to a user, we need to add their email address as the ID might not match on the other site.
				$user_id = intval( $user_rating['user_id'] );

				if ( 0 < $user_id ) {
					$rating_data['user_id'] = $user_id;

					if ( ! array_key_exists( $user_id, $users ) ) {
						$users[ $user_id ] = get_userdata( $user_id );
					}

					if ( $users[ $user_id ] && $users[ $user_id ]->user_email ) {
						$rating_data['user_email'] = $users[ $user_id ]->user_email;
					}
				}

				// Add to array.
				$data[] = $rating_data;
			}
		}

		return $data;
	}

	/**
	 * Clean up recipe data for export.
	 *
	 * @since	5.2.0
	 * @param	mixed $data Recipe data to export.
	 */
	public static function clean_up_recipe_for_export( $data ) {
		unset( $data[ 'image_id' ] );
		unset( $data[ 'pin_image_id' ] );
		unset( $data[ 'video_id' ] );
		unset( $data[ 'video_thumb_url' ] );
		unset( $data[ 'ingredients' ] ); // Use ingredients_flat for easier editing.
		unset( $data[ 'instructions' ] ); // Use instructions_flat for easier editing.

		foreach ( $data['tags'] as $tag => $terms ) {
			$term_names = array();

			foreach ( $terms as $term ) {
				if ( 'suitablefordiet' === $tag && isset( $term->actual_name ) ) {
					$term_names[] = $term->actual_name;
				} else {
					$term_names[] = $term->name;
				}
			}

			$data['tags'][ $tag ] = $term_names;
		}

		foreach ( $data['equipment'] as $index => $equipment ) {
			unset( $data['equipment'][ $index ]['id'] );
		}

		foreach ( $data['ingredients_flat'] as $index => $ingredient ) {
			unset( $data['ingredients_flat'][ $index ]['id'] );
		}

		foreach ( $data['instructions_flat'] as $index => $ingredient ) {
			unset( $data['instructions_flat'][ $index ]['image'] );
		}

		if ( isset( $data['custom_fields'] ) ) {
			foreach ( $data['custom_fields'] as $index => $custom_field ) {
				if ( is_array( $custom_field ) && isset( $custom_field['id'] ) ) {
					unset( $data['custom_fields'][ $index ]['id'] );
				}
			}
		}

		return $data;
	}
}
