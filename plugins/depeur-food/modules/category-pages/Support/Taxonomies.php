<?php
/**
 * Taxonomies — welche Taxonomien auf einer Kategorie-Seite kuratierbar sind.
 *
 * Post-type-agnostisch (ADR-4): die Menge ergibt sich aus den Taxonomien aller unterstützten
 * Post-Types (`depeur_food()->get_supported_post_types()`), ohne die technische `post_format`.
 * Genau für diese Taxonomien legt das Modul je ein Term-Auswahlfeld an und liest es aus.
 *
 * @package Depeur\Food\Modules\CategoryPages\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Support;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ermittelt die kuratierbaren Taxonomien und ihren Meta-Key.
 *
 * @since 0.3.0
 */
final class Taxonomies {

	/**
	 * Meta-Key-Präfix je Taxonomie (z. B. df_catpage_terms_post_tag).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const META_PREFIX = 'df_catpage_terms_';

	/**
	 * Liefert die kuratierbaren Taxonomie-Slugs (dedupliziert, filterbar).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, string>
	 */
	public static function supported(): array {
		$post_types = (array) depeur_food()->get_supported_post_types();

		$taxonomies = array();
		foreach ( $post_types as $post_type ) {
			foreach ( get_object_taxonomies( (string) $post_type ) as $taxonomy ) {
				$taxonomies[ $taxonomy ] = $taxonomy;
			}
		}

		// `post_format` ist ein technisches Format, kein kuratierbares Thema.
		unset( $taxonomies['post_format'] );

		/**
		 * Filtert die auf Kategorie-Seiten kuratierbaren Taxonomien.
		 *
		 * @since 0.3.0
		 *
		 * @param array<int, string> $taxonomies Taxonomie-Slugs.
		 * @param array<int, string> $post_types Unterstützte Post-Types.
		 */
		$taxonomies = apply_filters( 'depeur_food/category_pages/taxonomies', array_values( $taxonomies ), $post_types );

		return array_values( array_unique( array_filter( array_map( 'strval', (array) $taxonomies ) ) ) );
	}

	/**
	 * Meta-Key der Term-Auswahl einer Taxonomie.
	 *
	 * @since 0.3.0
	 *
	 * @param string $taxonomy Taxonomie-Slug.
	 * @return string
	 */
	public static function meta_key( string $taxonomy ): string {
		return self::META_PREFIX . $taxonomy;
	}
}
