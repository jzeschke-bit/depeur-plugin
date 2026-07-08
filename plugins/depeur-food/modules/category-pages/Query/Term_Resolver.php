<?php
/**
 * Term_Resolver — löst die kuratierte Term-Auswahl einer Kategorie-Seite in gruppierte Terms auf.
 *
 * Liefert `array( taxonomy => array( term_id, … ) )` für den Query_Builder. Quellen in
 * Reihenfolge (erste nicht-leere gewinnt):
 *   1. `df_catpage_terms` — neue strukturierte Meta (aus dem Plugin-Metabox, folgt).
 *   2. `rezept_tag` — Legacy-ACF-Feld der Alt-Seiten (flache ID-Liste) → auto-gruppiert.
 * So rendern bestehende Rezeptkategorie-Seiten sofort aus dem Plugin, ohne Neueingabe.
 *
 * Ersetzt das Legacy `alkipedia_get_multi_taxonomy_terms_from_acf()` +
 * `alkipedia_validate_and_group_tag_ids()` — inkl. Bugfix (Legacy referenzierte ein
 * undefiniertes `$tag_id`). Die Taxonomie einer ID wird direkt über `get_term()` bestimmt
 * (Term-IDs sind global eindeutig), nicht per Post-Type→Taxonomie-Iteration.
 *
 * @package Depeur\Food\Modules\CategoryPages\Query
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Query;

use WP_Term;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Löst die Term-Auswahl einer Kategorie-Seite auf.
 *
 * @since 0.3.0
 */
final class Term_Resolver {

	/**
	 * Neue strukturierte Term-Meta (Plugin-Metabox).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const META_TERMS = 'df_catpage_terms';

	/**
	 * Legacy-ACF-Feld der Alt-Seiten (flache Term-ID-Liste).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const META_LEGACY = 'rezept_tag';

	/**
	 * Ermittelt die gruppierten Terms einer Kategorie-Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return array<string, array<int>> taxonomy => Term-IDs (leer, wenn nichts kuratiert).
	 */
	public static function resolve( int $page_id ): array {
		if ( $page_id < 1 ) {
			return array();
		}

		// 1) Neue strukturierte Auswahl (hat Vorrang, sobald gesetzt).
		$structured = get_post_meta( $page_id, self::META_TERMS, true );
		if ( is_array( $structured ) && ! empty( $structured ) ) {
			return self::sanitize_grouped( $structured );
		}

		// 2) Legacy-Fallback: flache ID-Liste → nach Taxonomie auto-gruppieren.
		$legacy = get_post_meta( $page_id, self::META_LEGACY, true );
		if ( ! empty( $legacy ) ) {
			return self::group_by_taxonomy( (array) $legacy );
		}

		return array();
	}

	/**
	 * Gruppiert eine flache Term-ID-Liste nach der Taxonomie jeder ID.
	 *
	 * @since 0.3.0
	 *
	 * @param array<mixed> $term_ids Flache Liste von Term-IDs.
	 * @return array<string, array<int>>
	 */
	public static function group_by_taxonomy( array $term_ids ): array {
		$grouped = array();

		foreach ( $term_ids as $raw ) {
			$term_id = (int) $raw;
			if ( $term_id < 1 ) {
				continue;
			}

			// Term-IDs sind global eindeutig → get_term() liefert die zugehörige Taxonomie
			// direkt (Bugfix ggü. Legacy-Post-Type-Iteration).
			$term = get_term( $term_id );
			if ( $term instanceof WP_Term ) {
				$grouped[ $term->taxonomy ][] = $term_id;
			}
		}

		return self::dedupe( $grouped );
	}

	/**
	 * Säubert eine bereits gruppierte Struktur (existierende Taxonomien, valide IDs).
	 *
	 * @since 0.3.0
	 *
	 * @param array<mixed> $grouped Rohe Struktur taxonomy => IDs.
	 * @return array<string, array<int>>
	 */
	private static function sanitize_grouped( array $grouped ): array {
		$clean = array();

		foreach ( $grouped as $taxonomy => $ids ) {
			$taxonomy = (string) $taxonomy;
			if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$valid = array();
			foreach ( (array) $ids as $raw ) {
				$term_id = (int) $raw;
				if ( $term_id > 0 && term_exists( $term_id, $taxonomy ) ) {
					$valid[] = $term_id;
				}
			}
			if ( ! empty( $valid ) ) {
				$clean[ $taxonomy ] = $valid;
			}
		}

		return self::dedupe( $clean );
	}

	/**
	 * Dedupliziert die Term-IDs je Taxonomie.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, array<int>> $grouped Gruppierte Struktur.
	 * @return array<string, array<int>>
	 */
	private static function dedupe( array $grouped ): array {
		foreach ( $grouped as $taxonomy => $ids ) {
			$grouped[ $taxonomy ] = array_values( array_unique( array_map( 'intval', $ids ) ) );
		}

		return $grouped;
	}
}
