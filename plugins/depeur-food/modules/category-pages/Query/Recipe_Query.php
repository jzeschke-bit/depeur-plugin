<?php
/**
 * Recipe_Query — gemeinsame WP_Query-Konstruktion für den „Was koche ich heute"-Filter.
 *
 * Von Shortcode (Erstausgabe) und REST-Endpoint (Nachladen) genutzt, damit beide Pfade
 * exakt dieselbe Query fahren. Filtert post_tag per AND (`tag_slug__and`) oder OR
 * (`tag_slug__in`); post-type-agnostisch über die unterstützten Typen (ADR-4).
 *
 * @package Depeur\Food\Modules\CategoryPages\Query
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Query;

use WP_Query;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die WP_Query des Rezept-Filters.
 *
 * @since 0.3.0
 */
final class Recipe_Query {

	/**
	 * Beiträge pro Seite/Nachlade-Schritt.
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const PER_PAGE = 20;

	/**
	 * Baut die Query aus Tag-Slugs, Match-Modus und Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, string> $slugs post_tag-Slugs (bereits sanitisiert).
	 * @param string             $relation 'and' (alle Tags) oder 'or' (mindestens einer).
	 * @param int                $paged Seite (>= 1).
	 * @return WP_Query
	 */
	public static function build( array $slugs, string $relation, int $paged ): WP_Query {
		$args = array(
			'post_type'           => depeur_food()->get_supported_post_types(),
			'post_status'         => 'publish',
			'posts_per_page'      => self::PER_PAGE,
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $slugs ) ) {
			$key          = ( 'or' === $relation ) ? 'tag_slug__in' : 'tag_slug__and';
			$args[ $key ] = $slugs;
		}

		return new WP_Query( $args );
	}

	/**
	 * Beiträge pro Seite.
	 *
	 * @since 0.3.0
	 *
	 * @return int
	 */
	public static function per_page(): int {
		return self::PER_PAGE;
	}

	/**
	 * Baut den Ergebnis-Titel („X + Y Rezepte" bzw. „Alle Rezepte").
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, string> $slugs Tag-Slugs.
	 * @return string
	 */
	public static function title( array $slugs ): string {
		if ( empty( $slugs ) ) {
			return __( 'Alle Rezepte', 'depeur-food' );
		}

		$names = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'post_tag' );
			if ( $term ) {
				$names[] = $term->name;
			}
		}
		if ( empty( $names ) ) {
			return __( 'Alle Rezepte', 'depeur-food' );
		}

		/* translators: %s: mit „ + " verbundene Tag-Namen. */
		return sprintf( __( '%s Rezepte', 'depeur-food' ), implode( ' + ', $names ) );
	}
}
