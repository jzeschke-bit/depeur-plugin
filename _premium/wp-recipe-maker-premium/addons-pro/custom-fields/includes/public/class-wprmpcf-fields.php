<?php
/**
 * Handle custom fields for a recipe.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 */

/**
 * Handle custom fields for a recipe.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPCF_Fields {

	/**
	 * Get the value for a custom field for a recipe.
	 *
	 * @since    5.2.0
	 * @param	 mixed $recipe Recipe to get the value for.
	 * @param	 mixed $key  Custom field key to get the value for.
	 */
	public static function get( $recipe, $key ) {
		$custom_field = WPRMPCF_Manager::get_custom_field( $key );

		if ( $recipe && $custom_field ) {
			if ( 'image' === $custom_field['type'] ) {
				$image = $recipe->unserialize( $recipe->meta( 'wprm_custom_field_' . $key, false ) );

				// Get latest URL for image ID.
				if ( is_array( $image ) && isset( $image['id'] ) ) {
					$thumb = wp_get_attachment_image_src( $image['id'], 'full' );

					if ( $thumb && isset( $thumb[0] ) ) {
						$image['url'] = $thumb[0];
					}
				}

				return $image;
			} else {
				return $recipe->meta( 'wprm_custom_field_' . $key, false );
			}
		}

		return false;
	}

	/**
	 * Get the value for all custom fields for a recipe.
	 *
	 * @since    5.2.0
	 * @param	 mixed $recipe Recipe to get the values for.
	 */
	public static function get_all( $recipe ) {
		$values = array();
		$custom_fields = WPRMPCF_Manager::get_custom_fields();

		foreach ( $custom_fields as $key => $options ) {
			$values[ $key ] = self::get( $recipe, $key );
		}

		return $values;
	}

	/**
	 * Sanitize custom field values.
	 *
	 * @since    5.2.0
	 * @param	 mixed $values Custom field values
	 */
	public static function sanitize( $values ) {
		$sanitized = array();
		$custom_fields = WPRMPCF_Manager::get_custom_fields();

		foreach ( $custom_fields as $key => $options ) {
			if ( isset( $values[ $key ] ) ) {
				$value = $values[ $key ];

				switch( $options['type'] ) {
					case 'text':
					case 'link':
					case 'email':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;
					case 'textarea':
					case 'classic':
						$sanitized[ $key ] = WPRM_Recipe_Sanitizer::sanitize_html( $value );
						break;
					case 'image':
						if ( isset( $value['id'] ) ) {
							$sanitized[ $key ] = array(
								'id' => intval( $value['id'] ),
								'url' => isset( $value['url'] ) ? sanitize_text_field( $value['url'] ) : '',
							);
							break;
						}
					default:
						$sanitized[ $key ] = false;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Save custom field values.
	 *
	 * @since    5.2.0
	 * @param	 mixed $values Custom field values
	 */
	public static function save( $recipe_id, $values ) {
		$custom_fields = WPRMPCF_Manager::get_custom_fields();

		foreach ( $custom_fields as $key => $options ) {
			if ( isset( $values[ $key ] ) ) {
				$value = $values[ $key ];

				if ( false === $value ) {
					delete_post_meta( $recipe_id, 'wprm_custom_field_' . $key );
				} else {
					update_post_meta( $recipe_id, 'wprm_custom_field_' . $key, $value );
				}
			}
		}
	}
}
