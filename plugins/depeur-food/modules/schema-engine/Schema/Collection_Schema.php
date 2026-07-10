<?php
/**
 * Collection_Schema — hängt auf Kategorie-/Archiv-Seiten das WPRM-Rezept als CollectionPage ein.
 *
 * Port von `category-schema.php` → `custom_rank_math_json_ld` (Filter rank_math/json_ld):
 * liest das Term-Meta `WPRM` der abgefragten Kategorie, holt die WPRM-Rezept-Metadaten und
 * hängt sie als `recipe` unter die CollectionPage (isPartOf-Verknüpfung). Auf singularem
 * Content wird die WPRM-Rank-Math-Standardintegration erneut angewandt (wie im Legacy).
 *
 * Zusätzlich portiert: die `publishingPrinciples`-Anreicherung am publisher (aus
 * `rank-math.php`) — jedoch OHNE die dort hartkodierte Fremd-Domain. Die URL kommt aus dem
 * Modul-Setting `publishing_principles_url` bzw. dem Filter
 * `depeur_food/schema_engine/publishing_principles`; leer ⇒ übersprungen.
 *
 * Rank Math = Hard-Dependency (E1): ohne aktives Rank Math werden keine Hooks gesetzt.
 * WPRM = Soft-Dependency (E2): jede WPRM-Nutzung ist class_exists-gekapselt (graceful skip).
 *
 * @package Depeur\Food\Modules\SchemaEngine\Schema
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Schema;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\SchemaEngine\Support\Dependencies;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verdrahtet die CollectionPage-/publisher-Anreicherung am rank_math/json_ld-Filter.
 *
 * @since 0.2.0
 */
final class Collection_Schema {

	/**
	 * Modul-Slug (für den Options-Key des publishingPrinciples-Settings).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const MODULE_SLUG = 'schema-engine';

	/**
	 * Setzt die Filter – nur bei aktivem Rank Math (E1, sonst Ruhe).
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		if ( ! Dependencies::rank_math_active() ) {
			return;
		}

		// Prio 99 / 1 Argument wie category-schema.php.
		add_filter( 'rank_math/json_ld', array( $this, 'inject_collection_page' ), 99, 1 );

		// Prio 99 / 2 Argumente wie rank-math.php (publishingPrinciples am publisher).
		add_filter( 'rank_math/json_ld', array( $this, 'add_publishing_principles' ), 99, 2 );
	}

	/**
	 * Hängt auf Archiv-/Kategorie-Seiten das WPRM-Rezept als CollectionPage-Bestandteil ein.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $data Rank-Math-JSON-LD-Daten (Array von Schema-Objekten).
	 * @return mixed Angereicherte Daten.
	 */
	public function inject_collection_page( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		// Nicht-Archiv (singular): WPRM-Rank-Math-Standardintegration erneut anwenden (Legacy).
		if ( ! ( is_archive() || is_category() || is_tag() ) ) {
			if ( class_exists( 'WPRM_Metadata_Rank_Math' ) ) {
				$data = \WPRM_Metadata_Rank_Math::rank_math_json_ld( $data, null );
			}
			return $data;
		}

		// Kategorie-/Archiv-Rezept-ID aus dem Term-Meta (ADR-5: statt ACF get_field('WPRM', …)).
		$term_id   = get_queried_object_id();
		$recipe_id = (int) get_term_meta( $term_id, 'WPRM', true );
		if ( $recipe_id <= 0 || ! Dependencies::wprm_active() ) {
			return $data;
		}

		$metadata = $this->recipe_metadata( $recipe_id );
		if ( empty( $metadata ) ) {
			return $data;
		}

		// Kontext entfernen, damit das eingebettete Rezept nicht mit dem Wurzel-@context kollidiert.
		unset( $metadata['@context'] );

		// isPartOf auf die CollectionPage zeigen lassen, falls Rank Math eine ausgibt.
		$collection_key = $this->find_collection_page_key( $data );
		if ( false !== $collection_key && isset( $data[ $collection_key ]['@id'] ) ) {
			$metadata['isPartOf'] = array( '@id' => $data[ $collection_key ]['@id'] );
		}

		$data['recipe'] = $metadata;

		return $data;
	}

	/**
	 * Ergänzt publisher.publishingPrinciples, wenn eine URL konfiguriert ist.
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $data  Rank-Math-JSON-LD-Daten.
	 * @param mixed $jsonld Rank-Math-JsonLD-Instanz (ungenutzt, Signatur-Treue zu rank-math.php).
	 * @return mixed
	 */
	public function add_publishing_principles( $data, $jsonld = null ) {
		unset( $jsonld ); // Signatur-Parität mit dem Legacy-Filter; Wert nicht benötigt.

		if ( ! is_array( $data ) || ! isset( $data['publisher'] ) || ! is_array( $data['publisher'] ) ) {
			return $data;
		}

		$url = $this->publishing_principles_url();
		if ( '' === $url ) {
			return $data;
		}

		$data['publisher']['publishingPrinciples'] = array( $url );

		return $data;
	}

	/**
	 * Liest + sanitisiert die WPRM-Rezept-Metadaten (alle WPRM-Klassen class_exists-gekapselt).
	 *
	 * @since 0.2.0
	 *
	 * @param int $recipe_id WPRM-Rezept-ID.
	 * @return array Rezept-Metadaten oder leeres Array bei Skip.
	 */
	private function recipe_metadata( int $recipe_id ): array {
		$recipe = \WPRM_Recipe_Manager::get_recipe( $recipe_id );
		if ( ! $recipe || ! class_exists( 'WPRM_Metadata' ) ) {
			return array();
		}
		if ( 'other' === $recipe->type() || ! \WPRM_Metadata::should_output_metadata_for( $recipe->id() ) ) {
			return array();
		}

		$metadata = \WPRM_Metadata::sanitize_metadata( \WPRM_Metadata::get_metadata( $recipe ) );

		return is_array( $metadata ) ? $metadata : array();
	}

	/**
	 * Sucht den Array-Schlüssel des CollectionPage-Objekts in den JSON-LD-Daten.
	 *
	 * @since 0.2.0
	 *
	 * @param array $data JSON-LD-Daten.
	 * @return int|string|false Schlüssel oder false, wenn keine CollectionPage vorliegt.
	 */
	private function find_collection_page_key( array $data ) {
		foreach ( $data as $key => $schema ) {
			if ( is_array( $schema ) && isset( $schema['@type'] ) && 'CollectionPage' === $schema['@type'] ) {
				return $key;
			}
		}

		return false;
	}

	/**
	 * Ermittelt die publishingPrinciples-URL: Modul-Setting, gefiltert.
	 *
	 * @since 0.2.0
	 *
	 * @return string Bereinigte URL oder '' (⇒ Feature übersprungen).
	 */
	private function publishing_principles_url(): string {
		$options = get_option( SettingsRegistry::option_key( self::MODULE_SLUG ), array() );
		$url     = ( is_array( $options ) && isset( $options['publishing_principles_url'] ) ) ? (string) $options['publishing_principles_url'] : '';

		/**
		 * Filtert die publishingPrinciples-URL (publisher-Schema).
		 *
		 * @since 0.2.0
		 *
		 * @param string $url Aus dem Modul-Setting gelesene URL (Default '').
		 */
		$url = (string) apply_filters( 'depeur_food/schema_engine/publishing_principles', $url );

		return '' === $url ? '' : esc_url_raw( $url );
	}
}
