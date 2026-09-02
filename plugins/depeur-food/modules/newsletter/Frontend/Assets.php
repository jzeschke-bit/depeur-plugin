<?php
/**
 * Assets — lädt die Frontend-Assets des Newsletter-Moduls.
 *
 * Vanilla-only, kein jQuery, kein Build-Step (Asset-Convention, CLAUDE.md). Nur auf
 * singulären Ansichten unterstützter Post-Types, wo der Content_Inserter greifen kann —
 * so kein Bloat auf Archiven/Startseite. Cache-Busting via filemtime.
 *
 * @package Depeur\Food\Modules\Newsletter\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Frontend;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verdrahtet das bedingte Enqueue der Modul-Assets.
 *
 * @since 0.2.0
 */
final class Assets {

	/**
	 * Handle für Style + Script (df_-Prefix, Konvention).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const HANDLE = 'df-newsletter';

	/**
	 * Handle des Flodesk-Universal-Loaders (Abhängigkeit von HANDLE).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const LOADER_HANDLE = 'df-flodesk-loader';

	/**
	 * Verdrahtet den Enqueue-Hook.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Registriert Style + Script, wenn die aktuelle Ansicht ein unterstützter Single ist.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! is_singular() ) {
			return;
		}

		/** This filter is documented in Fields/Overrides.php */
		$supported = apply_filters( 'depeur_food/newsletter/post_types', depeur_food()->get_supported_post_types() );
		if ( ! in_array( (string) get_post_type(), (array) $supported, true ) ) {
			return;
		}

		$base_url  = DEPEUR_FOOD_URL . 'modules/newsletter/assets/';
		$base_path = DEPEUR_FOOD_PATH . 'modules/newsletter/assets/';

		$css_file    = $base_path . 'df-newsletter.css';
		$js_file     = $base_path . 'df-newsletter.js';
		$loader_file = $base_path . 'flodesk-loader.js';

		// filemtime als Version → Browser lädt neu, sobald sich die Datei ändert.
		$css_version    = is_file( $css_file ) ? (string) filemtime( $css_file ) : DEPEUR_FOOD_VERSION;
		$js_version     = is_file( $js_file ) ? (string) filemtime( $js_file ) : DEPEUR_FOOD_VERSION;
		$loader_version = is_file( $loader_file ) ? (string) filemtime( $loader_file ) : DEPEUR_FOOD_VERSION;

		wp_enqueue_style( self::HANDLE, $base_url . 'df-newsletter.css', array(), $css_version );

		// Flodesk-Universal-Loader ZUERST: legt window.fd an (Submit-Interception + Anti-Bot-
		// Token). Ohne ihn verlangt Flodesk beim Absenden ein Captcha (siehe flodesk-loader.js).
		wp_enqueue_script( self::LOADER_HANDLE, $base_url . 'flodesk-loader.js', array(), $loader_version, true );

		// Unser Verhalten hängt vom Loader ab (window.fd muss vor form:handle existieren).
		wp_enqueue_script( self::HANDLE, $base_url . 'df-newsletter.js', array( self::LOADER_HANDLE ), $js_version, true );
	}
}
