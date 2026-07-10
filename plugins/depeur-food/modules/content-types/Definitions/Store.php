<?php
/**
 * Store — Definitions-Persistenz für content-types.
 *
 * Einziger Zugriffspunkt auf die Plugin-Option `depeur_food_content-types` (ADR-1,
 * autoload=yes). Hält die CPT-/Taxonomie-Definitionen pro Site (BRIEF § 4.1) und trägt
 * die harte Invariante technisch: leerer/fehlender Store ⇒ die Provider registrieren
 * NICHTS (§ 3.3). Sanitisiert Einträge auf eine kuratierte, serialisierbare Key-Allowlist
 * (§ 4.2/§ 4.3) und schließt Callbacks/Closures aus (§ 7.3), damit die Option sauber in
 * der DB liegt.
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
 * Liest/schreibt den Definitions-Store und normalisiert Einträge.
 *
 * @since 0.1.0
 */
final class Store {

	/**
	 * Options-Key (ADR-1). MUSS `SettingsRegistry::option_key( 'content-types' )` entsprechen,
	 * damit der read-only Diagnose-Tab denselben Store liest (BRIEF § 6). Bindestrich-Form
	 * wie bei meta-registry.
	 *
	 * @var string
	 */
	const OPTION = 'depeur_food_content-types';

	/**
	 * Transient-Key des Orphan-Detektors (§ 3.4/§ 9.9). Als Konstante hier, damit der Store
	 * ihn bei jedem Schreibvorgang invalidieren kann, ohne die (später gebaute) Detektor-
	 * Klasse zu importieren.
	 *
	 * @var string
	 */
	const ORPHAN_TRANSIENT = 'depeur_food_content_types_orphan_check';

	/**
	 * Kuratierte Allowlist der CPT-Args (= die von ACF/CPT-UI exponierten Stellschrauben,
	 * BRIEF § 4.2). Bewusst OHNE Callback-Keys (z. B. register_meta_box_cb) → Store bleibt
	 * serialisierbar (§ 7.3).
	 *
	 * @var string[]
	 */
	const POST_TYPE_KEYS = array(
		'labels',
		'description',
		'public',
		'publicly_queryable',
		'exclude_from_search',
		'hierarchical',
		'show_ui',
		'show_in_menu',
		'show_in_nav_menus',
		'show_in_admin_bar',
		'show_in_rest',
		'rest_base',
		'rest_namespace',
		'menu_position',
		'menu_icon',
		'capability_type',
		'map_meta_cap',
		'supports',
		'has_archive',
		'rewrite',
		'query_var',
		'can_export',
		'delete_with_user',
		'taxonomies',
	);

	/**
	 * Kuratierte Allowlist der Taxonomie-Args (BRIEF § 4.3). `object_type` ist KEIN
	 * register_taxonomy-Arg, sondern der zweite Funktions-Parameter — wird hier trotzdem
	 * als Definitions-Key geführt (welche CPTs die Taxonomie trägt) und vom Provider
	 * ausgelesen.
	 *
	 * @var string[]
	 */
	const TAXONOMY_KEYS = array(
		'labels',
		'description',
		'public',
		'publicly_queryable',
		'hierarchical',
		'show_ui',
		'show_in_menu',
		'show_in_nav_menus',
		'show_in_rest',
		'rest_base',
		'rest_namespace',
		'show_admin_column',
		'show_tagcloud',
		'show_in_quick_edit',
		'rewrite',
		'query_var',
		'object_type',
	);

	/**
	 * Liefert den vollständigen, normalisierten Store.
	 *
	 * Fehlt/kaputt ⇒ leere Struktur (Invariante § 3.3 – KEIN Default-Seed in der Option).
	 *
	 * @since 0.1.0
	 *
	 * @return array{version:int,post_types:array,taxonomies:array}
	 */
	public function get(): array {
		$raw = get_option( self::OPTION );

		if ( ! is_array( $raw ) ) {
			return $this->empty_store();
		}

		return array(
			'version'    => isset( $raw['version'] ) ? (int) $raw['version'] : 1,
			'post_types' => ( isset( $raw['post_types'] ) && is_array( $raw['post_types'] ) ) ? $raw['post_types'] : array(),
			'taxonomies' => ( isset( $raw['taxonomies'] ) && is_array( $raw['taxonomies'] ) ) ? $raw['taxonomies'] : array(),
		);
	}

	/**
	 * CPT-Definitionen (slug => args).
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_post_types(): array {
		return $this->get()['post_types'];
	}

	/**
	 * Taxonomie-Definitionen (slug => args inkl. object_type).
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_taxonomies(): array {
		return $this->get()['taxonomies'];
	}

	/**
	 * True, wenn der Store nichts zu registrieren enthält (treibt den Provider-No-Op).
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		$store = $this->get();
		return empty( $store['post_types'] ) && empty( $store['taxonomies'] );
	}

	/**
	 * Fügt eine einzelne Definition hinzu/aktualisiert sie (idempotenter Upsert, § 4.5).
	 *
	 * Einziger Schreibpfad in den Store neben restore_seed(); wird vom Importer nach dem
	 * Sicherheits-Gate (Cap/Nonce/Whitelist) aufgerufen (§ 7.3). Die Args werden auf die
	 * serialisierbare Key-Allowlist reduziert.
	 *
	 * @since 0.1.0
	 *
	 * @param string $object post_type|taxonomy.
	 * @param string $slug   Der (bereits gescannte) Slug.
	 * @param array  $args   Aus dem Live-Objekt erfasste Argumente.
	 * @return bool True bei erfolgreichem Schreiben.
	 */
	public function save_entry( string $object, string $slug, array $args ): bool {
		$slug = sanitize_key( $slug );

		if ( '' === $slug ) {
			return false;
		}

		$store = $this->get();

		if ( 'post_type' === $object ) {
			$store['post_types'][ $slug ] = $this->sanitize_entry( $args, self::POST_TYPE_KEYS );
		} elseif ( 'taxonomy' === $object ) {
			$store['taxonomies'][ $slug ] = $this->sanitize_entry( $args, self::TAXONOMY_KEYS );
		} else {
			return false;
		}

		$this->persist( $store );

		return true;
	}

	/**
	 * Stellt die Definitionen aus dem Seed-Pack wieder her (§ 4.4-Recovery).
	 *
	 * Überschreibt die im Seed enthaltenen Slugs; andere Store-Einträge bleiben. Der Seed
	 * ist der Recovery-Boden, keine automatische Quelle — dieser Aufruf ist immer explizit
	 * (Operator-Klick im Importer, § 3.4).
	 *
	 * @since 0.1.0
	 *
	 * @return bool True, wenn ein gültiges Seed-Set übernommen wurde.
	 */
	public function restore_seed(): bool {
		$seed = require dirname( __DIR__ ) . '/config/seed.php';

		if ( ! is_array( $seed ) ) {
			return false;
		}

		$store = $this->get();

		if ( isset( $seed['post_types'] ) && is_array( $seed['post_types'] ) ) {
			foreach ( $seed['post_types'] as $slug => $args ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' !== $slug && is_array( $args ) ) {
					$store['post_types'][ $slug ] = $this->sanitize_entry( $args, self::POST_TYPE_KEYS );
				}
			}
		}

		if ( isset( $seed['taxonomies'] ) && is_array( $seed['taxonomies'] ) ) {
			foreach ( $seed['taxonomies'] as $slug => $args ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' !== $slug && is_array( $args ) ) {
					$store['taxonomies'][ $slug ] = $this->sanitize_entry( $args, self::TAXONOMY_KEYS );
				}
			}
		}

		$this->persist( $store );

		return ! empty( $store['post_types'] ) || ! empty( $store['taxonomies'] );
	}

	/**
	 * Signatur der rewrite-relevanten Store-Args (treibt den Einmal-Flush, BRIEF § 8/§ 9.6).
	 *
	 * Ändert sich nur, wenn ein rewrite-/archive-relevanter Wert im Store dazukommt oder
	 * mutiert (Import/Re-Import/Seed-Restore) → genau dann ist ein flush_rewrite_rules nötig.
	 * Reiner Store-Blick (keine Registrierungs-Abfrage), damit die Berechnung pro Request
	 * billig bleibt.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function rewrite_signature(): array {
		$store = $this->get();
		$sig   = array();

		foreach ( $store['post_types'] as $slug => $args ) {
			$sig[ 'pt:' . $slug ] = array(
				'rewrite'     => isset( $args['rewrite'] ) ? $args['rewrite'] : null,
				'has_archive' => isset( $args['has_archive'] ) ? $args['has_archive'] : null,
			);
		}

		foreach ( $store['taxonomies'] as $slug => $args ) {
			$sig[ 'tax:' . $slug ] = isset( $args['rewrite'] ) ? $args['rewrite'] : null;
		}

		ksort( $sig );

		return $sig;
	}

	/**
	 * Leere Store-Struktur (Invariante § 3.3).
	 *
	 * @since 0.1.0
	 *
	 * @return array{version:int,post_types:array,taxonomies:array}
	 */
	private function empty_store(): array {
		return array(
			'version'    => 1,
			'post_types' => array(),
			'taxonomies' => array(),
		);
	}

	/**
	 * Schreibt den Store (autoload=yes) und invalidiert den Orphan-Transient (§ 9.9).
	 *
	 * @since 0.1.0
	 *
	 * @param array $store Vollständige Store-Struktur.
	 * @return void
	 */
	private function persist( array $store ): void {
		// autoload=yes: der Store wird auf jedem init zur Registrierung gelesen, ist klein
		// und enthält keine Secrets (BRIEF § 4.1/§ 4.5-Bibel).
		update_option( self::OPTION, $store, true );
		delete_transient( self::ORPHAN_TRANSIENT );
	}

	/**
	 * Reduziert einen Eintrag auf die erlaubten, serialisierbaren Keys.
	 *
	 * @since 0.1.0
	 *
	 * @param array    $args        Roh-Argumente.
	 * @param string[] $allowed_keys Erlaubte Keys (POST_TYPE_KEYS/TAXONOMY_KEYS).
	 * @return array
	 */
	private function sanitize_entry( array $args, array $allowed_keys ): array {
		$clean = array();

		foreach ( $allowed_keys as $key ) {
			if ( ! array_key_exists( $key, $args ) ) {
				continue;
			}

			// Callbacks/Closures/Objekte raus → Option bleibt serialisierbar (§ 7.3).
			if ( ! $this->is_serializable( $args[ $key ] ) ) {
				continue;
			}

			$clean[ $key ] = $args[ $key ];
		}

		return $clean;
	}

	/**
	 * Prüft rekursiv, ob ein Wert gefahrlos in eine Option serialisierbar ist.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value Zu prüfender Wert.
	 * @return bool
	 */
	private function is_serializable( $value ): bool {
		if ( is_scalar( $value ) || null === $value ) {
			return true;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( ! $this->is_serializable( $item ) ) {
					return false;
				}
			}
			return true;
		}

		// Objekt, Closure oder Resource → nicht übernehmen.
		return false;
	}
}
