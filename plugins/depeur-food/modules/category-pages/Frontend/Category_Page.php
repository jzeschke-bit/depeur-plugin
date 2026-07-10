<?php
/**
 * Category_Page — Shortcode `[df_category_page]`: rendert das kuratierte Beitragsraster.
 *
 * Ersetzt die Query-/Render-Logik von `single-rezeptkategorie-template.php`, aber OHNE die
 * fragile `$wp_query`-Global-Fake-Technik: der Shortcode fährt eine eigene WP_Query und gibt
 * Grid + Pagination als HTML zurück. Das dünne Child-Template zeigt nur den Seiten-Intro
 * (`the_content`) und platziert diesen Shortcode.
 *
 * Seite-1-Verhalten (Legacy beibehalten, OE-2): Seite 1 zeigt eine kleine Vorschau
 * (`df_catpage_per_page_first`, Default 4) unter dem Intro; ab Seite 2 das volle Grid
 * (`df_catpage_per_page`, Default 21) mit passendem Offset. Pagination über `/page/N/`
 * (Rewrite, siehe Hooks\Rewrite).
 *
 * Post-type-agnostisch (ADR-4): Ziel-Typen aus `depeur_food()->get_supported_post_types()`.
 *
 * @package Depeur\Food\Modules\CategoryPages\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Frontend;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\CategoryPages\Query\Query_Builder;
use Depeur\Food\Modules\CategoryPages\Query\Term_Resolver;
use WP_Query;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert den Kategorie-Seiten-Shortcode.
 *
 * @since 0.3.0
 */
final class Category_Page {

	/**
	 * Shortcode-Tag.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const TAG = 'df_category_page';

	/**
	 * Verdrahtet Shortcode + Auto-Render geflaggter Seiten.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render' ) );

		// Auto-Render: geflaggte Kategorie-Seiten hängen das Raster automatisch an den Inhalt
		// (wie das alte Seiten-Template) — kein manueller Shortcode nötig. Prio 20 < Newsletter (99).
		add_filter( 'the_content', array( $this, 'maybe_append' ), 20 );
	}

	/**
	 * Hängt das Raster automatisch an geflaggte Kategorie-Seiten (the_content).
	 *
	 * Seite 1: Intro (Seiten-Inhalt) + Vorschau-Raster. Ab Seite 2: nur das Raster
	 * (Legacy-Verhalten). Manuelle Shortcode-Platzierung hat Vorrang (kein Doppel).
	 *
	 * @since 0.3.0
	 *
	 * @param string $content the_content-Wert.
	 * @return string
	 */
	public function maybe_append( $content ): string {
		$content = (string) $content;

		// Rekursionsschutz: render() fährt eine Sekundär-Query; deren Karten dürfen diesen
		// Filter nicht erneut auslösen.
		static $running = false;
		if ( $running ) {
			return $content;
		}

		if ( is_admin() || ! is_singular( 'page' ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		$page_id = (int) get_the_ID();
		if ( $page_id < 1 || ! get_post_meta( $page_id, 'df_catpage_enabled', true ) ) {
			return $content;
		}

		// Manuelle Platzierung gewinnt.
		if ( has_shortcode( (string) get_post_field( 'post_content', $page_id ), self::TAG ) ) {
			return $content;
		}

		$running = true;
		$grid    = $this->render( array( 'id' => $page_id ) );
		$running = false;

		if ( '' === $grid ) {
			return $content;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );

		return ( $paged > 1 ) ? $grid : $content . $grid;
	}

	/**
	 * Rendert das Raster für die aktuelle Kategorie-Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param array|string $atts Shortcode-Attribute (optional `id` überschreibt die Seite).
	 * @return string Grid- + Pagination-HTML (leer, wenn nichts gefunden).
	 */
	public function render( $atts ): string {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts, self::TAG );
		$page_id = absint( $atts['id'] );
		if ( $page_id < 1 ) {
			$page_id = absint( get_the_ID() );
		}
		if ( $page_id < 1 ) {
			return '';
		}

		$grouped = Term_Resolver::resolve( $page_id );
		$grouped = self::maybe_fallback( $page_id, $grouped );
		if ( empty( $grouped ) ) {
			return '';
		}

		$paged  = max( 1, (int) get_query_var( 'paged' ) );
		$window = self::page_window( $page_id, $paged );
		$first  = $window['first'];
		$per    = $window['per'];
		$offset = $window['offset'];
		$limit  = $window['limit'];

		$base_args = array_merge(
			Query_Builder::build( $grouped, self::query_options( $page_id ) ),
			array(
				'post_type'           => depeur_food()->get_supported_post_types(),
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			)
		);

		$total     = $this->count_total( $base_args );
		$max_pages = $this->max_pages( $total, $first, $per );

		if ( $limit < 1 ) {
			return '';
		}

		$query = new WP_Query(
			array_merge(
				$base_args,
				array(
					'posts_per_page' => $limit,
					'offset'         => $offset,
					'no_found_rows'  => true,
				)
			)
		);

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		$columns = ( $paged <= 1 ) ? 'grid-sm-col-2 grid-lg-col-2' : 'grid-sm-col-2 grid-lg-col-3';

		ob_start();
		echo '<div class="df-category-page">';

		// Optionale H2 über der Vorschau (nur Seite 1, Legacy „Weitere … die dir gefallen könnten").
		if ( $paged <= 1 ) {
			$heading = trim( (string) get_post_meta( $page_id, 'df_catpage_related_heading', true ) );
			if ( '' !== $heading ) {
				echo '<h2 class="df-category-page__heading">' . esc_html( $heading ) . '</h2>';
			}
		}

		echo '<div class="post-archive grid-cols ' . esc_attr( $columns ) . '">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$this->render_entry();
		}
		echo '</div>';
		echo $this->render_pagination( $page_id, $paged, $max_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links liefert bereits escapte Links.
		echo '</div>';
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Rendert eine einzelne Beitrags-Karte — bevorzugt via Kadence-Loop-Entry, sonst Fallback.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private function render_entry(): void {
		// Kadence rendert seine Archiv-Karten über diese Aktion (identische Optik zum Theme).
		if ( has_action( 'kadence_loop_entry' ) ) {
			do_action( 'kadence_loop_entry' );
			return;
		}

		// Fallback ohne Kadence: schlanke, escapte Karte.
		$permalink = get_permalink();
		$thumb     = get_the_post_thumbnail( get_the_ID(), 'medium' );
		echo '<article class="df-category-card">';
		echo '<a href="' . esc_url( $permalink ) . '">';
		echo wp_kses_post( $thumb );
		echo '<span class="df-category-card__title">' . esc_html( get_the_title() ) . '</span>';
		echo '</a>';
		echo '</article>';
	}

	/**
	 * Baut die Pagination (leer, wenn nur eine Seite).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id   Seiten-ID.
	 * @param int $paged     Aktuelle Seite.
	 * @param int $max_pages Anzahl Seiten.
	 * @return string
	 */
	private function render_pagination( int $page_id, int $paged, int $max_pages ): string {
		if ( $max_pages < 2 ) {
			return '';
		}

		$links = paginate_links(
			array(
				'base'      => trailingslashit( get_permalink( $page_id ) ) . '%_%',
				'format'    => 'page/%#%/',
				'current'   => $paged,
				'total'     => $max_pages,
				'mid_size'  => 2,
				'prev_text' => __( 'Zurück', 'depeur-food' ),
				'next_text' => __( 'Weiter', 'depeur-food' ),
			)
		);

		if ( empty( $links ) ) {
			return '';
		}

		// Standard-Pagination-Markup (nav.pagination > .nav-links) — greift die Kadence-/
		// Core-Paginierungs-Styles ab, statt eines nackten Link-Blocks.
		return sprintf(
			'<nav class="navigation pagination df-category-page__pagination" role="navigation" aria-label="%s"><div class="nav-links">%s</div></nav>',
			esc_attr__( 'Beiträge-Navigation', 'depeur-food' ),
			$links
		);
	}

	/**
	 * Fügt einen Fallback-Term ein, wenn nichts kuratiert wurde.
	 *
	 * @since 0.3.0
	 *
	 * @param int                        $page_id Seiten-ID.
	 * @param array<string, array<int>>  $grouped Aufgelöste Terms.
	 * @return array<string, array<int>>
	 */
	private static function maybe_fallback( int $page_id, array $grouped ): array {
		if ( ! empty( $grouped ) ) {
			return $grouped;
		}

		// Kein Default-Fallback mehr (Legacy nutzte 'low-carb' — verwirrend, wenn nichts
		// kuratiert ist). Nur ein explizit gesetzter Fallback-Term greift.
		$slug = (string) get_post_meta( $page_id, 'df_catpage_fallback_term', true );
		if ( '' === $slug ) {
			$slug = (string) apply_filters( 'depeur_food/category_pages/fallback_term', '', $page_id );
		}
		if ( '' === $slug ) {
			return array();
		}

		$term = get_term_by( 'slug', $slug, 'post_tag' );
		if ( $term ) {
			return array( 'post_tag' => array( (int) $term->term_id ) );
		}

		return array();
	}

	/**
	 * Liest die AND/OR-Optionen der Seite (aktuell Default; UI folgt in Build-Schritt 2b).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return array<string, mixed>
	 */
	private static function query_options( int $page_id ): array {
		$relation = (string) get_post_meta( $page_id, 'df_catpage_relation', true );
		$operator = (string) get_post_meta( $page_id, 'df_catpage_operator', true );

		return array(
			'relation' => '' !== $relation ? $relation : 'AND',
			'operator' => '' !== $operator ? $operator : 'AND',
		);
	}

	/**
	 * Zählt die Gesamt-Treffer der kuratierten Query (für die Pagination).
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, mixed> $base_args Query-Args (ohne Limit/Offset).
	 * @return int
	 */
	private function count_total( array $base_args ): int {
		$count = new WP_Query(
			array_merge(
				$base_args,
				array(
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
		$total = is_array( $count->posts ) ? count( $count->posts ) : 0;
		wp_reset_postdata();

		return $total;
	}

	/**
	 * Berechnet die Seitenzahl: Seite 1 zeigt `$first`, jede Folgeseite `$per`.
	 *
	 * @since 0.3.0
	 *
	 * @param int $total Gesamt-Treffer.
	 * @param int $first Menge auf Seite 1.
	 * @param int $per   Menge ab Seite 2.
	 * @return int
	 */
	private function max_pages( int $total, int $first, int $per ): int {
		if ( $total <= $first || $per < 1 ) {
			return 1;
		}

		return 1 + (int) ceil( ( $total - $first ) / $per );
	}

	/**
	 * Liest einen numerischen Meta-Wert mit Default.
	 *
	 * @since 0.3.0
	 *
	 * @param int    $page_id Seiten-ID.
	 * @param string $key     Meta-Key.
	 * @param int    $default Default, wenn nicht gesetzt.
	 * @return int
	 */
	private static function meta_number( int $page_id, string $key, int $default ): int {
		$value = get_post_meta( $page_id, $key, true );

		return ( '' === $value || null === $value ) ? $default : (int) $value;
	}

	/**
	 * Liest einen globalen Default aus dem category-pages-Settings-Tab (Fallback: harter Wert).
	 *
	 * @since 0.3.0
	 *
	 * @param string $key  Settings-Feld-ID (default_per_page_first|default_per_page).
	 * @param int    $hard Harter Legacy-Fallback, wenn nichts konfiguriert ist.
	 * @return int
	 */
	private static function global_default( string $key, int $hard ): int {
		$option = get_option( SettingsRegistry::option_key( 'category-pages' ), array() );
		if ( ! is_array( $option ) || ! isset( $option[ $key ] ) || '' === $option[ $key ] ) {
			return $hard;
		}

		return max( 0, (int) $option[ $key ] );
	}

	/**
	 * Berechnet das Anzeige-Fenster (offset/limit) für eine Seite — Seite 1 = `first`, ab Seite 2 = `per`.
	 *
	 * Eine Quelle der Wahrheit für die Paginierungs-Arithmetik von render().
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite (>= 1).
	 * @return array{first:int,per:int,offset:int,limit:int}
	 */
	private static function page_window( int $page_id, int $paged ): array {
		// Seiten-eigene Werte gewinnen; sonst die globalen Defaults (Settings-Tab), sonst Legacy 4/21.
		$first  = max( 0, (int) self::meta_number( $page_id, 'df_catpage_per_page_first', self::global_default( 'default_per_page_first', 4 ) ) );
		$per    = max( 1, (int) self::meta_number( $page_id, 'df_catpage_per_page', self::global_default( 'default_per_page', 21 ) ) );
		$offset = ( $paged <= 1 ) ? 0 : $first + ( $paged - 2 ) * $per;
		$limit  = ( $paged <= 1 ) ? $first : $per;

		return array(
			'first'  => $first,
			'per'    => $per,
			'offset' => $offset,
			'limit'  => $limit,
		);
	}

	/**
	 * Liefert die Beitrags-IDs, die auf einer bestimmten Kategorie-Seiten-Seite angezeigt werden.
	 *
	 * Nutzt dieselbe Term-Auflösung, Query-Erstellung und Fenster-Arithmetik wie render() — damit
	 * das CollectionPage-Schema (Frontend\Schema) exakt die gezeigten Beiträge auflistet. Reine
	 * ids-Abfrage (leichtgewichtig).
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @param int $paged   Aktuelle Seite (>= 1).
	 * @return int[] Beitrags-IDs in Anzeige-Reihenfolge (leer, wenn nichts kuratiert/gefunden).
	 */
	public static function post_ids_for( int $page_id, int $paged ): array {
		$grouped = Term_Resolver::resolve( $page_id );
		$grouped = self::maybe_fallback( $page_id, $grouped );
		if ( empty( $grouped ) ) {
			return array();
		}

		$window = self::page_window( $page_id, $paged );
		if ( $window['limit'] < 1 ) {
			return array();
		}

		$args = array_merge(
			Query_Builder::build( $grouped, self::query_options( $page_id ) ),
			array(
				'post_type'           => depeur_food()->get_supported_post_types(),
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page'      => $window['limit'],
				'offset'              => $window['offset'],
				'fields'              => 'ids',
				'no_found_rows'       => true,
			)
		);

		$query = new WP_Query( $args );
		$ids   = array_map( 'intval', (array) $query->posts );
		wp_reset_postdata();

		return $ids;
	}
}
