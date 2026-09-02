<?php
/**
 * Flodesk_Api — serverseitige Anbindung an die Flodesk-REST-API.
 *
 * Trägt Abonnenten OHNE das Flodesk-Formular-Widget ein (deshalb kein „I am not a robot"):
 * unser eigenes Formular sendet an unseren REST-Endpoint (Rest\Subscribe_Controller), der
 * ruft diese Klasse auf, die serverseitig mit dem API-Key gegen api.flodesk.com spricht.
 *
 * Auth: HTTP-Basic mit dem API-Key als Benutzername + leerem Passwort (Flodesk-Konvention).
 * Der Key verlässt NIE den Server (Secret, autoload=false).
 *
 * @package Depeur\Food\Modules\Newsletter\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Support;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dünner Client für die zwei benötigten Flodesk-Endpoints (Subscriber + Segment-Zuordnung).
 *
 * @since 0.3.0
 */
final class Flodesk_Api {

	/**
	 * Basis-URL der Flodesk-API.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const BASE_URL = 'https://api.flodesk.com/v1';

	/**
	 * Timeout je Request (Sekunden). Bewusst knapp, damit ein hängender Call den Submit
	 * nicht ewig blockiert.
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const TIMEOUT = 12;

	/**
	 * Trägt eine E-Mail als Abonnent ein und ordnet sie den konfigurierten Segmenten zu.
	 *
	 * @since 0.3.0
	 *
	 * @param string   $email       Bereits validierte E-Mail-Adresse.
	 * @param string   $api_key     Flodesk-API-Key.
	 * @param string[] $segment_ids Ziel-Segment-IDs (kann leer sein).
	 * @return true|\WP_Error true bei Erfolg, sonst WP_Error mit Kontext.
	 */
	public function subscribe( string $email, string $api_key, array $segment_ids = array() ) {
		if ( '' === $api_key ) {
			return new \WP_Error( 'df_flodesk_no_key', __( 'Kein Flodesk-API-Key konfiguriert.', 'depeur-food' ) );
		}

		// 1) Subscriber anlegen/aktualisieren (Upsert).
		$created = $this->request(
			'POST',
			'/subscribers',
			$api_key,
			array( 'email' => $email )
		);
		if ( is_wp_error( $created ) ) {
			return $created;
		}

		// 2) Optional Segmenten zuordnen (löst bei Double-Opt-In die Bestätigungsmail aus).
		if ( ! empty( $segment_ids ) ) {
			$assigned = $this->request(
				'POST',
				'/subscribers/' . rawurlencode( $email ) . '/segments',
				$api_key,
				array( 'segment_ids' => array_values( $segment_ids ) )
			);
			if ( is_wp_error( $assigned ) ) {
				return $assigned;
			}
		}

		return true;
	}

	/**
	 * Führt einen JSON-Request gegen die Flodesk-API aus und normalisiert Fehler.
	 *
	 * @since 0.3.0
	 *
	 * @param string $method HTTP-Methode.
	 * @param string $path   Pfad relativ zur BASE_URL (mit führendem Slash).
	 * @param string $api_key Flodesk-API-Key.
	 * @param array  $body   Request-Body (wird als JSON gesendet).
	 * @return array|\WP_Error Dekodierte Antwort bei 2xx, sonst WP_Error.
	 */
	private function request( string $method, string $path, string $api_key, array $body ) {
		$response = wp_remote_request(
			self::BASE_URL . $path,
			array(
				'method'  => $method,
				'timeout' => self::TIMEOUT,
				'headers' => array(
					// Basic-Auth: API-Key als Benutzername, leeres Passwort.
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Basic-Auth-Header, keine Verschleierung.
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'User-Agent'    => 'depeur-food/' . ( defined( 'DEPEUR_FOOD_VERSION' ) ? DEPEUR_FOOD_VERSION : '0' ) . ' (' . home_url( '/' ) . ')',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

			return is_array( $decoded ) ? $decoded : array();
		}

		// Fehlermeldung aus der API ziehen (falls vorhanden), sonst generisch.
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$message = ( is_array( $decoded ) && ! empty( $decoded['message'] ) ) ? (string) $decoded['message'] : '';

		return new \WP_Error(
			'df_flodesk_http_' . $code,
			'' !== $message ? $message : sprintf(
				/* translators: %d: HTTP-Statuscode der Flodesk-API. */
				__( 'Flodesk-API-Fehler (HTTP %d).', 'depeur-food' ),
				$code
			),
			array( 'status' => $code )
		);
	}
}
