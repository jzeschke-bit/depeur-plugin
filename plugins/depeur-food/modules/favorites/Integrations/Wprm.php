<?php
/**
 * Wprm — WP Recipe Maker-Integration (Soft-Dependency, ADR/E2).
 *
 * Fügt den Favoriten-Button in den WPRM-Rezeptbild-Container ein – aber NUR, wenn WPRM
 * aktiv ist und der Site-Owner die Integration im Settings-Tab nicht abgeschaltet hat.
 * Ohne WPRM verdrahtet sich die Klasse gar nicht (graceful skip); die Favoriten
 * funktionieren unabhängig davon auf allen unterstützten Post-Types.
 *
 * Zielauflösung = Eltern-Beitrag des Rezepts (KRITISCH beim Roundup). Injiziert wird am
 * the_content (nach do_blocks), wo der WPRM-Rezept-Block sein Bild rendert – das moderne
 * Block-Template ruft den früher genutzten Filter `wprm_recipe_image_container` NICHT auf.
 * Jeder Rezept-Container trägt seine Rezept-ID in der Klasse (Einzel: `wprm-recipe-<ID>`,
 * Roundup-Item: `wprm-recipe-roundup-item-<ID>`); daraus lösen wir über die stabile
 * `wprm_parent_post_id`-Meta den Ursprungs-Beitrag auf. Im Roundup läuft der Loop weiter
 * über den Roundup-Beitrag – get_the_ID() wäre also für JEDES Item derselbe (falsche)
 * Beitrag. Darum liken wir nie get_the_ID() im Roundup; beim Einzel-Rezept ist der
 * Eltern-Beitrag ohnehin der aktuelle, und get_the_ID() dient nur dort als Fallback.
 *
 * @package Depeur\Food\Modules\Favorites\Integrations
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Integrations;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\Favorites\Frontend\Shortcodes;
use Depeur\Food\Modules\Favorites\Meta\Like_Counter;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verdrahtet die WPRM-Button-Injektion, sofern WPRM aktiv und aktiviert.
 *
 * @since 0.2.0
 */
final class Wprm {

	/**
	 * Modul-Slug (für den Options-Zugriff), von module.php hereingereicht.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Prüft die Soft-Dependency + Einstellung und hängt sich ggf. in den WPRM-Filter.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		// Soft-Dependency: ohne WPRM passiert nichts.
		if ( ! self::wprm_active() ) {
			return;
		}

		// Opt-out über den Settings-Tab (Default: aktiv).
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Ein einziger Weg für ALLE WPRM-Fälle: Injektion am Post-Content (nach do_blocks).
		// Der WPRM-Rezept-Block (Einzel wie Roundup) steht im the_content und rendert das Bild
		// als <div class="wprm-recipe-image …">. Wir gehen jeden Rezept-Container durch – Einzel
		// (Klasse wprm-recipe-<ID>) UND Roundup-Item (wprm-recipe-roundup-item-<ID>) – lesen die
		// Rezept-ID aus der Container-Klasse und setzen das Herz auf den ELTERN-Beitrag dieses
		// Rezepts. Im Roundup ist das je Item der verlinkte Beitrag (der kritische Fall), beim
		// Einzel-Rezept der aktuelle Beitrag. Kein wprm_recipe_image_container-Filter mehr:
		// das moderne Block-Template ruft ihn ohnehin nicht auf, und ein einziger Hook schließt
		// jede Doppel-Injektion aus.
		add_filter( 'the_content', array( $this, 'inject_into_content' ), 20 );
	}

	/**
	 * Ist WPRM aktiv? (Soft-Dependency-Check.)
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function wprm_active(): bool {
		// Kern-Klasse von WP Recipe Maker. ANNAHME (zu verifizieren auf der Live-Site):
		// WPRM_Recipe_Manager existiert in aktuellen WPRM-Versionen (frei + Premium).
		return class_exists( 'WPRM_Recipe_Manager' );
	}

	/**
	 * Liest die Modul-Einstellung „WPRM-Button einfügen" (Default: true).
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		$option = get_option( SettingsRegistry::option_key( $this->slug ), array() );
		if ( ! is_array( $option ) ) {
			return true;
		}

		// Unset ⇒ Default aktiv; explizit gespeicherter Wert entscheidet.
		return ! array_key_exists( 'wprm_button', $option ) || ! empty( $option['wprm_button'] );
	}

	/**
	 * Injiziert das Herz in jedes WPRM-Rezeptbild des Post-Contents (Einzel + Roundup).
	 *
	 * Der WPRM-Rezept-Block rendert je Rezept einen Container mit einer ID-tragenden Klasse:
	 *   - Einzel-Rezept: <div class="wprm-recipe wprm-recipe-<ID> …">
	 *   - Roundup-Item:  <div class="wprm-recipe wprm-recipe-roundup-item wprm-recipe-roundup-item-<ID> …">
	 * darin das Bild <div class="wprm-recipe-image …">. Wir gehen jeden Container durch, lesen
	 * seine Rezept-ID und setzen das Herz auf den ELTERN-Beitrag dieses Rezepts – im Roundup
	 * also je Item der richtige verlinkte Beitrag (nicht der Roundup-Beitrag).
	 *
	 * Enge Gates: nur Haupt-Loop einer singulären Ansicht (verhindert Doppel-Läufe und
	 * Sekundär-Queries). Kein Post-Type-Gate hier, weil das Ziel je Rezept aufgelöst wird
	 * (der aktuelle Beitrag ist nur der Einzel-Rezept-Fallback und wird selbst geprüft).
	 *
	 * @since 0.3.0
	 *
	 * @param string $content Post-Content.
	 * @return string
	 */
	public function inject_into_content( $content ): string {
		$content = (string) $content;

		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// Kein Rezeptbild im Inhalt → nichts zu tun (spart die Regex).
		if ( false === strpos( $content, 'wprm-recipe-image' ) ) {
			return $content;
		}

		// Jeden Rezept-Container (Container-Öffnung … erstes Rezeptbild) einzeln behandeln.
		// Der Container trägt den Klassen-Token „wprm-recipe" (als eigenständige Klasse, nicht
		// als Präfix von wprm-recipe-image) – das matcht den Haupt-Rezept-Container der
		// Einzelseite (wprm-recipe wprm-recipe-template-…) UND jedes Roundup-Item
		// (wprm-recipe wprm-recipe-roundup-item-<ID> …). Die Ziel-Auflösung passiert im Callback.
		return (string) preg_replace_callback(
			'/(<div[^>]*class="(?:[^"]*\s)?wprm-recipe[\s"][^"]*"[^>]*>)(.*?)(<div[^>]*wprm-recipe-image\b[^>]*>)/s',
			array( $this, 'inject_recipe_image_match' ),
			$content
		);
	}

	/**
	 * Callback für preg_replace_callback: setzt ein Herz hinter das Rezeptbild-Öffnungs-Tag.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, string> $parts [0]=Gesamt, [1]=Container-Öffnung, [2]=dazwischen, [3]=Bild-Öffnung.
	 * @return string
	 */
	public function inject_recipe_image_match( array $parts ): string {
		// Doppel-Schutz: dieses Rezept trägt schon ein Herz (z. B. aus einem früheren Lauf).
		if ( false !== strpos( $parts[0], 'df-favorite-button' ) ) {
			return $parts[0];
		}

		if ( preg_match( '/wprm-recipe-roundup-item-(\d+)/', $parts[1], $id_match ) ) {
			// Roundup-Item: Ziel = Eltern-Beitrag DIESES Rezepts (nicht der Roundup-Beitrag).
			// Kein Fallback auf get_the_ID(), sonst bekäme der Roundup-Beitrag die Likes.
			$post_id = $this->resolve_recipe_parent( absint( $id_match[1] ) );
		} else {
			// Haupt-/Einzel-Rezept: Ziel = aktueller Beitrag (dessen eigenes Rezept).
			$current = absint( get_the_ID() );
			$post_id = $this->is_favoritable( $current ) ? $current : 0;
		}

		if ( $post_id < 1 ) {
			return $parts[0];
		}

		$button = Shortcodes::button_markup( $post_id, 'thumbnail', false );
		if ( '' === $button ) {
			return $parts[0];
		}

		// Button unmittelbar hinter das öffnende <div> des Rezeptbilds setzen.
		return $parts[1] . $parts[2] . $parts[3] . $button;
	}

	/**
	 * Löst den Eltern-Beitrag einer WPRM-Rezept-ID auf (ohne aktuellen-Beitrag-Fallback).
	 *
	 * Reihenfolge: stabile `wprm_parent_post_id`-Meta des Rezept-CPT → WPRM-API
	 * (`WPRM_Recipe::get_parent_post_id()`). Nur ein likebarer (veröffentlichter, unterstützter)
	 * Beitrag wird zurückgegeben, sonst 0 – so kann der Roundup NIE auf den Roundup-Beitrag
	 * zurückfallen.
	 *
	 * @since 0.3.0
	 *
	 * @param int|string $recipe_id WPRM-Rezept-ID.
	 * @return int Eltern-Beitrags-ID (> 0) oder 0.
	 */
	private function resolve_recipe_parent( $recipe_id ): int {
		$recipe_id = absint( $recipe_id );
		if ( $recipe_id < 1 ) {
			return 0;
		}

		// Stabile Meta, die WPRM auf jedem Rezept-CPT ablegt (versions-robust,
		// unabhängig von der WPRM-Klassen-API).
		$parent = absint( get_post_meta( $recipe_id, 'wprm_parent_post_id', true ) );

		// Fallback über die WPRM-API, falls die Meta nicht gesetzt ist.
		if ( $parent < 1 && class_exists( 'WPRM_Recipe_Manager' ) ) {
			$recipe = \WPRM_Recipe_Manager::get_recipe( $recipe_id );
			if ( is_object( $recipe ) && method_exists( $recipe, 'get_parent_post_id' ) ) {
				$parent = absint( $recipe->get_parent_post_id() );
			}
		}

		return $this->is_favoritable( $parent ) ? $parent : 0;
	}

	/**
	 * Prüft, ob ein Beitrag likebar ist (existiert, veröffentlicht, unterstützter Typ).
	 *
	 * @since 0.3.0
	 *
	 * @param int $post_id Beitrags-ID.
	 * @return bool
	 */
	private function is_favoritable( int $post_id ): bool {
		if ( $post_id < 1 ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
			return false;
		}

		return in_array( $post->post_type, Like_Counter::post_types(), true );
	}
}
