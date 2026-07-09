<?php
/**
 * Schema — korrektes Schema.org-Markup für die Folgeseiten (Seite 2+) einer Kategorie-Seite.
 *
 * DAS PROBLEM: Kategorie-Seiten sind technisch `page`-Singles. Auf Seite 1 stehen im Inhalt zwei
 * WPRM-Blöcke → Rank Math/WPRM geben dort (gewollt) Recipe- + ItemList-Schema aus. Ab Seite 2 ist
 * dieser Inhalt NICHT mehr gerendert (nur das Raster), Rank Math hält die Seite aber weiter für
 * ein einzelnes `page`/Recipe → es blieb fälschlich das Recipe/ItemList-Schema stehen.
 *
 * DAS ALT-VERHALTEN (das wir wiederherstellen): Auf Seite 2+ gehört ein CollectionPage-Schema —
 * die Seite ist dort eine paginierte Sammlung/Archivansicht. Das Alt-Theme erzwang das über den
 * fragilen `$wp_query->is_archive = true`-Trick. Hier stattdessen sauber über den
 * `rank_math/json_ld`-Filter: auf Seite 2+ Recipe/ItemList/Article entfernen und die WebPage in
 * eine CollectionPage mit echter ItemList der gezeigten Beiträge umwandeln (deutlich reichhaltiger
 * als das Alt-Setup: Name, Beschreibung, URL, isPartOf, inLanguage, nummerierte ItemList).
 *
 * SEITE 1 bleibt bewusst UNVERÄNDERT (normales Rank-Math-/WPRM-Schema).
 *
 * Rank Math ist Voraussetzung (der Filter feuert nur, wenn Rank Math aktiv ist) — ohne Rank Math
 * passiert schlicht nichts.
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
 * Wandelt das Schema paginierter Kategorie-Seiten in eine CollectionPage um.
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
	private const STRIP_TYPES = array( 'Recipe', 'ItemList', 'HowTo', 'Article', 'BlogPosting', 'NewsArticle' );

	/**
	 * Verdrahtet den JSON-LD-Filter.
	 *
	 * Prio 99: nach Rank Math + WPRM, damit deren Ausgabe vorliegt und wir sie umformen können.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_filter( 'rank_math/json_ld', array( $this, 'transform' ), 99, 1 );
	}

	/**
	 * Ersetzt auf Seite 2+ einer geflaggten Kategorie-Seite das Schema durch eine CollectionPage.
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
		if ( is_admin() || ! is_singular( 'page' ) || ! is_main_query() ) {
			return $data;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged < 2 ) {
			return $data; // Seite 1 bleibt unverändert.
		}

		$page_id = (int) get_queried_object_id();
		if ( $page_id < 1 || ! get_post_meta( $page_id, 'df_catpage_enabled', true ) ) {
			return $data;
		}

		$item_list = $this->build_item_list( $page_id, $paged );
		$name      = $this->collection_name( $page_id, $paged );
		$desc      = $this->collection_description( $page_id );
		$url       = $this->paged_url( $page_id, $paged );

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
				$entity['@type']      = 'CollectionPage';
				$entity['name']       = $name;
				$entity['mainEntity'] = $item_list;
				if ( '' !== $desc ) {
					$entity['description'] = $desc;
				}
				$data[ $key ]  = $entity;
				$webpage_found = true;
			}
		}

		// Fallback: gab es keine WebPage-Entität, eine eigenständige CollectionPage anhängen.
		if ( ! $webpage_found ) {
			$data['CollectionPage'] = array(
				'@type'      => 'CollectionPage',
				'@id'        => $url . '#collectionpage',
				'url'        => $url,
				'name'       => $name,
				'description' => $desc,
				'isPartOf'   => array( '@id' => home_url( '/' ) . '#website' ),
				'inLanguage' => get_bloginfo( 'language' ),
				'mainEntity' => $item_list,
			);
		}

		return $data;
	}

	/**
	 * Baut die ItemList der auf dieser Seite gezeigten Beiträge (nummeriert, mit URL/Name/Bild).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite.
	 * @return array ItemList-Struktur.
	 */
	private function build_item_list( int $page_id, int $paged ): array {
		$ids      = Category_Page::post_ids_for( $page_id, $paged );
		$elements = array();

		foreach ( $ids as $index => $id ) {
			$id      = (int) $id;
			$element = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'url'      => (string) get_permalink( $id ),
				'name'     => wp_strip_all_tags( (string) get_the_title( $id ) ),
			);

			$image = get_the_post_thumbnail_url( $id, 'medium_large' );
			if ( $image ) {
				$element['image'] = (string) $image;
			}

			$elements[] = $element;
		}

		return array(
			'@type'           => 'ItemList',
			'numberOfItems'   => count( $elements ),
			'itemListElement' => $elements,
		);
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
