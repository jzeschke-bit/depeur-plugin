<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bootstrapped.ventures/
 * @since             1.0.0
 * @package           WP_Recipe_Maker_Premium
 *
 * @wordpress-plugin
 * Plugin Name:       WP Recipe Maker Premium
 * Plugin URI:        https://bootstrapped.ventures/
 * Description:       Premium addons for WP Recipe Maker.
 * Version:           10.5.0
 * Author:            Bootstrapped Ventures
 * Author URI:        https://bootstrapped.ventures/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-recipe-maker-premium
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Autoloader.
spl_autoload_register( function( $classname ) {
	$wprm_namespace = 'BootstrappedVentures\WPRecipeMaker\\';

	$classname = trim( $classname, '\\' );
	if ( $wprm_namespace === substr( $classname, 0, strlen( $wprm_namespace ) ) ) {
		$classname = substr( $classname, strlen( $wprm_namespace ) );

		$folder_mapping = array(
			// Scoped vendors.
			'Amazon\ProductAdvertisingAPI\v1' => 'vendor/paapi5-php-sdk/src',
			
			// Make sure subfolders are loaded first.
			'GuzzleHttp\Promise' => 'vendor/guzzlehttp/promises/src',
			'GuzzleHttp\Psr7' => 'vendor/guzzlehttp/psr7/src',
			'GuzzleHttp' => 'vendor/guzzlehttp/guzzle/src',

			'Psr\Http\Client' => 'vendor/psr/http-client/src',
			'Psr\Http\Message' => 'vendor/psr/http-message/src',
			// WPRM.
			'Admin' => 'includes/admin',
			'Public' => 'includes/public',
		);

		foreach ( $folder_mapping as $match => $folder ) {
			if ( $match === substr( $classname, 0, strlen( $match ) ) ) {
				$classname = substr( $classname, strlen( $match ) );
				$classname = str_replace( '\\', '/', $classname );

				// Special naming for WordPress.
				if ( in_array( $match, array( 'Admin', 'Public' ) ) ) {
					$parts = explode( '/', $classname );
					$last = array_pop( $parts );

					// Change $last from CamelCase to dashes.
					$last = preg_replace( '/(?<!^)[A-Z]/', '-$0', $last );

					$classname = implode( '/', $parts ) . '/class-wprmp-' . $last;
					$classname = strtolower( $classname );
				}

				$file = plugin_dir_path( __FILE__ ) . $folder . $classname . '.php';
				if ( file_exists( $file ) ) {
					include_once $file;
				}
				break;
			}
		}
	}
} );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wprm-activator.php
 */
function activate_wp_recipe_maker_premium() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wprmp-activator.php';
	WPRMP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wprm-deactivator.php
 */
function deactivate_wp_recipe_maker_premium() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wprmp-deactivator.php';
	WPRMP_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_recipe_maker_premium' );
register_deactivation_hook( __FILE__, 'deactivate_wp_recipe_maker_premium' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-recipe-maker-premium.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_recipe_maker_premium() {
	$plugin = new WP_Recipe_Maker_Premium();
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array( $plugin, 'plugin_action_links' ), 1 );
}
run_wp_recipe_maker_premium();
