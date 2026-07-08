<?php
/**
 * Factory for routing Amazon API calls to the appropriate implementation.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Factory for routing Amazon API calls to the appropriate implementation.
 *
 * @since      10.3.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Amazon_Api_Factory {

	/**
	 * Check if any API credentials are available (either PA-API or Creators API).
	 *
	 * @since    10.3.0
	 * @return   bool True if any credentials are available.
	 */
	public static function has_api_credentials() {
		return self::has_paapi_credentials() || self::has_creators_credentials();
	}

	/**
	 * Check if PA-API credentials are available.
	 *
	 * @since    10.3.0
	 * @return   bool True if credentials are available.
	 */
	private static function has_paapi_credentials() {
		$access_key = trim( WPRM_Settings::get( 'amazon_access_key' ) );
		$secret_key = trim( WPRM_Settings::get( 'amazon_secret_key' ) );
		$partner_tag = trim( WPRM_Settings::get( 'amazon_partner_tag' ) );

		return ! empty( $access_key ) && ! empty( $secret_key ) && ! empty( $partner_tag );
	}

	/**
	 * Check if Creators API credentials are available.
	 *
	 * @since    10.3.0
	 * @return   bool True if credentials are available.
	 */
	private static function has_creators_credentials() {
		$credential_id = trim( WPRM_Settings::get( 'amazon_credential_id' ) );
		$credential_secret = trim( WPRM_Settings::get( 'amazon_credential_secret' ) );
		$credential_version = trim( WPRM_Settings::get( 'amazon_credential_version' ) );
		$partner_tag = trim( WPRM_Settings::get( 'amazon_partner_tag' ) );

		return ! empty( $credential_id ) && ! empty( $credential_secret ) && ! empty( $credential_version ) && ! empty( $partner_tag );
	}

	/**
	 * Get the API instance based on user settings.
	 *
	 * @since    10.3.0
	 * @return   string API class name.
	 */
	private static function get_api_class() {
		$api_type = WPRM_Settings::get( 'amazon_api_type' );

		// Auto-detect mode.
		if ( 'auto' === $api_type || ! $api_type ) {
			$has_creators = self::has_creators_credentials();
			$has_paapi = self::has_paapi_credentials();

			// If both are available, prefer Creators API.
			if ( $has_creators ) {
				return 'WPRMP_Amazon_Api_Creators';
			} else if ( $has_paapi ) {
				return 'WPRMP_Amazon_Api';
			}

			// No credentials available, default to PA-API (will show error).
			return 'WPRMP_Amazon_Api';
		}

		// Explicit selection.
		if ( 'creators' === $api_type ) {
			return 'WPRMP_Amazon_Api_Creators';
		} else if ( 'paapi' === $api_type ) {
			return 'WPRMP_Amazon_Api';
		}

		// Fallback to PA-API.
		return 'WPRMP_Amazon_Api';
	}

	/**
	 * Search for products through the Amazon API.
	 *
	 * @since    10.3.0
	 * @param 	 string $search Text to search for.
	 * @return   mixed Array of products or WP_Error.
	 */
	public static function search_products( $search ) {
		$api_type = WPRM_Settings::get( 'amazon_api_type' );

		// Auto mode with fallback logic.
		if ( 'auto' === $api_type || ! $api_type ) {
			$has_creators = self::has_creators_credentials();
			$has_paapi = self::has_paapi_credentials();

			// If both are available, try Creators API first, then fallback to PA-API.
			if ( $has_creators && $has_paapi ) {
				$result = call_user_func( array( 'WPRMP_Amazon_Api_Creators', 'search_products' ), $search );
				
				// If Creators API fails (but not for "NoResults"), try PA-API as fallback.
				if ( is_wp_error( $result ) && 'NoResults' !== $result->get_error_code() ) {
					$result = call_user_func( array( 'WPRMP_Amazon_Api', 'search_products' ), $search );
				}
				
				return $result;
			} else if ( $has_creators ) {
				return call_user_func( array( 'WPRMP_Amazon_Api_Creators', 'search_products' ), $search );
			} else if ( $has_paapi ) {
				return call_user_func( array( 'WPRMP_Amazon_Api', 'search_products' ), $search );
			}
		}

		// Explicit selection or no credentials.
		$api_class = self::get_api_class();
		return call_user_func( array( $api_class, 'search_products' ), $search );
	}

	/**
	 * Get products through the Amazon API.
	 *
	 * @since    10.3.0
	 * @param 	 array $asins ASINs to get the products for.
	 * @return   mixed Array of products or WP_Error.
	 */
	public static function get_products( $asins ) {
		$api_type = WPRM_Settings::get( 'amazon_api_type' );

		// Auto mode with fallback logic.
		if ( 'auto' === $api_type || ! $api_type ) {
			$has_creators = self::has_creators_credentials();
			$has_paapi = self::has_paapi_credentials();

			// If both are available, try Creators API first, then fallback to PA-API.
			if ( $has_creators && $has_paapi ) {
				$result = call_user_func( array( 'WPRMP_Amazon_Api_Creators', 'get_products' ), $asins );
				
				// If Creators API fails (but not for "NoResults"), try PA-API as fallback.
				if ( is_wp_error( $result ) && 'NoResults' !== $result->get_error_code() ) {
					$result = call_user_func( array( 'WPRMP_Amazon_Api', 'get_products' ), $asins );
				}
				
				return $result;
			} else if ( $has_creators ) {
				return call_user_func( array( 'WPRMP_Amazon_Api_Creators', 'get_products' ), $asins );
			} else if ( $has_paapi ) {
				return call_user_func( array( 'WPRMP_Amazon_Api', 'get_products' ), $asins );
			}
		}

		// Explicit selection or no credentials.
		$api_class = self::get_api_class();
		return call_user_func( array( $api_class, 'get_products' ), $asins );
	}
}
