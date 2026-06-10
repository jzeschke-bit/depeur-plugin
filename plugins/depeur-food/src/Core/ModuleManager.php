<?php
/**
 * Modul-Manager: Discovery aktivierter Module + Laden ihrer Bootstraps (ADR-1).
 *
 * @package Depeur\Food\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Discovery + Loader für das Toggle-Modul-Pattern (ADR-1). Die Master-Liste
// aktiver Module liegt in der Option OPTION_MODULES; tatsächlich geladen wird ein
// Modul nur, wenn es (a) in dieser Liste steht UND (b) physisch unter
// modules/<slug>/ mit gültigem manifest.php + module.php existiert. Diese
// Doppelprüfung verhindert Fatals durch verwaiste Options-Einträge nach dem
// Löschen eines Modulordners. modules/ ist in dieser Phase leer – init() lädt
// dann schlicht nichts (graceful). Die Modul-Konvention (manifest.php + module.php)
// legt dieses Verfahren fest; das erste echte Modul (_ExampleModule) folgt ihr.
/**
 * Klasse ModuleManager.
 *
 * @since 0.1.0
 */
final class ModuleManager {

	/**
	 * Option-Key der Master-Liste aktivierter Module (Slugs), ADR-1.
	 *
	 * @var string
	 */
	public const OPTION_MODULES = 'depeur_food_modules';

	/**
	 * Modulverzeichnis relativ zum Plugin-Root.
	 *
	 * @var string
	 */
	private const MODULES_DIR = 'modules';

	/**
	 * Cache der entdeckten Module pro Request. null = noch nicht gescannt.
	 *
	 * @var array{ modules: array<string, array{ name: string, description: string, version: string }>, errors: string[] }|null
	 */
	private static ?array $discovered = null;

	/**
	 * Lädt die Bootstraps aller aktiven, vorhandenen Module.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function init(): void {
		$active = self::get_active_module_slugs();
		$base   = DEPEUR_FOOD_PATH . self::MODULES_DIR . '/';

		foreach ( $active as $slug ) {
			$module_file = $base . $slug . '/module.php';

			// Defensiv: get_active_module_slugs() filtert bereits gegen die Discovery,
			// aber ein fehlendes module.php darf nie zu einem Fatal führen.
			if ( ! is_file( $module_file ) ) {
				continue;
			}

			require_once $module_file;
			// Der eigentliche Modul-Bootstrap läuft in module.php (lazy je Aktivierung).
		}
	}

	/**
	 * Liefert die aktiven Modul-Slugs (Schnittmenge aus Option und Discovery).
	 *
	 * @since 0.1.0
	 *
	 * @return string[]
	 */
	public static function get_active_module_slugs(): array {
		$saved = get_option( self::OPTION_MODULES, array() );
		if ( ! is_array( $saved ) ) {
			return array();
		}

		$discovered = self::get_discovered_modules();
		$known      = array_keys( $discovered['modules'] );

		return array_values( array_intersect( $saved, $known ) );
	}

	/**
	 * Scannt modules/<slug>/ nach manifest.php + module.php und liefert Module + Fehler.
	 *
	 * @since 0.1.0
	 *
	 * @return array{ modules: array<string, array{ name: string, description: string, version: string }>, errors: string[] }
	 */
	public static function get_discovered_modules(): array {
		if ( null !== self::$discovered ) {
			return self::$discovered;
		}

		$modules = array();
		$errors  = array();
		$dir     = DEPEUR_FOOD_PATH . self::MODULES_DIR;

		if ( ! is_dir( $dir ) ) {
			self::$discovered = array(
				'modules' => array(),
				'errors'  => array(),
			);
			return self::$discovered;
		}

		$items = scandir( $dir );
		if ( false === $items ) {
			self::$discovered = array(
				'modules' => array(),
				'errors'  => array( __( 'Modul-Verzeichnis konnte nicht gelesen werden.', 'depeur-food' ) ),
			);
			return self::$discovered;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $dir . '/' . $item;
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$manifest_file = $path . '/manifest.php';
			if ( ! is_file( $manifest_file ) ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordnername. */
					__( 'Modul „%s“: manifest.php fehlt.', 'depeur-food' ),
					$item
				);
				continue;
			}

			$manifest = self::load_manifest( $manifest_file );
			if ( null === $manifest ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordnername. */
					__( 'Modul „%s“: manifest.php ist ungültig.', 'depeur-food' ),
					$item
				);
				continue;
			}

			if ( ! is_file( $path . '/module.php' ) ) {
				$errors[] = sprintf(
					/* translators: %s: Modul-Ordnername. */
					__( 'Modul „%s“: module.php fehlt.', 'depeur-food' ),
					$item
				);
				continue;
			}

			// Schlüssel = Ordnername (zugleich Pfad-Zuordnung beim Laden).
			$modules[ $item ] = array(
				'name'        => isset( $manifest['name'] ) ? $manifest['name'] : $item,
				'description' => isset( $manifest['description'] ) ? $manifest['description'] : '',
				'version'     => isset( $manifest['version'] ) ? $manifest['version'] : '0',
			);
		}

		self::$discovered = array(
			'modules' => $modules,
			'errors'  => $errors,
		);
		return self::$discovered;
	}

	/**
	 * Lädt manifest.php und gibt das Array zurück (oder null bei ungültigem Inhalt).
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Absoluter Pfad zu manifest.php.
	 * @return array|null Manifest-Array oder null.
	 */
	private static function load_manifest( string $file ): ?array {
		$manifest = include $file;
		if ( ! is_array( $manifest ) ) {
			return null;
		}
		return $manifest;
	}
}
