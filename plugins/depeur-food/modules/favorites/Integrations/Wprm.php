<?php
/**
 * Wprm — WP Recipe Maker-Integration (Soft-Dependency, ADR/E2).
 *
 * Fügt den Favoriten-Button in den WPRM-Rezeptbild-Container ein – aber NUR, wenn WPRM
 * aktiv ist und der Site-Owner die Integration im Settings-Tab nicht abgeschaltet hat.
 * Ohne WPRM verdrahtet sich die Klasse gar nicht (graceful skip); die Favoriten
 * funktionieren unabhängig davon auf allen unterstützten Post-Types.
 *
 * Zielauflösung = Eltern-Beitrag des Rezepts (KRITISCH beim Roundup). Der Filter
 * `wprm_recipe_image_container` feuert sowohl beim normalen Rezept-Block als auch je
 * Roundup-Item – jeweils mit DER Rezept-ID des Items. Im Roundup läuft der Loop aber
 * weiterhin über den Roundup-Beitrag, d. h. get_the_ID() liefert dort für JEDES Item
 * denselben (falschen) Roundup-Beitrag. Würden wir get_the_ID() liken, bekäme der
 * Roundup-Beitrag alle Likes statt der einzelnen verlinkten Beiträge. Deshalb lösen wir
 * aus der Rezept-ID den Ursprungs-Beitrag auf (`wprm_parent_post_id`-Meta, das WPRM auf
 * jedem Rezept-CPT ablegt) – beim Einzel-Rezept ist das der aktuelle Beitrag, im Roundup
 * genau der jeweils verlinkte Beitrag. get_the_ID() bleibt nur der Fallback, wenn das
 * Rezept keinen auflösbaren Eltern-Beitrag hat (z. B. externes Rezept).
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

		// WPRM reicht ($image_container, $recipe_id) herein – Legacy nutzte denselben Filter.
		add_filter( 'wprm_recipe_image_container', array( $this, 'inject_button' ), 10, 2 );
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
	 * Fügt den Favoriten-Button nach dem ersten schließenden </div> des Bild-Containers ein.
	 *
	 * Das Ziel ist der aufgelöste Eltern-Beitrag des Rezepts (Roundup-tauglich), nicht der
	 * aktuelle Loop-Post – siehe Klassen-Docblock.
	 *
	 * @since 0.2.0
	 *
	 * @param string     $image_container HTML des WPRM-Bild-Containers.
	 * @param int|string $recipe_id       Rezept-ID des Items (Quelle für den Eltern-Beitrag).
	 * @return string Angereichertes HTML.
	 */
	public function inject_button( $image_container, $recipe_id ): string {
		$image_container = (string) $image_container;

		$post_id = $this->resolve_target_post( $recipe_id );
		if ( $post_id < 1 ) {
			return $image_container;
		}

		$button = Shortcodes::button_markup( $post_id, 'thumbnail', false );
		if ( '' === $button ) {
			return $image_container;
		}

		// Nach dem ERSTEN </div> einsetzen (Legacy nutzte str_replace über alle – hier
		// bewusst nur das erste Vorkommen, um nicht mehrere Buttons zu erzeugen).
		$pos = strpos( $image_container, '</div>' );
		if ( false !== $pos ) {
			return substr_replace( $image_container, $button . '</div>', $pos, strlen( '</div>' ) );
		}

		return $image_container . $button;
	}

	/**
	 * Löst aus einer WPRM-Rezept-ID den zu likenden Beitrag auf (Eltern-Beitrag zuerst).
	 *
	 * Reihenfolge:
	 *   1. `wprm_parent_post_id`-Meta des Rezept-CPT – der Beitrag, in dem das Rezept
	 *      angelegt wurde. Im Roundup ist das je Item der verlinkte Beitrag (der kritische
	 *      Fall), beim Einzel-Rezept der aktuelle Beitrag.
	 *   2. WPRM-API (`WPRM_Recipe::get_parent_post_id()`), falls die Meta (noch) fehlt.
	 *   3. Fallback get_the_ID() – NUR für den Einzel-Rezept-Fall ohne auflösbaren Eltern-
	 *      Beitrag (externes Rezept). Im Roundup greift dieser Fallback praktisch nicht,
	 *      weil reale Rezepte immer einen Eltern-Beitrag tragen.
	 *
	 * @since 0.3.0
	 *
	 * @param int|string $recipe_id WPRM-Rezept-ID.
	 * @return int Beitrags-ID (> 0) oder 0, wenn nichts Likebares auflösbar ist.
	 */
	private function resolve_target_post( $recipe_id ): int {
		$recipe_id = absint( $recipe_id );
		$parent    = 0;

		if ( $recipe_id > 0 ) {
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
		}

		if ( $this->is_favoritable( $parent ) ) {
			return $parent;
		}

		// Einzel-Rezept ohne Eltern-Meta: der aktuelle Loop-Post. Bewusst NACH der
		// Eltern-Auflösung, damit der Roundup-Beitrag nie fälschlich das Ziel wird.
		$current = absint( get_the_ID() );
		if ( $this->is_favoritable( $current ) ) {
			return $current;
		}

		return 0;
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
