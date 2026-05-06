<?php
/**
 * Speichert und lädt Regeln (Prefix-Map, Ignore-Listen, UI-Defaults).
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Services;

use Depeur\WPSuite\Modules\AutoloadCleanup\Admin\Settings;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zentrale Speicherung für Prefix-Mappings, ignorierte Optionen/Prefixe und UI-Einstellungen.
 */
class RulesStore {

	/**
	 * Maximale Anzahl Einträge pro Liste (Anti-Ablage-Explosion).
	 *
	 * @var int
	 */
	const MAX_PREFIX_MAP = 200;
	const MAX_IGNORED_PREFIXES = 100;
	const MAX_IGNORED_OPTIONS = 500;

	/**
	 * Liefert die komplette Regeln-Struktur (mit Defaults).
	 *
	 * @return array{ prefix_map: array, ignored_prefixes: array, ignored_options: array, ui: array }
	 */
	public static function get_all() {
		$raw = get_option( Settings::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		return array(
			'prefix_map'        => isset( $raw['prefix_map'] ) && is_array( $raw['prefix_map'] ) ? $raw['prefix_map'] : array(),
			'ignored_prefixes'  => isset( $raw['ignored_prefixes'] ) && is_array( $raw['ignored_prefixes'] ) ? $raw['ignored_prefixes'] : array(),
			'ignored_options'   => isset( $raw['ignored_options'] ) && is_array( $raw['ignored_options'] ) ? $raw['ignored_options'] : array(),
			'ui'                => array(
				'per_page'       => isset( $raw['ui']['per_page'] ) ? (int) $raw['ui']['per_page'] : 50,
				'min_size_bytes' => isset( $raw['ui']['min_size_bytes'] ) ? (int) $raw['ui']['min_size_bytes'] : 0,
			),
		);
	}

	/**
	 * Prüft, ob eine Option oder ihr Prefix ignoriert wird.
	 *
	 * @param string $option_name Option-Name.
	 * @return bool
	 */
	public static function is_ignored( $option_name ) {
		$rules = self::get_all();
		if ( in_array( $option_name, $rules['ignored_options'], true ) ) {
			return true;
		}
		foreach ( $rules['ignored_prefixes'] as $prefix ) {
			if ( $prefix !== '' && strpos( $option_name, $prefix ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Liefert den Plugin-Slug für einen Option-Namen (längster passender Prefix aus prefix_map).
	 *
	 * @param string $option_name Option-Name.
	 * @return string Leer wenn kein Mapping.
	 */
	public static function get_plugin_slug_for_option( $option_name ) {
		$rules = self::get_all();
		$best  = '';
		$best_len = 0;
		foreach ( $rules['prefix_map'] as $prefix => $slug ) {
			if ( $prefix !== '' && strpos( $option_name, $prefix ) === 0 ) {
				$len = strlen( $prefix );
				if ( $len > $best_len ) {
					$best_len = $len;
					$best     = $slug;
				}
			}
		}
		return $best;
	}

	/**
	 * Speichert aktualisierte Regeln (sanitized).
	 *
	 * @param array $input prefix_map, ignored_prefixes, ignored_options, ui.
	 * @return bool True bei Erfolg.
	 */
	public static function save( array $input ) {
		$prefix_map       = isset( $input['prefix_map'] ) && is_array( $input['prefix_map'] ) ? $input['prefix_map'] : array();
		$ignored_prefixes = isset( $input['ignored_prefixes'] ) && is_array( $input['ignored_prefixes'] ) ? $input['ignored_prefixes'] : array();
		$ignored_options  = isset( $input['ignored_options'] ) && is_array( $input['ignored_options'] ) ? $input['ignored_options'] : array();
		$ui               = isset( $input['ui'] ) && is_array( $input['ui'] ) ? $input['ui'] : array();

		$prefix_map = array_slice( self::sanitize_prefix_map( $prefix_map ), 0, self::MAX_PREFIX_MAP );
		$ignored_prefixes = array_slice( self::sanitize_string_list( $ignored_prefixes ), 0, self::MAX_IGNORED_PREFIXES );
		$ignored_options  = array_slice( self::sanitize_string_list( $ignored_options ), 0, self::MAX_IGNORED_OPTIONS );
		$per_page         = isset( $ui['per_page'] ) ? max( 1, min( 500, (int) $ui['per_page'] ) ) : 50;
		$min_size_bytes   = isset( $ui['min_size_bytes'] ) ? max( 0, (int) $ui['min_size_bytes'] ) : 0;

		$data = array(
			'prefix_map'        => $prefix_map,
			'ignored_prefixes'  => $ignored_prefixes,
			'ignored_options'   => $ignored_options,
			'ui'                => array(
				'per_page'       => $per_page,
				'min_size_bytes' => $min_size_bytes,
			),
		);
		return update_option( Settings::OPTION_KEY, $data );
	}

	/**
	 * Sanitized eine Liste von Key-Value-Paaren (Prefix => Slug).
	 *
	 * @param array $map Prefix => Plugin-Slug.
	 * @return array
	 */
	private static function sanitize_prefix_map( array $map ) {
		$out = array();
		foreach ( $map as $prefix => $slug ) {
			$p = is_string( $prefix ) ? sanitize_text_field( $prefix ) : '';
			$s = is_string( $slug ) ? sanitize_text_field( $slug ) : '';
			if ( $p !== '' ) {
				$out[ $p ] = $s;
			}
		}
		return $out;
	}

	/**
	 * Sanitized eine Liste von Strings (Option-Namen oder Prefixe).
	 *
	 * @param array $list Liste von Strings.
	 * @return array
	 */
	private static function sanitize_string_list( array $list ) {
		$out = array();
		foreach ( $list as $item ) {
			if ( is_string( $item ) ) {
				$v = sanitize_text_field( $item );
				if ( $v !== '' && ! in_array( $v, $out, true ) ) {
					$out[] = $v;
				}
			}
		}
		return $out;
	}
}
