<?php
/**
 * Archive_Injector — speist die unterstützten Inhaltstypen in Standard-Archiv-Abfragen ein.
 *
 * ZWECK (WOFÜR): Standard-Archive (Tag, Kategorie, eigene Taxonomien, Autor, Datum) zeigen von
 * Haus aus nur `post`. Auf einer Seite mit Cocktails/Trinkspielen/… erwartet man aber, dass diese
 * Inhaltstypen dort MIT auftauchen (z. B. alle Beiträge UND Cocktails mit demselben Tag). Genau
 * das macht dieses Modul: es erweitert die Haupt-Abfrage solcher Archive um die vom Plugin
 * unterstützten Post-Types.
 *
 * WARUM PLUGIN (nicht Theme): Das ist Query-Logik, kein Layout. Sie hängt an der einen
 * post-type-agnostischen Quelle der Wahrheit (ADR-4, depeur_food()->get_supported_post_types()) —
 * dieselbe Liste, die alle Feature-Module nutzen. Im Alt-Theme lag das in der functions.php
 * (alkipedia_add_cpt_to_tag_archives + wpse107459_add_cpt_author); hier ist es sauber toggelbar.
 *
 * WARUM NICHT im Modul content-types: content-types DEFINIERT/registriert Typen (und ist beim
 * Pivot geparkt, weil ACF die CPTs besitzt). Dieses Modul REGISTRIERT nichts — es berücksichtigt
 * nur bereits registrierte Typen in Abfragen. Zwei verschiedene Aufgaben, darum getrennt.
 *
 * SICHERHEIT/UMFANG: Nur Lese-Abfragen im Frontend, nur die Haupt-Abfrage (is_main_query),
 * nie im Admin, nie in Feeds (Syndication bleibt unverändert). Keine Schreibvorgänge.
 *
 * @package Depeur\Food\Modules\ArchiveTypes\Query
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\ArchiveTypes\Query;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erweitert Standard-Archiv-Abfragen um die unterstützten Inhaltstypen.
 *
 * @since 0.3.0
 */
final class Archive_Injector {

	/**
	 * Modul-Slug (Options-Key der Settings). Entspricht dem Ordnernamen.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const MODULE_SLUG = 'archive-types';

	/**
	 * Verdrahtet die Query-Erweiterung.
	 *
	 * pre_get_posts läuft nach parse_query, aber vor dem eigentlichen DB-Zugriff — der richtige
	 * Ort, um post_type der Haupt-Abfrage zu setzen.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'inject' ) );
	}

	/**
	 * Setzt auf passenden Archiven den post_type auf die unterstützten Typen.
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_Query $query Die (Haupt-)Abfrage.
	 * @return void
	 */
	public function inject( $query ): void {
		// Nur Frontend-Haupt-Abfrage; Admin-Listen und Feeds (RSS) bewusst unangetastet.
		if ( is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_feed() ) {
			return;
		}

		// Post-type-agnostische Quelle der Wahrheit (ADR-4). Ohne Plugin-Helper → nichts tun.
		if ( ! function_exists( 'depeur_food' ) ) {
			return;
		}
		$supported = depeur_food()->get_supported_post_types();
		if ( empty( $supported ) ) {
			return;
		}

		// Ziel-Typen je nach Archiv-Art bestimmen.
		$types = $this->types_for_archive( $query, $supported );
		if ( null === $types ) {
			return; // Kein von uns behandeltes Archiv.
		}

		/**
		 * Erlaubt das Feintunen der eingespeisten Typen je Archiv (z. B. einen Typ ausnehmen).
		 *
		 * @since 0.3.0
		 *
		 * @param string[]  $types     Ermittelte Post-Type-Slugs.
		 * @param \WP_Query $query     Die Abfrage (für Kontext: is_author/is_tag/…).
		 * @param string[]  $supported Vollständige Liste der unterstützten Typen.
		 */
		$types = apply_filters( 'depeur_food/archive_types/post_types', $types, $query, $supported );

		$types = array_values( array_filter( array_map( 'strval', (array) $types ) ) );
		if ( ! empty( $types ) ) {
			$query->set( 'post_type', $types );
		}
	}

	/**
	 * Ermittelt die einzuspeisenden Typen für die konkrete Archiv-Art.
	 *
	 * - Taxonomie-Archive (Tag/Kategorie/eigene Taxonomie): nur die unterstützten Typen, die diese
	 *   Taxonomie überhaupt führen (sonst würde ein Typ ohne die Taxonomie sinnlos mitgefragt).
	 * - Autor-/Datums-Archive: alle unterstützten Typen (dort gibt es keine Taxonomie-Schranke).
	 * - Alles andere (Startseite, Suche, Single, CPT-eigenes Archiv …): nicht anfassen → null.
	 *
	 * WICHTIG: Query-Methoden ($query->is_tag() …) statt globaler Conditional-Tags — Letztere sind
	 * in pre_get_posts nicht zuverlässig, die Query-eigenen Methoden schon.
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_Query $query     Die Abfrage.
	 * @param string[]  $supported Unterstützte Typen.
	 * @return string[]|null Liste der Typen oder null, wenn das Archiv nicht behandelt wird.
	 */
	private function types_for_archive( \WP_Query $query, array $supported ): ?array {
		// a) Taxonomie-Archive: passende Taxonomie bestimmen.
		$taxonomy = '';
		if ( $query->is_tag() ) {
			$taxonomy = 'post_tag';
		} elseif ( $query->is_category() ) {
			$taxonomy = 'category';
		} elseif ( $query->is_tax() ) {
			$obj = $query->get_queried_object();
			if ( $obj instanceof \WP_Term ) {
				$taxonomy = $obj->taxonomy;
			}
		}

		if ( '' !== $taxonomy ) {
			if ( ! $this->enabled( 'on_taxonomy' ) ) {
				return null; // Taxonomie-Archive per Einstellung deaktiviert.
			}
			// Nur Typen behalten, die diese Taxonomie führen (inkl. des Typs, dem sie „gehört").
			$filtered = array();
			foreach ( $supported as $post_type ) {
				if ( in_array( $taxonomy, get_object_taxonomies( $post_type ), true ) ) {
					$filtered[] = $post_type;
				}
			}
			return $filtered;
		}

		// b) Autor-/Datums-Archive: alle unterstützten Typen (jeweils per Einstellung schaltbar).
		if ( $query->is_author() && $this->enabled( 'on_author' ) ) {
			return $supported;
		}
		if ( $query->is_date() && $this->enabled( 'on_date' ) ) {
			return $supported;
		}

		// c) Kein von uns behandeltes (bzw. aktiviertes) Archiv.
		return null;
	}

	/**
	 * Liest einen Modul-Schalter (Default: an, solange nicht explizit abgeschaltet).
	 *
	 * @since 0.3.0
	 *
	 * @param string $key Settings-Feld-ID (on_taxonomy|on_author|on_date).
	 * @return bool
	 */
	private function enabled( string $key ): bool {
		$option = get_option( SettingsRegistry::option_key( self::MODULE_SLUG ), array() );
		if ( ! is_array( $option ) ) {
			return true;
		}

		// Unset ⇒ Default aktiv; explizit gespeicherter Wert entscheidet.
		return ! array_key_exists( $key, $option ) || ! empty( $option[ $key ] );
	}
}
