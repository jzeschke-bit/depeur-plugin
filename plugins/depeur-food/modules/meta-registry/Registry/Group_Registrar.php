<?php
/**
 * Group_Registrar — Editor-UI-Registrierung (acf_add_local_field_group).
 *
 * Baut aus der Single-Source-Field-Registry (config/fields.php) + den Gruppen-Metadaten
 * (config/groups.php) die ACF-Field-Groups und registriert sie als „local" Groups. Durch
 * Reuse der Discovery-Keys überschreiben sie die DB-/UI-Groups gleichen Keys (kein
 * Doppel-Render; BRIEF meta-registry § 4.5/§ 9.2). Läuft nur, wenn ACF aktiv ist (§ 9.1).
 *
 * @package Depeur\Food\Modules\MetaRegistry\Registry
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\MetaRegistry\Registry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die ACF-Field-Groups aus der Field-Registry.
 *
 * @since 0.1.0
 */
final class Group_Registrar {

	/**
	 * Verdrahtet die Registrierung am acf/init-Hook.
	 *
	 * Per did_action-Guard (BRIEF § 9.10): ACF feuert acf/init während init (prio 5), der
	 * ModuleManager lädt das Modul aber erst auf init (prio 10) – also NACH acf/init. Ein
	 * add_action('acf/init') liefe dann ins Leere. Daher: schon gefeuert → sofort
	 * registrieren, sonst regulär einhängen.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( did_action( 'acf/init' ) ) {
			$this->register();
		} else {
			add_action( 'acf/init', array( $this, 'register' ) );
		}
	}

	/**
	 * Baut und registriert alle ACF-Field-Groups.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		// ACF-unabhängige Datenschicht (Field_Registrar) läuft getrennt; ohne ACF gibt es
		// nur keine Editor-UI (§ 9.1).
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$fields = require dirname( __DIR__ ) . '/config/fields.php';
		/** This filter is documented in Registry/Field_Registrar.php */
		$fields = apply_filters( 'depeur_food/meta/registry', $fields );

		$groups = require dirname( __DIR__ ) . '/config/groups.php';

		/**
		 * Filtert die ACF-Group-Metadaten (Locations etc.) vor der Registrierung.
		 *
		 * Trägt die Location-Filterbarkeit (BRIEF § 3.4/§ 5): Site/Wizard können Locations
		 * anpassen, ohne den Code zu ändern.
		 *
		 * @since 0.1.0
		 *
		 * @param array $groups Gruppen-Metadaten (keyed nach Group-Slug).
		 */
		$groups = apply_filters( 'depeur_food/meta/groups', $groups );

		if ( ! is_array( $fields ) || ! is_array( $groups ) ) {
			return;
		}

		foreach ( $groups as $slug => $group ) {
			if ( empty( $group['key'] ) ) {
				continue;
			}

			$group_fields = $this->collect_fields( (string) $slug, $fields );

			// Leere Group nicht registrieren (z. B. wenn alle Felder weggefiltert wurden).
			if ( empty( $group_fields ) ) {
				continue;
			}

			acf_add_local_field_group(
				array_merge(
					array(
						'key'      => $group['key'],
						'title'    => isset( $group['title'] ) ? $group['title'] : '',
						'fields'   => $group_fields,
						'location' => isset( $group['location'] ) ? $group['location'] : array(),
					),
					$this->group_defaults( $group )
				)
			);
		}
	}

	/**
	 * Sammelt die ACF-Field-Arrays aller Felder, die zu einer Group gehören.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug   Group-Slug.
	 * @param array  $fields Field-Registry.
	 * @return array
	 */
	private function collect_fields( string $slug, array $fields ): array {
		$collected = array();

		foreach ( $fields as $field ) {
			// Orphan/meta-only überspringen (kein Editor-Feld, § 4.5).
			if ( isset( $field['editor_ui'] ) && false === $field['editor_ui'] ) {
				continue;
			}
			if ( empty( $field['key'] ) || empty( $field['name'] ) ) {
				continue;
			}

			$field_groups = isset( $field['group'] ) ? (array) $field['group'] : array();
			if ( ! in_array( $slug, $field_groups, true ) ) {
				continue;
			}

			$collected[] = $this->build_acf_field( $field, $slug );
		}

		return $collected;
	}

	/**
	 * Baut ein einzelnes ACF-Field-Array aus der Feld-Definition.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $field Feld-Definition.
	 * @param string $slug  Aktueller Group-Slug (für group_overrides).
	 * @return array
	 */
	private function build_acf_field( array $field, string $slug ): array {
		$acf_type = isset( $field['acf_type'] ) ? $field['acf_type'] : 'text';

		$acf_field = array(
			'key'   => $field['key'],
			'label' => isset( $field['label'] ) ? $field['label'] : $field['name'],
			'name'  => $field['name'],
			// email rendert live als Text-Feld (§ 4.3); sonst acf_type == ACF-Feldtyp.
			'type'  => ( 'email' === $acf_type ) ? 'text' : $acf_type,
		);

		if ( ! empty( $field['acf'] ) && is_array( $field['acf'] ) ) {
			$acf_field = array_merge( $acf_field, $field['acf'] );
		}

		// Pro-Group-Override (z. B. Newsletter show_app_promo Default 0 auf CPT, § 4.5).
		if ( isset( $field['group_overrides'][ $slug ] ) && is_array( $field['group_overrides'][ $slug ] ) ) {
			$acf_field = array_merge( $acf_field, $field['group_overrides'][ $slug ] );
		}

		return $acf_field;
	}

	/**
	 * Gemeinsame ACF-Group-Defaults (Position aus der Group-Definition).
	 *
	 * @since 0.1.0
	 *
	 * @param array $group Group-Metadaten.
	 * @return array
	 */
	private function group_defaults( array $group ): array {
		return array(
			'menu_order'            => 0,
			'position'              => isset( $group['position'] ) ? $group['position'] : 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
		);
	}
}
