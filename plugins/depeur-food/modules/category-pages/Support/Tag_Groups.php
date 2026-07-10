<?php
/**
 * Tag_Groups — gruppiert post_tag-Terms für den „Was koche ich heute"-Filter.
 *
 * Liest die (aus dem Alt-Theme stammende) Term-Meta `tag_group` und sortiert die Schlagwörter
 * in benannte Filter-Gruppen (Legacy: 6 feste Gruppen). Labels + Default-Gruppe sind
 * filterbar; die vollständige Registrierung/Admin-Spalte der Term-Meta folgt in Build-Schritt 5.
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
 * Liest die Tag-Gruppen-Zuordnung und liefert gruppierte Schlagwörter.
 *
 * @since 0.3.0
 */
final class Tag_Groups {

	/**
	 * Term-Meta-Key der Gruppenzuordnung (aus dem Alt-Theme übernommen).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const META_KEY = 'tag_group';

	/**
	 * Standard-Labels der Filter-Gruppen (Legacy), filterbar.
	 *
	 * @since 0.3.0
	 *
	 * @return array<string, string> Gruppen-Key => Label.
	 */
	public static function label_map(): array {
		$labels = array(
			'anlass'          => __( 'Nach Anlass', 'depeur-food' ),
			'zubereitung'     => __( 'Nach Zubereitung', 'depeur-food' ),
			'zutaten'         => __( 'Nach Zutaten', 'depeur-food' ),
			'saisonales'      => __( 'Saisonales', 'depeur-food' ),
			'ernaehrung_ziel' => __( 'Nach Ernährung & Ziel', 'depeur-food' ),
			'herkunft'        => __( 'Nach Herkunft', 'depeur-food' ),
		);

		/**
		 * Filtert die Labels der Tag-Filter-Gruppen.
		 *
		 * @since 0.3.0
		 *
		 * @param array<string, string> $labels Gruppen-Key => Label.
		 */
		return (array) apply_filters( 'depeur_food/category_pages/tag_group_labels', $labels );
	}

	/**
	 * Default-Gruppe für Tags ohne Zuordnung.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public static function default_group(): string {
		/** This filter is documented in Support/Tag_Groups.php */
		return (string) apply_filters( 'depeur_food/category_pages/tag_group_default', 'zutaten' );
	}

	/**
	 * Liefert die Schlagwörter gruppiert (in Label-Reihenfolge, Extras hinten).
	 *
	 * @since 0.3.0
	 *
	 * @return array<string, array{label: string, tags: array<int, \WP_Term>}>
	 */
	public static function grouped_tags(): array {
		$tags = get_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $tags ) || empty( $tags ) ) {
			return array();
		}

		$labels  = self::label_map();
		$default = self::default_group();
		$groups  = array();

		foreach ( $tags as $tag ) {
			$key = (string) get_term_meta( $tag->term_id, self::META_KEY, true );
			if ( '' === $key ) {
				$key = $default;
			}
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'label' => isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( $key ),
					'tags'  => array(),
				);
			}
			$groups[ $key ]['tags'][] = $tag;
		}

		// Reihenfolge: erst die bekannten Label-Gruppen, dann übrige.
		$ordered = array();
		foreach ( array_keys( $labels ) as $key ) {
			if ( isset( $groups[ $key ] ) ) {
				$ordered[ $key ] = $groups[ $key ];
				unset( $groups[ $key ] );
			}
		}
		foreach ( $groups as $key => $group ) {
			$ordered[ $key ] = $group;
		}

		return $ordered;
	}
}
