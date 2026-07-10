<?php
/**
 * Fields — provisioniert link_de/link_en (Post + Term) via Core-Field_Provisioner.
 *
 * Post-type-agnostisch: Ziel-Post-Types = `depeur_food()->get_supported_post_types()`
 * (filterbar), Taxonomien filterbar. Nutzt die Discovery-Field-Keys (Reuse → überschreibt
 * die site-eigene ACF-Group gleichen Keys, kein Doppel-Render). Speichert als `link`
 * (Array {title,url,target}) mit `show_in_rest` — der Kern-Mehrwert gegenüber ACF (das
 * heute nichts in REST exponiert).
 *
 * @package Depeur\Food\Modules\LanguageSelector\Provisioning
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\LanguageSelector\Provisioning;

use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die Feld-Deklarationen und übergibt sie an den Provisioner.
 *
 * @since 0.2.0
 */
final class Fields {

	/**
	 * Löst die Ziel-Typen auf und startet die Provisionierung.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$post_types = $this->post_types();
		$taxonomies = $this->taxonomies();

		$link_acf = array(
			'return_format' => 'url',
		);

		$fields = array(
			array(
				'name'     => 'link_de',
				'acf_type' => 'link',
				'object'   => array( 'post', 'term' ),
				'subtypes' => array(
					'post' => $post_types,
					'term' => $taxonomies,
				),
				'label'    => __( 'Deutsche URL', 'depeur-food' ),
				'key'      => 'field_5f29e0d3a8543',
				'default'  => array(),
				'acf'      => $link_acf,
			),
			array(
				'name'     => 'link_en',
				'acf_type' => 'link',
				'object'   => array( 'post', 'term' ),
				'subtypes' => array(
					'post' => $post_types,
					'term' => $taxonomies,
				),
				'label'    => __( 'Englische URL', 'depeur-food' ),
				'key'      => 'field_5f29e36e1144d',
				'default'  => array(),
				'acf'      => $link_acf,
			),
		);

		$group = array(
			'key'      => 'group_5f29db788a4f8',
			'title'    => __( 'Übersetzungen (Cross-Domain)', 'depeur-food' ),
			'location' => $this->location( $post_types, $taxonomies ),
			'position' => 'acf_after_title',
		);

		new Field_Provisioner( $fields, $group );
	}

	/**
	 * Ziel-Post-Types (post-type-agnostisch, filterbar).
	 *
	 * @since 0.2.0
	 *
	 * @return string[]
	 */
	private function post_types(): array {
		$types = depeur_food()->get_supported_post_types();

		/**
		 * Filtert die Post-Types, für die link_de/link_en angelegt werden.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $types Unterstützte Post-Types.
		 */
		return array_values( array_unique( (array) apply_filters( 'depeur_food/language_selector/post_types', $types ) ) );
	}

	/**
	 * Ziel-Taxonomien (Default: category, filterbar).
	 *
	 * @since 0.2.0
	 *
	 * @return string[]
	 */
	private function taxonomies(): array {
		/**
		 * Filtert die Taxonomien, für die link_de/link_en angelegt werden.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $taxonomies Ziel-Taxonomien.
		 */
		return array_values( array_unique( (array) apply_filters( 'depeur_food/language_selector/taxonomies', array( 'category' ) ) ) );
	}

	/**
	 * Baut die ACF-Location-Regeln (OR-Gruppen je Post-Type + Taxonomie).
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $post_types Post-Types.
	 * @param string[] $taxonomies Taxonomien.
	 * @return array
	 */
	private function location( array $post_types, array $taxonomies ): array {
		$location = array();

		foreach ( $post_types as $post_type ) {
			$location[] = array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				),
			);
		}

		foreach ( $taxonomies as $taxonomy ) {
			$location[] = array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => $taxonomy,
				),
			);
		}

		return $location;
	}
}
