<?php
/**
 * Responsible for importing WP Ultimate Recipe recipes.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin/import
 */

/**
 * Responsible for importing WP Ultimate Recipe recipes.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin/import
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import_Wpultimaterecipe {
	public static function init() {
		add_filter( 'wprm_import_settings_wpultimaterecipe', array( __CLASS__, 'settings' ) );
		add_filter( 'wprm_import_recipe_wpultimaterecipe', array( __CLASS__, 'recipe' ), 10, 3 );
	}

	/**
	 * Adjust the WP Ultimate Recipe import settings.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $html Current settings HTML.
	 */
	public static function settings( $html ) {
		// Match custom fields.
		if ( WPRM_Addons::is_active( 'custom-fields' ) ) {
			$wpurp_custom_fields = get_option( 'wpurp_custom_fields', array() );

			if ( is_array( $wpurp_custom_fields ) && 0 < count( $wpurp_custom_fields ) ) {
				$html .= '<h4>Custom Fields</h4>';

				$wprm_custom_fields = WPRMPCF_Manager::get_custom_fields();

				if ( ! $wprm_custom_fields ) {
					$html .= "<p>You need to create the custom fields in WP Recipe Maker first if you want to import them.</p>";
				}

				foreach ( $wprm_custom_fields as $wprm_key => $wprm_options ) {
					if ( 'image' !== $wprm_options['type'] ) {
						$html .= '<label for="wpurp-custom-fields-' . $wprm_key . '">' . $wprm_options['name'] . ':</label> ';
						$html .= '<select name="wpurp-custom-fields-' . $wprm_key . '" id="wpurp-custom-fields-' . $wprm_key . '">';
						$html .= "<option value=\"\">Don't import anything for this custom field</option>";

						foreach ( $wpurp_custom_fields as $wpurp_key => $wpurp_options ) {
							$selected = $wprm_key === $wpurp_key ? ' selected="selected"' : '';
							$html .= '<option value="' . esc_attr( $wpurp_key ) . '"' . esc_html( $selected ) . '>' . esc_html( $wpurp_options['name'] ) . '</option>';
						}

						$html .= '</select>';
						$html .= '<br />';
					}
				}
			}
		}
	
		return $html;
	}

	/**
	 * Adjust the WP Ultimate Recipe imported recipe.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $recipe 		Current imported recipe.
	 * @param	mixed $id 			IDs of imported recipe.
	 * @param	array $post_data 	POST data passed along when submitting the form.
	 */
	public static function recipe( $recipe, $id, $post_data ) {
		// Custom fields.
		if ( WPRM_Addons::is_active( 'custom-fields' ) ) {
			$post_meta = get_post_custom( $id );

			$custom_fields = array();
			$wprm_custom_fields = WPRMPCF_Manager::get_custom_fields();

			foreach ( $wprm_custom_fields as $wprm_key => $wprm_options ) {
				$wpurp_key = isset( $post_data[ 'wpurp-custom-fields-' . $wprm_key ] ) ? $post_data[ 'wpurp-custom-fields-' . $wprm_key ] : false;

				if ( $wpurp_key ) {
					$value = isset( $post_meta[ $wpurp_key ] ) ? $post_meta[ $wpurp_key ][0] : false;

					if ( $value ) {
						$custom_fields[ $wprm_key ] = $value;
					}
				}
			}

			if ( $custom_fields ) {
				$recipe['custom_fields'] = $custom_fields;
			}
		}
	
		return $recipe;
	}
}
WPRMP_Import_Wpultimaterecipe::init();
