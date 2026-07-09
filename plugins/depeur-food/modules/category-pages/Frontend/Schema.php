<?php
/**
 * Schema — kontextuell korrektes Schema.org-Markup für die Folgeseiten (Seite 2+) einer Kategorie-Seite.
 *
 * DAS PROBLEM: Kategorie-Seiten sind technisch `page`-Singles. Auf Seite 1 stehen im Inhalt zwei
 * WPRM-Snippets → dort (gewollt) ein Recipe-Schema UND eine WPRM-Roundup-ItemList ANDERER Rezepte.
 * Ab Seite 2 ist dieser Inhalt nicht mehr gerendert, das WPRM-Schema blieb aber stehen.
 *
 * ZIEL: Ab Seite 2 ein passendes Archiv-Schema:
 *   - eine CollectionPage MIT einer ItemList der TATSÄCHLICH auf dieser Seite gezeigten Beiträge
 *     (Best Practice für eine paginierte Sammlung),
 *   - OHNE die von Seite 1 stammenden WPRM-Sachen (Recipe + WPRM-Roundup-ItemList anderer Rezepte).
 *
 * DREI ZUGRIFFE (weil mehrere Quellen JSON-LD erzeugen):
 *   1) rank_math/json_ld: WebPage → CollectionPage umtypen + unsere ItemList als mainEntity setzen;
 *      etwaige Recipe/ItemList/Article aus dem Rank-Math-Graph entfernen.
 *   2) wprm_recipe_metadata: WPRMs Recipe-<script> auf Seite 2+ leeren.
 *   3) Output-Sicherheitsnetz: WPRMs Roundup-ItemList ist ein SEPARATES <script> ohne
 *      zuverlässigen Filter → auf Seite 2+ stray Recipe/ItemList-<script> aus der fertigen Seite
 *      entfernen. UNSERE CollectionPage (mit eingebetteter ItemList) wird dabei geschützt, weil ihr
 *      Script „CollectionPage" enthält — WPRMs Roundup-Script nicht.
 *
 * SEITE 1 bleibt unverändert.
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
 * Wandelt das Schema paginierter Kategorie-Seiten in eine CollectionPage mit ItemList um.
 *
 * @since 0.3.0
 */
final class Schema {

	/**
	 * Top-Level-Entity-Typen im Rank-Math-Graph, die auf Folgeseiten NICHT gehören → entfernt.
	 * (Unsere eigene ItemList sitzt EINGEBETTET in der CollectionPage, nicht als Top-Level-Entität,
	 * und ist davon nicht betroffen.)
	 *
	 * @since 0.3.0
	 * @var string[]
	 */
	private const STRIP_TYPES = array( 'Recipe', 'ItemList', 'HowTo', 'HowToStep', 'NutritionInformation', 'Article', 'BlogPosting', 'NewsArticle' );

	/**
	 * Verdrahtet die drei Zugriffe.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// 1) Rank-Math-Graph. Prio 99 = nach Rank Math/WPRM.
		add_filter( 'rank_math/json_ld', array( $this, 'transform' ), 99, 1 );

		// 2) WPRMs Recipe-<script>. Prio 99 = nach der Autor-Anreicherung (schema-engine, Prio 10).
		add_filter( 'wprm_recipe_metadata', array( $this, 'maybe_suppress_wprm' ), 99, 1 );

		// 3) Output-Sicherheitsnetz gegen WPRMs separates Roundup-ItemList-<script>.
		add_action( 'template_redirect', array( $this, 'maybe_buffer' ) );
	}

	/**
	 * Ersetzt auf Seite 2+ das Rank-Math-Schema durch eine CollectionPage mit ItemList.
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

		$paged     = max( 1, (int) get_query_var( 'paged' ) );
		$name      = $this->collection_name( $page_id, $paged );
		$desc      = $this->collection_description( $page_id );
		$url       = $this->paged_url( $page_id, $paged );
		$item_list = $this->build_item_list( $page_id, $paged );

		$webpage_found = false;
		foreach ( $data as $key => $entity ) {
			if ( ! is_array( $entity ) || ! isset( $entity['@type'] ) ) {
				continue;
			}
			$types = (array) $entity['@type'];

			// Top-Level Recipe/ItemList/Article der Einzelseite entfernen (gehören nur auf Seite 1).
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
			$collection = array(
				'@type'      => 'CollectionPage',
				'@id'        => $url . '#collectionpage',
				'url'        => $url,
				'name'       => $name,
				'isPartOf'   => array( '@id' => home_url( '/' ) . '#website' ),
				'inLanguage' => get_bloginfo( 'language' ),
				'mainEntity' => $item_list,
			);
			if ( '' !== $desc ) {
				$collection['description'] = $desc;
			}
			$data['CollectionPage'] = $collection;
		}

		return $data;
	}

	/**
	 * Unterdrückt WPRMs Recipe-Schema-Ausgabe auf Seite 2+ (leeres Metadaten-Array).
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $metadata WPRM-Metadaten-Array.
	 * @return mixed Leeres Array auf Folgeseiten, sonst unverändert.
	 */
	public function maybe_suppress_wprm( $metadata ) {
		return $this->subpage_id() > 0 ? array() : $metadata;
	}

	/**
	 * Startet auf Seite 2+ einen Output-Puffer, der stray WPRM-Recipe/ItemList-<script> entfernt.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function maybe_buffer(): void {
		if ( $this->subpage_id() > 0 ) {
			ob_start( array( $this, 'strip_stray_jsonld' ) );
		}
	}

	/**
	 * Entfernt aus der fertigen Seite JSON-LD-<script>, die Recipe/ItemList enthalten — außer der
	 * eigenen CollectionPage (deren Script „CollectionPage" enthält und geschützt bleibt).
	 *
	 * WOFÜR: WPRMs Roundup-ItemList (Rezepte von Seite 1) kommt als separates Script ohne
	 * zuverlässigen Filter. Unsere CollectionPage-ItemList (die gezeigten Beiträge) sitzt dagegen
	 * im Rank-Math-Script MIT „CollectionPage" und wird daher NICHT entfernt.
	 *
	 * @since 0.3.0
	 *
	 * @param string $html Gepufferte Seiten-Ausgabe.
	 * @return string Bereinigte Ausgabe.
	 */
	public function strip_stray_jsonld( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$result = preg_replace_callback(
			'#<script\b[^>]*type=(["\'])application/ld\+json\1[^>]*>(.*?)</script>#is',
			static function ( $matches ) {
				$json = $matches[2];

				// Unsere CollectionPage (mit eingebetteter ItemList) niemals anfassen.
				if ( false !== strpos( $json, '"CollectionPage"' ) ) {
					return $matches[0];
				}

				// Stray Recipe/ItemList (WPRM Seite-1-Reste) entfernen.
				if ( preg_match( '#"@type"\s*:\s*"(?:Recipe|ItemList)"#', $json ) ) {
					return '';
				}

				return $matches[0];
			},
			$html
		);

		// Sicherheit: bei einem preg-Fehler (z. B. Backtrack-Limit) NULL → niemals die Seite leeren.
		return is_string( $result ) ? $result : $html;
	}

	/**
	 * Baut die ItemList der auf dieser Seite gezeigten Beiträge (nummeriert, mit URL/Name/Bild).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite.
	 * @return array ItemList-Struktur (mainEntity der CollectionPage).
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
	 * Liefert die Seiten-ID, WENN gerade Seite 2+ einer geflaggten Kategorie-Seite gerendert wird.
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
