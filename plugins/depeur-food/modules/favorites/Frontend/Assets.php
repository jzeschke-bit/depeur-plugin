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

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\Favorites\Meta\Like_Counter;
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
	 * Modul-Slug (Options-Kontext für die Grid-Herzen-Einstellung).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Verdrahtet den Enqueue-Hook.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername), für den Options-Zugriff.
	 */
	public function __construct( string $slug = 'favorites' ) {
		$this->slug = $slug;

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
				// Automatische Herz-Injektion auf Kadence-Blocks-Karten (Post Grid/Carousel).
				'gridHearts'       => $this->grid_hearts_enabled(),
				// Nur diese Post-Types bekommen ein Herz (Kern-Klasse type-<pt> auf der Karte).
				'postTypes'        => array_values( Like_Counter::post_types() ),
				// Selektor-Override; leer ⇒ das JS nutzt seine Kadence-Standard-Selektoren.
				'gridSelectors'    => $this->grid_selectors(),
				// Generisches aria-label für die per-JS gebauten Herzen (kein Titel-Kontext im JS).
				'buttonLabel'      => esc_attr__( 'Zu Favoriten hinzufügen oder entfernen', 'depeur-food' ),
			)
		);
	}

	/**
	 * Liest die Einstellung „Herzen auf Kadence-Karten injizieren" (Default: aktiv).
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	private function grid_hearts_enabled(): bool {
		$option = get_option( SettingsRegistry::option_key( $this->slug ), array() );
		if ( ! is_array( $option ) ) {
			return true;
		}

		// Unset ⇒ Default aktiv; explizit gespeicherter Wert entscheidet.
		return ! array_key_exists( 'grid_hearts', $option ) || ! empty( $option['grid_hearts'] );
	}

	/**
	 * Liefert einen optionalen CSS-Selektor-Override für die Grid-Herzen.
	 *
	 * Standard leer → das JS nutzt seine eigenen, auf Kadence-Blocks abgestimmten
	 * Selektoren. Über den Filter kann eine Site abweichende/zusätzliche Container
	 * ansteuern, ohne den Code zu ändern.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private function grid_selectors(): string {
		/**
		 * Filtert die CSS-Selektoren für die automatische Herz-Injektion auf Karten.
		 *
		 * @since 0.3.0
		 *
		 * @param string $selectors Leerer String ⇒ JS-Standardselektoren.
		 */
		return (string) apply_filters( 'depeur_food/favorites/grid_selectors', '' );
	}
}
