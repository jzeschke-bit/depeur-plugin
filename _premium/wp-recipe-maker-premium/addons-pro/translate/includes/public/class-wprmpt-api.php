<?php
/**
 * Handle the Translation API.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/translate
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 */

/**
 * Handle the Translation API.
 *
 * @since      7.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/translate
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPT_Api {

	/**
	 * Get the Google Translate API Key.
	 *
	 * @since    7.0.0
	 */
	public static function get_api_key() {
		$api_key = WPRM_Settings::get( 'translate_api_key' );
		return $api_key ? $api_key : false;
	}

	/**
	 * Translate text.
	 *
	 * @since    7.0.0
	 * @param	 mixed $text 	Text to translate.
	 * @param	 mixed $source  Optional source to translate from. Defaults to automatic detection.
	 * @param	 mixed $target  Optional target to translate to. Defaults to English.
	 */
	public static function translate( $text, $source = '', $target = 'en' ) {
		$api_key = self::get_api_key();

		if ( $api_key ) {
			$api_url = 'https://translation.googleapis.com/language/translate/v2?key=' . urlencode( $api_key );
			$api_url .= '&q=' . urlencode( $text );
			$api_url .= '&target=' . urlencode( $target );
			if ( $source ) {
				$api_url .= '&source=' . urlencode( $source );
			}

			$response = wp_remote_get( $api_url );
			$body = ! is_wp_error( $response ) && isset( $response['body'] ) ? json_decode( $response['body'] ) : false;

			if ( $body && isset( $body->data ) ) {
				$data = $body->data;

				if ( $data && isset( $data->translations ) ) {
					$translations = $data->translations;

					if ( is_array( $translations ) && isset( $translations[0] ) ) {
						$translated = $translations[0]->translatedText;

						if ( $translated ) {
							return $translated;
						}
					}
				}
			}
		}

		return false;
	}
}
