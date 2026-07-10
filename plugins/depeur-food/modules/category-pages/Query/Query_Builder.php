<?php
/**
 * Query_Builder — baut aus gruppierten Terms den taxonomie-bezogenen WP_Query-Args-Teil.
 *
 * Reine, WordPress-freie Logik (Input = gruppierte Term-IDs + Optionen, Output = Args-Array)
 * → lokal unit-testbar. Ersetzt das Legacy `alkipedia_build_multi_taxonomy_query()`, aber
 * mit konfigurierbarem AND/OR statt stur AND (BRIEF § 9).
 *
 * Semantik:
 *   - `post_tag` läuft über die nativen `tag__and` (UND) bzw. `tag__in` (ODER) Query-Vars.
 *   - jede andere Taxonomie wird ein `tax_query`-Eintrag mit `operator` AND|IN.
 *   - bei >1 Taxonomie-Block wird die Block-`relation` (AND|OR) gesetzt.
 *
 * @package Depeur\Food\Modules\CategoryPages\Query
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Query;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Konstruiert den tax_query-/tag-Teil der WP_Query-Args.
 *
 * @since 0.3.0
 */
final class Query_Builder {

	/**
	 * Baut den Args-Fragment aus gruppierten Terms.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, array<int>> $grouped_terms taxonomy-slug => Liste von Term-IDs.
	 * @param array<string, mixed>      $options       Optionen:
	 *      - 'relation'  string  'AND'|'OR' zwischen den Taxonomie-Blöcken (Default AND).
	 *      - 'operator'  string  'AND'|'OR' innerhalb einer Taxonomie (Default AND).
	 *      - 'operators' array   Pro-Taxonomie-Override taxonomy-slug => 'AND'|'OR'.
	 * @return array<string, mixed> WP_Query-Args-Fragment (leer, wenn keine Terms).
	 */
	public static function build( array $grouped_terms, array $options = array() ): array {
		$relation   = self::normalize_relation( $options['relation'] ?? 'AND' );
		$default_op = self::normalize_operator( $options['operator'] ?? 'AND' );
		$per_group  = ( isset( $options['operators'] ) && is_array( $options['operators'] ) ) ? $options['operators'] : array();

		$args      = array();
		$tax_query = array();

		foreach ( $grouped_terms as $taxonomy => $term_ids ) {
			$taxonomy = (string) $taxonomy;
			$term_ids = self::clean_ids( $term_ids );
			if ( empty( $term_ids ) ) {
				continue;
			}

			$operator = isset( $per_group[ $taxonomy ] )
				? self::normalize_operator( $per_group[ $taxonomy ] )
				: $default_op;

			// post_tag über die nativen, performanteren tag__and/tag__in-Query-Vars.
			if ( 'post_tag' === $taxonomy ) {
				if ( 'AND' === $operator ) {
					$args['tag__and'] = $term_ids;
				} else {
					$args['tag__in'] = $term_ids;
				}
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
				// tax_query kennt AND|IN (nicht OR): OR-Semantik = IN (mind. ein Term).
				'operator' => ( 'AND' === $operator ) ? 'AND' : 'IN',
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = $relation;
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		return $args;
	}

	/**
	 * Normalisiert einen Relations-Wert auf 'AND'|'OR'.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $value Roher Wert.
	 * @return string 'AND'|'OR'.
	 */
	private static function normalize_relation( $value ): string {
		return ( 'OR' === strtoupper( (string) $value ) ) ? 'OR' : 'AND';
	}

	/**
	 * Normalisiert einen Operator-Wert auf 'AND'|'OR'.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $value Roher Wert.
	 * @return string 'AND'|'OR'.
	 */
	private static function normalize_operator( $value ): string {
		return ( 'OR' === strtoupper( (string) $value ) ) ? 'OR' : 'AND';
	}

	/**
	 * Bereinigt eine Term-ID-Liste (int, positiv, eindeutig).
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $term_ids Rohe ID-Liste.
	 * @return array<int> Bereinigte IDs.
	 */
	private static function clean_ids( $term_ids ): array {
		$ids = array_map( 'intval', (array) $term_ids );
		$ids = array_filter(
			$ids,
			static function ( $id ) {
				return $id > 0;
			}
		);

		return array_values( array_unique( $ids ) );
	}
}
