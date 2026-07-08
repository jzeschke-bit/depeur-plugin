<?php
/**
 * Manage custom fields.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 */

/**
 * Manage custom fields.
 *
 * @since      5.2.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/custom-fields
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/custom-fields/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPCF_Manager {

	/**
	 * Cached custom fields.
	 *
	 * @since	5.2.0
	 * @access	private
	 * @var		array $custom_fields Cached custom fields.
	 */
	private static $custom_fields = false;

	/**
	 * Get custom fields.
	 *
	 * @since    5.2.0
	 */
	public static function get_custom_fields() {
		if ( false === self::$custom_fields ) {
			self::$custom_fields = get_option( 'wprm_custom_fields', array() );
		}

		return self::$custom_fields;
	}

	/**
	 * Get a specific custom field.
	 *
	 * @since    5.2.0
	 * @param	 mixed $key Key of the custom field.
	 */
	public static function get_custom_field( $key ) {
		$custom_fields = WPRMPCF_Manager::get_custom_fields();

		if ( array_key_exists( $key, $custom_fields ) ) {
			return $custom_fields[ $key ];
		}

		return false;
	}

	/**
	 * Update custom fields.
	 *
	 * @since    5.2.0
	 * @param	 mixed $custom_fields New custom fields.
	 */
	private static function update_custom_fields( $custom_fields ) {
		update_option( 'wprm_custom_fields', $custom_fields );
		self::$custom_fields = $custom_fields;
	}

	/**
	 * Sanitize custom field.
	 *
	 * @since    5.2.0
	 * @param	 mixed $unsanitized Custom field to sanitize.
	 */
	public static function sanitize_custom_field( $unsanitized ) {
		$field = array();

		$field['key'] = isset( $unsanitized['key'] ) ? sanitize_key( $unsanitized['key'] ) : '';
		$field['name'] = isset( $unsanitized['name'] ) ? sanitize_text_field( $unsanitized['name'] ) : '';

		// Make sure type is valid or default to "text".
		$field['type'] = 'text';
		$type_options = self::get_type_options();
		foreach ( $type_options as $type_option ) {
			if ( $type_option['value'] === $unsanitized['type'] ) {
				$field['type'] = $unsanitized['type'];
				break;
			}
		}

		// Required fields.
		if ( ! $field['key'] || ! $field['name'] ) {
			return false;
		}

		return $field;
	}

	/**
	 * Create custom field.
	 *
	 * @since    5.2.0
	 * @param	 mixed $field Custom field to create.
	 */
	public static function create_custom_field( $field ) {
		$field = self::sanitize_custom_field( $field );

		if ( $field ) {
			$custom_fields = self::get_custom_fields();

			if ( ! array_key_exists( $field['key'], $custom_fields ) ) {
				$custom_fields[ $field['key'] ] = $field;
				self::update_custom_fields( $custom_fields );

				return $field;
			}
		}

		return false;
	}

	/**
	 * Update custom field.
	 *
	 * @since    5.2.0
	 * @param	 mixed $field Custom field to update.
	 */
	public static function update_custom_field( $field ) {
		$field = self::sanitize_custom_field( $field );

		if ( $field ) {
			$custom_fields = self::get_custom_fields();

			if ( array_key_exists( $field['key'], $custom_fields ) ) {
				$custom_fields[ $field['key'] ] = $field;
				self::update_custom_fields( $custom_fields );

				return $field;
			}
		}

		return false;
	}

	/**
	 * Delete custom field.
	 *
	 * @since    5.2.0
	 * @param	 mixed $key Key of the custom field to delete.
	 */
	public static function delete_custom_field( $key ) {
		if ( $key ) {
			$custom_fields = self::get_custom_fields();

			if ( array_key_exists( $key, $custom_fields ) ) {
				unset( $custom_fields[ $key ] );
				self::update_custom_fields( $custom_fields );

				return true;
			}
		}

		return false;
	}

	/**
	 * Get custom field type options.
	 *
	 * @since    5.2.0
	 */
	public static function get_type_options() {
		return array(
			array( 'value' => 'text', 		'label' => __( 'Text', 'wp-recipe-maker-premium' ) ),
			array( 'value' => 'textarea', 	'label' => __( 'Rich Textarea', 'wp-recipe-maker-premium' ) ),
			array( 'value' => 'classic', 	'label' => __( 'Classic Editor', 'wp-recipe-maker-premium' ) ),
			array( 'value' => 'link', 		'label' => __( 'Link', 'wp-recipe-maker-premium' ) ),
			array( 'value' => 'email', 		'label' => __( 'Email', 'wp-recipe-maker-premium' ) ),
			array( 'value' => 'image', 		'label' => __( 'Image', 'wp-recipe-maker-premium' ) ),
		);
	}
}
