<?php
/**
 * Aktion: Einzelne Option oder mehrere Optionen löschen.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Actions
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Actions;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Führt delete_option für eine oder mehrere Optionen aus (mit Nonce/Capability im Controller).
 */
class DeleteOptionAction {

	/**
	 * Löscht eine einzelne Option.
	 *
	 * @param string $option_name Option-Name (sollte bereits sanitized sein).
	 * @return array{ success: bool, message: string, deleted: int }
	 */
	public static function run_single( $option_name ) {
		$option_name = self::sanitize_option_name( $option_name );
		if ( $option_name === '' ) {
			return array( 'success' => false, 'message' => __( 'Ungültiger Option-Name.', 'depeur-wp-suite' ), 'deleted' => 0 );
		}
		if ( self::is_protected( $option_name ) ) {
			return array( 'success' => false, 'message' => __( 'Diese Option darf nicht gelöscht werden.', 'depeur-wp-suite' ), 'deleted' => 0 );
		}
		$ok = delete_option( $option_name );
		return array(
			'success' => $ok,
			'message' => $ok ? __( 'Option gelöscht.', 'depeur-wp-suite' ) : __( 'Löschen fehlgeschlagen oder Option existierte nicht.', 'depeur-wp-suite' ),
			'deleted' => $ok ? 1 : 0,
		);
	}

	/**
	 * Löscht mehrere Optionen (Bulk).
	 *
	 * @param array $option_names Liste von Option-Namen (werden sanitized).
	 * @return array{ success: bool, message: string, deleted: int }
	 */
	public static function run_bulk( array $option_names ) {
		$deleted = 0;
		foreach ( $option_names as $name ) {
			$name = self::sanitize_option_name( $name );
			if ( $name === '' || self::is_protected( $name ) ) {
				continue;
			}
			if ( delete_option( $name ) ) {
				$deleted++;
			}
		}
		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of deleted options */
				_n( '%d Option gelöscht.', '%d Optionen gelöscht.', $deleted, 'depeur-wp-suite' ),
				$deleted
			),
			'deleted' => $deleted,
		);
	}

	/**
	 * Sanitized einen Option-Namen (nur erlaubte Zeichen).
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	private static function sanitize_option_name( $name ) {
		if ( ! is_string( $name ) ) {
			return '';
		}
		return sanitize_text_field( $name );
	}

	/**
	 * Prüft, ob eine Option nicht gelöscht werden darf (WordPress-Core, Suite-Core).
	 *
	 * @param string $option_name Option-Name.
	 * @return bool
	 */
	private static function is_protected( $option_name ) {
		$protected = array(
			'siteurl',
			'home',
			'blogname',
			'blogdescription',
			'users_can_register',
			'admin_email',
			'default_role',
			'template',
			'stylesheet',
			'active_plugins',
			'cron',
			'rewrite_rules',
			'depeur_wp_suite_modules',
		);
		if ( in_array( $option_name, $protected, true ) ) {
			return true;
		}
		if ( strpos( $option_name, 'depeur_wp_suite_' ) === 0 ) {
			return true;
		}
		return false;
	}
}
