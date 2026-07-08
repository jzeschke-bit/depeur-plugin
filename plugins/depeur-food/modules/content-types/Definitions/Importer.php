<?php
/**
 * Importer — Live-Scan + Übernahme registrierter Typen in den Store (BRIEF § 4.5/§ 7.3).
 *
 * Enumeriert die live registrierten (nicht-`_builtin`, nicht-Denylist) Post-Types und
 * Taxonomien, erfasst ihre Args args-treu aus dem Live-Objekt (`get_post_type_object`/
 * `get_taxonomy`) und schreibt sie in den Definitions-Store. **Reine Logik-Klasse** — kein
 * UI, kein Nonce/Cap (das Sicherheits-Gate liegt im aufrufenden Admin-Handler bzw. entfällt
 * bei WP-CLI). Callbacks werden nie mitgenommen (Store-Sanitize, § 7.3).
 *
 * ⚠️ Taxonomie-Scan per object_type-Schnitt, NICHT nach `public` filtern — sonst fiele
 * `herkunft` (public=false) still weg und cocktails-Taxonomie-Queries brächen (§ 4.5/§ 9.8).
 *
 * @package Depeur\Food\Modules\ContentTypes\Definitions
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\ContentTypes\Definitions;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scannt live registrierte Typen und übernimmt gewählte in den Store.
 *
 * @since 0.1.0
 */
final class Importer {

	/**
	 * Präfix-Denylist: Plugin-/Core-eigene Typen nie importierbar (§ 9.12). Ergänzt um die
	 * auf der alkipedia-Staging gefundenen Fremd-Typen `wpcf7_*` (Contact Form 7) und
	 * `rank_math_*` (Rank Math), damit der Scan nur echte Content-Typen zeigt.
	 *
	 * @var string[]
	 */
	const DENY_PREFIXES = array(
		'acf-',
		'acf_',
		'kadence_',
		'wprm_',
		'rm_',
		'wpcf7_',
		'rank_math_',
		'kb_',
	);

	/**
	 * Definitions-Store (Schreibziel).
	 *
	 * @var Store
	 */
	private $store;

	/**
	 * @since 0.1.0
	 *
	 * @param Store|null $store Optionaler Store (Default: neuer Store) – erleichtert Tests.
	 */
	public function __construct( ?Store $store = null ) {
		$this->store = $store instanceof Store ? $store : new Store();
	}

	/**
	 * Scannt importierbare Kandidaten (Vorschau-Datenquelle für UI/CLI).
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $selected_post_types Optional: CPTs, für die Taxonomien geschnitten
	 *                                      werden. Leer ⇒ alle Kandidaten-CPTs.
	 * @return array{post_types:array,taxonomies:array}
	 */
	public function scan( array $selected_post_types = array() ): array {
		$stored_pt  = $this->store->get_post_types();
		$stored_tax = $this->store->get_taxonomies();

		$post_types = array();
		foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $slug => $obj ) {
			if ( $this->is_denied( $slug ) ) {
				continue;
			}
			$post_types[ $slug ] = array(
				'label'       => $this->singular_label( $obj, $slug ),
				'public'      => (bool) $obj->public,
				// public → im UI vorangehakt; non-public bleibt wählbar, aber nicht empfohlen.
				'recommended' => (bool) $obj->public,
				'in_store'    => isset( $stored_pt[ $slug ] ),
			);
		}

		// Taxonomien über den object_type-Schnitt mit den Ziel-CPTs (inkl. non-public!).
		$target_pts = ! empty( $selected_post_types ) ? $selected_post_types : array_keys( $post_types );

		$taxonomies = array();
		foreach ( get_taxonomies( array( '_builtin' => false ), 'objects' ) as $slug => $tax ) {
			if ( $this->is_denied( $slug ) ) {
				continue;
			}
			$object_types = (array) $tax->object_type;
			if ( empty( array_intersect( $object_types, $target_pts ) ) ) {
				continue;
			}
			$taxonomies[ $slug ] = array(
				'label'       => $this->singular_label( $tax, $slug ),
				'public'      => (bool) $tax->public,
				'object_type' => array_values( $object_types ),
				// Per object_type-Schnitt gewählt → empfohlen, unabhängig von public (herkunft).
				'recommended' => true,
				'in_store'    => isset( $stored_tax[ $slug ] ),
			);
		}

		$candidates = array(
			'post_types' => $post_types,
			'taxonomies' => $taxonomies,
		);

		/**
		 * Filtert die Scan-Kandidaten (BRIEF § 5) – Denylist erweitern / Typ erzwingen.
		 *
		 * @since 0.1.0
		 *
		 * @param array $candidates Kandidaten (post_types/taxonomies).
		 */
		return apply_filters( 'depeur_food/content_types/importable', $candidates );
	}

	/**
	 * Erfasst die kuratierten CPT-Args aus dem Live-Objekt (args-treu, § 4.2).
	 *
	 * Polymorphe Werte (has_archive string|bool, show_in_menu bool|string, rest_base
	 * string|false …) werden FORM-treu erhalten, nicht normalisiert (§ 3.7/§ 4.6).
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug CPT-Slug.
	 * @return array
	 */
	public function capture_post_type( string $slug ): array {
		$obj = get_post_type_object( $slug );
		if ( null === $obj ) {
			return array();
		}

		$args = array(
			'labels'              => (array) $obj->labels,
			'description'         => (string) $obj->description,
			'public'              => (bool) $obj->public,
			'publicly_queryable'  => (bool) $obj->publicly_queryable,
			'exclude_from_search' => (bool) $obj->exclude_from_search,
			'hierarchical'        => (bool) $obj->hierarchical,
			'show_ui'             => (bool) $obj->show_ui,
			'show_in_menu'        => $obj->show_in_menu,        // bool|string – formtreu.
			'show_in_nav_menus'   => (bool) $obj->show_in_nav_menus,
			'show_in_admin_bar'   => (bool) $obj->show_in_admin_bar,
			'show_in_rest'        => (bool) $obj->show_in_rest,
			'rest_base'           => $obj->rest_base,           // string|false.
			'rest_namespace'      => $obj->rest_namespace,
			'menu_position'       => $obj->menu_position,       // int|null.
			'menu_icon'           => $obj->menu_icon,
			'capability_type'     => $obj->capability_type,
			'map_meta_cap'        => (bool) $obj->map_meta_cap,
			'supports'            => array_keys( get_all_post_type_supports( $slug ) ),
			'has_archive'         => $obj->has_archive,         // string|bool – formtreu.
			'rewrite'             => $obj->rewrite,             // array|bool.
			'query_var'           => $obj->query_var,           // string|bool.
			'can_export'          => (bool) $obj->can_export,
			'delete_with_user'    => $obj->delete_with_user,    // bool|null.
			'taxonomies'          => get_object_taxonomies( $slug ),
		);

		/**
		 * Justiert eine erfasste Definition vor dem Speichern (BRIEF § 5).
		 *
		 * @since 0.1.0
		 *
		 * @param array  $args   Erfasste Args.
		 * @param string $slug   Slug.
		 * @param string $object 'post_type'|'taxonomy'.
		 */
		return apply_filters( 'depeur_food/content_types/import_definition', $args, $slug, 'post_type' );
	}

	/**
	 * Erfasst die kuratierten Taxonomie-Args aus dem Live-Objekt (§ 4.3).
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Taxonomie-Slug.
	 * @return array
	 */
	public function capture_taxonomy( string $slug ): array {
		$tax = get_taxonomy( $slug );
		if ( false === $tax ) {
			return array();
		}

		$args = array(
			'labels'             => (array) $tax->labels,
			'description'        => (string) $tax->description,
			'public'             => (bool) $tax->public,         // ⚠️ herkunft=false – erhalten.
			'publicly_queryable' => (bool) $tax->publicly_queryable,
			'hierarchical'       => (bool) $tax->hierarchical,
			'show_ui'            => (bool) $tax->show_ui,
			'show_in_menu'       => (bool) $tax->show_in_menu,
			'show_in_nav_menus'  => (bool) $tax->show_in_nav_menus,
			'show_in_rest'       => (bool) $tax->show_in_rest,
			'rest_base'          => $tax->rest_base,
			'rest_namespace'     => $tax->rest_namespace,
			'show_admin_column'  => (bool) $tax->show_admin_column,
			'show_tagcloud'      => (bool) $tax->show_tagcloud,
			'show_in_quick_edit' => (bool) $tax->show_in_quick_edit,
			'rewrite'            => $tax->rewrite,
			'query_var'          => $tax->query_var,
			'object_type'        => array_values( (array) $tax->object_type ),
		);

		return apply_filters( 'depeur_food/content_types/import_definition', $args, $slug, 'taxonomy' );
	}

	/**
	 * Übernimmt gewählte Slugs in den Store (Args serverseitig aus dem Live-Objekt, § 7.3).
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $post_type_slugs Gewählte CPT-Slugs.
	 * @param string[] $taxonomy_slugs  Gewählte Taxonomie-Slugs.
	 * @return array{post_types:int,taxonomies:int} Anzahl übernommener Definitionen.
	 */
	public function import( array $post_type_slugs, array $taxonomy_slugs ): array {
		$imported = array(
			'post_types' => 0,
			'taxonomies' => 0,
		);

		foreach ( $post_type_slugs as $slug ) {
			$slug = (string) $slug;
			if ( '' === $slug || $this->is_denied( $slug ) ) {
				continue;
			}
			$args = $this->capture_post_type( $slug );
			if ( ! empty( $args ) && $this->store->save_entry( 'post_type', $slug, $args ) ) {
				$imported['post_types']++;
			}
		}

		foreach ( $taxonomy_slugs as $slug ) {
			$slug = (string) $slug;
			if ( '' === $slug || $this->is_denied( $slug ) ) {
				continue;
			}
			$args = $this->capture_taxonomy( $slug );
			if ( ! empty( $args ) && $this->store->save_entry( 'taxonomy', $slug, $args ) ) {
				$imported['taxonomies']++;
			}
		}

		return $imported;
	}

	/**
	 * True, wenn der Slug per Präfix-Denylist ausgeschlossen ist (§ 9.12).
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Zu prüfender Slug.
	 * @return bool
	 */
	private function is_denied( string $slug ): bool {
		foreach ( self::DENY_PREFIXES as $prefix ) {
			if ( 0 === strpos( $slug, $prefix ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Liest ein sprechendes Einzel-Label aus einem Post-Type-/Taxonomie-Objekt.
	 *
	 * @since 0.1.0
	 *
	 * @param object $obj  WP_Post_Type|WP_Taxonomy.
	 * @param string $slug Fallback-Slug.
	 * @return string
	 */
	private function singular_label( $obj, string $slug ): string {
		if ( isset( $obj->labels->singular_name ) && '' !== $obj->labels->singular_name ) {
			return (string) $obj->labels->singular_name;
		}
		if ( isset( $obj->label ) && '' !== $obj->label ) {
			return (string) $obj->label;
		}
		return $slug;
	}
}
