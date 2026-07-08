<?php
/**
 * Responsible for communicating with the Amazon API.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for communicating with the Amazon API.
 *
 * @since      9.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ItemsResult;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\ApiException;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\Configuration;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsRequest;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResource;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetItemsResponse;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResponse;

class WPRMP_Amazon_Api {

	private static $api = false;

	private static $tag = false;
	private static $key = false;
	private static $secret = false;

	/**
	 * Get the Amazon API object.
	 *
	 * @since    9.1.0
	 */
	public static function get_api() {
		if ( false === self::$api ) {
			// Disable error reporting once Amazon API is used to prevent REST API responses from breaking.
			error_reporting(0);

			// Check if API credentials are set.
			if ( ! self::validate_api_credentials() ) {
				return self::handle_errors( 'missing_credentials' );
			}

			$config = new Configuration();
			$config->setAccessKey( self::$key );
			$config->setSecretKey( self::$secret );
	
			/*
			 * PAAPI host and region to which you want to send request
			 * For more details refer: https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
			 */
			$store = WPRMP_Amazon::get_store();
			$config->setHost( $store['host'] );
			$config->setRegion( $store['region'] );
	
			self::$api = new DefaultApi( new \BootstrappedVentures\WPRecipeMaker\GuzzleHttp\Client(), $config );
		}

		return self::$api;
	}

	/**
	 * Check if the API credentials are set.
	 *
	 * @since    9.1.0
	 */
	public static function validate_api_credentials() {
		// Get from settings.
		$tag = trim( WPRM_Settings::get( 'amazon_partner_tag' ) );
		$key = trim( WPRM_Settings::get( 'amazon_access_key' ) );
		$secret = trim( WPRM_Settings::get( 'amazon_secret_key' ) );

		// Store API credentials for use by the API.
		self::$tag = $tag;
		self::$key = $key;
		self::$secret = $secret;

		if ( ! $tag || ! $key || ! $secret ) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Search for products through the Amazon API.
	 *
	 * @since    9.1.0
	 * @param 	 string $search Text to search for.
	 */
	public static function search_products( $search ) {
		$api = self::get_api();

		// Make sure API is actually set.
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		
		// Form request.
		$searchItemsRequest = new SearchItemsRequest();
		$searchItemsRequest->setKeywords( $search );
		$searchItemsRequest->setPartnerTag( self::$tag );
		$searchItemsRequest->setPartnerType( PartnerType::ASSOCIATES );
		$searchItemsRequest->setResources( array(
			SearchItemsResource::ITEM_INFOTITLE,
			SearchItemsResource::OFFERSLISTINGSPRICE,
			SearchItemsResource::IMAGESPRIMARYLARGE,
			SearchItemsResource::OFFERSLISTINGSAVAILABILITYTYPE,
			SearchItemsResource::OFFERSLISTINGSAVAILABILITYMESSAGE,
		) );
	
		// Send request.
		try {
			$searchItemsResponse = $api->searchItems($searchItemsRequest);
	
			// Parse request.
			if ( $searchItemsResponse->getSearchResult() !== null ) {
				return @$searchItemsResponse->getSearchResult()->getItems();
			}

			if ( $searchItemsResponse->getErrors() !== null ) {
				return self::handle_errors( $searchItemsResponse->getErrors()[0] );
			}

			return array();
		} catch ( ApiException $exception ) {
			$decode = json_decode( $exception->getResponseBody() );
			$error = empty( $decode->Errors[0] ) ? false : $decode->Errors[0];

			return self::handle_errors( $error );
		} catch ( Exception $exception ) {
		}

		return self::handle_errors( false );
	}

	/**
	 * Get products through the Amazon API.
	 *
	 * @since    9.1.0
	 * @param 	 array $asins ASINs to get the products for.
	 */
	public static function get_products( $asins ) {
		$api = self::get_api();

		// Make sure API is actually set.
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		
		// Form request.
		$getItemsRequest = new GetItemsRequest();
		$getItemsRequest->setItemIds( $asins );
		$getItemsRequest->setPartnerTag( self::$tag );
		$getItemsRequest->setPartnerType( PartnerType::ASSOCIATES );
		$getItemsRequest->setResources( array(
			GetItemsResource::ITEM_INFOTITLE,
			GetItemsResource::IMAGESPRIMARYLARGE,
			GetItemsResource::OFFERSLISTINGSAVAILABILITYTYPE,
			GetItemsResource::OFFERSLISTINGSAVAILABILITYMESSAGE,
		) );
	
		// Send request.
		try {
			$getItemsResponse = $api->getItems($getItemsRequest);
	
			// Parse request.
			if ( $getItemsResponse->getItemsResult() !== null ) {
				return @$getItemsResponse->getItemsResult()->getItems();
			}

			if ( $getItemsResponse->getErrors() !== null ) {
				return self::handle_errors( $getItemsResponse->getErrors()[0] );
			}

			return array();
		} catch ( ApiException $exception ) {
			$decode = json_decode( $exception->getResponseBody() );
			$error = empty( $decode->Errors[0] ) ? false : $decode->Errors[0];

			return self::handle_errors( $error );
		} catch ( Exception $exception ) {
		}

		return self::handle_errors( false );
	}

	/**
	 * Handle API errors.
	 *
	 * @since    9.1.0
	 * @param 	 mixed $api_error Error to handle.
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
			} else if ( 'BootstrappedVentures\WPRecipeMaker\Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ErrorData' === get_class( $api_error ) ) {
				$error['code'] = $api_error->getCode();
				$error['message'] = $api_error->getMessage();
			} else if ( isset( $api_error->Code ) && isset( $api_error->Message ) ) {
				$error['code'] = $api_error->Code;
				$error['message'] = $api_error->Message;
			}
		}

		// Customize the error message to display.
		switch( $error['code'] ) {
			case 'AccessDenied':
			case 'AccessDeniedAwsUsers':
				$error['message'] = __( 'This key does not have access to the Amazon API.', 'wp-recipe-maker-premium' );
				break;
			case 'UnrecognizedClient':
			case 'InvalidSignature':
			case 'IncompleteSignature':
				$error['message'] = __( 'Invalid Amazon API credentials. Check your credentials and try again later.', 'wp-recipe-maker-premium' );
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

		return new \WP_Error(
			$error['code'],
			$error['message'],
			$error['data']
		);
	}
}
