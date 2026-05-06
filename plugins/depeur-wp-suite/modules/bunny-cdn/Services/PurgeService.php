<?php
/**
 * Purge-Service: Debounce, Trigger-Auswertung, Aufruf BunnyApi.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Services;

use Depeur\WPSuite\Support\Logger;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zentrale Purge-Logik mit Debounce (Standard 30 Sekunden).
 */
class PurgeService {

	/**
	 * Option-Key für letzten Purge (Zeit + Erfolg).
	 *
	 * @var string
	 */
	const OPTION_LAST_PURGE = 'depeur_wp_suite_bunny_cdn_last_purge';

	/**
	 * Transient-Key für Debounce (kein erneuter Purge innerhalb des Fensters).
	 *
	 * @var string
	 */
	const DEBOUNCE_TRANSIENT = 'depeur_wp_suite_bunny_cdn_purge_debounce';

	/**
	 * Debounce-Fenster in Sekunden.
	 *
	 * @var int
	 */
	const DEBOUNCE_SECONDS = 30;

	/**
	 * Führt Purge All aus, wenn konfiguriert und nicht im Debounce-Fenster.
	 *
	 * @return array{ success: bool, message: string, skipped_debounce?: bool }
	 */
	public function purge_all_if_allowed() {
		$options = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		if ( empty( $options['enable_bunny_cdn'] ) ) {
			return array( 'success' => false, 'message' => __( 'BunnyCDN ist deaktiviert.', 'depeur-wp-suite' ) );
		}

		$api_key       = isset( $options['bunny_api_key'] ) ? $options['bunny_api_key'] : '';
		$pull_zone_id  = isset( $options['bunny_pull_zone_id'] ) ? trim( (string) $options['bunny_pull_zone_id'] ) : '';
		if ( $api_key === '' || $pull_zone_id === '' ) {
			return array( 'success' => false, 'message' => __( 'API Key oder Pull Zone ID fehlt.', 'depeur-wp-suite' ) );
		}

		// Staging: MVP purgt auch auf Staging. Agentur-Feature (konzeptionell): Purge auf Staging deaktivierbar; EnvironmentDetector::get_environment() für spätere Nutzung vorhanden.

		// Debounce
		if ( get_transient( self::DEBOUNCE_TRANSIENT ) ) {
			if ( (bool) get_option( 'depeur_wp_suite_logging_enabled', false ) ) {
				Logger::log( 'info', 'BunnyCDN Purge übersprungen (Debounce).', array( 'context' => 'bunny-cdn' ) );
			}
			return array(
				'success'          => true,
				'message'          => __( 'Purge innerhalb des Debounce-Fensters übersprungen.', 'depeur-wp-suite' ),
				'skipped_debounce'  => true,
			);
		}

		set_transient( self::DEBOUNCE_TRANSIENT, 1, self::DEBOUNCE_SECONDS );

		$result = BunnyApi::purge_pull_zone( $pull_zone_id, $api_key );

		$this->save_last_purge_result( $result );

		return array(
			'success' => $result['success'],
			'message' => $result['message'],
		);
	}

	/**
	 * Speichert das letzte Purge-Ergebnis für die Admin-Anzeige.
	 *
	 * @param array{ success: bool, code: int, message: string } $result
	 */
	private function save_last_purge_result( $result ) {
		update_option(
			self::OPTION_LAST_PURGE,
			array(
				'time'    => time(),
				'success' => (bool) $result['success'],
				'message' => isset( $result['message'] ) ? $result['message'] : '',
			),
			false
		);
	}

	/**
	 * Liefert die Daten des letzten Purges (für Admin-Status).
	 *
	 * @return array{ time: int, success: bool, message: string }|null
	 */
	public static function get_last_purge() {
		$data = get_option( self::OPTION_LAST_PURGE, null );
		return is_array( $data ) ? $data : null;
	}
}
