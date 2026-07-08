<?php
/**
 * Handle licensing for the Premium addon.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Handle licensing for the Premium addon.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_License {

	private static $debug = false;

	/**
	 *  EDD store to contact.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $store EDD store to contact.
	 */
	private static $store = 'https://bootstrapped.ventures';

	/**
	 *  Premium products on this website.
	 *
	 * @since    1.3.0
	 * @access   private
	 * @var      array $products Premium products on this website.
	 */
	private static $products = array();

	/**
	 * Register actions and filters.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			include( WPRMP_DIR . 'vendor/edd/EDD_SL_Plugin_Updater.php' );
		}

		self::set_premium_bundle();

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_license_status' ) );

		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
		add_filter( 'wprm_settings_update', array( __CLASS__, 'check_license_key_on_settings_update' ), 10, 2 );
		
		// Sync tracking preference to settings.
		add_filter( 'wprm_settings', array( __CLASS__, 'sync_tracking_to_settings' ) );
		
		add_action( 'init', array( __CLASS__, 'edd_plugin_updater' ) );

		// Add usage data tracking to API requests.
		add_filter( 'edd_sl_plugin_updater_api_params', array( __CLASS__, 'add_tracking_to_api_params' ), 10, 3 );

		if ( is_admin() ) {
			add_filter( 'wprm_should_load_admin_assets', array( __CLASS__, 'load_admin_assets' ) );
			add_action( 'admin_notices', array( __CLASS__, 'license_inactive_notice' ) );
			add_action( 'admin_init', array( __CLASS__, 'clear_cache_on_force_check' ) );

			if ( self::$debug ) {
				add_action( 'admin_init', array( __CLASS__, 'debug_license' ) );
			}
		}
	}

	/**
	 * Set correct bundle as product.
	 *
	 * @since    2.0.0
	 */
	public static function set_premium_bundle() {
		switch ( WPRMP_BUNDLE ) {
			case 'Elite':
				self::$products['elite'] = array(
					'item_id' => 23343,
					'name' => 'WP Recipe Maker Premium - Elite Bundle',
					'file' => WPRMP_DIR . 'wp-recipe-maker-premium.php',
					'version' => WPRMP_VERSION,
				);
				break;
			case 'Pro':
				self::$products['pro'] = array(
					'item_id' => 23292,
					'name' => 'WP Recipe Maker Premium - Pro Bundle',
					'file' => WPRMP_DIR . 'wp-recipe-maker-premium.php',
					'version' => WPRMP_VERSION,
				);
				break;
			case 'Premium':
			default:
				self::$products['premium'] = array(
					'item_id' => 4684,
					'name' => 'WP Recipe Maker Premium',
					'file' => WPRMP_DIR . 'wp-recipe-maker-premium.php',
					'version' => WPRMP_VERSION,
				);
				break;
		}
	}

	/**
	 * Get all the WP Recipe Maker products on this website.
	 *
	 * @since    1.3.0
	 */
	public static function get_products() {
		return apply_filters( 'wprmp_edd_products', self::$products );
	}

	/**
	 * Get the active license details.
	 *
	 * @since    10.2.2
	 * @return   array License details with key and item_id.
	 */
	public static function get_license_details() {
		$license_key = '';
		$item_id = '';

		$products = self::get_products();
		foreach ( $products as $product_id => $product ) {
			$license_key = WPRM_Settings::get( 'license_' . $product_id );
			if ( $license_key ) {
				$item_id = isset( $product['item_id'] ) ? $product['item_id'] : '';
				break;
			}
		}

		return array(
			'key' => $license_key,
			'item_id' => $item_id,
		);
	}

	/**
	 * Set up plugin updater to check for plugin updates.
	 *
	 * @since    1.0.0
	 */
	public static function edd_plugin_updater() {
		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$products = self::get_products();

		foreach ( $products as $id => $product ) {
			new EDD_SL_Plugin_Updater( self::$store, $product['file'], array(
					'version' 	=> $product['version'],
					'license' 	=> WPRM_Settings::get( 'license_' . $id ),
					'item_id' 	=> $product['item_id'],
					'author' 	=> 'Bootstrapped Ventures',
					'beta'		=> false,
				)
			);
		}
	}

	/**
	 * Add usage data tracking to API requests.
	 * This replicates the SDK's allow_tracking feature.
	 *
	 * @since    10.1.0
	 * @param    array  $api_params  The array of data sent in the request.
	 * @param    array  $api_data     The array of data set up in the class constructor.
	 * @param    string $plugin_file  The full path and filename of the file.
	 * @return   array  Modified API parameters.
	 */
	public static function add_tracking_to_api_params( $api_params, $api_data, $plugin_file ) {
		// Get general tracking preference (defaults to true if not set).
		$allow_tracking = self::get_allow_tracking();
		$api_params['allow_tracking'] = $allow_tracking ? 1 : 0;

		return $api_params;
	}

	/**
	 * Get the allow tracking preference (general setting, not per product).
	 * Replicates SDK's tracking preference storage.
	 *
	 * @since    10.1.0
	 * @return   bool   Whether tracking is allowed (defaults to true).
	 */
	public static function get_allow_tracking() {
		$option_name = 'wprm_license_allow_tracking';
		$data = get_option( $option_name, false );

		// Handle legacy boolean values.
		if ( is_bool( $data ) ) {
			return $data;
		}

		// Handle new array format with timestamp (like SDK).
		if ( is_array( $data ) && isset( $data['allowed'] ) ) {
			return $data['allowed'];
		}

		// Default to false if not set (opt-in tracking).
		return false;
	}

	/**
	 * Set the allow tracking preference (general setting, not per product).
	 * Replicates SDK's tracking preference storage.
	 *
	 * @since    10.1.0
	 * @param    bool   $allow_tracking Whether to allow tracking.
	 */
	public static function set_allow_tracking( $allow_tracking ) {
		$option_name = 'wprm_license_allow_tracking';
		$data = array(
			'allowed'   => (bool) $allow_tracking,
			'timestamp' => time(),
		);
		update_option( $option_name, $data, false );
	}

	/**
	 * Sync tracking preference from option to WPRM_Settings format.
	 * This ensures the settings page displays the correct tracking value.
	 *
	 * @since    10.1.0
	 * @param    array $settings Current settings.
	 */
	public static function sync_tracking_to_settings( $settings ) {
		$allow_tracking = self::get_allow_tracking();
		$settings['license_allow_tracking'] = $allow_tracking;
		return $settings;
	}

	/**
	 * Add license key settings.
	 *
	 * @since    3.0.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMP_DIR . 'templates/admin/settings/license.php' );

		if ( isset( $structure['licenseKey'] ) ) {
			$structure['licenseKey'] = $license_key;
		} else {
			$structure = array( 'licenseKey' => $license_key ) + $structure;
		}

		return $structure;
	}

	/**
	 * Check if the license key was updated.
	 *
	 * @since    3.0.0
	 * @param    array $new_settings Settings after update.
	 * @param    array $old_settings Settings before update.
	 */
	public static function check_license_key_on_settings_update( $new_settings, $old_settings ) {
		$products = self::get_products();

		foreach ( $products as $id => $product ) {
			$old_license = isset( $old_settings[ 'license_' . $id ] ) ? $old_settings[ 'license_' . $id ] : '';
			$new_license = isset( $new_settings[ 'license_' . $id ] ) ? $new_settings[ 'license_' . $id ] : '';

			// License hasn't changed and status is active: do nothing.
			if ( $old_license === $new_license && 'valid' === self::get_license_status( $id ) ) {
				continue;
			}
			
			// Something changed, so clear the status.
			self::update_license_status( $id, '' );

			// Deactivate the old license if there was one.
			if ( $old_license ) {
				self::deactivate_license( $id, $old_license );
			}

			// Activate the new license.
			self::activate_license( $id, $new_license );
		}

		// Update tracking preference if changed.
		$old_tracking = isset( $old_settings['license_allow_tracking'] ) ? (bool) $old_settings['license_allow_tracking'] : false;
		$new_tracking = isset( $new_settings['license_allow_tracking'] ) ? (bool) $new_settings['license_allow_tracking'] : false;
		
		if ( $old_tracking !== $new_tracking ) {
			self::set_allow_tracking( $new_tracking );
		}

		return $new_settings;
	}

	public static function check_license_status( $transient ) {
		$products = self::get_products();

		foreach ( $products as $id => $product ) {
			self::update_license( $id, WPRM_Settings::get( 'license_' . $id ) );
		}

		// Only check once.
		remove_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_license_status' ) );

		return $transient;
	}

	/**
	 * Clear EDD cache when user clicks "Check again" on Updates page.
	 *
	 * @since    10.3.1
	 */
	public static function clear_cache_on_force_check() {
		// Check if we're on the update-core.php page with force-check parameter.
		if ( ! isset( $_GET['force-check'] ) || '1' !== $_GET['force-check'] ) {
			return;
		}

		// Verify we're on the update-core.php page.
		global $pagenow;
		if ( 'update-core.php' !== $pagenow ) {
			return;
		}

		// Clear EDD cache for all products.
		$products = self::get_products();
		foreach ( $products as $id => $product ) {
			$license = WPRM_Settings::get( 'license_' . $id );
			if ( ! $license ) {
				continue;
			}

			$plugin_slug = basename( dirname( $product['file'] ) );
			$cache_key = 'edd_sl_' . md5( serialize( $plugin_slug . $license . false ) );
			delete_option( $cache_key );
		}
	}

	/**
	 * Update the status of the license key.
	 *
	 * @since    1.0.0
	 * @param    mixed $id     ID of the product we are updating the license for.
	 * @param    mixed $status Status to set.
	 */
	public static function update_license_status( $id, $status ) {
		update_option( 'wprm_license_' . $id . '_status', $status, false );
	}

	/**
	 * Get the status of the license key.
	 *
	 * @since    3.0.0
	 * @param    mixed $id ID of the product we are getting the license status for.
	 */
	public static function get_license_status( $id ) {
		$status = get_option( 'wprm_license_' . $id . '_status', false );

		// Backwards compatibility.
		if ( false === $status ) {
			$status = WPRM_Settings::get( 'license_' . $id . '_status' );
		}

		return $status;
	}

	/**
	 * Get SSL verification setting.
	 *
	 * @since    1.0.0
	 * @return   bool Whether to verify SSL certificates.
	 */
	private static function verify_ssl() {
		return (bool) apply_filters( 'wprmp_license_ssl_verify', true );
	}

	/**
	 * Activate a license key.
	 *
	 * @since    1.0.0
	 * @param    mixed $id     ID of the product we are activating the license for.
	 * @param    mixed $license License key to activate.
	 */
	public static function activate_license( $id, $license ) {
		$products = self::get_products();
		$product = $products[ $id ];

		$api_params = array(
			'edd_action' 	=> 'activate_license',
			'license' 	 	=> $license,
			'item_id' 	 	=> $product['item_id'],
			'url'        	=> home_url(),
			'environment'	=> function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Add tracking preference to activation request.
		$api_params['allow_tracking'] = self::get_allow_tracking() ? 1 : 0;

		// Call the EDD license API.
		$response = wp_remote_post( self::$store, array( 'timeout' => 60, 'sslverify' => self::verify_ssl(), 'body' => $api_params ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data ) {
			self::update_license_status( $id, $license_data->license );
		}
	}

	/**
	 * Deactivate a license key.
	 *
	 * @since    1.0.0
	 * @param    mixed $id     ID of the product we are deactivating the license for.
	 * @param    mixed $license License key to deactivate.
	 */
	public static function deactivate_license( $id, $license ) {
		$products = self::get_products();
		$product = $products[ $id ];

		$api_params = array(
			'edd_action' 	=> 'deactivate_license',
			'license' 	 	=> $license,
			'item_id'    	=> $product['item_id'],
			'url'        	=> home_url(),
			'environment' 	=> function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Add tracking preference to deactivation request.
		$api_params['allow_tracking'] = self::get_allow_tracking() ? 1 : 0;

		// Call the EDD license API.
		$response = wp_remote_post( self::$store, array( 'timeout' => 60, 'sslverify' => self::verify_ssl(), 'body' => $api_params ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data && 'deactivated' === $license_data->license ) {
			return true;
		}
	}

	/**
	 * Update the license key status.
	 *
	 * @since	1.0.0
	 * @param	mixed $id     ID of the product we are updating the license for.
	 * @param	mixed $license License key to update.
	 */
	public static function update_license( $id, $license ) {
		$products = self::get_products();
		$product = $products[ $id ];

		$api_params = array(
			'edd_action' 	=> 'check_license',
			'license' 	 	=> $license,
			'item_id' 	 	=> $product['item_id'],
			'url'        	=> home_url(),
			'environment' 	=> function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Add tracking preference to license check request.
		$api_params['allow_tracking'] = self::get_allow_tracking() ? 1 : 0;

		// Call the EDD license API.
		$response = wp_remote_post( self::$store, array( 'timeout' => 60, 'sslverify' => self::verify_ssl(), 'body' => $api_params ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data ) {
			self::update_license_status( $id, $license_data->license );
		}
	}

	public static function debug_license() {
		// Optionally clear update transients to force fresh check.
		if ( isset( $_GET['wprm_clear_update_cache'] ) && current_user_can( 'manage_options' ) ) {
			delete_site_transient( 'update_plugins' );
			$products = self::get_products();
			foreach ( $products as $id => $product ) {
				$plugin_slug = basename( dirname( $product['file'] ) );
				$license = WPRM_Settings::get( 'license_' . $id );
				$cache_key = 'edd_sl_' . md5( serialize( $plugin_slug . $license . false ) );
				delete_option( $cache_key );
			}
			WPRM_Debug::log( 'Update cache cleared. Refresh the plugins page to trigger a new check.' );
		}

		$products = self::get_products();

		foreach ( $products as $id => $product ) {
			$license = WPRM_Settings::get( 'license_' . $id );
			WPRM_Debug::log( 'Checking license for product: ' . $id );
			WPRM_Debug::log( $license );

			$api_params = array(
				'edd_action' => 'check_license',
				'license' 	 => $license,
				'item_id' 	 => $product['item_id'],
				'url'        => home_url(),
			);
	
			// Call the EDD license API.
			$response = wp_remote_post( self::$store, array( 'timeout' => 60, 'sslverify' => self::verify_ssl(), 'body' => $api_params ) );

			if ( is_wp_error( $response ) ) {
				WPRM_Debug::log( $response );
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( $license_data ) {
					WPRM_Debug::log( $license_data );

					// If license is valid, check for plugin updates.
					if ( isset( $license_data->license ) && 'valid' === $license_data->license ) {
						$plugin_slug = basename( dirname( $product['file'] ) );
						$plugin_name = plugin_basename( $product['file'] );
						
						WPRM_Debug::log( 'Plugin slug: ' . $plugin_slug );
						WPRM_Debug::log( 'Plugin name: ' . $plugin_name );
						
						$version_api_params = array(
							'edd_action'  => 'get_version',
							'license'     => $license,
							'item_id'     => $product['item_id'],
							'version'     => $product['version'],
							'slug'        => $plugin_slug,
							'author'      => 'Bootstrapped Ventures',
							'url'         => home_url(),
							'beta'        => false,
							'php_version' => phpversion(),
							'wp_version'  => get_bloginfo( 'version' ),
						);

						// Add tracking preference to version check request.
						$version_api_params['allow_tracking'] = self::get_allow_tracking() ? 1 : 0;

						// Call the EDD version API.
						$version_response = wp_remote_post( self::$store, array( 'timeout' => 60, 'sslverify' => self::verify_ssl(), 'body' => $version_api_params ) );

						if ( is_wp_error( $version_response ) ) {
							WPRM_Debug::log( 'Version check error: ' . $version_response->get_error_message() );
						} else {
							$version_data = json_decode( wp_remote_retrieve_body( $version_response ) );

							if ( $version_data && isset( $version_data->new_version ) ) {
								$current_version = $product['version'];
								$latest_version = $version_data->new_version;
								$update_available = version_compare( $current_version, $latest_version, '<' );

								WPRM_Debug::log( 'Current version: ' . $current_version );
								WPRM_Debug::log( 'Latest version: ' . $latest_version );
								WPRM_Debug::log( 'Update available: ' . ( $update_available ? 'Yes' : 'No' ) );
								
								// Check if package URL exists (required for WordPress to show update button).
								if ( isset( $version_data->package ) ) {
									WPRM_Debug::log( 'Package URL exists: Yes' );
									WPRM_Debug::log( 'Package URL: ' . $version_data->package );
								} else {
									WPRM_Debug::log( 'Package URL exists: No (This is why WordPress is not showing the update!)' );
								}
								
								WPRM_Debug::log( $version_data );
								
								// Check what WordPress sees in the update transient.
								$update_transient = get_site_transient( 'update_plugins' );
								if ( $update_transient && isset( $update_transient->response[ $plugin_name ] ) ) {
									WPRM_Debug::log( 'WordPress update transient contains plugin: Yes' );
									WPRM_Debug::log( 'Transient data: ' . print_r( $update_transient->response[ $plugin_name ], true ) );
								} else {
									WPRM_Debug::log( 'WordPress update transient contains plugin: No' );
									if ( $update_transient && isset( $update_transient->checked[ $plugin_name ] ) ) {
										WPRM_Debug::log( 'Plugin is in checked array with version: ' . $update_transient->checked[ $plugin_name ] );
									}
								}
								
								// Check EDD updater cache.
								$cache_key = 'edd_sl_' . md5( serialize( $plugin_slug . $license . false ) );
								$edd_cache = get_option( $cache_key );
								if ( $edd_cache ) {
									WPRM_Debug::log( 'EDD cache exists: Yes' );
									WPRM_Debug::log( 'EDD cache timeout: ' . ( isset( $edd_cache['timeout'] ) ? date( 'Y-m-d H:i:s', $edd_cache['timeout'] ) : 'N/A' ) );
									$cached_data = isset( $edd_cache['value'] ) ? json_decode( $edd_cache['value'] ) : null;
									if ( $cached_data && isset( $cached_data->new_version ) ) {
										$cached_version = $cached_data->new_version;
										WPRM_Debug::log( 'Cached version: ' . $cached_version );
										
										// Check if cached version is older than latest version.
										if ( version_compare( $cached_version, $latest_version, '<' ) ) {
											WPRM_Debug::log( 'Cached version is outdated! Clearing cache to force refresh.' );
											delete_option( $cache_key );
											delete_site_transient( 'update_plugins' );
											WPRM_Debug::log( 'Cache cleared. WordPress will check for updates on next page load.' );
										} else {
											if ( isset( $cached_data->package ) ) {
												WPRM_Debug::log( 'Cached package URL exists: Yes' );
											} else {
												WPRM_Debug::log( 'Cached package URL exists: No' );
											}
										}
									}
								} else {
									WPRM_Debug::log( 'EDD cache exists: No' );
								}
							} else {
								WPRM_Debug::log( 'Version check response: ' . wp_remote_retrieve_body( $version_response ) );
							}
						}
					}
				} else {
					WPRM_Debug::log( $response );
				}
			}
		}
	}

	/**
	 * Load admin assets on plugins page to make sure license activation works there.
	 *
	 * @since    6.8.0
	 */
	public static function load_admin_assets( $load ) {
		$screen = get_current_screen();

		if ( $screen && 'plugins' === $screen->id && current_user_can( 'manage_options' ) ) {
			$products = self::get_products();

			foreach ( $products as $id => $product ) {
				if ( ! in_array( self::get_license_status( $id ), array( 'valid', 'expired' ) ) ) {
					return true;
				}
			}
		}

		return $load;
	}

	/**
	 * Show a notice on the plugin page if the license is inactive.
	 *
	 * @since    1.0.0
	 */
	public static function license_inactive_notice() {
		$screen = get_current_screen();

		if ( $screen && 'plugins' === $screen->id && current_user_can( 'manage_options' ) ) {
			$products = self::get_products();

			foreach ( $products as $id => $product ) {
				$license_status = self::get_license_status( $id );

				if ( 'expired' === $license_status ) {
					require( WPRMP_DIR . 'templates/admin/settings/license_expired.php' );
				} elseif ( 'invalid_item_id' === $license_status ) {
					require( WPRMP_DIR . 'templates/admin/settings/license_different.php' );
				} elseif ( 'valid' !== $license_status ) {
					require( WPRMP_DIR . 'templates/admin/settings/license_invalid.php' );
				}
			}
		}
	}
}

WPRMP_License::init();
