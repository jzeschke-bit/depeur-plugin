<?php
/**
 * Loop_Grid — rendert eine Beitragsliste als Kadence-Loop-Raster (Core-Helfer).
 *
 * Gemeinsame Render-Naht für Feature-Module (favorites-Archiv, category-pages-Filter): eine
 * Menge Posts wird als Grid aus Kadence-Loop-Entries ausgegeben (identische Karten-Optik wie
 * die normalen Archiv-Karten). Ohne Kadence greift eine schlanke, escapte Fallback-Karte.
 *
 * Bewusst im Core (src/Support/), damit Module ihn nutzen können, OHNE sich gegenseitig zu
 * importieren (Plugin-Splitting-Disziplin). Post-type-agnostisch (ADR-4).
 *
 * @package Depeur\Food\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Support;

use WP_Query;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rendert Posts als Kadence-Loop-Raster.
 *
 * @since 0.3.0
 */
final class Loop_Grid {

	/**
	 * Rendert eine ID-Liste (Reihenfolge = post__in) als Raster.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int>           $ids  Post-IDs.
	 * @param array<string, mixed> $opts Optionen: 'post_type', 'columns', 'preserve_order' (bool).
	 * @return string Raster-HTML (leer bei keinen Treffern).
	 */
	public static function render_ids( array $ids, array $opts = array() ): string {
		$ids = self::clean_ids( $ids );
		if ( empty( $ids ) ) {
			return '';
		}

		$post_type = isset( $opts['post_type'] ) ? $opts['post_type'] : depeur_food()->get_supported_post_types();
		$order     = ( ! isset( $opts['preserve_order'] ) || $opts['preserve_order'] ) ? 'post__in' : 'date';

		$query = new WP_Query(
			array(
				'post_type'           => $post_type,
				'post__in'            => $ids,
				'orderby'             => $order,
				'posts_per_page'      => count( $ids ),
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return self::render_query( $query, $opts );
	}

	/**
	 * Rendert eine bereits gebaute WP_Query als Raster (setzt danach die Query-Loop zurück).
	 *
	 * @since 0.3.0
	 *
	 * @param WP_Query             $query Die Query.
	 * @param array<string, mixed> $opts  Optionen: 'columns'.
	 * @return string Raster-HTML (leer bei keinen Treffern).
	 */
	public static function render_query( WP_Query $query, array $opts = array() ): string {
		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		$columns = isset( $opts['columns'] ) ? (string) $opts['columns'] : 'grid-sm-col-2 grid-lg-col-3';

		ob_start();
		echo '<div class="post-archive grid-cols ' . esc_attr( $columns ) . '">';
		while ( $query->have_posts() ) {
			$query->the_post();
			self::render_entry();
		}
		echo '</div>';
		wp_reset_postdata();

		return (string) ob_get_clean();
	}

	/**
	 * Rendert eine einzelne Karte — bevorzugt via Kadence-Loop-Entry, sonst Fallback.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_entry(): void {
		if ( has_action( 'kadence_loop_entry' ) ) {
			do_action( 'kadence_loop_entry' );
			return;
		}

		$thumb = get_the_post_thumbnail( get_the_ID(), 'medium' );
		echo '<article class="df-loop-card">';
		echo '<a href="' . esc_url( (string) get_permalink() ) . '">';
		echo wp_kses_post( $thumb );
		echo '<span class="df-loop-card__title">' . esc_html( get_the_title() ) . '</span>';
		echo '</a>';
		echo '</article>';
	}

	/**
	 * Bereinigt eine ID-Liste (int, positiv, eindeutig).
	 *
	 * @since 0.3.0
	 *
	 * @param array<mixed> $ids Rohe Liste.
	 * @return array<int>
	 */
	private static function clean_ids( array $ids ): array {
		$clean = array_filter(
			array_map( 'intval', $ids ),
			static function ( $id ) {
				return $id > 0;
			}
		);

		return array_values( array_unique( $clean ) );
	}
}
