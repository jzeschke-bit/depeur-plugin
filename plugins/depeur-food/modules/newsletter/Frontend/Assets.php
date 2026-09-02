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

use Depeur\Food\Modules\Newsletter\Support\Config;

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

		$base_url    = DEPEUR_FOOD_URL . 'modules/newsletter/assets/';
		$base_path   = DEPEUR_FOOD_PATH . 'modules/newsletter/assets/';
		$loader_file = $base_path . 'flodesk-loader.js';
		// filemtime als Version → Browser lädt neu, sobald sich die Datei ändert.
		$loader_version = is_file( $loader_file ) ? (string) filemtime( $loader_file ) : DEPEUR_FOOD_VERSION;

		$mode = $this->display_mode();

		// NATIVE Modi (flodesk_inline/flodesk_popup): Flodesk rendert das Formular selbst. Nur den
		// Universal-Loader laden + Flodesks eigenen Init-Aufruf anhängen (offizielles Embed-Muster).
		// Kein eigenes CSS/JS → kein Captcha, Design/Anti-Bot komplett aus Flodesk.
		if ( 'flodesk_inline' === $mode || 'flodesk_popup' === $mode ) {
			if ( ! Config::flag( 'newsletter_enabled' ) ) {
				return;
			}
			$form_id = Config::text( 'flodesk_form_id' );
			if ( '' === $form_id ) {
				return;
			}

			wp_enqueue_script( self::LOADER_HANDLE, $base_url . 'flodesk-loader.js', array(), $loader_version, true );

			// 'flodesk_inline' rendert in den vom Content_Inserter gesetzten Container;
			// 'flodesk_popup' lässt Flodesk sein eigenes Popup zeigen (ohne containerEl).
			if ( 'flodesk_inline' === $mode ) {
				$init = sprintf(
					"window.fd('form', { formId: %s, containerEl: %s });",
					wp_json_encode( $form_id ),
					wp_json_encode( '#fd-form-' . $form_id )
				);
			} else {
				$init = sprintf( "window.fd('form', { formId: %s });", wp_json_encode( $form_id ) );
			}
			wp_add_inline_script( self::LOADER_HANDLE, $init, 'after' );

			return;
		}

		// EIGENES-DESIGN-Modi (spotlight/minimal/popup): unser Markup + CSS + Verhalten.
		$css_file = $base_path . 'df-newsletter.css';
		$js_file  = $base_path . 'df-newsletter.js';

		$css_version = is_file( $css_file ) ? (string) filemtime( $css_file ) : DEPEUR_FOOD_VERSION;
		$js_version  = is_file( $js_file ) ? (string) filemtime( $js_file ) : DEPEUR_FOOD_VERSION;

		wp_enqueue_style( self::HANDLE, $base_url . 'df-newsletter.css', array(), $css_version );

		// Flodesk-Universal-Loader ZUERST: legt window.fd an (Submit-Interception + Anti-Bot-
		// Token). Ohne ihn verlangt Flodesk beim Absenden ein Captcha (siehe flodesk-loader.js).
		wp_enqueue_script( self::LOADER_HANDLE, $base_url . 'flodesk-loader.js', array(), $loader_version, true );

		// Unser Verhalten hängt vom Loader ab (window.fd muss vor form:handle existieren).
		wp_enqueue_script( self::HANDLE, $base_url . 'df-newsletter.js', array( self::LOADER_HANDLE ), $js_version, true );
	}

	/**
	 * Aktueller Darstellungs-Modus (Config, validiert; Default 'spotlight').
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private function display_mode(): string {
		$mode = Config::text( 'newsletter_display_mode', 'spotlight' );

		$allowed = array( 'spotlight', 'minimal', 'popup', 'flodesk_inline', 'flodesk_popup' );

		return in_array( $mode, $allowed, true ) ? $mode : 'spotlight';
	}
}
