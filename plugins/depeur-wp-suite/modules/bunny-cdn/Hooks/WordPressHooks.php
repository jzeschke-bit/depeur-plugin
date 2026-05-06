<?php
/**
 * WordPress-Hooks: save_post, delete_post, trash_post, manueller Purge-Button.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Hooks
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Hooks;

use Depeur\WPSuite\Modules\BunnyCDN\Services\PurgeService;
use Depeur\WPSuite\Modules\BunnyCDN\Services\CdnRewriter;
use Depeur\WPSuite\Modules\BunnyCDN\Integrations\RunCloudIntegration;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert alle WordPress- und Admin-Hooks für BunnyCDN.
 */
class WordPressHooks {

	/**
	 * Registriert Hooks (nur wenn BunnyCDN aktiviert).
	 */
	public static function register() {
		$opts = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		if ( empty( $opts['enable_bunny_cdn'] ) ) {
			return;
		}

		// Post/Page: nach Speichern, Löschen, in Papierkorb
		add_action( 'save_post', array( __CLASS__, 'on_save_post' ), 20, 3 );
		add_action( 'trashed_post', array( __CLASS__, 'on_trashed_post' ), 20, 1 );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 20, 1 );

		// RunCloud-Sync (wenn RunCloud erkannt und mindestens eine Sync-Option aktiviert)
		if ( RunCloudIntegration::is_runcloud_detected() && ( ! empty( $opts['enable_runcloud_sync'] ) || ! empty( $opts['enable_runcloud_sync_single'] ) ) ) {
			RunCloudIntegration::register_purge_sync();
		}

		// Frontend: URLs von Origin auf CDN-Hostname umschreiben (CSS, JS, Themes, Uploads, Plugins)
		CdnRewriter::register();

		// Admin: manueller Purge-Button (AJAX oder Link mit Nonce)
		add_action( 'admin_init', array( __CLASS__, 'handle_manual_purge_request' ) );
	}

	/**
	 * Nach Speichern eines Beitrags oder einer Seite: Purge auslösen.
	 *
	 * @param int      $post_id Post-ID.
	 * @param \WP_Post $post    Post-Objekt.
	 * @param bool     $update  Ob Update (true) oder Neu (false).
	 */
	public static function on_save_post( $post_id, $post, $update ) {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		$post_types = array( 'post', 'page' );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}
		$purge = new PurgeService();
		$purge->purge_all_if_allowed();
	}

	/**
	 * Nach Verschieben in den Papierkorb.
	 *
	 * @param int $post_id Post-ID.
	 */
	public static function on_trashed_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}
		$purge = new PurgeService();
		$purge->purge_all_if_allowed();
	}

	/**
	 * Nach endgültigem Löschen (Post ist zu diesem Zeitpunkt bereits gelöscht).
	 *
	 * @param int $post_id Post-ID.
	 */
	public static function on_deleted_post( $post_id ) {
		$purge = new PurgeService();
		$purge->purge_all_if_allowed();
	}

	/**
	 * Verarbeitet manuellen Purge-Request aus dem Admin (Link mit Nonce).
	 */
	public static function handle_manual_purge_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['depeur_bunny_purge'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'depeur_bunny_purge_all' ) ) {
			return;
		}
		$purge   = new PurgeService();
		$result  = $purge->purge_all_if_allowed();
		$message = $result['message'];
		$type    = $result['success'] ? 'success' : 'error';
		if ( ! empty( $result['skipped_debounce'] ) ) {
			$type = 'info';
		}
		$ref  = wp_get_referer();
		$base = admin_url( 'admin.php?page=depeur-wp-suite-settings&tab=bunny-cdn' );
		if ( $ref && strpos( $ref, 'page=depeur-wp-suite-bunny-cdn' ) !== false ) {
			$base = admin_url( 'admin.php?page=depeur-wp-suite-bunny-cdn' );
		} elseif ( $ref && strpos( $ref, 'page=depeur-wp-suite-settings' ) !== false ) {
			$base = remove_query_arg( array( 'depeur_bunny_purge_done', 'depeur_bunny_msg', 'depeur_bunny_type' ), $ref );
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'depeur_bunny_purge_done' => 1,
					'depeur_bunny_msg'        => rawurlencode( $message ),
					'depeur_bunny_type'       => $type,
				),
				$base
			)
		);
		exit;
	}
}
