<?php
/**
 * BunnyCDN API: Purge und optionale Abfragen über WordPress HTTP API.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Services;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BunnyCDN REST API (Pull Zone Purge).
 */
class BunnyApi {

	/**
	 * Basis-URL der Bunny API.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.bunny.net';

	/**
	 * Timeout für HTTP-Requests (Sekunden).
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 15;

	/**
	 * Führt einen Full-Purge für eine Pull Zone aus.
	 *
	 * @param string $pull_zone_id Pull Zone ID (numerisch).
	 * @param string $api_key      API Key (niemals loggen).
	 * @return array{ success: bool, code: int, message: string }
	 */
	public static function purge_pull_zone( $pull_zone_id, $api_key ) {
		$pull_zone_id = preg_replace( '/[^0-9]/', '', (string) $pull_zone_id );
		if ( $pull_zone_id === '' || $api_key === '' ) {
			return array(
				'success' => false,
				'code'    => 0,
				'message' => __( 'Pull Zone ID oder API Key fehlt.', 'depeur-wp-suite' ),
			);
		}

		$url  = self::API_BASE . '/pullzone/' . $pull_zone_id . '/purgeCache';
		$args = array(
			'method'  => 'POST',
			'timeout' => self::REQUEST_TIMEOUT,
			'headers'  => array(
				'AccessKey' => $api_key,
				'Accept'     => 'application/json',
			),
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'code'    => 0,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// 200/204 = Erfolg
		if ( $code >= 200 && $code < 300 ) {
			return array(
				'success' => true,
				'code'    => $code,
				'message' => __( 'Cache erfolgreich geleert.', 'depeur-wp-suite' ),
			);
		}

		$message = __( 'Unbekannter API-Fehler.', 'depeur-wp-suite' );
		$decoded = json_decode( $body, true );
		if ( is_array( $decoded ) && isset( $decoded['ErrorMessage'] ) ) {
			$message = sanitize_text_field( (string) $decoded['ErrorMessage'] );
		}

		return array(
			'success' => false,
			'code'    => $code,
			'message' => $message,
		);
	}

	// Pro-Feature (konzeptionell): URL-basiertes Purge über Purge-URL-Endpoint der API.
}
