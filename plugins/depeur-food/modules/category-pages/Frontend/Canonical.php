<?php
/**
 * Canonical — selbst-referenzierende Canonical für paginierte Kategorie-Seiten.
 *
 * SEO-Fix, portiert aus dem Alt-`rank-math.php` („Fix Canonical URL für paginierte
 * Rezeptkategorie-Seiten"): Kategorie-Seiten sind technisch eine einzelne `page`, deren
 * Folgeseiten über `/page/N/` laufen. Rank Math kanonisiert eine `page` standardmäßig auf
 * ihren Permalink OHNE `/page/N/` — d. h. Seite 2+ zeigte auf Seite 1 und Google wertete die
 * dortigen (anderen!) Beiträge als Duplikat. Hier setzen wir ab Seite 2 eine
 * selbst-referenzierende Canonical auf die tatsächliche `…/page/N/`-URL.
 *
 * Sauberer als das Legacy: die URL wird aus `get_permalink()` gebaut (nicht aus rohem
 * `$_SERVER['HTTP_HOST']`/`REQUEST_URI`). Greift nur für geflaggte Kategorie-Seiten
 * (`df_catpage_enabled`) im Haupt-Query; Seite 1 behält die Standard-Canonical.
 *
 * @package Depeur\Food\Modules\CategoryPages\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Frontend;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filtert die Rank-Math-Canonical für paginierte Kategorie-Seiten.
 *
 * @since 0.3.0
 */
final class Canonical {

	/**
	 * Verdrahtet den Canonical-Filter.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_filter( 'rank_math/frontend/canonical', array( $this, 'filter_canonical' ), 10, 1 );
	}

	/**
	 * Setzt ab Seite 2 die selbst-referenzierende `…/page/N/`-Canonical.
	 *
	 * @since 0.3.0
	 *
	 * @param string $canonical Bisherige Canonical-URL (Rank Math).
	 * @return string
	 */
	public function filter_canonical( $canonical ): string {
		if ( ! is_singular( 'page' ) || ! is_main_query() ) {
			return (string) $canonical;
		}

		$post_id = (int) get_queried_object_id();
		// Nur geflaggte Kategorie-Seiten (sonst Standard-Canonical unangetastet lassen).
		if ( $post_id < 1 || ! get_post_meta( $post_id, 'df_catpage_enabled', true ) ) {
			return (string) $canonical;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged < 2 ) {
			// Seite 1: der Standard-Permalink IST die korrekte Canonical.
			return (string) $canonical;
		}

		return trailingslashit( (string) get_permalink( $post_id ) ) . 'page/' . $paged . '/';
	}
}
