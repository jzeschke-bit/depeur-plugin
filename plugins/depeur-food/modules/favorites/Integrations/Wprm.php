<?php
/**
 * Wprm — WP Recipe Maker-Integration (Soft-Dependency, ADR/E2).
 *
 * Fügt den Favoriten-Button in den WPRM-Rezeptbild-Container ein – aber NUR, wenn WPRM
 * aktiv ist und der Site-Owner die Integration im Settings-Tab nicht abgeschaltet hat.
 * Ohne WPRM verdrahtet sich die Klasse gar nicht (graceful skip); die Favoriten
 * funktionieren unabhängig davon auf allen unterstützten Post-Types.
 *
 * Bewusste Vereinfachung ggü. dem Legacy: der Button zielt auf den aktuell angezeigten
 * Beitrag (get_the_ID()), nicht auf ein per Roundup-Meta aufgelöstes Eltern-Rezept. Die
 * Legacy-Auflösung (meta_query über _wprm_recipe_roundup_recipes) war fragil und nur im
 * Roundup-Sonderfall relevant; der Favorit gehört ohnehin zum Beitrag, nicht zum Rezept.
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
	 * @since 0.2.0
	 *
	 * @param string     $image_container HTML des WPRM-Bild-Containers.
	 * @param int|string $recipe_id       Rezept-ID (Fallback-Ziel, wenn kein Loop-Post da ist).
	 * @return string Angereichertes HTML.
	 */
	public function inject_button( $image_container, $recipe_id ): string {
		$image_container = (string) $image_container;

		$post_id = absint( get_the_ID() );
		if ( $post_id < 1 ) {
			$post_id = absint( $recipe_id );
		}

		if ( $post_id < 1 || ! in_array( get_post_type( $post_id ), Like_Counter::post_types(), true ) ) {
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
}
