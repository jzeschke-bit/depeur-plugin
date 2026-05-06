<?php
/**
 * Aktion: Regeln (Prefix-Map, Ignore-Listen, UI) speichern.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Actions
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Actions;

use Depeur\WPSuite\Modules\AutoloadCleanup\Services\RulesStore;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Speichert vom Formular übergebene Regeln (Nonce/Capability im Controller).
 */
class UpdateRulesAction {

	/**
	 * Speichert die Regeln.
	 *
	 * @param array $input POST-Daten (prefix_map, ignored_prefixes, ignored_options, ui).
	 * @return array{ success: bool, message: string }
	 */
	public static function run( array $input ) {
		$current = RulesStore::get_all();
		$prefix_map = $current['prefix_map'];
		if ( isset( $input['prefix_map'] ) && is_array( $input['prefix_map'] ) ) {
			$prefix_map = array();
			foreach ( $input['prefix_map'] as $prefix => $slug ) {
				$p = is_string( $prefix ) ? sanitize_text_field( $prefix ) : '';
				$s = is_string( $slug ) ? sanitize_text_field( $slug ) : '';
				if ( $p !== '' ) {
					$prefix_map[ $p ] = $s;
				}
			}
		}
		$ignored_prefixes = $current['ignored_prefixes'];
		if ( isset( $input['ignored_prefixes'] ) && is_array( $input['ignored_prefixes'] ) ) {
			$ignored_prefixes = array();
			foreach ( $input['ignored_prefixes'] as $v ) {
				if ( is_string( $v ) ) {
					$t = sanitize_text_field( $v );
					if ( $t !== '' ) {
						$ignored_prefixes[] = $t;
					}
				}
			}
		}
		$ignored_options = $current['ignored_options'];
		if ( isset( $input['ignored_options'] ) && is_array( $input['ignored_options'] ) ) {
			$ignored_options = array();
			foreach ( $input['ignored_options'] as $v ) {
				if ( is_string( $v ) ) {
					$t = sanitize_text_field( $v );
					if ( $t !== '' ) {
						$ignored_options[] = $t;
					}
				}
			}
		}
		$ui = $current['ui'];
		if ( isset( $input['per_page'] ) ) {
			$ui['per_page'] = max( 1, min( 500, (int) $input['per_page'] ) );
		}
		if ( isset( $input['min_size_bytes'] ) ) {
			$ui['min_size_bytes'] = max( 0, (int) $input['min_size_bytes'] );
		}
		$ok = RulesStore::save( array(
			'prefix_map'       => $prefix_map,
			'ignored_prefixes' => $ignored_prefixes,
			'ignored_options'  => $ignored_options,
			'ui'               => $ui,
		) );
		return array(
			'success' => $ok,
			'message' => $ok ? __( 'Regeln gespeichert.', 'depeur-wp-suite' ) : __( 'Speichern fehlgeschlagen.', 'depeur-wp-suite' ),
		);
	}
}
