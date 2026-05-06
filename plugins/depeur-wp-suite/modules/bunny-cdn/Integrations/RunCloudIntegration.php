<?php
/**
 * RunCloud-Integration: Erkennung und Hook-Anbindung für Purge-Sync.
 * Konzeptuell orientiert an der Cloudflare-Integration des RunCloud Hub Plugins.
 *
 * KEINE harte Abhängigkeit: Wenn RunCloud fehlt oder Hooks fehlen, läuft BunnyCDN standalone.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Integrations
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Integrations;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RunCloud-Integration für BunnyCDN (Purge-Sync bei RunCloud Purge All).
 */
class RunCloudIntegration {

	/**
	 * Prüft, ob das RunCloud-Plugin aktiv und nutzbar ist.
	 *
	 * @return bool
	 */
	public static function is_runcloud_detected() {
		return defined( 'RUNCLOUD_HUB_FILE' );
	}

	/**
	 * Registriert die Listener für RunCloud-Purge (All + optional Single), um BunnyCDN mitzuleeren.
	 * Nur aufrufen, wenn RunCloud erkannt und mindestens eine Sync-Option aktiviert ist.
	 */
	public static function register_purge_sync() {
		$opts = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		// Konzeptuell orientiert an der Cloudflare-Integration des RunCloud Hub Plugins.
		if ( ! empty( $opts['enable_runcloud_sync'] ) ) {
			add_action( 'runcloud_purge_nginx_cache_after', array( __CLASS__, 'on_runcloud_purge_after' ), 10, 0 );
			add_action( 'runcloud_purge_nginx_cache', array( __CLASS__, 'on_runcloud_purge' ), 10, 0 );
		}
		if ( ! empty( $opts['enable_runcloud_sync_single'] ) ) {
			add_action( 'runcloud_purge_nginx_cache_post', array( __CLASS__, 'on_runcloud_purge_single' ), 10, 1 );
			add_action( 'runcloud_purge_nginx_cache_home', array( __CLASS__, 'on_runcloud_purge_single' ), 10, 0 );
		}
	}

	/**
	 * Wird ausgeführt, nachdem RunCloud den NGINX-Cache geleert hat.
	 */
	public static function on_runcloud_purge_after() {
		self::trigger_bunny_purge_if_sync_enabled();
	}

	/**
	 * Wird ausgeführt, wenn RunCloud den NGINX-Cache leert (alternativer Hook).
	 */
	public static function on_runcloud_purge() {
		self::trigger_bunny_purge_if_sync_enabled();
	}

	/**
	 * Wird ausgeführt, wenn RunCloud nur eine Seite/Beitrag oder die Startseite leert.
	 *
	 * @param int $post_id Post-ID (bei runcloud_purge_nginx_cache_post).
	 */
	public static function on_runcloud_purge_single( $post_id = 0 ) {
		self::trigger_bunny_purge_if_sync_single_enabled();
	}

	/**
	 * Löst BunnyCDN Purge All aus, wenn RunCloud-Sync „Single“ aktiviert ist.
	 */
	private static function trigger_bunny_purge_if_sync_single_enabled() {
		$opts = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		if ( empty( $opts['enable_bunny_cdn'] ) || empty( $opts['enable_runcloud_sync_single'] ) ) {
			return;
		}
		$purge_service = new \Depeur\WPSuite\Modules\BunnyCDN\Services\PurgeService();
		$purge_service->purge_all_if_allowed();
	}

	/**
	 * Löst BunnyCDN Purge All aus, wenn RunCloud-Sync („Purge All“) aktiviert ist.
	 */
	private static function trigger_bunny_purge_if_sync_enabled() {
		$opts = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		if ( empty( $opts['enable_bunny_cdn'] ) || empty( $opts['enable_runcloud_sync'] ) ) {
			return;
		}
		$purge_service = new \Depeur\WPSuite\Modules\BunnyCDN\Services\PurgeService();
		$purge_service->purge_all_if_allowed();
	}
}
