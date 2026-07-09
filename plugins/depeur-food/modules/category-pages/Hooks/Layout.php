<?php
/**
 * Layout — schaltet Sidebar + Beitragsbild auf den Folgeseiten (Seite 2+) einer Kategorie-Seite ab.
 *
 * Auf Seite 1 bleibt das normale Seiten-Layout (mit Sidebar); ab Seite 2 wird das
 * Kadence-Layout per Request auf „normal" gezwungen — das ist Kadence-„NORMAL": KEINE
 * Sidebar, aber die normale, geboxte Content-Breite (NICHT „fullwidth"/edge-to-edge).
 * Umgesetzt über den Read-Filter des Kadence-Post-Meta `_kad_post_layout` (nicht persistiert,
 * nur für diesen Request). Greift nur für das geflaggte, aktuell abgefragte Page-Objekt.
 *
 * Hinweis: Der genaue Kadence-Hebel ist versionsabhängig; falls die Sidebar auf Seite 2
 * bestehen bleibt, ist hier der einzige anzupassende Ort (Meta-Key / Filtername).
 *
 * @package Depeur\Food\Modules\CategoryPages\Hooks
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Hooks;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erzwingt Fullwidth (keine Sidebar) auf Kategorie-Folgeseiten.
 *
 * @since 0.3.0
 */
final class Layout {

	/**
	 * Kadence-Post-Meta, das das Seiten-Layout bestimmt.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const KADENCE_LAYOUT_META = '_kad_post_layout';

	/**
	 * Ziel-Layout ab Seite 2: Kadence-„NORMAL" = kein Sidebar, normale Content-Breite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const TARGET_LAYOUT = 'normal';

	/**
	 * Kadence-Post-Meta „Beitragsbild anzeigen".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const KADENCE_FEATURE_META = '_kad_post_feature';

	/**
	 * Verdrahtet die Layout-Umschaltung nach dem Query-Parsing.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_disable_sidebar' ) );
	}

	/**
	 * Schaltet ab Seite 2 einer geflaggten Kategorie-Seite auf Fullwidth.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function maybe_disable_sidebar(): void {
		if ( is_admin() || ! is_singular( 'page' ) || ! is_main_query() ) {
			return;
		}
		if ( max( 1, (int) get_query_var( 'paged' ) ) < 2 ) {
			return;
		}
		$page_id = (int) get_queried_object_id();
		if ( $page_id < 1 || ! get_post_meta( $page_id, 'df_catpage_enabled', true ) ) {
			return;
		}

		add_filter( 'get_post_metadata', array( $this, 'force_layout' ), 10, 3 );
	}

	/**
	 * Liefert für das abgefragte Page-Objekt das Kadence-Layout `normal` (Read-Filter).
	 *
	 * @since 0.3.0
	 *
	 * @param mixed  $value     Bisheriger (Kurzschluss-)Wert.
	 * @param int    $object_id Objekt-ID des Meta-Reads.
	 * @param string $meta_key  Meta-Key.
	 * @return mixed Layout-Array für den Layout-Key des abgefragten Objekts, sonst unverändert.
	 */
	public function force_layout( $value, $object_id, $meta_key ) {
		if ( (int) $object_id !== (int) get_queried_object_id() ) {
			return $value;
		}

		// Array zurückgeben: der Core-Kurzschluss liefert bei single=true selbst $check[0].
		if ( self::KADENCE_LAYOUT_META === $meta_key ) {
			return array( self::TARGET_LAYOUT );
		}
		// Beitragsbild (Hero) ab Seite 2 ausblenden (Kadence-Wert „hide").
		if ( self::KADENCE_FEATURE_META === $meta_key ) {
			return array( 'hide' );
		}

		return $value;
	}
}
