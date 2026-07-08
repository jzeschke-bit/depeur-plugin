<?php
/**
 * Handles translations.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 */

/**
 * Handles translations.
 *
 * @since      7.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPT_Translate {

	/**
	 * Translate text.
	 *
	 * @since    7.0.0
	 * @param	 mixed $text Text to translate.
	 */
	public static function translate( $text ) {
		$text = trim( $text );

		if ( $text && WPRM_Settings::get( 'translate_enabled' ) ) {
			$source = WPRM_Settings::get( 'translate_source_language' );
			return WPRMPT_Api::translate( $text, $source );
		}

		return false;
	}

	/**
	 * Translate text or keep text if no translation possible.
	 *
	 * @since    7.0.0
	 * @param	 mixed $text Text to translate.
	 */
	public static function translate_or_keep( $text ) {
		$translation = self::translate( $text );

		return $translation ? $translation : $text;
	}
}
