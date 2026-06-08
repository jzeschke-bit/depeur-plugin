<?php
/**
 * Plugin Name: Depeur Food
 * Plugin URI: https://depeur.de
 * Description: Modulares Plugin für die Depeur-Content-Sites (Schema, Favoriten, Newsletter, Cache-Bridge, Recipe-Extras). Post-type-agnostisch, ohne ACF-Runtime-Abhängigkeit.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Tested up to: 6.5
 * Requires PHP: 8.2
 * Author: Depeur
 * Author URI: https://depeur.de
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: depeur-food
 *
 * @package Depeur\Food
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin-Konstanten.
define( 'DEPEUR_FOOD_VERSION', '0.1.0' );
define( 'DEPEUR_FOOD_FILE', __FILE__ );
define( 'DEPEUR_FOOD_PATH', plugin_dir_path( __FILE__ ) );
define( 'DEPEUR_FOOD_URL', plugin_dir_url( __FILE__ ) );
define( 'DEPEUR_FOOD_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader (kein Composer erforderlich).
require_once DEPEUR_FOOD_PATH . 'src/Helpers/Autoloader.php';
Depeur\Food\Helpers\Autoloader::register( DEPEUR_FOOD_PATH );

/**
 * Globaler Accessor auf das Plugin-Singleton.
 *
 * Ermöglicht depeur_food()->get_supported_post_types() aus jedem Kontext (ADR-4). Bewusste
 * Abweichung von der Depeur-WP-Suite-Vorlage, die ohne globalen Helper auskommt. Muss nach
 * der Autoloader-Registrierung stehen, damit die Plugin-Klasse auflösbar ist.
 *
 * @since 0.1.0
 *
 * @return \Depeur\Food\Core\Plugin
 */
function depeur_food(): \Depeur\Food\Core\Plugin {
	return \Depeur\Food\Core\Plugin::get_instance();
}

// Aktivierung/Deaktivierung – Defaults setzen bzw. (künftig) aufräumen.
register_activation_hook( DEPEUR_FOOD_FILE, array( \Depeur\Food\Core\Activation::class, 'activate' ) );
register_deactivation_hook( DEPEUR_FOOD_FILE, array( \Depeur\Food\Core\Activation::class, 'deactivate' ) );

// Textdomain beim Laden der Plugins registrieren.
add_action( 'plugins_loaded', 'depeur_food_load_textdomain' );

/**
 * Lädt die Plugin-Textdomain.
 *
 * @since 0.1.0
 *
 * @return void
 */
function depeur_food_load_textdomain(): void {
	load_plugin_textdomain(
		'depeur-food',
		false,
		dirname( DEPEUR_FOOD_BASENAME ) . '/languages'
	);
}

// Core am init-Hook initialisieren.
add_action( 'init', array( \Depeur\Food\Core\Plugin::class, 'init' ) );
