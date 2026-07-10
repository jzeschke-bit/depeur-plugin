<?php
/**
 * Provisioning\Fields — meldet die Schema-Engine-Felder an (Meta + ACF-Group).
 *
 * Liest die statischen Kataloge config/fields.php + config/groups.php, bucketet die Felder
 * je interner Group und übergibt jede Gruppe an einen eigenen Field_Provisioner (Core-Shared-
 * Klasse). Dieser hängt Meta-Registrierung (init) und ACF-Group (acf/init) selbst ein.
 *
 * Zwei dynamische Anpassungen gegenüber den statischen Katalogen:
 *   1. Post-Type-Agnostik (ADR-4): die post-Subtypes von `reviewed_by` und die Location der
 *      review-Group kommen aus depeur_food()->get_supported_post_types(), filterbar über
 *      `depeur_food/schema_engine/post_types`.
 *   2. Abschaltbare Social-Box: die Gruppe „author_social" wird nur provisioniert, wenn der
 *      Schalter aktiv ist (Setting `social_profiles`, Default an; Filter
 *      `depeur_food/schema_engine/social_profiles`).
 *
 * KEIN Cross-Module-Import: hängt nur an der Core-Klasse Field_Provisioner + WP.
 *
 * @package Depeur\Food\Modules\SchemaEngine\Provisioning
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Provisioning;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die Field_Provisioner-Instanzen der Schema-Engine.
 *
 * @since 0.2.0
 */
final class Fields {

	/**
	 * Interner Group-Slug der abschaltbaren Social-Box (Schlüssel in groups.php).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const SOCIAL_GROUP = 'author_social';

	/**
	 * Interner Group-Slug der Review-Group (post-type-agnostische Location).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const REVIEW_GROUP = 'review';

	/**
	 * Meta-Key des dynamisch verdrahteten Review-Felds.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const REVIEW_FIELD = 'reviewed_by';

	/**
	 * Modul-Slug (= Ordnername) – bildet den Options-Key depeur_food_{slug} (ADR-1).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Liest die Kataloge und provisioniert je Gruppe einen Field_Provisioner.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (aus module.php via basename( __DIR__ )).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;
		$this->provision();
	}

	/**
	 * Sammelt Felder je Gruppe und startet die Provisioner.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function provision(): void {
		$fields = require dirname( __DIR__ ) . '/config/fields.php';
		$groups = require dirname( __DIR__ ) . '/config/groups.php';

		if ( ! is_array( $fields ) || ! is_array( $groups ) ) {
			return;
		}

		$post_types    = $this->supported_post_types();
		$social_active = $this->social_enabled();

		foreach ( $groups as $group_slug => $group_meta ) {
			// Social-Box abgeschaltet → Gruppe komplett überspringen (kein Meta, kein ACF-Feld).
			if ( self::SOCIAL_GROUP === $group_slug && ! $social_active ) {
				continue;
			}

			$group_fields = $this->fields_for_group( $fields, $group_slug, $post_types );
			if ( empty( $group_fields ) ) {
				continue;
			}

			// Review-Group post-type-agnostisch verorten (ADR-4).
			if ( self::REVIEW_GROUP === $group_slug ) {
				$group_meta['location'] = $this->post_type_location( $post_types );
			}

			new Field_Provisioner( $group_fields, $group_meta );
		}
	}

	/**
	 * Filtert den Feld-Katalog auf eine Gruppe und injiziert dynamische Subtypes.
	 *
	 * @since 0.2.0
	 *
	 * @param array<int,array<string,mixed>> $fields     Voller Feld-Katalog.
	 * @param string                         $group_slug Interner Group-Slug.
	 * @param string[]                       $post_types Unterstützte Post-Types (ADR-4).
	 * @return array<int,array<string,mixed>>
	 */
	private function fields_for_group( array $fields, string $group_slug, array $post_types ): array {
		$out = array();

		foreach ( $fields as $field ) {
			if ( ! isset( $field['group'] ) || $group_slug !== $field['group'] ) {
				continue;
			}

			// reviewed_by: statischen Bootstrap-Subtype durch die echte Live-Liste ersetzen.
			if ( isset( $field['name'] ) && self::REVIEW_FIELD === $field['name'] ) {
				$field['subtypes']['post'] = $post_types;
			}

			$out[] = $field;
		}

		return $out;
	}

	/**
	 * Liefert die unterstützten Post-Types, durch den Modul-Filter gereicht (ADR-4).
	 *
	 * @since 0.2.0
	 *
	 * @return string[] Mindestens array( 'post' ) (Core-Garantie), leere Rückgabe abgesichert.
	 */
	private function supported_post_types(): array {
		$types = depeur_food()->get_supported_post_types();

		/**
		 * Filtert die Post-Types, für die das Review-Feld (reviewed_by) registriert wird.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $types Unterstützte Post-Types (Default: Core-Liste).
		 */
		$types = apply_filters( 'depeur_food/schema_engine/post_types', $types );

		if ( ! is_array( $types ) || empty( $types ) ) {
			return array( 'post' );
		}

		return array_values( array_unique( array_map( 'strval', $types ) ) );
	}

	/**
	 * Baut ACF-Location-Regeln (OR-verknüpft) aus einer Post-Type-Liste.
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $post_types Post-Type-Slugs.
	 * @return array<int,array<int,array<string,string>>>
	 */
	private function post_type_location( array $post_types ): array {
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

		return $location;
	}

	/**
	 * Ist die Autor-Social-Box aktiv? Setting (Default an) + Filter-Override.
	 *
	 * Reihenfolge: gespeicherter Modul-Schalter `social_profiles` (fehlt er, greift der
	 * Default „an"), danach der Filter als finales Wort. So kann der Site-Owner die Box im
	 * Tab abschalten und ein Entwickler sie hart per Code erzwingen/unterdrücken.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	private function social_enabled(): bool {
		$options = get_option( SettingsRegistry::option_key( $this->slug ), array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		// Default an: nur ein ausdrücklich gespeichertes `false` schaltet ab.
		$enabled = ! array_key_exists( 'social_profiles', $options ) || ! empty( $options['social_profiles'] );

		/**
		 * Schaltet die Autor-Social-Profil-Felder (Provisionierung + Editor-UI) an/aus.
		 *
		 * @since 0.2.0
		 *
		 * @param bool $enabled Aktueller Zustand aus dem Modul-Setting (Default true).
		 */
		return (bool) apply_filters( 'depeur_food/schema_engine/social_profiles', $enabled );
	}
}
