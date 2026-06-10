<?php
/**
 * Registry für Modul-Einstellungen: Tabs, Felder, Defaults, Sanitize (ADR-1 Multi-Option).
 *
 * @package Depeur\Food\Core\Settings
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core\Settings;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Registry-Pattern (statische Sammelstelle): Module melden beim Laden ihr
// Settings-Schema hier an, die SettingsPage liest es beim Rendern wieder aus.
// Gewählt statt Single-Array-Option (§ 1.1 der Standards-Bibel), weil ADR-1 das
// Multi-Option-Pattern vorschreibt – pro Modul eine eigene Option
// depeur_food_{slug} (autoload=no für Secrets), entkoppelt vom Rest. Diese
// Klasse hält NUR die Schemata im Arbeitsspeicher; das tatsächliche
// Lesen/Schreiben der Optionen erledigt die SettingsPage, die Master-Liste
// aktiver Module (depeur_food_modules) verwaltet der ModuleManager. Bewusst
// statisch, weil die Registrierung ein globaler, instanzloser Vorgang zur
// Ladezeit ist (jedes Modul ruft register() einmalig auf).
/**
 * Klasse SettingsRegistry.
 *
 * @since 0.1.0
 */
final class SettingsRegistry {

	/**
	 * Option-Key-Prefix pro Modul: depeur_food_{module_slug} (ADR-1).
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'depeur_food_';

	/**
	 * Registrierte Schemata pro Modul:
	 * [ module_slug => [ 'tab_label' => string, 'fields' => array, 'description' => string ] ].
	 *
	 * @var array<string, array{ tab_label: string, fields: array, description: string }>
	 */
	private static array $schemas = array();

	/**
	 * Registriert ein Settings-Schema für ein Modul.
	 *
	 * Wird typischerweise von einem Modul beim Laden aufgerufen, damit sein Tab
	 * und seine Felder in der Settings-Page erscheinen. Ein erneuter Aufruf mit
	 * demselben Slug überschreibt das vorherige Schema (idempotent pro Modul).
	 *
	 * @since 0.1.0
	 *
	 * @param string $module_slug Modul-Slug (Ordnername, z. B. _ExampleModule); bildet zugleich den Options-Key-Suffix.
	 * @param string $tab_label   Anzeigename des Tabs (deutsch).
	 * @param array  $fields      Felddefinitionen. Pro Feld: id, label, type (checkbox|text|select|password),
	 *                            default, options (für select), sanitize (optional callback),
	 *                            autoload (optional bool, für Secrets false).
	 * @param string $description Optionale Tab-Beschreibung (§ 6.2 Settings-Page-Intro).
	 *
	 * @return void
	 */
	public static function register( string $module_slug, string $tab_label, array $fields, string $description = '' ): void {
		// Slug unverändert lassen – er ist zugleich Options-Key-Suffix und Pfad-Zuordnung zum Modulordner.
		self::$schemas[ $module_slug ] = array(
			'tab_label'   => $tab_label,
			'fields'      => $fields,
			'description' => $description,
		);
	}

	/**
	 * Liefert die Schemata, gefiltert auf die aktuell aktivierten Module.
	 *
	 * Die Liste der aktiven Module besitzt der ModuleManager (Option
	 * depeur_food_modules); sie wird hier hereingereicht, damit die Registry
	 * keine Option selbst lesen muss und ohne WP-Bootstrap testbar bleibt.
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $active_module_slugs Liste aktivierter Modul-Slugs.
	 * @return array<string, array{ tab_label: string, fields: array, description: string }>
	 */
	public static function get_schemas_for_active_modules( array $active_module_slugs ): array {
		$out = array();

		foreach ( self::$schemas as $slug => $schema ) {
			if ( in_array( $slug, $active_module_slugs, true ) ) {
				$out[ $slug ] = $schema;
			}
		}

		return $out;
	}

	/**
	 * Liefert alle registrierten Schemata (interne Nutzung/Tests).
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, array{ tab_label: string, fields: array, description: string }>
	 */
	public static function get_all_schemas(): array {
		return self::$schemas;
	}

	/**
	 * Baut den vollständigen Options-Key für ein Modul (ADR-1).
	 *
	 * @since 0.1.0
	 *
	 * @param string $module_slug Modul-Slug.
	 * @return string z. B. depeur_food_cache_bridge.
	 */
	public static function option_key( string $module_slug ): string {
		return self::OPTION_PREFIX . $module_slug;
	}

	/**
	 * Sanitisiert einen Eingabewert anhand seines Feldtyps.
	 *
	 * Reihenfolge: zuerst ein feldspezifischer sanitize-Callback (falls gesetzt),
	 * sonst der Default pro Typ. Unbekannte Typen fallen defensiv auf
	 * sanitize_text_field zurück, damit nie ungeprüfte Werte gespeichert werden.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Eingabewert (roh, aus $_POST).
	 * @param array $field Felddefinition (siehe register()).
	 * @return mixed Bereinigter Wert.
	 */
	public static function sanitize_field( mixed $value, array $field ): mixed {
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
			return call_user_func( $field['sanitize'], $value, $field );
		}

		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'checkbox':
				// Checkbox: Anwesenheit im POST = true; nicht angehakt liefert WP den Wert gar nicht erst.
				return ! empty( $value );
			case 'select':
				// Nur ein in den Optionen definierter Schlüssel ist gültig, sonst zurück auf Default.
				$options = ( isset( $field['options'] ) && is_array( $field['options'] ) ) ? array_keys( $field['options'] ) : array();
				return in_array( $value, $options, true ) ? $value : ( isset( $field['default'] ) ? $field['default'] : '' );
			case 'password':
			case 'text':
			default:
				return is_string( $value ) ? sanitize_text_field( $value ) : '';
		}
	}
}
