<?php
/**
 * Content_Inserter — fügt Newsletter-Formular + App-Promotion in den_content ein.
 *
 * Späte the_content-Priorität, splittet den Inhalt nach `</p>` und schiebt die Elemente an
 * konfigurierbaren Absatz-Positionen ein. Post-type-agnostisch (ADR-4): nur auf singulären
 * Ansichten unterstützter Typen aktiv (depeur_food/newsletter/post_types).
 *
 * Portiert aus spotlight-subscribe.php:1004–1207, aber SAUBER: keine globalen Funktionen,
 * kein hartkodierter Post-Type-`switch`, klare Sichtbarkeits-Entscheidung
 * (global-Toggle ∧ Device ∧ Per-Post-Override).
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
 * Verdrahtet den the_content-Filter und baut den angereicherten Inhalt.
 *
 * @since 0.2.0
 */
final class Content_Inserter {

	/**
	 * The_content-Priorität. Bewusst spät (nach wpautop/Shortcodes), damit die `</p>`-Marken
	 * bereits stehen — wie im Legacy (Prio 99).
	 *
	 * @since 0.2.0
	 * @var int
	 */
	private const PRIORITY = 99;

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
	 * Verdrahtet den Filter (Standards-Bibel § 1.1: Hook-Wiring im Konstruktor).
	 *
	 * @since 0.2.0
	 *
	 * @param Flodesk   $flodesk   Newsletter-Formular-Renderer (Dependency-Injection).
	 * @param App_Promo $app_promo App-Promotion-Renderer (Dependency-Injection).
	 */
	public function __construct( Flodesk $flodesk, App_Promo $app_promo ) {
		$this->flodesk   = $flodesk;
		$this->app_promo = $app_promo;

		add_filter( 'the_content', array( $this, 'filter_content' ), self::PRIORITY );
	}

	/**
	 * The_content-Callback: reichert den Haupt-Inhalt unterstützter Post-Types an.
	 *
	 * @since 0.2.0
	 *
	 * @param string $content Roher the_content-Wert.
	 * @return string Angereicherter oder unveränderter Inhalt.
	 */
	public function filter_content( $content ): string {
		$content = (string) $content;

		// Nur im Haupt-Loop einer singulären Frontend-Ansicht – nie in Admin, Feeds,
		// Widgets oder Sekundär-Queries (verhindert Doppel-Einfügung).
		if ( is_admin() || ! is_singular() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $content;
		}

		if ( ! $this->is_supported_post_type( (string) get_post_type( $post_id ) ) ) {
			return $content;
		}

		$show_newsletter = $this->should_show_newsletter( (int) $post_id );
		$show_app_promo  = $this->should_show_app_promo( (int) $post_id );

		if ( ! $show_newsletter && ! $show_app_promo ) {
			return $content;
		}

		$content = $this->insert_elements( $content, (int) $post_id, $show_newsletter, $show_app_promo );

		/**
		 * Koordinations-Haken für weitere the_content-Einschübe (z. B. Favoriten, P4).
		 *
		 * Bewusst NACH dem Einfügen platziert, damit spätere Module den kombinierten Inhalt
		 * sehen und ihren eigenen Slot relativ dazu setzen können — der eigentliche Slot-
		 * Konflikt wird hier NICHT gelöst, nur koordinierbar gemacht.
		 *
		 * @since 0.2.0
		 *
		 * @param string $content Angereicherter Inhalt.
		 * @param int    $post_id Aktuelle Post-ID.
		 */
		return (string) apply_filters( 'depeur_food/newsletter/content', $content, $post_id );
	}

	/**
	 * Prüft, ob der Post-Type zu den unterstützten Typen gehört (filterbar).
	 *
	 * @since 0.2.0
	 *
	 * @param string $post_type Aktueller Post-Type.
	 * @return bool
	 */
	private function is_supported_post_type( string $post_type ): bool {
		/** This filter is documented in Fields/Overrides.php */
		$supported = apply_filters( 'depeur_food/newsletter/post_types', depeur_food()->get_supported_post_types() );

		return in_array( $post_type, (array) $supported, true );
	}

	/**
	 * Sichtbarkeits-Entscheidung Newsletter: global-Toggle ∧ Device ∧ Per-Post-Override.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post-ID.
	 * @return bool
	 */
	private function should_show_newsletter( int $post_id ): bool {
		if ( ! Config::flag( 'newsletter_enabled' ) ) {
			return false;
		}
		if ( ! $this->device_allows( 'newsletter' ) ) {
			return false;
		}

		return $this->resolve_visibility( $post_id, 'show_newsletter_form', 'newsletter' );
	}

	/**
	 * Sichtbarkeits-Entscheidung App-Promotion: global-Toggle ∧ Device ∧ Per-Post-Override.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post-ID.
	 * @return bool
	 */
	private function should_show_app_promo( int $post_id ): bool {
		if ( ! Config::flag( 'app_promo_enabled' ) ) {
			return false;
		}
		if ( ! $this->device_allows( 'app_promo' ) ) {
			return false;
		}

		return $this->resolve_visibility( $post_id, 'show_app_promo', 'app_promo' );
	}

	/**
	 * Löst die 3-Zustand-Sichtbarkeit auf: Per-Post-Wahl schlägt den Post-Type-Standard.
	 *
	 * Das Per-Post-Feld ist „Standard / Anzeigen / Ausblenden": „1" erzwingt Anzeigen, „0"
	 * erzwingt Ausblenden, „" (bzw. nicht gesetzt) folgt dem Post-Type-Standard (Einstellungen).
	 *
	 * @since 0.3.0
	 *
	 * @param int    $post_id  Post-ID.
	 * @param string $meta_key Per-Post-Meta-Key (show_newsletter_form|show_app_promo).
	 * @param string $element  'newsletter' oder 'app_promo'.
	 * @return bool
	 */
	private function resolve_visibility( int $post_id, string $meta_key, string $element ): bool {
		$choice = (string) get_post_meta( $post_id, $meta_key, true );

		if ( '1' === $choice ) {
			return true;
		}
		if ( '0' === $choice ) {
			return false;
		}

		// „Standard" (leer / nicht gesetzt) → Post-Type-Vorgabe aus den Einstellungen.
		return Config::type_default( (string) get_post_type( $post_id ), $element );
	}

	/**
	 * Device-Gate (Mobile vs. Desktop) je Element-Typ.
	 *
	 * @since 0.2.0
	 *
	 * @param string $element 'newsletter' oder 'app_promo'.
	 * @return bool
	 */
	private function device_allows( string $element ): bool {
		$key = wp_is_mobile() ? $element . '_show_on_mobile' : $element . '_show_on_desktop';

		return Config::flag( $key );
	}

	/**
	 * Fügt die aktivierten Elemente an ihren Absatz-Positionen in den Inhalt ein.
	 *
	 * Splittet nach `</p>`; bei zu wenigen Absätzen (< 2) werden die Elemente dem Inhalt
	 * vorangestellt (Legacy-Verhalten). Positionen sind 1-basiert (UI) → 0-basierter Index.
	 *
	 * @since 0.2.0
	 *
	 * @param string $content         Roher Inhalt.
	 * @param int    $post_id         Post-ID (für die Per-Post-Newsletter-Position).
	 * @param bool   $show_newsletter Newsletter einfügen.
	 * @param bool   $show_app_promo  App-Promotion einfügen.
	 * @return string
	 */
	private function insert_elements( string $content, int $post_id, bool $show_newsletter, bool $show_app_promo ): string {
		$parts = explode( '</p>', $content );
		$count = count( $parts );

		$mode = $this->newsletter_mode();

		// Zu wenige Absätze: Elemente vorne anfügen (App-Promotion zuerst, dann Newsletter).
		if ( $count < 2 ) {
			$prefix = '';
			if ( $show_app_promo ) {
				$prefix .= $this->app_promo->render();
			}
			if ( $show_newsletter ) {
				$prefix .= $this->flodesk->render( $mode );
			}

			return $prefix . $content;
		}

		if ( $show_app_promo ) {
			$index           = min( $this->app_promo_index(), $count - 1 );
			$parts[ $index ] = $this->app_promo->render() . $parts[ $index ];
		}

		if ( $show_newsletter ) {
			$index           = min( $this->newsletter_index( $post_id ), $count - 1 );
			$parts[ $index ] = $this->flodesk->render( $mode ) . $parts[ $index ];
		}

		return implode( '</p>', $parts );
	}

	/**
	 * Darstellungs-Variante des automatisch eingefügten Formulars (Config, validiert).
	 *
	 * @since 0.3.0
	 *
	 * @return string 'spotlight', 'minimal' oder 'popup'.
	 */
	private function newsletter_mode(): string {
		$mode = Config::text( 'newsletter_display_mode', 'spotlight' );

		return in_array( $mode, array( 'spotlight', 'minimal', 'popup' ), true ) ? $mode : 'spotlight';
	}

	/**
	 * Der 0-basierte Einfüge-Index des Newsletters: Per-Post-Wert, sonst globaler Default.
	 *
	 * Die Funktion metadata_exists unterscheidet einen echt gespeicherten Per-Post-Wert vom
	 * register_post_meta-Default — nur dann sticht der Post die globale Position.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post-ID.
	 * @return int Index ≥ 0.
	 */
	private function newsletter_index( int $post_id ): int {
		if ( metadata_exists( 'post', $post_id, 'newsletter_position' ) ) {
			$position = (int) get_post_meta( $post_id, 'newsletter_position', true );
		} else {
			$position = Config::number( 'newsletter_position', 4 );
		}

		return max( 0, $position - 1 );
	}

	/**
	 * Der 0-basierte Einfüge-Index der App-Promotion (nur global, kein Per-Post-Override).
	 *
	 * @since 0.2.0
	 *
	 * @return int Index ≥ 0.
	 */
	private function app_promo_index(): int {
		return max( 0, Config::number( 'app_promo_position', 1 ) - 1 );
	}
}
