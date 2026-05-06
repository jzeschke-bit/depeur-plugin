<?php
/**
 * Aktion: Alle Optionen mit einem Prefix löschen (nach Preview + Bestätigung).
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Actions
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Actions;

use Depeur\WPSuite\Modules\AutoloadCleanup\Services\OptionsRepository;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Führt Massenlöschung nach Prefix aus. Schutz: keine Core-Prefixe.
 */
class BulkDeletePrefixAction {

	/**
	 * Prefixe, die nicht per Bulk gelöscht werden dürfen.
	 *
	 * @var string[]
	 */
	const PROTECTED_PREFIXES = array( 'depeur_wp_suite_', 'wp_', '_transient_' );

	/**
	 * Führt das Löschen aus.
	 *
	 * @param string $prefix Prefix (sollte bereits sanitized sein).
	 * @return array{ success: bool, message: string, deleted: int }
	 */
	public static function run( $prefix ) {
		$prefix = self::sanitize_prefix( $prefix );
		if ( $prefix === '' ) {
			return array( 'success' => false, 'message' => __( 'Prefix darf nicht leer sein.', 'depeur-wp-suite' ), 'deleted' => 0 );
		}
		if ( self::is_protected( $prefix ) ) {
			return array( 'success' => false, 'message' => __( 'Dieser Prefix ist geschützt und darf nicht per Bulk gelöscht werden.', 'depeur-wp-suite' ), 'deleted' => 0 );
		}
		$deleted = OptionsRepository::delete_by_prefix( $prefix );
		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: 1: number of deleted options, 2: prefix */
				_n( '%1$d Option mit Prefix „%2$s“ gelöscht.', '%1$d Optionen mit Prefix „%2$s“ gelöscht.', $deleted, 'depeur-wp-suite' ),
				$deleted,
				$prefix
			),
			'deleted' => $deleted,
		);
	}

	/**
	 * Sanitized einen Prefix-String.
	 *
	 * @param string $prefix Raw prefix.
	 * @return string
	 */
	private static function sanitize_prefix( $prefix ) {
		if ( ! is_string( $prefix ) ) {
			return '';
		}
		return trim( sanitize_text_field( $prefix ) );
	}

	/**
	 * Prüft, ob der Prefix geschützt ist.
	 *
	 * @param string $prefix Prefix.
	 * @return bool
	 */
	private static function is_protected( $prefix ) {
		foreach ( self::PROTECTED_PREFIXES as $p ) {
			if ( $p === $prefix || strpos( $prefix, $p ) === 0 ) {
				return true;
			}
		}
		return false;
	}
}
