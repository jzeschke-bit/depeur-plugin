<?php
/**
 * Assets — Frontend-Enqueue des Favoriten-Moduls (Vanilla JS + CSS).
 *
 * Bindet df-favorites.js/.css ein und übergibt per wp_localize_script die Konfiguration
 * (REST-URLs, wp_rest-Nonce, Storage-/Legacy-Keys) als window.dfFavorites. Kein jQuery,
 * kein Build-Step (Asset-Convention der CLAUDE.md: Frontend strikt Vanilla).
 *
 * Enqueue läuft frontend-weit (wp_enqueue_scripts feuert nur im Frontend), weil die
 * einmalige Cookie→localStorage-Migration früh und überall greifen muss – sonst könnten
 * Nutzer beim ersten Seitenaufruf ohne Button ihre Legacy-Favoriten verlieren. Das Modul
 * selbst wird nur geladen, wenn es aktiv ist („geladen" ⟺ „aktiv").
 *
 * @package Depeur\Food\Modules\Favorites\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Frontend;

use Depeur\Food\Modules\Favorites\Rest\Favorites_Controller;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und enqueued die Frontend-Assets.
 *
 * @since 0.2.0
 */
final class Assets {

	/**
	 * Handle des Frontend-Skripts (zugleich Style-Handle).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const HANDLE = 'df-favorites';

	/**
	 * Verdrahtet den Enqueue-Hook.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueued Skript + Style und lokalisiert die JS-Konfiguration.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function enqueue(): void {
		$dir = DEPEUR_FOOD_PATH . 'modules/favorites/assets/';
		$url = DEPEUR_FOOD_URL . 'modules/favorites/assets/';

		$js_file  = $dir . 'js/df-favorites.js';
		$css_file = $dir . 'css/df-favorites.css';

		// filemtime als Cache-Buster (nur wenn die Datei existiert – sonst Plugin-Version).
		$js_ver  = is_file( $js_file ) ? (string) filemtime( $js_file ) : DEPEUR_FOOD_VERSION;
		$css_ver = is_file( $css_file ) ? (string) filemtime( $css_file ) : DEPEUR_FOOD_VERSION;

		wp_enqueue_style(
			self::HANDLE,
			$url . 'css/df-favorites.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			self::HANDLE,
			$url . 'js/df-favorites.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script(
			self::HANDLE,
			'dfFavorites',
			array(
				'toggleUrl'        => rest_url( Favorites_Controller::REST_NAMESPACE . Favorites_Controller::ROUTE_TOGGLE ),
				'listUrl'          => rest_url( Favorites_Controller::REST_NAMESPACE . Favorites_Controller::ROUTE_LIST ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'storageKey'       => 'df_favorites',
				// Legacy-Quellen für die einmalige Migration (Cookie + alter localStorage-Key).
				'legacyCookie'     => 'my_favorite_posts',
				'legacyStorageKey' => 'my_favorite_posts',
			)
		);
	}
}
