<?php
/**
 * Shortcodes — manuelle Einbettung von Newsletter-Formular und App-Promotion.
 *
 * Registriert die df_-präfixierten Shortcodes [df_newsletter] und [df_app_promo] (Konventions-
 * Prefix df_). Delegiert das Rendern an dieselben Provider wie der Content_Inserter, respektiert
 * aber die globalen Aktivierungs-Toggles. Ausgabe ist bereits im Provider vollständig escaped.
 *
 * Ersetzt spotlight-subscribe.php:1212–1252 ([spotlight_newsletter]/[spotlight_app_promo]/[spotlight_both]).
 *
 * @package Depeur\Food\Modules\Newsletter\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Frontend;

use Depeur\Food\Modules\Newsletter\Providers\Flodesk;
use Depeur\Food\Modules\Newsletter\Support\Config;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verdrahtet die Modul-Shortcodes.
 *
 * @since 0.2.0
 */
final class Shortcodes {

	/**
	 * Newsletter-Formular-Renderer.
	 *
	 * @since 0.2.0
	 * @var Flodesk
	 */
	private Flodesk $flodesk;

	/**
	 * App-Promotion-Renderer.
	 *
	 * @since 0.2.0
	 * @var App_Promo
	 */
	private App_Promo $app_promo;

	/**
	 * Registriert die Shortcodes (Standards-Bibel § 1.1: Wiring im Konstruktor).
	 *
	 * @since 0.2.0
	 *
	 * @param Flodesk   $flodesk   Newsletter-Formular-Renderer (Dependency-Injection).
	 * @param App_Promo $app_promo App-Promotion-Renderer (Dependency-Injection).
	 */
	public function __construct( Flodesk $flodesk, App_Promo $app_promo ) {
		$this->flodesk   = $flodesk;
		$this->app_promo = $app_promo;

		// df_ ist der projektweite Frontend-Prefix (CLAUDE.md › Asset-Convention), bewusst
		// statt depeur_food_. Deshalb hier der gezielte Sniff-Ausschluss (kein globaler Prefix).
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- df_ ist der dokumentierte Frontend-Shortcode-Prefix.
		add_shortcode( 'df_newsletter', array( $this, 'render_newsletter' ) );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- df_ ist der dokumentierte Frontend-Shortcode-Prefix.
		add_shortcode( 'df_app_promo', array( $this, 'render_app_promo' ) );
	}

	/**
	 * [df_newsletter] — gibt das Flodesk-Formular aus, wenn Newsletter global aktiv ist.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,mixed>|string $atts Shortcode-Attribute (aktuell ungenutzt).
	 * @return string Formular-Markup (vom Provider escaped) oder leerer String.
	 */
	public function render_newsletter( $atts = array() ): string {
		unset( $atts );

		if ( ! Config::flag( 'newsletter_enabled' ) ) {
			return '';
		}

		return $this->flodesk->render();
	}

	/**
	 * [df_app_promo] — gibt den App-Promotion-Block aus, wenn App-Promo global aktiv ist.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string,mixed>|string $atts Shortcode-Attribute (aktuell ungenutzt).
	 * @return string Block-Markup (vom Provider escaped) oder leerer String.
	 */
	public function render_app_promo( $atts = array() ): string {
		unset( $atts );

		if ( ! Config::flag( 'app_promo_enabled' ) ) {
			return '';
		}

		return $this->app_promo->render();
	}
}
