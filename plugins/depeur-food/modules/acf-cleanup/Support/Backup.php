<?php
/**
 * Backup — exportiert ACF-Feldgruppen (inkl. Feld-Posts) als JSON, bevor sie gelöscht werden.
 *
 * Der Export ist eine gezielte, jederzeit lesbare Rückfall-Datei (Gruppen-Post + Meta +
 * rekursiv alle Feld-Posts). Er liegt unter uploads/depeur-food-backups/ und enthält nur
 * Feld-DEFINITIONEN (keine Nutzerdaten/Secrets). Geschrieben wird über WP_Filesystem
 * (WPCS-konform, kein direktes file_put_contents).
 *
 * @package Depeur\Food\Modules\AcfCleanup\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\AcfCleanup\Support;

use WP_Error;
use WP_Post;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serialisiert Feldgruppen samt Feld-Posts in eine JSON-Backup-Datei.
 *
 * @since 0.2.0
 */
final class Backup {

	/**
	 * Unterordner in uploads/ für die Backups.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const SUBDIR = 'depeur-food-backups';

	/**
	 * Exportiert die angegebenen Gruppen (inkl. Feld-Posts) in eine JSON-Datei.
	 *
	 * @since 0.2.0
	 *
	 * @param int[] $group_ids Post-IDs der zu sichernden Feldgruppen.
	 * @return string|WP_Error Absoluter Pfad der Backup-Datei oder Fehler.
	 */
	public static function export( array $group_ids ) {
		$data = array(
			'generated' => current_time( 'mysql' ),
			'site'      => home_url(),
			'note'      => 'ACF field-group backup created by depeur-food acf-cleanup before deletion.',
			'groups'    => array(),
		);

		foreach ( $group_ids as $group_id ) {
			$group = get_post( (int) $group_id );
			if ( ! $group instanceof WP_Post ) {
				continue;
			}
			$data['groups'][] = array(
				'post'   => self::post_to_array( $group ),
				'meta'   => get_post_meta( (int) $group_id ),
				'fields' => self::export_fields( (int) $group_id ),
			);
		}

		return self::write( $data );
	}

	/**
	 * Exportiert die Feld-Posts einer Gruppe/eines Feldes rekursiv (Repeater/Group-Subfelder).
	 *
	 * @since 0.2.0
	 *
	 * @param int $parent_id Post-ID der Gruppe bzw. des Eltern-Feldes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function export_fields( int $parent_id ): array {
		$fields = get_posts(
			array(
				'post_type'        => 'acf-field',
				'post_parent'      => $parent_id,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		$out = array();
		foreach ( $fields as $field ) {
			$out[] = array(
				'post'   => self::post_to_array( $field ),
				'meta'   => get_post_meta( (int) $field->ID ),
				'fields' => self::export_fields( (int) $field->ID ),
			);
		}

		return $out;
	}

	/**
	 * Reduziert einen Post auf die für ACF relevanten, restore-fähigen Felder.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Post $post Der zu serialisierende Post.
	 * @return array<string, mixed>
	 */
	private static function post_to_array( WP_Post $post ): array {
		return array(
			'post_title'   => $post->post_title,
			'post_name'    => $post->post_name,
			'post_content' => $post->post_content,
			'post_excerpt' => $post->post_excerpt,
			'post_status'  => $post->post_status,
			'post_type'    => $post->post_type,
			'menu_order'   => (int) $post->menu_order,
		);
	}

	/**
	 * Schreibt die Daten als JSON-Datei nach uploads/depeur-food-backups/.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, mixed> $data Zu sichernde Struktur.
	 * @return string|WP_Error Absoluter Pfad oder Fehler.
	 */
	private static function write( array $data ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'df_backup_uploads', (string) $uploads['error'] );
		}

		$dir = trailingslashit( $uploads['basedir'] ) . self::SUBDIR;
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error(
				'df_backup_mkdir',
				__( 'Backup-Verzeichnis konnte nicht angelegt werden.', 'depeur-food' )
			);
		}

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error(
				'df_backup_json',
				__( 'Backup konnte nicht serialisiert werden.', 'depeur-food' )
			);
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return new WP_Error(
				'df_backup_fs',
				__( 'Dateisystem-Zugriff nicht verfügbar.', 'depeur-food' )
			);
		}

		// Verzeichnis-Listing unterbinden (defensiv; Inhalt sind nur Definitionen).
		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! $wp_filesystem->exists( $index ) ) {
			$wp_filesystem->put_contents( $index, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		$file = trailingslashit( $dir ) . 'acf-cleanup-' . gmdate( 'Ymd-His' ) . '.json';
		if ( ! $wp_filesystem->put_contents( $file, $json, FS_CHMOD_FILE ) ) {
			return new WP_Error(
				'df_backup_write',
				__( 'Backup-Datei konnte nicht geschrieben werden.', 'depeur-food' )
			);
		}

		return $file;
	}
}
