<?php
/**
 * PSR-4-ähnlicher Autoloader für Depeur Food.
 * Läuft ohne Composer – alle Klassen im Namespace Depeur\Food werden aus src/ geladen,
 * Modul-Klassen (Depeur\Food\Modules\…) aus modules/{slug}/.
 *
 * @package Depeur\Food
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Helpers;

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
	 * Namespace-Prefix (Depeur\Food).
	 *
	 * @var string
	 */
	private static $prefix = 'Depeur\\Food\\';

	/**
	 * Registriert den Autoloader.
	 *
	 * @param string $base_dir Absoluter Pfad zum Plugin-Stammverzeichnis (depeur-food/).
	 */
	public static function register( $base_dir ) {
		self::$base_dir = rtrim( $base_dir, '/' );
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Lädt eine Klasse, wenn sie zum Namespace gehört.
	 *
	 * @param string $class_name Vollständiger Klassenname.
	 * @return bool True bei Erfolg, sonst false.
	 */
	public static function load( $class_name ) {
		$len = strlen( self::$prefix );
		if ( strncmp( self::$prefix, $class_name, $len ) !== 0 ) {
			return false;
		}

		$relative = substr( $class_name, $len );
		$relative = str_replace( '\\', '/', $relative );
		$file     = self::$base_dir . '/src/' . $relative . '.php';

		if ( is_file( $file ) ) {
			require_once $file;
			return true;
		}

		// Modul-Namespace: Depeur\Food\Modules\SchemaEngine\Engine -> modules/schema-engine/Engine.php.
		$modules_prefix = 'Modules\\';
		$modules_len    = strlen( self::$prefix . $modules_prefix );
		if ( strncmp( self::$prefix . $modules_prefix, $class_name, $modules_len ) === 0 ) {
			$after_modules = substr( $class_name, $modules_len );
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
