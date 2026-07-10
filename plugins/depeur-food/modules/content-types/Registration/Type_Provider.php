<?php
/**
 * Type_Provider — registriert die CPTs aus dem Store (BRIEF § 4.5).
 *
 * Iteriert auf `init` (prio 10) über die Store-Definitionen und ruft register_post_type()
 * args-treu auf. Der Skip-Guard (`post_type_exists`) verhindert Doppel-Registrierung und
 * trägt die ACF-Koexistenz: solange ACF (oder ein anderes Plugin) den Typ registriert,
 * ruht dieser Provider; nach ACF-Abschaltung übernimmt er (§ 8/§ 9.3). Besitzt zusätzlich
 * den signaturgesteuerten Einmal-Flush der Rewrite-Rules (§ 9.6).
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
 * Registriert die Custom Post Types des Stores.
 *
 * @since 0.1.0
 */
final class Type_Provider {

	/**
	 * Option, in der die zuletzt geflushte Rewrite-Signatur liegt (autoload=no – nur beim
	 * Flush-Vergleich gelesen).
	 *
	 * @var string
	 */
	const FLUSH_SIG_OPTION = 'depeur_food_content-types_flush_sig';

	/**
	 * Verdrahtet Registrierung + Flush am init-Hook.
	 *
	 * did_action-Guard: Der ModuleManager lädt aktive Module bereits AUF init (prio 10) –
	 * läuft init also schon, sofort registrieren (analog meta-registry § 9.10). Der Flush
	 * hängt an prio 12 (nach den Taxonomien auf prio 11).
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->register();
			$this->maybe_flush();
		} else {
			add_action( 'init', array( $this, 'register' ), 10 );
			add_action( 'init', array( $this, 'maybe_flush' ), 12 );
		}
	}

	/**
	 * Registriert alle Store-CPTs (Skip-Guard je Slug). Leerer Store = No-Op (§ 3.3).
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
		 * Filtert die CPT-Definitionen vor der Registrierung (BRIEF § 5).
		 *
		 * Site/Wizard/Modul kann Definitionen ergänzen, überschreiben oder entfernen.
		 * Erwartet das Store-Format (slug => args).
		 *
		 * @since 0.1.0
		 *
		 * @param array $defs CPT-Definitionen aus dem Store.
		 */
		$defs = apply_filters( 'depeur_food/content_types/post_types', $store->get_post_types() );

		if ( ! is_array( $defs ) ) {
			return;
		}

		foreach ( $defs as $slug => $args ) {
			$slug = (string) $slug;

			// Skip-Guard: nie doppelt registrieren (ACF/Fremd gewinnt, § 4.5/§ 9.3).
			if ( '' === $slug || post_type_exists( $slug ) ) {
				continue;
			}

			// Graceful Degradation: unvollständiger Eintrag ohne Labels wird übersprungen,
			// kein Fatal/Notice im Hot-Path (§ 9.11).
			if ( ! is_array( $args ) || ( empty( $args['labels'] ) && empty( $args['label'] ) ) ) {
				continue;
			}

			register_post_type( $slug, $args );
		}
	}

	/**
	 * Flusht die Rewrite-Rules genau dann einmalig, wenn sich die rewrite-relevante
	 * Store-Signatur geändert hat (§ 8/§ 9.6). NIE ein Flush pro Request (§ 10).
	 *
	 * Der Flush selbst wird auf `shutdown` verlegt, damit er nach der vollständigen
	 * Registrierung (CPTs + Taxonomien) läuft und die Rules korrekt regeneriert werden.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_flush(): void {
		$store = new Store();

		// Leerer Store registriert nichts → es gibt keine Rules zu flushen.
		if ( $store->is_empty() ) {
			return;
		}

		$signature = md5( (string) wp_json_encode( $store->rewrite_signature() ) );

		if ( get_option( self::FLUSH_SIG_OPTION ) === $signature ) {
			return;
		}

		update_option( self::FLUSH_SIG_OPTION, $signature, false );

		if ( ! has_action( 'shutdown', 'flush_rewrite_rules' ) ) {
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}
	}
}
