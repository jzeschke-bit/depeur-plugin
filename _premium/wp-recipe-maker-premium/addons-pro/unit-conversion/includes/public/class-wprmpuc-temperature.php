<?php
/**
 * Handle unit conversion for temperatures.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.10.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 */

/**
 * Handle unit conversion for temperatures.
 *
 * @since      8.10.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Temperature {

	/**
	 * Register actions and filters.
	 *
	 * @since    8.10.0
	 */
	public static function init() {
		add_filter( 'wprm_temperature_shortcode', array( __CLASS__, 'temperature_shortcode' ), 10, 2 );
	}

	/**
	 * Alter the temperature shortcode output.
	 *
	 * @since    8.10.0
	 * @param    string	$output Current shortcode output.
	 * @param    array	$atts Shortcode attributes.
	 */
	public static function temperature_shortcode( $output, $atts ) {
		if ( $output ) {
			// Second unit output
			if ( 'both' === WPRM_Settings::get( 'unit_conversion_temperature' ) && WPRM_Addons::is_active( 'unit-conversion' ) && WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
				$value = WPRM_Recipe_Parser::parse_quantity( $atts['value'] );
				$unit = strtoupper( sanitize_key( $atts['unit'] ) );
				$new_unit = 'C' === $unit ? 'F' : 'C';

				$converted_output = '<span class="wprm-temperature-converted"> (';

				// Recalculate value
				if ( 'C' === $new_unit ) {
					$value = ( $value - 32 ) * 5 / 9;
				} else {
					$value = ( $value * 9 / 5 ) + 32;
				}
				$value = WPRM_Recipe_Parser::format_quantity( $value, 0 );

				// Value output
				$converted_output .= '<span class="wprm-temperature-converted-value">';
				$converted_output .= esc_html( $value );
				$converted_output .= '</span>';

				// Unit output
				$converted_output .= '<span class="wprm-temperature-converted-unit">';
				switch ( $new_unit ) {
					case 'C':
						$converted_output .= ' °C';
						break;
					case 'F':
						$converted_output .= ' °F';
						break;
				}
				$converted_output .= '</span>';

				$converted_output .= ')</span>';

				// Insert before last occurrence of </span>.
				$pos = strrpos( $output, '</span>' );
				if( $pos !== false ) {
					$output = substr_replace( $output, $converted_output, $pos );
				}
			}
		}

		return $output;
	}
}

WPRMPUC_Temperature::init();
