<?php
/**
 * Plugin Name: Depeur WP Suite
 * Plugin URI: https://depeur.de
 * Description: Modulare WordPress-Plugin-Suite mit Core, Modul-System, Einstellungen und Support.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Tested up to: 6.5
 * Requires PHP: 8.0
 * Author: Depeur
 * Author URI: https://depeur.de
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: depeur-wp-suite
 *
 * @package Depeur\WPSuite
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin-Konstanten.
define( 'DEPEUR_WP_SUITE_VERSION', '1.1.0' );
define( 'DEPEUR_WP_SUITE_FILE', __FILE__ );
define( 'DEPEUR_WP_SUITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'DEPEUR_WP_SUITE_URL', plugin_dir_url( __FILE__ ) );
define( 'DEPEUR_WP_SUITE_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader (ohne Composer erforderlich).
require_once DEPEUR_WP_SUITE_PATH . 'src/Helpers/Autoloader.php';
Depeur\WPSuite\Helpers\Autoloader::register( DEPEUR_WP_SUITE_PATH );

// Textdomain beim Plugins geladen laden.
add_action( 'plugins_loaded', 'depeur_wp_suite_load_textdomain' );

/**
 * Lädt die Plugin-Textdomain.
 */
function depeur_wp_suite_load_textdomain() {
	load_plugin_textdomain(
		'depeur-wp-suite',
		false,
		dirname( DEPEUR_WP_SUITE_BASENAME ) . '/languages'
	);
}

// Core initialisieren.
add_action( 'init', array( Depeur\WPSuite\Core\Plugin::class, 'init' ) );
