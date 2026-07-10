<?php
/**
 * Title — wendet den Custom-Titel einer Kategorie-Seite auf H1 + SEO-Titel an.
 *
 * Ersetzt das Legacy-`rezeptkategorie_titel`-Verhalten: der im Panel gesetzte
 * `df_catpage_title` überschreibt den sichtbaren Seitentitel (H1) und – falls Rank Math
 * aktiv ist – den SEO-Titel. Auf Folgeseiten wird „ – Seite N" angehängt.
 *
 * Eng geführt: greift nur für das aktuell abgefragte, geflaggte Page-Objekt im Haupt-Query
 * (die Sekundär-Query der Beitragskarten hat andere IDs und bleibt unberührt).
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
 * Filtert Seitentitel (H1) und SEO-Titel für Kategorie-Seiten.
 *
 * @since 0.3.0
 */
final class Title {

	/**
	 * Verdrahtet die Titel-Filter.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_filter( 'the_title', array( $this, 'filter_the_title' ), 10, 2 );
		add_filter( 'rank_math/frontend/title', array( $this, 'filter_seo_title' ), 10, 1 );
	}

	/**
	 * Überschreibt den sichtbaren Titel (H1) des geflaggten Page-Objekts.
	 *
	 * @since 0.3.0
	 *
	 * @param string $title   Titel.
	 * @param int    $post_id Post-ID (the_title reicht sie herein).
	 * @return string
	 */
	public function filter_the_title( $title, $post_id = 0 ): string {
		$post_id = (int) $post_id;

		if ( is_admin() || $post_id < 1 ) {
			return (string) $title;
		}
		// Nur das aktuell abgefragte Page-Objekt im Haupt-Query (nicht Menüs/Sekundär-Queries).
		if ( ! is_singular( 'page' ) || ! is_main_query() || (int) get_queried_object_id() !== $post_id ) {
			return (string) $title;
		}

		$custom = $this->custom_title( $post_id );

		return ( '' !== $custom ) ? $custom : (string) $title;
	}

	/**
	 * Überschreibt den SEO-Titel (Rank Math) inkl. Seiten-Suffix ab Seite 2.
	 *
	 * @since 0.3.0
	 *
	 * @param string $seo_title Bisheriger SEO-Titel.
	 * @return string
	 */
	public function filter_seo_title( $seo_title ): string {
		if ( ! is_singular( 'page' ) || ! is_main_query() ) {
			return (string) $seo_title;
		}

		$post_id = (int) get_queried_object_id();
		$custom  = $this->custom_title( $post_id );
		if ( '' === $custom ) {
			return (string) $seo_title;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged > 1 ) {
			/* translators: 1: Seitentitel, 2: Seitennummer. */
			return sprintf( __( '%1$s – Seite %2$d', 'depeur-food' ), $custom, $paged );
		}

		return $custom;
	}

	/**
	 * Liefert den Custom-Titel einer geflaggten Kategorie-Seite (sonst leer).
	 *
	 * @since 0.3.0
	 *
	 * @param int $post_id Page-ID.
	 * @return string
	 */
	private function custom_title( int $post_id ): string {
		if ( $post_id < 1 || ! get_post_meta( $post_id, 'df_catpage_enabled', true ) ) {
			return '';
		}

		return trim( (string) get_post_meta( $post_id, 'df_catpage_title', true ) );
	}
}
