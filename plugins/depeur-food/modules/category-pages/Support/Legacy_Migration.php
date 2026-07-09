<?php
/**
 * Legacy_Migration — migriert die alten Rezeptkategorie-Seiten ins category-pages-System.
 *
 * Die Alt-Seiten kuratieren ihre Beiträge über verstreute `rezept_*`-ACF-Felder (je nach
 * Seite `rezept_tag`, `rezept_anlass_tags`, `rezept_art_tags`, …) + `rezeptkategorie_titel`
 * und ein eigenes Theme-Template. Diese Klasse liest ALLE `rezept_*`-Term-IDs einer Seite,
 * gruppiert sie per Taxonomie (Term-IDs sind global eindeutig) und schreibt sie in die neuen
 * Felder `df_catpage_terms_{tax}` / `df_catpage_title` / `df_catpage_enabled`; zusätzlich wird
 * das Alt-Template abgelöst (→ Standard, damit das Plugin per the_content automatisch rendert).
 *
 * Reine Logik (Scan/Backup/Migrate); die UI + das Sicherheits-Gate liegen in Admin/Migration_Page.
 *
 * @package Depeur\Food\Modules\CategoryPages\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Support;

use Depeur\Food\Modules\CategoryPages\Query\Term_Resolver;
use WP_Error;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scannt, sichert und migriert Alt-Rezeptkategorie-Seiten.
 *
 * @since 0.3.0
 */
final class Legacy_Migration {

	/**
	 * Alt-Theme-Template der Rezeptkategorie-Seiten.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const OLD_TEMPLATE = 'single-rezeptkategorie-template.php';

	/**
	 * Legacy-Titel-Meta.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const TITLE_META = 'rezeptkategorie_titel';

	/**
	 * Backup-Unterordner in uploads/.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const BACKUP_SUBDIR = 'depeur-food-backups';

	/**
	 * Scannt Kandidaten-Seiten und liefert Vorschau-Zeilen.
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function scan(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		$rows = array();
		foreach ( $ids as $id ) {
			$id         = (int) $id;
			$term_ids   = self::legacy_term_ids( $id );
			$title      = (string) get_post_meta( $id, self::TITLE_META, true );
			$is_old_tpl = ( self::OLD_TEMPLATE === (string) get_post_meta( $id, '_wp_page_template', true ) );

			// Kandidat, wenn Alt-Template ODER Legacy-Titel ODER kuratierte Terms vorhanden.
			if ( empty( $term_ids ) && '' === $title && ! $is_old_tpl ) {
				continue;
			}

			$grouped = Term_Resolver::group_by_taxonomy( $term_ids );

			$rows[] = array(
				'id'           => $id,
				'title'        => get_the_title( $id ),
				'grouped'      => $grouped,
				'terms_label'  => self::describe_terms( $grouped ),
				'new_title'    => ( '' !== $title ) ? self::related_heading( $title ) : '',
				'old_template' => $is_old_tpl,
				'already'      => (bool) get_post_meta( $id, 'df_catpage_enabled', true ),
			);
		}

		return $rows;
	}

	/**
	 * Migriert mehrere Seiten; liefert die Anzahl erfolgreich migrierter Seiten.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int> $ids Seiten-IDs.
	 * @return int
	 */
	public static function migrate_all( array $ids ): int {
		$done = 0;
		foreach ( $ids as $id ) {
			if ( self::migrate_page( (int) $id ) ) {
				++$done;
			}
		}

		return $done;
	}

	/**
	 * Migriert eine einzelne Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return bool
	 */
	public static function migrate_page( int $page_id ): bool {
		if ( $page_id < 1 || 'page' !== get_post_type( $page_id ) ) {
			return false;
		}

		$grouped = Term_Resolver::group_by_taxonomy( self::legacy_term_ids( $page_id ) );

		// Terms je Taxonomie in die neuen Felder (ACF-sauber via update_field, sonst Meta).
		foreach ( $grouped as $taxonomy => $term_ids ) {
			self::write_field( 'field_df_catpage_tax_' . $taxonomy, Taxonomies::meta_key( $taxonomy ), array_map( 'intval', $term_ids ), $page_id );
		}

		// Legacy-Titel wird die H2-Vorschau-Überschrift (NICHT die H1) — die H1 bleibt der
		// Seitentitel. Dabei die Legacy-Optik „Weitere {Titel} die dir gefallen könnten"
		// anwenden (Jonas-Korrektur; Prä-/Postfix über related_heading() filterbar).
		$title = (string) get_post_meta( $page_id, self::TITLE_META, true );
		if ( '' !== $title ) {
			self::write_field( 'field_df_catpage_related_heading', 'df_catpage_related_heading', self::related_heading( $title ), $page_id );
		}

		// Als Kategorie-Seite aktivieren.
		self::write_field( 'field_df_catpage_enabled', 'df_catpage_enabled', 1, $page_id );

		// Alt-Template ablösen → Standard (Plugin rendert per the_content automatisch).
		if ( self::OLD_TEMPLATE === (string) get_post_meta( $page_id, '_wp_page_template', true ) ) {
			delete_post_meta( $page_id, '_wp_page_template' );
		}

		return true;
	}

	/**
	 * Sichert die betroffenen Seiten (volle Meta) als JSON, bevor migriert wird.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int> $ids Seiten-IDs.
	 * @return string|WP_Error Pfad oder Fehler.
	 */
	public static function backup( array $ids ) {
		$data = array(
			'generated' => current_time( 'mysql' ),
			'site'      => home_url(),
			'note'      => 'Rezeptkategorie-Seiten-Migration: Meta-Snapshot vor der Migration.',
			'pages'     => array(),
		);

		foreach ( $ids as $id ) {
			$id                    = (int) $id;
			$data['pages'][ $id ] = array(
				'title'    => get_the_title( $id ),
				'template' => get_post_meta( $id, '_wp_page_template', true ),
				'meta'     => get_post_meta( $id ),
			);
		}

		return self::write_backup( $data );
	}

	/**
	 * Stellt den jüngsten Migrations-Backup wieder her (macht die Migration rückgängig).
	 *
	 * @since 0.3.0
	 *
	 * @return array{restored: int, file: string}|WP_Error
	 */
	public static function restore_latest() {
		$file = self::latest_backup_path();
		if ( '' === $file ) {
			return new WP_Error( 'df_restore_none', __( 'Kein Migrations-Backup gefunden.', 'depeur-food' ) );
		}

		$json = self::read_file( $file );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['pages'] ) || ! is_array( $data['pages'] ) ) {
			return new WP_Error( 'df_restore_parse', __( 'Backup ist leer oder unlesbar.', 'depeur-food' ) );
		}

		$restored = 0;
		foreach ( $data['pages'] as $id => $page ) {
			$id = (int) $id;
			if ( $id < 1 || ! is_array( $page ) ) {
				continue;
			}
			self::restore_page( $id, $page );
			++$restored;
		}

		flush_rewrite_rules( false );

		return array(
			'restored' => $restored,
			'file'     => basename( $file ),
		);
	}

	/**
	 * Ob überhaupt ein Backup zum Wiederherstellen existiert.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function has_backup(): bool {
		return '' !== self::latest_backup_path();
	}

	/**
	 * Listet die vorhandenen Migrations-Backups (Name + Größe), neueste zuerst.
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array{name: string, size: int}>
	 */
	public static function list_backups(): array {
		$files = self::backup_files();
		if ( empty( $files ) ) {
			return array();
		}
		rsort( $files ); // Zeitgestempelte Namen: neueste zuerst.

		$out = array();
		foreach ( $files as $file ) {
			$size  = @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Datei kann zwischenzeitlich weg sein.
			$out[] = array(
				'name' => basename( $file ),
				'size' => (int) ( false === $size ? 0 : $size ),
			);
		}

		return $out;
	}

	/**
	 * Löscht ALLE Migrations-Backups; liefert die Anzahl gelöschter Dateien.
	 *
	 * @since 0.3.0
	 *
	 * @return int
	 */
	public static function delete_backups(): int {
		$files = self::backup_files();
		if ( empty( $files ) ) {
			return 0;
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return 0;
		}

		$deleted = 0;
		foreach ( $files as $file ) {
			if ( $wp_filesystem->delete( $file ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Liefert die Pfade aller Migrations-Backup-Dateien.
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, string>
	 */
	private static function backup_files(): array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return array();
		}
		$pattern = trailingslashit( $uploads['basedir'] ) . self::BACKUP_SUBDIR . '/rezeptkategorie-migration-*.json';
		$files   = glob( $pattern );

		return is_array( $files ) ? $files : array();
	}

	/**
	 * Setzt eine Seite auf ihren Backup-Zustand zurück (nur Migrations-Keys + Template).
	 *
	 * @since 0.3.0
	 *
	 * @param int                  $page_id Seiten-ID.
	 * @param array<string, mixed> $page    Backup-Eintrag der Seite (meta + template).
	 * @return void
	 */
	private static function restore_page( int $page_id, array $page ): void {
		$backup_meta = ( isset( $page['meta'] ) && is_array( $page['meta'] ) ) ? $page['meta'] : array();

		// Alle df_catpage_-Keys (inkl. ACF-`_`-Refs) auf den Backup-Stand bringen.
		foreach ( get_post_meta( $page_id ) as $key => $values ) {
			if ( 0 !== strpos( ltrim( $key, '_' ), 'df_catpage_' ) ) {
				continue;
			}
			if ( isset( $backup_meta[ $key ][0] ) ) {
				update_post_meta( $page_id, $key, maybe_unserialize( $backup_meta[ $key ][0] ) );
			} else {
				delete_post_meta( $page_id, $key );
			}
		}

		// Alt-Template wiederherstellen (oder entfernen, falls im Backup keins war).
		$template = isset( $page['template'] ) ? (string) $page['template'] : '';
		if ( '' !== $template ) {
			update_post_meta( $page_id, '_wp_page_template', $template );
		} else {
			delete_post_meta( $page_id, '_wp_page_template' );
		}
	}

	/**
	 * Pfad des jüngsten Migrations-Backups (leer, wenn keins existiert).
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private static function latest_backup_path(): string {
		$files = self::backup_files();
		if ( empty( $files ) ) {
			return '';
		}
		sort( $files ); // Zeitgestempelte Namen sortieren chronologisch.

		return (string) end( $files );
	}

	/**
	 * Liest eine Datei via WP_Filesystem.
	 *
	 * @since 0.3.0
	 *
	 * @param string $file Absoluter Pfad.
	 * @return string|WP_Error
	 */
	private static function read_file( string $file ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return new WP_Error( 'df_restore_fs', __( 'Dateisystem-Zugriff nicht verfügbar.', 'depeur-food' ) );
		}
		$content = $wp_filesystem->get_contents( $file );
		if ( false === $content ) {
			return new WP_Error( 'df_restore_read', __( 'Backup-Datei konnte nicht gelesen werden.', 'depeur-food' ) );
		}

		return (string) $content;
	}

	/**
	 * Wendet die Legacy-Optik „Weitere {Titel} die dir gefallen könnten" an (filterbar).
	 *
	 * @since 0.3.0
	 *
	 * @param string $title Legacy-Kategorie-Titel.
	 * @return string
	 */
	public static function related_heading( string $title ): string {
		/**
		 * Filtert die H2-Überschrift-Vorlage der Migration (%s = Legacy-Titel).
		 *
		 * @since 0.3.0
		 *
		 * @param string $template sprintf-Vorlage mit einem %s.
		 */
		$template = (string) apply_filters(
			'depeur_food/category_pages/migration_heading_template',
			/* translators: %s: der alte Kategorie-Titel, z. B. „Weihnachtscocktails". */
			__( 'Weitere %s die dir gefallen könnten', 'depeur-food' )
		);

		return sprintf( $template, $title );
	}

	/**
	 * Sammelt alle Term-IDs aus den `rezept_*`-Meta-Feldern einer Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return array<int>
	 */
	private static function legacy_term_ids( int $page_id ): array {
		$ids = array();

		foreach ( get_post_meta( $page_id ) as $key => $values ) {
			// `rezept_*` (mit Unterstrich) = die Term-Felder; `rezeptkategorie_titel` fällt raus.
			if ( 0 !== strpos( $key, 'rezept_' ) ) {
				continue;
			}
			foreach ( (array) $values as $raw ) {
				$value = maybe_unserialize( $raw );
				foreach ( (array) $value as $candidate ) {
					$term_id = (int) $candidate;
					if ( $term_id > 0 ) {
						$ids[] = $term_id;
					}
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Schreibt einen Feldwert via ACF (falls verfügbar), sonst via Post-Meta.
	 *
	 * update_field pflegt den ACF-Feldkey-Verweis mit, damit der Editor den Wert korrekt zeigt.
	 *
	 * @since 0.3.0
	 *
	 * @param string $field_key ACF-Feldkey.
	 * @param string $meta_key  Meta-Key (Fallback).
	 * @param mixed  $value     Wert.
	 * @param int    $page_id   Seiten-ID.
	 * @return void
	 */
	private static function write_field( string $field_key, string $meta_key, $value, int $page_id ): void {
		if ( function_exists( 'update_field' ) ) {
			update_field( $field_key, $value, $page_id );
			return;
		}
		update_post_meta( $page_id, $meta_key, $value );
	}

	/**
	 * Baut eine lesbare Beschreibung der gruppierten Terms für die Vorschau.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, array<int>> $grouped Gruppierte Terms.
	 * @return string
	 */
	private static function describe_terms( array $grouped ): string {
		if ( empty( $grouped ) ) {
			return '—';
		}

		$parts = array();
		foreach ( $grouped as $taxonomy => $term_ids ) {
			$names = array();
			foreach ( $term_ids as $term_id ) {
				$term = get_term( (int) $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$names[] = $term->name;
				}
			}
			$parts[] = $taxonomy . ': ' . ( empty( $names ) ? '?' : implode( ', ', $names ) );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * Schreibt die Backup-Datei (WP_Filesystem).
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, mixed> $data Zu sichernde Struktur.
	 * @return string|WP_Error
	 */
	private static function write_backup( array $data ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'df_migrate_uploads', (string) $uploads['error'] );
		}

		$dir = trailingslashit( $uploads['basedir'] ) . self::BACKUP_SUBDIR;
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'df_migrate_mkdir', __( 'Backup-Verzeichnis konnte nicht angelegt werden.', 'depeur-food' ) );
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error( 'df_migrate_json', __( 'Backup konnte nicht serialisiert werden.', 'depeur-food' ) );
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return new WP_Error( 'df_migrate_fs', __( 'Dateisystem-Zugriff nicht verfügbar.', 'depeur-food' ) );
		}

		$file = trailingslashit( $dir ) . 'rezeptkategorie-migration-' . gmdate( 'Ymd-His' ) . '.json';
		if ( ! $wp_filesystem->put_contents( $file, $json, FS_CHMOD_FILE ) ) {
			return new WP_Error( 'df_migrate_write', __( 'Backup-Datei konnte nicht geschrieben werden.', 'depeur-food' ) );
		}

		return $file;
	}
}
