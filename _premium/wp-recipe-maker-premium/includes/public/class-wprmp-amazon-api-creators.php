<?php
/**
 * Responsible for communicating with the Amazon Creators API.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.3.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for communicating with the Amazon Creators API.
 *
 * @since      10.3.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Amazon_Api_Creators {

	private static $tag = false;
	private static $credential_id = false;
	private static $credential_secret = false;
	private static $credential_version = false;

	/**
	 * Base URL for Amazon Creators API.
	 *
	 * @since    10.3.0
	 */
	private static function get_api_base_url() {
		return 'https://creatorsapi.amazon';
	}

	/**
	 * Check if the API credentials are set.
	 *
	 * @since    10.3.0
	 */
	public static function validate_api_credentials() {
		// Get from settings.
		$tag = WPRM_Settings::get( 'amazon_partner_tag' );
		$credential_id = WPRM_Settings::get( 'amazon_credential_id' );
		$credential_secret = WPRM_Settings::get( 'amazon_credential_secret' );
		$credential_version = WPRM_Settings::get( 'amazon_credential_version' );

		// Convert to strings and trim (handle false/null/numeric values).
		// Note: credential_version might be stored as a number (2.1) or string ("2.1").
		$tag = ( is_string( $tag ) || is_numeric( $tag ) ) ? trim( (string) $tag ) : '';
		$credential_id = ( is_string( $credential_id ) || is_numeric( $credential_id ) ) ? trim( (string) $credential_id ) : '';
		$credential_secret = ( is_string( $credential_secret ) || is_numeric( $credential_secret ) ) ? trim( (string) $credential_secret ) : '';
		$credential_version = ( is_string( $credential_version ) || is_numeric( $credential_version ) ) ? trim( (string) $credential_version ) : '';

		// Store API credentials for use by the API (always as strings, never false).
		self::$tag = $tag;
		self::$credential_id = $credential_id;
		self::$credential_secret = $credential_secret;
		self::$credential_version = $credential_version;

		if ( empty( $tag ) || empty( $credential_id ) || empty( $credential_secret ) || empty( $credential_version ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Get OAuth 2.0 access token with caching.
	 *
	 * @since    10.3.0
	 */
	private static function get_access_token() {
		// Check cache first.
		$cache_key = 'wprm_amazon_creators_token_' . md5( self::$credential_id . self::$credential_secret );
		$cached_token = get_transient( $cache_key );

		if ( false !== $cached_token && is_array( $cached_token ) && isset( $cached_token['token'] ) && isset( $cached_token['expires'] ) ) {
			// Check if token is still valid (with 5 minute buffer).
			if ( $cached_token['expires'] > ( time() + 300 ) ) {
				return array(
					'success' => true,
					'token' => $cached_token['token'],
				);
			}
		}

		// Determine OAuth2 endpoint based on credential version (matches SDK logic).
		// Version 2.1 = North America (NA) → us-east-1
		// Version 2.2 = Europe (EU) → eu-south-2
		// Version 2.3 = Far East (FE) → us-west-2
		$auth_region = 'us-west-2'; // Default
		if ( '2.1' === self::$credential_version ) {
			$auth_region = 'us-east-1';
		} elseif ( '2.2' === self::$credential_version ) {
			$auth_region = 'eu-south-2';
		} elseif ( '2.3' === self::$credential_version ) {
			$auth_region = 'us-west-2';
		}

		// Build auth endpoint URL.
		$auth_url = 'https://creatorsapi.auth.' . $auth_region . '.amazoncognito.com/oauth2/token';

		// Prepare credentials for Basic Auth.
		$credentials = base64_encode( self::$credential_id . ':' . self::$credential_secret );

		// Request token.
		$response = wp_remote_post( $auth_url, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . $credentials,
			),
			'body' => 'grant_type=client_credentials&scope=creatorsapi/default',
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error' => array(
					'code' => 'http_error',
					'message' => __( 'Failed to connect to Amazon OAuth2 service. Please check your internet connection and try again.', 'wp-recipe-maker-premium' ),
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for HTTP errors.
		if ( 200 !== $response_code ) {
			$error_code = 'oauth2_error';
			$error_message = __( 'Failed to obtain OAuth2 access token.', 'wp-recipe-maker-premium' );
			
			if ( $data && isset( $data['error'] ) ) {
				$amazon_error = $data['error'];
				
				// Provide more specific error messages for common OAuth2 errors.
				if ( 'invalid_client' === $amazon_error ) {
					$error_code = 'InvalidClient';
					$error_message = __( 'Invalid Amazon API credentials. Please check your Credential ID, Credential Secret, and Credential Version in WP Recipe Maker > Settings > Amazon Products.', 'wp-recipe-maker-premium' );
				} elseif ( 'invalid_grant' === $amazon_error ) {
					$error_code = 'InvalidGrant';
					$error_message = __( 'Invalid Amazon API credentials. Please check your Credential ID and Credential Secret.', 'wp-recipe-maker-premium' );
				} elseif ( 'unauthorized_client' === $amazon_error ) {
					$error_code = 'UnauthorizedClient';
					$error_message = __( 'Unauthorized client. Please verify your credentials are correct and have the necessary permissions.', 'wp-recipe-maker-premium' );
				}
			}
			
			return array(
				'success' => false,
				'error' => array(
					'code' => $error_code,
					'message' => $error_message,
				),
			);
		}

		if ( ! isset( $data['access_token'] ) ) {
			$error_code = 'token_missing';
			$error_message = __( 'Failed to obtain OAuth2 access token.', 'wp-recipe-maker-premium' );
			
			if ( $data && isset( $data['error'] ) ) {
				if ( 'invalid_client' === $data['error'] ) {
					$error_code = 'InvalidClient';
					$error_message = __( 'Invalid Amazon API credentials. Please check your Credential ID, Credential Secret, and Credential Version in WP Recipe Maker > Settings > Amazon Products.', 'wp-recipe-maker-premium' );
				}
			}
			
			return array(
				'success' => false,
				'error' => array(
					'code' => $error_code,
					'message' => $error_message,
				),
			);
		}

		$token = $data['access_token'];
		$expires_in = isset( $data['expires_in'] ) ? intval( $data['expires_in'] ) : 3600;

		// Cache token (expires in 1 hour, cache for slightly less).
		$expires_at = time() + $expires_in;
		set_transient( $cache_key, array(
			'token' => $token,
			'expires' => $expires_at,
		), $expires_in - 60 ); // Cache for 1 minute less than expiry.

		return array(
			'success' => true,
			'token' => $token,
		);
	}
	
	/**
	 * Filter request body to remove false/null values that Amazon doesn't accept.
	 *
	 * @since    10.3.0
	 * @param 	 array $body Request body array.
	 * @return   array Filtered request body.
	 */
	private static function filter_request_body( $body ) {
		$filtered = array();
		
		foreach ( $body as $key => $value ) {
			// Skip false and null values (Amazon expects strings, not booleans).
			if ( false === $value || null === $value ) {
				continue;
			}
			
			// Recursively filter arrays.
			if ( is_array( $value ) ) {
				$filtered_value = self::filter_request_body( $value );
				// Only include non-empty arrays.
				if ( ! empty( $filtered_value ) ) {
					$filtered[ $key ] = $filtered_value;
				}
			} else {
				$filtered[ $key ] = $value;
			}
		}
		
		return $filtered;
	}

	/**
	 * Make API request to Amazon Creators API.
	 *
	 * @since    10.3.0
	 * @param 	 string $endpoint API endpoint path.
	 * @param 	 array  $body Request body data.
	 * @param 	 string $marketplace Marketplace identifier.
	 * @return   mixed Array response data or WP_Error.
	 */
	private static function make_api_request( $endpoint, $body, $marketplace ) {
		// Disable error reporting once Amazon API is used to prevent REST API responses from breaking.
		error_reporting(0);

		// Note: Credentials should already be validated by the calling method (search_products/get_products),
		// but we check again here as a safety measure. Since validate_api_credentials() is idempotent,
		// calling it multiple times is safe.
		if ( ! self::validate_api_credentials() ) {
			return self::handle_errors( 'missing_credentials' );
		}

		// Get access token.
		$token_result = self::get_access_token();
		if ( ! is_array( $token_result ) || ! isset( $token_result['success'] ) || ! $token_result['success'] ) {
			// Extract error details if available.
			if ( isset( $token_result['error'] ) ) {
				return self::handle_errors( $token_result['error'] );
			} else {
				return self::handle_errors( array(
					'code' => 'token_error',
					'message' => __( 'Failed to obtain OAuth2 access token.', 'wp-recipe-maker-premium' ),
				) );
			}
		}
		
		$token = $token_result['token'];

		// Build API URL.
		$api_url = self::get_api_base_url() . $endpoint;

		// Validate marketplace parameter (SDK does this, so we should too).
		if ( empty( $marketplace ) || strlen( $marketplace ) > 1000 ) {
			return self::handle_errors( array(
				'code' => 'invalid_marketplace',
				'message' => __( 'Invalid marketplace parameter.', 'wp-recipe-maker-premium' ),
			) );
		}

		// Build request headers.
		$user_agent = 'wp-recipe-maker-premium';
		if ( defined( 'WPRMP_VERSION' ) ) {
			$user_agent .= '/' . WPRMP_VERSION;
		}
		
		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $token . ', Version ' . self::$credential_version,
			'x-marketplace' => $marketplace,
			'User-Agent' => $user_agent,
		);

		// Filter out false/null values from request body (Amazon doesn't accept false where strings are expected).
		$body = self::filter_request_body( $body );

		// Encode request body.
		$json_body = json_encode( $body );
		if ( false === $json_body ) {
			return self::handle_errors( array(
				'code' => 'json_encode_error',
				'message' => __( 'Failed to encode request body as JSON.', 'wp-recipe-maker-premium' ),
			) );
		}

		// Send request.
		$response = wp_remote_post( $api_url, array(
			'headers' => $headers,
			'body' => $json_body,
			'timeout' => 30,
		) );

		// Check for WordPress HTTP errors.
		if ( is_wp_error( $response ) ) {
			return self::handle_errors( array(
				'code' => 'http_error',
				'message' => $response->get_error_message(),
			) );
		}

		// Get response code and body.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );

		// Check for rate limiting (HTTP 429).
		if ( 429 === $response_code ) {
			// Check for Retry-After header.
			$retry_after = 0;
			if ( isset( $response_headers['retry-after'] ) ) {
				$retry_after = intval( $response_headers['retry-after'] );
			} elseif ( isset( $response_headers['Retry-After'] ) ) {
				$retry_after = intval( $response_headers['Retry-After'] );
			}
			
			// Default to 60 seconds if no Retry-After header.
			if ( $retry_after <= 0 ) {
				$retry_after = 60;
			}
			
			return self::handle_errors( array(
				'code' => 'TooManyRequests',
				'message' => sprintf( __( 'Rate limit exceeded. Please retry after %d seconds.', 'wp-recipe-maker-premium' ), $retry_after ),
				'retry_after' => $retry_after,
			) );
		}

		// Parse JSON response.
		$data = json_decode( $response_body, true );
		
		// Check for JSON parsing errors.
		if ( null === $data && json_last_error() !== JSON_ERROR_NONE ) {
			return self::handle_errors( array(
				'code' => 'json_decode_error',
				'message' => sprintf( __( 'Failed to parse API response: %s', 'wp-recipe-maker-premium' ), json_last_error_msg() ),
			) );
		}

		// Check for HTTP errors.
		if ( 200 !== $response_code ) {
			// Try to extract error from response.
			if ( $data && isset( $data['errors'] ) && is_array( $data['errors'] ) && count( $data['errors'] ) > 0 ) {
				return self::handle_errors( $data['errors'][0] );
			}
			if ( $data && isset( $data['message'] ) ) {
				return self::handle_errors( array(
					'code' => isset( $data['code'] ) ? $data['code'] : 'http_error',
					'message' => $data['message'],
				) );
			}
			return self::handle_errors( array(
				'code' => 'http_error',
				'message' => sprintf( __( 'HTTP error %d: %s', 'wp-recipe-maker-premium' ), $response_code, $response_body ),
			) );
		}

		// Check for API errors in response.
		if ( $data && isset( $data['errors'] ) && is_array( $data['errors'] ) && count( $data['errors'] ) > 0 ) {
			return self::handle_errors( $data['errors'][0] );
		}

		return $data;
	}

	/**
	 * Search for products through the Amazon Creators API.
	 *
	 * @since    10.3.0
	 * @param 	 string $search Text to search for.
	 * @return   mixed Array of items or WP_Error.
	 */
	public static function search_products( $search ) {
		// Validate credentials first (this sets self::$tag and other properties).
		if ( ! self::validate_api_credentials() ) {
			return self::handle_errors( 'missing_credentials' );
		}
		
		// Sanitize search input.
		$search = sanitize_text_field( $search );
		
		// Get store for marketplace.
		$store = WPRMP_Amazon::get_store();
		$marketplace = isset( $store['marketplace'] ) ? $store['marketplace'] : 'www.amazon.com';

		// Build request body.
		$request_body = array(
			'keywords' => $search,
			'partnerTag' => self::$tag,
			'resources' => array(
				'itemInfo.title',
				'offersV2.listings.price',
				'images.primary.large',
				'offersV2.listings.availability',
			),
		);

		// Make API request.
		$response = self::make_api_request( '/catalog/v1/searchItems', $request_body, $marketplace );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse response.
		if ( isset( $response['searchResult'] ) && isset( $response['searchResult']['items'] ) ) {
			return $response['searchResult']['items'];
		}

		return array();
	}

	/**
	 * Get products through the Amazon Creators API.
	 *
	 * @since    10.3.0
	 * @param 	 array $asins ASINs to get the products for.
	 * @return   mixed Array of items or WP_Error.
	 */
	public static function get_products( $asins ) {
		// Validate credentials first (this sets self::$tag and other properties).
		if ( ! self::validate_api_credentials() ) {
			return self::handle_errors( 'missing_credentials' );
		}
		
		// Ensure we have ASINs.
		if ( empty( $asins ) || ! is_array( $asins ) ) {
			return self::handle_errors( array(
				'code' => 'invalid_request',
				'message' => __( 'No ASINs provided.', 'wp-recipe-maker-premium' ),
			) );
		}
		
		// Sanitize ASIN values.
		$asins = array_map( 'sanitize_text_field', $asins );
		
		// Get store for marketplace.
		$store = WPRMP_Amazon::get_store();
		$marketplace = isset( $store['marketplace'] ) ? $store['marketplace'] : 'www.amazon.com';

		// Build request body.
		$request_body = array(
			'itemIds' => $asins,
			'partnerTag' => self::$tag,
			'resources' => array(
				'itemInfo.title',
				'images.primary.large',
				'offersV2.listings.availability',
			),
		);

		// Make API request.
		$response = self::make_api_request( '/catalog/v1/getItems', $request_body, $marketplace );

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Parse response.
		if ( isset( $response['itemsResult'] ) && isset( $response['itemsResult']['items'] ) ) {
			return $response['itemsResult']['items'];
		}

		return array();
	}

	/**
	 * Handle API errors.
	 *
	 * @since    10.3.0
	 * @param 	 mixed $api_error Error to handle.
	 * @return   WP_Error Error object.
	 */
	public static function handle_errors( $api_error ) {
		$error = array(
			'code' => 'unknown_amazon_api_error',
			'message' => __( 'Unknown Amazon API error.', 'wp-recipe-maker-premium' ),
			'data' => array(
				'api_error' => $api_error,
			),
		);
		
		if ( false !== $api_error ) {
			if ( 'missing_credentials' === $api_error ) {
				$error['code'] = 'missing_credentials';
				$error['message'] = __( 'API credentials not set on the WP Recipe Maker > Settings > Amazon Products page.', 'wp-recipe-maker-premium' );
			} else if ( is_array( $api_error ) ) {
				if ( isset( $api_error['code'] ) && isset( $api_error['message'] ) ) {
					$error['code'] = $api_error['code'];
					$error['message'] = $api_error['message'];
				}
			} else if ( is_object( $api_error ) ) {
				// Handle object errors (for backward compatibility).
				if ( isset( $api_error->code ) && isset( $api_error->message ) ) {
					$error['code'] = $api_error->code;
					$error['message'] = $api_error->message;
				}
			}
		}

		// Customize the error message to display.
		// Note: Only override if the message doesn't already contain specific details.
		switch( $error['code'] ) {
			case 'AccessDenied':
			case 'AccessDeniedAwsUsers':
				$error['message'] = __( 'This key does not have access to the Amazon API.', 'wp-recipe-maker-premium' );
				break;
			case 'UnrecognizedClient':
			case 'InvalidSignature':
			case 'IncompleteSignature':
			case 'InvalidClient':
			case 'InvalidGrant':
			case 'UnauthorizedClient':
				// Don't override the message if it was already customized with more details.
				if ( false === strpos( $error['message'], 'Credential ID' ) && false === strpos( $error['message'], 'Credential Secret' ) ) {
					$error['message'] = __( 'Invalid Amazon API credentials. Check your credentials and try again later.', 'wp-recipe-maker-premium' );
				}
				break;
			case 'InvalidPartnerTag':
				$error['message'] = __( 'Invalid Amazon Partner Tag.', 'wp-recipe-maker-premium' );
				break;
			case 'TooManyRequests':
				$error['message'] = __( 'No response due to too many requests. Consider slowing down. The API also returns this error if you do not have 3 qualifying sales within the previous period.', 'wp-recipe-maker-premium' );
				break;
		}
		
		// Add code and message as part of data as well.
		$error['data']['code'] = $error['code'];
		$error['data']['message'] = $error['message'];
		
		// Add debug info if we have the raw error.
		if ( false !== $api_error && 'unknown_amazon_api_error' === $error['code'] ) {
			$error['data']['debug'] = array(
				'error_type' => gettype( $api_error ),
				'error_value' => is_object( $api_error ) ? get_class( $api_error ) : ( is_array( $api_error ) ? 'array' : $api_error ),
			);
		}

		return new \WP_Error(
			$error['code'],
			$error['message'],
			$error['data']
		);
	}
}
