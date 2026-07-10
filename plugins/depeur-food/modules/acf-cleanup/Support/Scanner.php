<?php
/**
 * Scanner — klassifiziert die ACF-DB-Feldgruppen für den Migrations-Aufräumer.
 *
 * Reine Lese-/Klassifikationslogik (keine Löschung, keine Ausgabe). Eine DB-Feldgruppe
 * gilt als „covered" (= löschbares Duplikat), wenn ihr Key aktuell als LOKALE (PHP-)
 * Feldgruppe registriert ist — dann überschattet das Plugin sie bereits und die DB-Version
 * ist nur noch Ballast. Alles andere ist „keep".
 *
 * Bewusste Brandmauer: Es wird AUSSCHLIESSLICH der Post-Type `acf-field-group` betrachtet.
 * CPT-/Taxonomie-Definitionen (`acf-post-type`/`acf-taxonomy`) tauchen hier nie auf und
 * können daher gar nicht versehentlich gelöscht werden.
 *
 * @package Depeur\Food\Modules\AcfCleanup\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\AcfCleanup\Support;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klassifiziert ACF-DB-Feldgruppen in „covered" (löschbar) und „keep".
 *
 * @since 0.2.0
 */
final class Scanner {

	/**
	 * Der einzige Post-Type, den dieses Modul je anfasst. NICHT `acf-post-type`/
	 * `acf-taxonomy` — CPT-/Taxonomie-Definitionen bleiben grundsätzlich außen vor.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const GROUP_POST_TYPE = 'acf-field-group';

	/**
	 * Baut den Klassifikations-Report aller ACF-DB-Feldgruppen.
	 *
	 * @since 0.2.0
	 *
	 * @return array{available: bool, covered: array<int, array<string, mixed>>, keep: array<int, array<string, mixed>>}
	 */
	public static function report(): array {
		// ACF ist plugin-weite Hard-Dependency; defensiv trotzdem prüfen.
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array(
				'available' => false,
				'covered'   => array(),
				'keep'      => array(),
			);
		}

		$local_keys = self::local_group_keys();
		$covered    = array();
		$keep       = array();

		foreach ( self::db_groups() as $group ) {
			if ( in_array( $group['key'], $local_keys, true ) ) {
				$covered[] = $group;
			} else {
				$keep[] = $group;
			}
		}

		return array(
			'available' => true,
			'covered'   => $covered,
			'keep'      => $keep,
		);
	}

	/**
	 * Keys aller aktuell LOKAL (PHP/JSON) registrierten Feldgruppen.
	 *
	 * Genau diese Keys überschattet das Plugin — ihre DB-Zwillinge sind Duplikate.
	 *
	 * @since 0.2.0
	 *
	 * @return string[] Eindeutige Group-Keys.
	 */
	public static function local_group_keys(): array {
		$keys = array();

		// acf_get_local_field_groups() liefert ausschließlich die lokal (nicht aus der DB)
		// registrierten Gruppen — die präziseste Quelle. Fallback: die zusammengeführte
		// Sicht nach dem `local`-Flag filtern (ältere ACF-Versionen).
		if ( function_exists( 'acf_get_local_field_groups' ) ) {
			$groups = (array) acf_get_local_field_groups();
			foreach ( $groups as $group ) {
				if ( ! empty( $group['key'] ) ) {
					$keys[] = (string) $group['key'];
				}
			}
		} else {
			$groups = (array) acf_get_field_groups();
			foreach ( $groups as $group ) {
				if ( ! empty( $group['local'] ) && ! empty( $group['key'] ) ) {
					$keys[] = (string) $group['key'];
				}
			}
		}

		return array_values( array_unique( $keys ) );
	}

	/**
	 * Alle ACF-DB-Feldgruppen (publiziert + deaktiviert), key = post_name.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int, array<string, mixed>> Liste mit id/key/title/status/field_count.
	 */
	public static function db_groups(): array {
		$posts = get_posts(
			array(
				'post_type'        => self::GROUP_POST_TYPE,
				'post_status'      => array( 'publish', 'acf-disabled' ),
				'numberposts'      => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		$groups = array();
		foreach ( $posts as $post ) {
			$groups[] = array(
				'id'          => (int) $post->ID,
				'key'         => (string) $post->post_name,
				'title'       => (string) $post->post_title,
				'status'      => (string) $post->post_status,
				'field_count' => self::count_fields( (int) $post->ID ),
			);
		}

		return $groups;
	}

	/**
	 * Zählt die direkten Feld-Posts einer Gruppe (nur zur Anzeige in der Vorschau).
	 *
	 * @since 0.2.0
	 *
	 * @param int $group_id Post-ID der Feldgruppe.
	 * @return int Anzahl direkter Feld-Posts.
	 */
	private static function count_fields( int $group_id ): int {
		$fields = get_posts(
			array(
				'post_type'        => 'acf-field',
				'post_parent'      => $group_id,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		return count( $fields );
	}
}
