<?php
/**
 * Registry für Modul-Einstellungen: Tabs, Felder, Defaults, Sanitize.
 *
 * @package Depeur\WPSuite\Core\Settings
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Core\Settings;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse SettingsRegistry.
 */
class SettingsRegistry {

	/**
	 * Gespeicherte Schemata pro Modul: [ module_slug => [ 'tab_label' => ..., 'fields' => [...] ] ].
	 *
	 * @var array<string, array{ tab_label: string, fields: array }>
	 */
	private static $schemas = array();

	/**
	 * Option-Key-Prefix pro Modul: depeur_wp_suite_{module_slug}.
	 */
	const OPTION_PREFIX = 'depeur_wp_suite_';

	/**
	 * Registriert ein Settings-Schema für ein Modul.
	 *
	 * @param string $module_slug Modul-Slug (Ordnername, z. B. _ExampleModule).
	 * @param string $tab_label   Anzeigename des Tabs (deutsch).
	 * @param array  $fields      Array von Felddefinitionen. Pro Feld: id, label, type (checkbox|text|select), default, options (für select), sanitize (optional callback), autoload (optional bool, für Secrets false).
	 * @param string $description Optionale Beschreibung für den Tab-Bereich.
	 */
	public static function register( $module_slug, $tab_label, array $fields, $description = '' ) {
		// Slug unverändert lassen (Ordnername für Pfad-Zuordnung).
		self::$schemas[ $module_slug ] = array(
			'tab_label'   => $tab_label,
			'fields'      => $fields,
			'description' => $description,
		);
	}

	/**
	 * Liefert alle registrierten Schemata (nur für aktivierte Module gefiltert).
	 *
	 * @param string[] $active_module_slugs Liste aktivierter Modul-Slugs.
	 * @return array<string, array{ tab_label: string, fields: array }>
	 */
	public static function get_schemas_for_active_modules( array $active_module_slugs ) {
		$out = array();
		foreach ( self::$schemas as $slug => $schema ) {
			if ( in_array( $slug, $active_module_slugs, true ) ) {
				$out[ $slug ] = $schema;
			}
		}
		return $out;
	}

	/**
	 * Liefert das komplette Schema-Array (für interne Nutzung).
	 *
	 * @return array<string, array{ tab_label: string, fields: array }>
	 */
	public static function get_all_schemas() {
		return self::$schemas;
	}

	/**
	 * Option-Key für ein Modul.
	 *
	 * @param string $module_slug Modul-Slug.
	 * @return string
	 */
	public static function option_key( $module_slug ) {
		return self::OPTION_PREFIX . $module_slug;
	}

	/**
	 * Sanitize-Callback pro Feldtyp (Standard).
	 *
	 * @param mixed  $value   Eingabewert.
	 * @param array  $field   Felddefinition.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_field( $value, $field ) {
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
			return call_user_func( $field['sanitize'], $value, $field );
		}

		switch ( isset( $field['type'] ) ? $field['type'] : 'text' ) {
			case 'checkbox':
				return ! empty( $value );
			case 'select':
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? array_keys( $field['options'] ) : array();
				return in_array( $value, $options, true ) ? $value : ( isset( $field['default'] ) ? $field['default'] : '' );
			case 'password':
			case 'text':
			default:
				return is_string( $value ) ? sanitize_text_field( $value ) : '';
		}
	}
}
