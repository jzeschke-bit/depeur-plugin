<?php
/**
 * PSR-4-ähnlicher Autoloader für Depeur WP Suite.
 * Läuft ohne Composer – alle Klassen im Namespace Depeur\WPSuite werden aus src/ geladen.
 *
 * @package Depeur\WPSuite
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Helpers;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse Autoloader.
 */
final class Autoloader {

	/**
	 * Basisverzeichnis des Plugins (absoluter Pfad).
	 *
	 * @var string
	 */
	private static $base_dir = '';

	/**
	 * Namespace-Prefix (Depeur\WPSuite).
	 *
	 * @var string
	 */
	private static $prefix = 'Depeur\\WPSuite\\';

	/**
	 * Registriert den Autoloader.
	 *
	 * @param string $base_dir Absoluter Pfad zum Plugin-Stammverzeichnis (depeur-wp-suite/).
	 */
	public static function register( $base_dir ) {
		self::$base_dir = rtrim( $base_dir, '/' );
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Lädt eine Klasse, wenn sie zum Namespace gehört.
	 *
	 * @param string $class Vollständiger Klassenname.
	 * @return bool True bei Erfolg, sonst false.
	 */
	public static function load( $class ) {
		$len = strlen( self::$prefix );
		if ( strncmp( self::$prefix, $class, $len ) !== 0 ) {
			return false;
		}

		$relative = substr( $class, $len );
		$relative = str_replace( '\\', '/', $relative );
		$file     = self::$base_dir . '/src/' . $relative . '.php';

		if ( is_file( $file ) ) {
			require_once $file;
			return true;
		}

		// Modul-Namespace: Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings -> modules/bunny-cdn/Admin/Settings.php
		$modules_prefix = 'Modules\\';
		$modules_len    = strlen( self::$prefix . $modules_prefix );
		if ( strncmp( self::$prefix . $modules_prefix, $class, $modules_len ) === 0 ) {
			$after_modules = substr( $class, $modules_len );
			$parts         = explode( '\\', $after_modules );
			$module_name   = $parts[0];
			$slug          = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $module_name ) );
			$rest          = array_slice( $parts, 1 );
			$path          = $slug . '/' . implode( '/', $rest ) . '.php';
			$file          = self::$base_dir . '/modules/' . $path;
			if ( is_file( $file ) ) {
				require_once $file;
				return true;
			}
		}

		return false;
	}
}
