<?php
/**
 * Test, ob die Seite über BunnyCDN ausgeliefert wird und Cache-Header gesetzt sind.
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
 * Prüft CDN-Erreichbarkeit und Cache-Header (X-Cache, CDN-Cache, Server).
 */
class CacheTest {

	/**
	 * Timeout für Test-Request (Sekunden).
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 10;

	/**
	 * Führt einen Test-Request aus und wertet die Response-Header aus.
	 *
	 * @param string|null $cdn_hostname Optional CDN-Hostname (z. B. zone.b-cdn.net). Sonst Home-URL.
	 * @return array{ success: bool, message: string, details: string }
	 */
	public static function test( $cdn_hostname = null ) {
		$url = self::get_test_url( $cdn_hostname );
		if ( ! $url ) {
			return array(
				'success'  => false,
				'message'  => __( 'Keine Test-URL möglich: Bitte CDN-Hostname in den Einstellungen eintragen oder die Startseite ist nicht erreichbar.', 'depeur-wp-suite' ),
				'details'  => '',
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => self::REQUEST_TIMEOUT,
				'sslverify'  => true,
				'user-agent' => 'Depeur-WP-Suite-BunnyCDN-Test/1.0',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'  => false,
				'message'  => __( 'Anfrage fehlgeschlagen.', 'depeur-wp-suite' ),
				'details'  => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$headers = wp_remote_retrieve_headers( $response );
		if ( $code < 200 || $code >= 400 ) {
			return array(
				'success'  => false,
				'message'  => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Antwort mit Status %d.', 'depeur-wp-suite' ),
					$code
				),
				'details'  => $url,
			);
		}

		$server    = isset( $headers['server'] ) ? (string) $headers['server'] : '';
		$x_cache   = isset( $headers['x-cache'] ) ? (string) $headers['x-cache'] : '';
		$cdn_cache = isset( $headers['cdn-cache'] ) ? (string) $headers['cdn-cache'] : '';

		$is_bunny = ( stripos( $server, 'bunny' ) !== false ) || ( stripos( $x_cache, 'HIT' ) !== false || stripos( $x_cache, 'MISS' ) !== false ) || ( stripos( $cdn_cache, 'HIT' ) !== false || stripos( $cdn_cache, 'MISS' ) !== false );

		if ( $is_bunny ) {
			$cache_status = trim( $x_cache ?: $cdn_cache );
			$details      = $server ? sprintf( 'Server: %s', $server ) : '';
			if ( $cache_status !== '' ) {
				$details .= ( $details ? ' | ' : '' ) . 'Cache: ' . $cache_status;
			}
			$is_hit = ( stripos( $cache_status, 'HIT' ) !== false );
			if ( $is_hit ) {
				$message = __( 'Die Seite wird über BunnyCDN ausgeliefert. Cache-Status: HIT (Inhalt kommt aus dem CDN-Cache).', 'depeur-wp-suite' );
			} else {
				$message = __( 'Die Seite wird über BunnyCDN ausgeliefert. Cache-Status: MISS (Inhalt wurde vom Origin geladen; beim nächsten Aufruf kann HIT erscheinen).', 'depeur-wp-suite' );
			}
			return array(
				'success'  => true,
				'message'  => $message,
				'details'  => $details,
			);
		}

		return array(
			'success'  => false,
			'message'  => __( 'Keine BunnyCDN-typischen Header gefunden. Die Anfrage wurde möglicherweise nicht über die Pull Zone ausgeliefert (z. B. direkter Aufruf der Origin-URL).', 'depeur-wp-suite' ),
			'details'  => $server ? sprintf( 'Server: %s', $server ) : $url,
		);
	}

	/**
	 * Liefert die URL für den Test (CDN-Hostname oder Home-URL).
	 *
	 * @param string|null $cdn_hostname Optional.
	 * @return string|null
	 */
	private static function get_test_url( $cdn_hostname = null ) {
		$hostname = $cdn_hostname ? trim( (string) $cdn_hostname ) : '';
		if ( $hostname !== '' ) {
			$scheme = is_ssl() ? 'https' : 'http';
			return $scheme . '://' . $hostname . '/';
		}
		$home = home_url( '/' );
		return $home ? $home : null;
	}
}
