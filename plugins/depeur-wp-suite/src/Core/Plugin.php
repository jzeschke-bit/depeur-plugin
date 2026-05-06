<?php
/**
 * Core-Plugin-Klasse: Initialisierung, Hooks, keine Fachlogik.
 *
 * @package Depeur\WPSuite\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Core;

use Depeur\WPSuite\Support\Logger;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse Plugin.
 */
class Plugin {

	/**
	 * Einmalige Initialisierung (init-Hook).
	 */
	public static function init() {
		// Nur im Admin: Menü, Einstellungsseiten und Settings-Registrierung (für options.php nötig).
		if ( is_admin() ) {
			AdminMenu::register();
			add_action( 'admin_init', array( \Depeur\WPSuite\Core\Settings\SettingsPage::class, 'register_all_settings' ) );
		}

		// Modul-Manager (lädt nur aktivierte Module).
		ModuleManager::init();

		// Logger nur initialisieren, wenn Logging aktiviert ist.
		$logging_enabled = (bool) get_option( 'depeur_wp_suite_logging_enabled', false );
		if ( $logging_enabled ) {
			Logger::init();
		}
	}
}
