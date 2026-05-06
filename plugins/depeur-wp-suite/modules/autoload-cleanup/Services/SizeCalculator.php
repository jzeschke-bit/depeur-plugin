<?php
/**
 * Berechnet Byte-Größe und human-readable Darstellung.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Services;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Größenberechnung für Option-Werte (Bytes + lesbare Ausgabe).
 */
class SizeCalculator {

	/**
	 * Formatiert Bytes als lesbaren String (B, KB, MB).
	 *
	 * @param int $bytes Größe in Bytes.
	 * @return string
	 */
	public static function format_bytes( $bytes ) {
		$bytes = (int) $bytes;
		if ( $bytes < 1024 ) {
			return $bytes . ' B';
		}
		if ( $bytes < 1024 * 1024 ) {
			return number_format_i18n( $bytes / 1024, 1 ) . ' KB';
		}
		return number_format_i18n( $bytes / ( 1024 * 1024 ), 2 ) . ' MB';
	}

	/**
	 * Berechnet die Byte-Größe eines Option-Werts (aus der DB-Spalte).
	 * Wenn die Länge bereits übergeben wird (z. B. aus SQL LENGTH(option_value)), nutze sie.
	 *
	 * @param string $raw_value Roher option_value (serialized oder String).
	 * @return int Bytes.
	 */
	public static function size_bytes( $raw_value ) {
		if ( ! is_string( $raw_value ) ) {
			return 0;
		}
		return strlen( $raw_value );
	}
}
