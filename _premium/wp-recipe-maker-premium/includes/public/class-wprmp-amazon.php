<?php
/**
 * Responsible for handling anything Amazon related.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for handling anything Amazon related.
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

class WPRMP_Amazon {

	/**
	 * Get affiliate link to a specific product without going through the API.
	 * https://affiliate-program.amazon.com/help/node/topic/GP38PJ6EUR6PFBEC
	 *
	 * @since    9.1.0
	 */
	public static function get_noapi_affiliate_link( $asin ) {
		$link = '';

		if ( $asin ) {
			$tag = trim( WPRM_Settings::get( 'amazon_partner_tag' ) );

			if ( $tag ) {
				$store = self::get_store();
				$domain = str_ireplace( 'webservices.', 'www.', $store['host'] );

				$link = 'https://' . $domain . '/dp/' . $asin . '/ref=nosim?tag=' . $tag;
			}
		}

		return $link;
	}

	/**
	 * Get non-affiliate product URL for a specific ASIN.
	 * Used for linking to product pages without affiliate tracking.
	 *
	 * @since    9.1.0
	 * @param    string $asin Product ASIN.
	 * @return   string Product URL.
	 */
	public static function get_product_url( $asin ) {
		$url = '';

		if ( $asin ) {
			$store = self::get_store();
			$domain = isset( $store['marketplace'] ) ? $store['marketplace'] : 'www.amazon.com';

			$url = 'https://' . $domain . '/dp/' . $asin;
		}

		return $url;
	}

	/**
	 * Search for products through the Amazon API.
	 *
	 * @since    9.1.0
	 */
	public static function search_products( $search ) {
		$result = array(
			'error' => false,
			'products' => array(),
		);

		$products = WPRMP_Amazon_Api_Factory::search_products( $search );

		// Add error to result or parse products.
		if ( is_wp_error( $products ) ) {
			if ( 'NoResults' !== $products->get_error_code() ) {
				$result['error'] = $products->get_error_data();
			}
		} else {
			$result['products'] = self::get_data_from_products( $products );
		}

		return $result;
	}

	/**
	 * Get products through the Amazon API.
	 *
	 * @since    9.1.0
	 */
	public static function get_products( $asins ) {
		$result = array(
			'error' => false,
			'products' => array(),
		);

		$products = WPRMP_Amazon_Api_Factory::get_products( $asins );

		// Add error to result or parse products.
		if ( is_wp_error( $products ) ) {
			if ( 'NoResults' !== $products->get_error_code() ) {
				$result['error'] = $products->get_error_data();
			}
		} else {
			$data = self::get_data_from_products( $products );

			$result['products'] = array();

			// Make sure all ASINs are set, even if product wasn't actually found.
			foreach ( $asins as $asin ) {
				// Default to false.
				$result['products'][ $asin ] = false;

				// Check if ASIN is set in data.
				$data_key = array_search( $asin, array_column( $data, 'asin' ) );

				if ( false !== $data_key ) {
					$result['products'][ $asin ] = $data[ $data_key ];
				}
			}
		}

		return $result;
	}

	/**
	 * Extract the data we want from the items Amazon returns.
	 * Handles both PA-API and Creators API response formats.
	 *
	 * @since    9.1.0
	 */
	public static function get_data_from_products( $products ) {
		$data = array();

		if ( is_array( $products ) && ! is_wp_error( $products ) ) {
			foreach ( $products as $product ) {
				if ( $product ) {
					// Detect API type: Creators API returns arrays, PA-API returns objects.
					$is_creators_api = is_array( $product );
					$is_creators_api_object = false;
					
					if ( ! $is_creators_api && is_object( $product ) ) {
						// Check if it's a Creators API object (has getOffersV2 method).
						$is_creators_api_object = method_exists( $product, 'getOffersV2' );
					}

					// Extract ASIN based on API type.
					if ( $is_creators_api ) {
						// Creators API array format.
						$asin = isset( $product['asin'] ) ? $product['asin'] : '';
					} elseif ( $is_creators_api_object ) {
						// Creators API object format.
						$asin = $product->getAsin();
					} else {
						// PA-API format.
						$asin = $product->getASIN();
					}

					if ( $asin ) {
						$name = '';
						$link = '';
						$image = '';
						$image_width = 0;
						$image_height = 0;
						$price = '';
						$availability_type = '';
						$availability_message = '';

						if ( $is_creators_api ) {
							// Creators API array format.
							$item_info = isset( $product['itemInfo'] ) ? $product['itemInfo'] : null;
							if ( $item_info && isset( $item_info['title'] ) && isset( $item_info['title']['displayValue'] ) ) {
								$name = $item_info['title']['displayValue'];
							}
							$link = isset( $product['detailPageURL'] ) ? $product['detailPageURL'] : '';

							$images = isset( $product['images'] ) ? $product['images'] : null;
							if ( $images && isset( $images['primary'] ) && isset( $images['primary']['large'] ) ) {
								$image_data = $images['primary']['large'];
								$image = isset( $image_data['url'] ) ? $image_data['url'] : '';
								$image_width = isset( $image_data['width'] ) ? intval( $image_data['width'] ) : 0;
								$image_height = isset( $image_data['height'] ) ? intval( $image_data['height'] ) : 0;
							}

							$offers = isset( $product['offersV2'] ) ? $product['offersV2'] : null;
							if ( $offers && isset( $offers['listings'] ) && is_array( $offers['listings'] ) && count( $offers['listings'] ) > 0 ) {
								$listing = $offers['listings'][0];
								if ( $listing && isset( $listing['price'] ) && isset( $listing['price']['money'] ) && isset( $listing['price']['money']['displayAmount'] ) ) {
									$price = $listing['price']['money']['displayAmount'];
								}
								// Extract availability information.
								if ( $listing && isset( $listing['availability'] ) ) {
									$availability = $listing['availability'];
									if ( isset( $availability['type'] ) && is_string( $availability['type'] ) ) {
										$type_value = $availability['type'];
										$valid_types = array( 'AVAILABLE_DATE', 'IN_STOCK', 'IN_STOCK_SCARCE', 'LEADTIME', 'OUT_OF_STOCK', 'PREORDER', 'UNAVAILABLE', 'UNKNOWN', 'NOT_FOUND' );
										if ( in_array( $type_value, $valid_types, true ) ) {
											$availability_type = $type_value;
										}
									}
									if ( isset( $availability['message'] ) ) {
										$availability_message = $availability['message'];
									}
								}
							}
						} elseif ( $is_creators_api_object ) {
							// Creators API object format.
							$item_info = $product->getItemInfo();
							if ( $item_info && $item_info->getTitle() ) {
								$name = $item_info->getTitle()->getDisplayValue() ? $item_info->getTitle()->getDisplayValue() : '';
							}
							$link = $product->getDetailPageURL() ? $product->getDetailPageURL() : '';

							$images = $product->getImages();
							if ( $images && $images->getPrimary() && $images->getPrimary()->getLarge() ) {
								$image_data = $images->getPrimary()->getLarge();
								$image = $image_data->getUrl() ? $image_data->getUrl() : '';
								$image_width = $image_data->getWidth() ? intval( $image_data->getWidth() ) : 0;
								$image_height = $image_data->getHeight() ? intval( $image_data->getHeight() ) : 0;
							}

							$offers = $product->getOffersV2();
							if ( $offers && $offers->getListings() && is_array( $offers->getListings() ) && count( $offers->getListings() ) > 0 ) {
								$listing = $offers->getListings()[0];
								if ( $listing && $listing->getPrice() && $listing->getPrice()->getMoney() ) {
									$price = $listing->getPrice()->getMoney()->getDisplayAmount() ? $listing->getPrice()->getMoney()->getDisplayAmount() : '';
								}
								// Extract availability information.
								if ( $listing && method_exists( $listing, 'getAvailability' ) ) {
									$availability = $listing->getAvailability();
									if ( $availability ) {
										if ( method_exists( $availability, 'getType' ) ) {
											$type_value = $availability->getType();
											// Only use type if it's a valid status type.
											if ( $type_value && is_string( $type_value ) ) {
												$valid_types = array( 'AVAILABLE_DATE', 'IN_STOCK', 'IN_STOCK_SCARCE', 'LEADTIME', 'OUT_OF_STOCK', 'PREORDER', 'UNAVAILABLE', 'UNKNOWN', 'NOT_FOUND' );
												if ( in_array( $type_value, $valid_types, true ) ) {
													$availability_type = $type_value;
												}
											}
										}
										if ( method_exists( $availability, 'getMessage' ) ) {
											$availability_message = $availability->getMessage() ? $availability->getMessage() : '';
										}
									}
								}
							}
						} else {
							// PA-API format.
							$name = null !== $product->getItemInfo() && null !== $product->getItemInfo()->getTitle() && null !== $product->getItemInfo()->getTitle()->getDisplayValue() ? $product->getItemInfo()->getTitle()->getDisplayValue() : '';
							$link = $product->getDetailPageURL();
							$image_data = null !== $product->getImages() && null !== $product->getImages()->getPrimary() && null !== $product->getImages()->getPrimary()->getLarge() ? $product->getImages()->getPrimary()->getLarge() : false;
							$image = $image_data && null !== $image_data->getURL() ? $image_data->getURL() : '';
							$image_width = $image_data && null !== $image_data->getWidth() ? intval( $image_data->getWidth() ) : 0;
							$image_height = $image_data && null !== $image_data->getHeight() ? intval( $image_data->getHeight() ) : 0;
							$price = null !== $product->getOffers() && null !== $product->getOffers()->getListings() && is_array( $product->getOffers()->getListings() ) && count( $product->getOffers()->getListings() ) > 0 && null !== $product->getOffers()->getListings()[0]->getPrice() ? $product->getOffers()->getListings()[0]->getPrice()->getDisplayAmount() : '';
							
							// Extract availability information from PA-API.
							if ( null !== $product->getOffers() && null !== $product->getOffers()->getListings() && is_array( $product->getOffers()->getListings() ) && count( $product->getOffers()->getListings() ) > 0 ) {
								$listing = $product->getOffers()->getListings()[0];
								if ( null !== $listing && method_exists( $listing, 'getAvailability' ) ) {
									$availability = $listing->getAvailability();
									if ( null !== $availability ) {
										if ( method_exists( $availability, 'getType' ) ) {
											$type_value = $availability->getType();
											// Only use type if it's a valid status type.
											if ( $type_value && is_string( $type_value ) ) {
												$valid_types = array( 'AVAILABLE_DATE', 'IN_STOCK', 'IN_STOCK_SCARCE', 'LEADTIME', 'OUT_OF_STOCK', 'PREORDER', 'UNAVAILABLE', 'UNKNOWN', 'NOT_FOUND' );
												if ( in_array( $type_value, $valid_types, true ) ) {
													$availability_type = $type_value;
												}
											}
										}
										if ( method_exists( $availability, 'getMessage' ) ) {
											$availability_message = $availability->getMessage() ? $availability->getMessage() : '';
										}
									}
								}
							}
						}

						$data[] = array(
							'asin' => $asin,
							'name' => $name,
							'link' => $link,
							'image' => $image,
							'image_width' => $image_width,
							'image_height' => $image_height,
							'price' => $price,
							'availability_type' => $availability_type,
							'availability_message' => $availability_message,
						);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Get selected Amazon store.
	 *
	 * @since    9.1.0
	 */
	public static function get_store() {
		$all_stores = self::get_stores();
		$store = WPRM_Settings::get( 'amazon_store' );

		if ( ! isset( $all_stores[ $store ] ) ) {
			$store = 'united_states';
		}

		return $all_stores[ $store ];
	}

	/**
	 * Get all available Amazon stores.
	 *
	 * @since    9.1.0
	 */
	public static function get_stores() {
		include( WPRM_DIR . 'templates/settings/group-amazon.php' );

		if ( is_array( $amazon_stores ) ) {
			return $amazon_stores;
		}

		// Make sure default is always there.
		return array(
			'united_states' => array(
				'label' => 'United States',
				'host' => 'webservices.amazon.com',
				'region' => 'us-east-1',
				'marketplace' => 'www.amazon.com',
				'credential_version' => '2.1',
				'auth_region' => 'us-west-2',
			),
		);
	}
}
