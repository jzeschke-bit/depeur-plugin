<?php
/**
 * Erkennung der Umgebung (Live vs. Staging) für bedingtes Purge-Verhalten.
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
 * Erkennt Live vs. Staging (RunCloud-Indikatoren, WP_ENV, Domain).
 */
class EnvironmentDetector {

	/**
	 * Gibt die aktuelle Umgebung zurück: 'live' oder 'staging'.
	 *
	 * @param array $options Modul-Optionen (auto_detect_environment, environment_override).
	 * @return string 'live'|'staging'
	 */
	public static function get_environment( array $options = array() ) {
		$override = isset( $options['environment_override'] ) ? $options['environment_override'] : 'auto';
		if ( $override === 'live' || $override === 'staging' ) {
			return $override;
		}

		$auto = isset( $options['auto_detect_environment'] ) ? $options['auto_detect_environment'] : true;
		if ( ! $auto ) {
			return 'live';
		}

		// WP_ENV (z. B. von Bedrock / RunCloud)
		if ( defined( 'WP_ENV' ) && is_string( WP_ENV ) ) {
			$env = strtolower( WP_ENV );
			if ( $env === 'staging' || $env === 'development' ) {
				return 'staging';
			}
			if ( $env === 'production' || $env === 'live' ) {
				return 'live';
			}
		}

		// RunCloud: typische Staging-Subdomain oder Konstante (falls vorhanden)
		if ( defined( 'RUNCLOUD_HUB_FILE' ) && function_exists( 'runcloud_hub' ) ) {
			$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			if ( $host && ( strpos( $host, 'staging' ) !== false || strpos( $host, 'stage.' ) !== false ) ) {
				return 'staging';
			}
		}

		// Domain: staging in Hostname -> staging
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		if ( $host && ( strpos( strtolower( $host ), 'staging' ) !== false || strpos( strtolower( $host ), 'stage.' ) !== false ) ) {
			return 'staging';
		}

		return 'live';
	}
}
