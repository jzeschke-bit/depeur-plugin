<?php
/**
 * Handle the export of taxonomies to JSON.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the export of recipes to JSON.
 *
 * @since      6.8.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Export_Taxonomies {

	/**
	 * Export taxonomy terms to JSON.
	 *
	 * @since	6.8.0
	 * @param	int $term_ids IDs of the terms to export.
	 */
	public static function bulk_edit_export( $term_ids ) {
		$export = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );

			if ( $term && ! is_wp_error( $term ) ) {
				$data = self::clean_up_term_for_export( $term );

				if ( $data ) {
					$export[] = $data;
				}
			}
		}

		$json = json_encode( $export, JSON_PRETTY_PRINT );
		
		// Create file.
		$upload_dir = wp_upload_dir();
		$slug = 'wprm';
		$dir = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . $slug );
		$url = $upload_dir['baseurl'] . '/' . $slug . '/';

		wp_mkdir_p( $dir );

		$filename = 'WPRM Taxonomy Export.json';
		$filepath = $dir . $filename;

		$f = fopen( $filepath, 'wb' );
		if ( ! $f ) {
			wp_die( 'Unable to create taxonomy export file. Check file permissions' );
		}

		fwrite( $f, $json );
		fclose( $f );

		return array(
			'result' => __( 'Your taxonomy terms have been exported to:', 'wp-recipe-maker' ) . '<br/><a href="' . esc_url( $url . $filename ) . '?t=' . time() . '" target="_blank">' . $url . $filename . '</a>',
		);
	}

	/**
	 * Clean up term data for export.
	 *
	 * @since	6.8.0
	 * @param	mixed $term Term to export.
	 */
	public static function clean_up_term_for_export( $term ) {
		// Only for WPRM taxonomies.
		if ( 'wprm_' !== substr( $term->taxonomy, 0, 5 ) ) {
			return false;
		}

		// Get any WPRM metadata.
		$term_meta = get_term_meta( $term->term_id );

		$meta = array();
		foreach ( $term_meta as $key => $value ) {
			// Only include WPRM or WPUPG meta (and compatibility for incorrectly named meta).
			if ( 'wprm' === substr( $key, 0, 4 ) || 'wpupg' === substr( $key, 0, 5 ) || 'wprpn_nutrition' === $key ) {
				$meta[ $key ] = maybe_unserialize( $value[0] );

				// Get image URLs for image IDs.
				if ( 'image_id' === substr( $key, -8 ) || 'wpupg_custom_image' === $key ) {
					$image_id = intval( $value[0] );

					if ( $image_id ) {
						$thumb = wp_get_attachment_image_src( $image_id, 'full' );

						if ( $thumb && isset( $thumb[0] ) ) {
							$meta[ $key . '_url' ] = $thumb[0];
						}
					}
				}
			}
		}

		return array(
			'taxonomy' => $term->taxonomy,
			'name' => $term->name,
			'meta' => $meta,
		);
	}
}
