<?php
/**
 * Modul-Manager: Discovery, Aktivierung, Laden aktivierter Module.
 *
 * @package Depeur\WPSuite\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Core;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse ModuleManager.
 */
class ModuleManager {

	/**
	 * Option-Key für die Liste aktivierter Module (Slugs).
	 *
	 * @var string
	 */
	const OPTION_MODULES = 'depeur_wp_suite_modules';

	/**
	 * Relativer Pfad zum Module-Verzeichnis (von Plugin-Root).
	 *
	 * @var string
	 */
	const MODULES_DIR = 'modules';

	/**
	 * Cache für entdeckte Module.
	 *
	 * @var array{ modules: array, errors: string[] }|null
	 */
	private static $discovered = null;

	/**
	 * Initialisiert den Modul-Manager und lädt aktivierte Module.
	 */
	public static function init() {
		$active = self::get_active_module_slugs();
		$base   = DEPEUR_WP_SUITE_PATH . self::MODULES_DIR . '/';

		foreach ( $active as $slug ) {
			$module_file = $base . $slug . '/module.php';
			if ( ! is_file( $module_file ) ) {
				continue;
			}
			require_once $module_file;
			// Modul-Bootstrap wird in module.php ausgeführt (z. B. Klasse aufrufen).
		}
	}

	/**
	 * Liefert die Liste aktivierter Modul-Slugs.
	 *
	 * @return string[]
	 */
	public static function get_active_module_slugs() {
		$saved = get_option( self::OPTION_MODULES, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}
		$discovered = self::get_discovered_modules();
		$slugs      = array_keys( $discovered['modules'] );
		return array_values( array_intersect( $saved, $slugs ) );
	}

	/**
	 * Scannt modules/<ordner>/manifest.php und liefert Module + Fehler.
	 *
	 * @return array{ modules: array<string, array{ name: string, description: string, version: string }>, errors: string[] }
	 */
	public static function get_discovered_modules() {
		if ( self::$discovered !== null ) {
			return self::$discovered;
		}

		$modules = array();
		$errors  = array();
		$dir     = DEPEUR_WP_SUITE_PATH . self::MODULES_DIR;

		if ( ! is_dir( $dir ) ) {
			self::$discovered = array( 'modules' => array(), 'errors' => array() );
			return self::$discovered;
		}

		$items = scandir( $dir );
		if ( $items === false ) {
			self::$discovered = array( 'modules' => array(), 'errors' => array( __( 'Module-Verzeichnis konnte nicht gelesen werden.', 'depeur-wp-suite' ) ) );
			return self::$discovered;
		}

		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			$path = $dir . '/' . $item;
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$manifest_file = $path . '/manifest.php';
			if ( ! is_file( $manifest_file ) ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordner */
					__( 'Modul „%s“: manifest.php fehlt.', 'depeur-wp-suite' ),
					$item
				);
				continue;
			}

			$manifest = self::load_manifest( $manifest_file );
			if ( $manifest === null ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordner */
					__( 'Modul „%s“: manifest.php ungültig.', 'depeur-wp-suite' ),
					$item
				);
				continue;
			}

			if ( ! is_file( $path . '/module.php' ) ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordner */
					__( 'Modul „%s“: module.php fehlt.', 'depeur-wp-suite' ),
					$item
				);
				continue;
			}

			// Schlüssel = Ordnername (für Pfad beim Laden).
			$modules[ $item ] = array(
				'name'        => isset( $manifest['name'] ) ? $manifest['name'] : $item,
				'description' => isset( $manifest['description'] ) ? $manifest['description'] : '',
				'version'     => isset( $manifest['version'] ) ? $manifest['version'] : '0',
			);
		}

		self::$discovered = array( 'modules' => $modules, 'errors' => $errors );
		return self::$discovered;
	}

	/**
	 * Lädt manifest.php und gibt das Array zurück (oder null bei Fehler).
	 *
	 * @param string $file Absoluter Pfad zu manifest.php.
	 * @return array|null Manifest-Array oder null.
	 */
	private static function load_manifest( $file ) {
		$manifest = include $file;
		if ( ! is_array( $manifest ) ) {
			return null;
		}
		return $manifest;
	}
}
