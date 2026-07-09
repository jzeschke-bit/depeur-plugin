<?php
/**
 * Schema — korrektes Schema.org-Markup für die Folgeseiten (Seite 2+) einer Kategorie-Seite.
 *
 * DAS PROBLEM: Kategorie-Seiten sind technisch `page`-Singles. Auf Seite 1 stehen im Inhalt zwei
 * WPRM-Blöcke → Rank Math/WPRM geben dort (gewollt) Recipe- + ItemList-Schema aus. Ab Seite 2 ist
 * dieser Inhalt NICHT mehr gerendert (nur das Raster), das Recipe-/ItemList-Schema blieb aber stehen.
 *
 * DIE LÖSUNG (zweigleisig, weil zwei Quellen die JSON-LD erzeugen):
 *   1) Rank Math (Filter rank_math/json_ld): auf Seite 2+ die WebPage in eine CollectionPage
 *      umtypen (behält @id/Verknüpfungen) und etwaige Recipe/ItemList/Article aus dem Rank-Math-
 *      Graph entfernen.
 *   2) WPRM gibt sein Recipe-Schema in einem EIGENEN <script> aus (nicht über Rank Math). Das
 *      erreicht der obige Filter nicht → zusätzlich der Filter wprm_recipe_metadata: auf Seite 2+
 *      liefern wir ein leeres Metadaten-Array zurück, wodurch WPRM kein Recipe/ItemList/HowTo/
 *      Nutrition-Schema mehr ausgibt.
 *
 * ERGEBNIS ab Seite 2: eine schlichte CollectionPage (Name „… – Seite N", Beschreibung, URL,
 * isPartOf, inLanguage) — bewusst OHNE ItemList (eine CollectionPage genügt). SEITE 1 bleibt
 * unverändert (normales Rank-Math-/WPRM-Schema).
 *
 * Rank Math ist Voraussetzung für Teil 1, WPRM für Teil 2 — fehlt eines, passiert dort nichts.
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
 * Wandelt das Schema paginierter Kategorie-Seiten in eine schlichte CollectionPage um.
 *
 * @since 0.3.0
 */
final class Schema {

	/**
	 * Entity-Typen, die auf Folgeseiten NICHT gehören (Inhalt der Seite 1) → werden entfernt.
	 *
	 * @since 0.3.0
	 * @var string[]
	 */
	private const STRIP_TYPES = array( 'Recipe', 'ItemList', 'HowTo', 'HowToStep', 'NutritionInformation', 'Article', 'BlogPosting', 'NewsArticle' );

	/**
	 * Verdrahtet beide Schema-Quellen.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Quelle 1: Rank-Math-Graph. Prio 99 = nach Rank Math/WPRM.
		add_filter( 'rank_math/json_ld', array( $this, 'transform' ), 99, 1 );

		// Quelle 2: WPRMs eigenes Recipe-<script>. Prio 99 = nach der Autor-Anreicherung
		// (schema-engine, Prio 10), damit unser „leer" auf Seite 2+ gewinnt.
		add_filter( 'wprm_recipe_metadata', array( $this, 'maybe_suppress_wprm' ), 99, 1 );
	}

	/**
	 * Ersetzt auf Seite 2+ einer geflaggten Kategorie-Seite das Rank-Math-Schema durch CollectionPage.
	 *
	 * @since 0.3.0
	 *
	 * @param array $data Von Rank Math zusammengestellte JSON-LD-Entitäten.
	 * @return array
	 */
	public function transform( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$page_id = $this->subpage_id();
		if ( $page_id < 1 ) {
			return $data; // Seite 1 / keine Kategorie-Seite → unverändert.
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		$name  = $this->collection_name( $page_id, $paged );
		$desc  = $this->collection_description( $page_id );
		$url   = $this->paged_url( $page_id, $paged );

		$webpage_found = false;
		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || ! isset( $entity['@type'] ) ) {
				continue;
			}
			$types = (array) $entity['@type'];

			// Recipe/ItemList/Article der Einzelseite entfernen (gehören nur auf Seite 1).
			if ( array_intersect( $types, self::STRIP_TYPES ) ) {
				unset( $data[ $key ] );
				continue;
			}

			// Die WebPage in eine CollectionPage umtypen + anreichern (behält @id/Verknüpfungen).
			if ( in_array( 'WebPage', $types, true ) ) {
				$entity['@type'] = 'CollectionPage';
				$entity['name']  = $name;
				if ( '' !== $desc ) {
					$entity['description'] = $desc;
				}
				$data[ $key ]  = $entity;
				$webpage_found = true;
			}
		}

		// Fallback: gab es keine WebPage-Entität, eine schlichte CollectionPage anhängen.
		if ( ! $webpage_found ) {
			$collection = array(
				'@type'      => 'CollectionPage',
				'@id'        => $url . '#collectionpage',
				'url'        => $url,
				'name'       => $name,
				'isPartOf'   => array( '@id' => home_url( '/' ) . '#website' ),
				'inLanguage' => get_bloginfo( 'language' ),
			);
			if ( '' !== $desc ) {
				$collection['description'] = $desc;
			}
			$data['CollectionPage'] = $collection;
		}

		return $data;
	}

	/**
	 * Unterdrückt WPRMs Recipe-Schema-Ausgabe auf Seite 2+ einer geflaggten Kategorie-Seite.
	 *
	 * Ein leeres Metadaten-Array veranlasst WPRM, kein Recipe/ItemList/HowTo/Nutrition-Schema
	 * auszugeben. Auf allen anderen Seiten (inkl. Seite 1) bleibt die Ausgabe unverändert.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $metadata WPRM-Metadaten-Array.
	 * @return mixed Leeres Array auf Folgeseiten, sonst das unveränderte Array.
	 */
	public function maybe_suppress_wprm( $metadata ) {
		return $this->subpage_id() > 0 ? array() : $metadata;
	}

	/**
	 * Liefert die Seiten-ID, WENN gerade Seite 2+ einer geflaggten Kategorie-Seite gerendert wird.
	 *
	 * Gemeinsame Wächter-Logik für beide Filter — identisch zu Hooks\Layout (dort verifiziert,
	 * dass is_singular/paged/df_catpage_enabled auf diesen Seiten korrekt greifen).
	 *
	 * @since 0.3.0
	 *
	 * @return int Seiten-ID (> 0) oder 0, wenn nicht zutreffend.
	 */
	private function subpage_id(): int {
		if ( is_admin() || ! is_singular( 'page' ) || ! is_main_query() ) {
			return 0;
		}
		if ( max( 1, (int) get_query_var( 'paged' ) ) < 2 ) {
			return 0;
		}
		$page_id = (int) get_queried_object_id();
		if ( $page_id < 1 || ! get_post_meta( $page_id, 'df_catpage_enabled', true ) ) {
			return 0;
		}

		return $page_id;
	}

	/**
	 * Baut den CollectionPage-Namen: Kategorie-Titel + „– Seite N".
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite.
	 * @return string
	 */
	private function collection_name( int $page_id, int $paged ): string {
		$title = trim( (string) get_post_meta( $page_id, 'df_catpage_title', true ) );
		if ( '' === $title ) {
			$title = (string) get_the_title( $page_id );
		}

		/* translators: %d: Seitenzahl. */
		return $title . ' ' . sprintf( __( '– Seite %d', 'depeur-food' ), $paged );
	}

	/**
	 * Ermittelt eine Beschreibung für die CollectionPage (Auszug der Seite, sonst leer).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return string
	 */
	private function collection_description( int $page_id ): string {
		$excerpt = has_excerpt( $page_id ) ? get_the_excerpt( $page_id ) : '';

		return trim( wp_strip_all_tags( (string) $excerpt ) );
	}

	/**
	 * Baut die kanonische URL der paginierten Seite (…/page/N/).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite.
	 * @return string
	 */
	private function paged_url( int $page_id, int $paged ): string {
		return trailingslashit( (string) get_permalink( $page_id ) ) . 'page/' . $paged . '/';
	}
}
