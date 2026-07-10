<?php
/**
 * Taxonomy_Provider — registriert die Taxonomien aus dem Store (BRIEF § 4.5).
 *
 * Läuft auf `init` prio 11 (NACH den CPTs auf prio 10, damit die object_type-Verknüpfung
 * greift, § 9.7). Skip-Guard via `taxonomy_exists`. Erhält `public` form-treu je Taxonomie
 * (⚠️ `herkunft` = false, § 4.3) und feuert am Ende die `registered`-Action (§ 5).
 *
 * @package Depeur\Food\Modules\ContentTypes\Registration
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\ContentTypes\Registration;

use Depeur\Food\Modules\ContentTypes\Definitions\Store;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die Taxonomien des Stores.
 *
 * @since 0.1.0
 */
final class Taxonomy_Provider {

	/**
	 * Verdrahtet die Registrierung am init-Hook (prio 11, nach den CPTs).
	 *
	 * did_action-Guard wie im Type_Provider; die Instanziierungs-Reihenfolge in module.php
	 * (Type zuerst) sichert bei bereits laufendem init die CPT-vor-Taxonomie-Ordnung.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->register();
		} else {
			add_action( 'init', array( $this, 'register' ), 11 );
		}
	}

	/**
	 * Registriert alle Store-Taxonomien (Skip-Guard je Slug). Leerer Store = No-Op (§ 3.3).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		$store = new Store();

		if ( $store->is_empty() ) {
			return;
		}

		/**
		 * Filtert die Taxonomie-Definitionen vor der Registrierung (BRIEF § 5).
		 *
		 * @since 0.1.0
		 *
		 * @param array $defs Taxonomie-Definitionen aus dem Store.
		 */
		$defs = apply_filters( 'depeur_food/content_types/taxonomies', $store->get_taxonomies() );

		$registered = array();

		if ( is_array( $defs ) ) {
			foreach ( $defs as $slug => $def ) {
				$slug = (string) $slug;

				// Skip-Guard: nie doppelt registrieren (§ 4.5/§ 9.3).
				if ( '' === $slug || taxonomy_exists( $slug ) ) {
					continue;
				}

				if ( ! is_array( $def ) || empty( $def['labels'] ) && empty( $def['label'] ) ) {
					continue; // Graceful (§ 9.11).
				}

				// object_type ist der 2. register_taxonomy-Parameter, kein arg → herauslösen.
				$object_type = ( isset( $def['object_type'] ) && is_array( $def['object_type'] ) )
					? $def['object_type']
					: array();

				$args = $def;
				unset( $args['object_type'] );

				register_taxonomy( $slug, $object_type, $args );

				$registered[] = $slug;
			}
		}

		// Nur die tatsächlich (von uns oder ACF/Fremd) registrierten CPTs melden.
		$post_types = array_values( array_filter( array_keys( $store->get_post_types() ), 'post_type_exists' ) );

		/**
		 * Feuert, nachdem alle content-types-Registrierungen auf init durch sind (BRIEF § 5).
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $post_types Registrierte CPT-Slugs aus dem Store.
		 * @param string[] $registered In diesem Lauf registrierte Taxonomie-Slugs.
		 */
		do_action( 'depeur_food/content_types/registered', $post_types, $registered );
	}
}
