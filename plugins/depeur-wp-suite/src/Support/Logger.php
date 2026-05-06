<?php
/**
 * Einfacher Datei-Logger. Standard: aus. Aktivierbar über Core-Einstellung.
 * Logs in uploads/depeur-wp-suite-logs/, Rotation (max. 7 Dateien). Keine Secrets loggen.
 *
 * @package Depeur\WPSuite\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Support;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse Logger.
 */
class Logger {

	/**
	 * Maximale Anzahl Logdateien (Rotation).
	 *
	 * @var int
	 */
	const MAX_FILES = 7;

	/**
	 * Log-Verzeichnis-Name unter uploads.
	 *
	 * @var string
	 */
	const LOG_DIR = 'depeur-wp-suite-logs';

	/**
	 * Ob der Logger initialisiert wurde.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Absoluter Pfad zum Log-Verzeichnis.
	 *
	 * @var string
	 */
	private static $log_dir = '';

	/**
	 * Initialisiert den Logger (Verzeichnis anlegen, Rotation prüfen).
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return;
		}
		self::$log_dir = $upload_dir['basedir'] . '/' . self::LOG_DIR;
		if ( ! is_dir( self::$log_dir ) ) {
			wp_mkdir_p( self::$log_dir );
		}
		if ( is_dir( self::$log_dir ) && is_writable( self::$log_dir ) ) {
			self::$initialized = true;
			self::rotate_if_needed();
		}
	}

	/**
	 * Schreibt einen Log-Eintrag. Niemals Secrets (API-Keys, Passwörter) übergeben.
	 *
	 * @param string $level   Level: info, warning, error.
	 * @param string $message Nachricht (bereits bereinigt, keine sensiblen Daten).
	 * @param array  $context Optional zusätzliche Daten (werden serialisiert; keine Secrets).
	 */
	public static function log( $level, $message, array $context = array() ) {
		if ( ! self::$initialized ) {
			return;
		}
		$enabled = (bool) get_option( 'depeur_wp_suite_logging_enabled', false );
		if ( ! $enabled ) {
			return;
		}

		$file = self::$log_dir . '/log-' . gmdate( 'Y-m-d' ) . '.log';
		$line = gmdate( 'Y-m-d H:i:s' ) . ' [' . $level . '] ' . $message;
		if ( ! empty( $context ) ) {
			$line .= ' ' . wp_json_encode( $context );
		}
		$line .= "\n";

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Rotiert alte Logdateien (älteste löschen, wenn mehr als MAX_FILES).
	 */
	private static function rotate_if_needed() {
		$files = glob( self::$log_dir . '/log-*.log' );
		if ( ! is_array( $files ) || count( $files ) <= self::MAX_FILES ) {
			return;
		}
		usort( $files, function ( $a, $b ) {
			return filemtime( $a ) - filemtime( $b );
		} );
		$to_remove = array_slice( $files, 0, count( $files ) - self::MAX_FILES );
		foreach ( $to_remove as $path ) {
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}
}
