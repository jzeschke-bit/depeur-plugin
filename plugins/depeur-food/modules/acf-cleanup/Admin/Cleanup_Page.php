<?php
/**
 * Cleanup_Page — geführte Admin-Seite des Migrations-Aufräumers.
 *
 * Zeigt eine Vorschau (Dry-Run) aller ACF-DB-Feldgruppen — welche das Plugin bereits
 * liefert (löschbares Duplikat) und welche bleiben — und bietet einen abgesicherten
 * Lösch-Vorgang: Cap-Check → Nonce → server-seitiger Re-Scan → Backup → Löschen nur der
 * neu berechneten „covered"-Menge (der geposteten Liste wird NICHT vertraut).
 *
 * Sicherheit ist hier bewusst mehrschichtig, weil der Pfad destruktiv ist (§ 12): die Seite
 * selbst ist read-only bis auf den einen Submit; der Submit läuft über admin-post.php mit
 * eigener Nonce und eigener Capability-Prüfung.
 *
 * @package Depeur\Food\Modules\AcfCleanup\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\AcfCleanup\Admin;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Modules\AcfCleanup\Support\Backup;
use Depeur\Food\Modules\AcfCleanup\Support\Scanner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert die Aufräum-Unterseite und verarbeitet den Lösch-POST.
 *
 * @since 0.2.0
 */
final class Cleanup_Page {

	/**
	 * Page-Parameter der Unterseite.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-acf-cleanup';

	/**
	 * Admin-post-Action des Lösch-Vorgangs.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const ACTION = 'depeur_food_acf_cleanup';

	/**
	 * Nonce-Action/-Name des Lösch-Formulars.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const NONCE = 'depeur_food_acf_cleanup_nonce';

	/**
	 * Erforderliche Capability (identisch zum Core-Menü).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * Verdrahtet Menü-Registrierung und POST-Handler.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		// Prio 20: nach dem Core-Menü (AdminMenu::register), damit MENU_SLUG existiert.
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
		// Verschlankung: aus dem Sidebar-Menü ausblenden (nur über den Migrations-Assistenten
		// erreichbar). Prio 999 = nach allen Registrierungen. Seite bleibt über ?page= aufrufbar.
		add_action( 'admin_menu', array( $this, 'hide_from_menu' ), 999 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );

		// Diesen Schritt im zentralen Migrations-Assistenten (Core) anmelden. Kopplung nur über
		// den Hook-STRING (kein Core-Klassen-Import nötig) — s. Migration_Assistant::STEPS_FILTER.
		add_filter( 'depeur_food/migration/steps', array( $this, 'register_migration_step' ) );
	}

	/**
	 * Meldet den ACF-Aufräum-Schritt im Migrations-Assistenten an.
	 *
	 * Status: „offen", solange löschbare Duplikat-Gruppen (covered) existieren; sonst „erledigt".
	 * one_time=true — dies ist ein reines Einmal-Werkzeug; nach Abschluss der Migration darf der
	 * Assistent das Modul acf-cleanup automatisch deaktivieren (Menü-Ballast weg).
	 *
	 * @since 0.2.0
	 *
	 * @param array<int, array<string, mixed>> $steps Bisherige Schritte.
	 * @return array<int, array<string, mixed>> Ergänzte Schritte.
	 */
	public function register_migration_step( array $steps ): array {
		$report  = Scanner::report();
		$covered = isset( $report['covered'] ) && is_array( $report['covered'] ) ? count( $report['covered'] ) : 0;

		if ( $covered > 0 ) {
			$status      = 'todo';
			$status_text = sprintf(
				/* translators: %d: Anzahl löschbarer ACF-Duplikat-Gruppen. */
				_n( '%d ACF-Duplikat-Gruppe entfernbar', '%d ACF-Duplikat-Gruppen entfernbar', $covered, 'depeur-food' ),
				$covered
			);
		} else {
			$status      = 'done';
			$status_text = __( 'Keine löschbaren ACF-Duplikate.', 'depeur-food' );
		}

		$steps[] = array(
			'id'           => 'acf-cleanup',
			'title'        => __( 'ACF-Duplikate aufräumen', 'depeur-food' ),
			'description'  => __( 'Entfernt vom Plugin überschattete ACF-Feldgruppen-Duplikate (mit Backup). Reines Einmal-Werkzeug.', 'depeur-food' ),
			'status'       => $status,
			'status_text'  => $status_text,
			'action_url'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'action_label' => __( 'Öffnen', 'depeur-food' ),
			'module'       => 'acf-cleanup',
			'one_time'     => true,
		);

		return $steps;
	}

	/**
	 * Meldet die Unterseite unter dem Depeur-Food-Menü an.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Migration & Aufräumen', 'depeur-food' ),
			__( 'Migration & Aufräumen', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Blendet die Unterseite aus dem Sidebar-Menü aus (bleibt über ?page= erreichbar).
	 *
	 * Verschlankung: nur über den Migrations-Assistenten (dessen „Öffnen"-Link) erreichbar.
	 * remove_submenu_page entfernt nur den sichtbaren Eintrag; Callback + $_registered_pages
	 * bleiben bestehen, die Seite ist also weiter aufrufbar.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function hide_from_menu(): void {
		remove_submenu_page( AdminMenu::MENU_SLUG, self::PAGE_SLUG );
	}

	/**
	 * Rendert die Vorschau + das Lösch-Formular (read-only bis auf den Submit).
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$report = Scanner::report();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Migration & Aufräumen (ACF)', 'depeur-food' ); ?></h1>

			<?php $this->render_notice(); ?>

			<?php // § 6.2 Admin-UI-Doku: erklären, was passiert, bevor jemand klickt. ?>
			<p style="max-width: 65em;">
				<?php esc_html_e( 'Dieses Werkzeug entfernt nur die ACF-Feldgruppen, die das Plugin bereits selbst per Code anlegt (Duplikate). Deine Feldinhalte bleiben dabei unangetastet – gelöscht wird nur die überflüssige Gruppen-Definition. CPT-/Taxonomie-Definitionen und Gruppen, die das Plugin nicht abdeckt, werden nie angefasst.', 'depeur-food' ); ?>
			</p>
			<p style="max-width: 65em;">
				<strong><?php esc_html_e( 'Vor dem Löschen wird automatisch ein Backup der betroffenen Gruppen (inkl. Felder) als JSON unter uploads/depeur-food-backups/ abgelegt.', 'depeur-food' ); ?></strong>
			</p>

			<?php
			if ( ! $report['available'] ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'ACF ist nicht verfügbar – es kann nichts klassifiziert werden.', 'depeur-food' ) . '</p></div>';
				echo '</div>';
				return;
			}

			$this->render_table(
				__( 'Wird entfernt (vom Plugin bereits übernommen)', 'depeur-food' ),
				$report['covered'],
				'covered'
			);
			$this->render_table(
				__( 'Bleibt (nicht vom Plugin abgedeckt)', 'depeur-food' ),
				$report['keep'],
				'keep'
			);

			$this->render_form( $report['covered'] );
			?>
		</div>
		<?php
	}

	/**
	 * Rendert eine Klassifikations-Tabelle.
	 *
	 * @since 0.2.0
	 *
	 * @param string                           $heading Überschrift.
	 * @param array<int, array<string, mixed>> $groups  Gruppenliste.
	 * @param string                           $kind    'covered' oder 'keep' (nur für die Leer-Meldung).
	 * @return void
	 */
	private function render_table( string $heading, array $groups, string $kind ): void {
		?>
		<h2 style="margin-top: 2em;"><?php echo esc_html( $heading ); ?> <span class="count">(<?php echo (int) count( $groups ); ?>)</span></h2>
		<?php if ( empty( $groups ) ) : ?>
			<p class="description">
				<?php
				echo 'covered' === $kind
					? esc_html__( 'Keine Duplikate gefunden – nichts zu entfernen.', 'depeur-food' )
					: esc_html__( 'Keine weiteren Gruppen.', 'depeur-food' );
				?>
			</p>
			<?php return; ?>
		<?php endif; ?>
		<table class="widefat striped" style="max-width: 65em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Titel (DB)', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Key', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Felder', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'depeur-food' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $groups as $group ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $group['title'] ); ?></td>
						<td><code><?php echo esc_html( (string) $group['key'] ); ?></code></td>
						<td><?php echo (int) $group['field_count']; ?></td>
						<td><?php echo esc_html( (string) $group['status'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Rendert das Lösch-Formular (nur wenn es überhaupt Duplikate gibt).
	 *
	 * @since 0.2.0
	 *
	 * @param array<int, array<string, mixed>> $covered Löschbare Gruppen.
	 * @return void
	 */
	private function render_form( array $covered ): void {
		if ( empty( $covered ) ) {
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1.5em;">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
			<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
			<p>
				<button type="submit" class="button button-primary"
					onclick="return confirm('<?php echo esc_js( __( 'Backup anlegen und die als Duplikat markierten ACF-Gruppen löschen?', 'depeur-food' ) ); ?>');">
					<?php
					printf(
						/* translators: %d: Anzahl der zu löschenden Gruppen. */
						esc_html__( 'Backup + %d Duplikate löschen', 'depeur-food' ),
						(int) count( $covered )
					);
					?>
				</button>
			</p>
		</form>
		<?php
	}

	/**
	 * Verarbeitet den Lösch-POST: Cap → Nonce → Re-Scan → Backup → Löschen.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION, self::NONCE );

		// Der geposteten Liste NICHT vertrauen: die zu löschende Menge server-seitig neu
		// bestimmen. So kann kein manipulierter POST eine nicht-abgedeckte Gruppe treffen.
		$report  = Scanner::report();
		$covered = $report['covered'];

		if ( empty( $covered ) ) {
			$this->redirect( 'noop' );
		}

		// 1) Backup ZUERST — schlägt es fehl, wird nichts gelöscht.
		$ids    = array_map( 'intval', wp_list_pluck( $covered, 'id' ) );
		$backup = Backup::export( $ids );
		if ( is_wp_error( $backup ) ) {
			$this->redirect( 'backup_failed' );
		}

		// 2) Löschen (Feld-Posts rekursiv, dann die Gruppe).
		$deleted = 0;
		foreach ( $ids as $group_id ) {
			if ( self::delete_group( $group_id ) ) {
				++$deleted;
			}
		}

		$this->redirect(
			'done',
			array(
				'deleted' => $deleted,
				'backup'  => rawurlencode( basename( (string) $backup ) ),
			)
		);
	}

	/**
	 * Löscht eine Feldgruppe samt aller (rekursiven) Feld-Posts.
	 *
	 * @since 0.2.0
	 *
	 * @param int $group_id Post-ID der Feldgruppe.
	 * @return bool True, wenn die Gruppe gelöscht wurde.
	 */
	private static function delete_group( int $group_id ): bool {
		self::delete_fields( $group_id );

		// wp_delete_post() liefert das gelöschte WP_Post-Objekt bei Erfolg, sonst false/null
		// → (bool)-Cast wertet nur den Erfolg als true.
		return (bool) wp_delete_post( $group_id, true );
	}

	/**
	 * Löscht die Feld-Posts unter einem Eltern-Post rekursiv (Repeater/Group-Subfelder).
	 *
	 * @since 0.2.0
	 *
	 * @param int $parent_id Post-ID der Gruppe bzw. des Eltern-Feldes.
	 * @return void
	 */
	private static function delete_fields( int $parent_id ): void {
		$fields = get_posts(
			array(
				'post_type'        => 'acf-field',
				'post_parent'      => $parent_id,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		foreach ( $fields as $field_id ) {
			self::delete_fields( (int) $field_id );
			wp_delete_post( (int) $field_id, true );
		}
	}

	/**
	 * PRG-Redirect zurück auf die Seite mit Status-Flag.
	 *
	 * @since 0.2.0
	 *
	 * @param string               $status Status-Slug (done/noop/backup_failed).
	 * @param array<string, mixed> $extra  Zusätzliche Query-Args.
	 * @return void
	 */
	private function redirect( string $status, array $extra = array() ): void {
		$args = array_merge(
			array(
				'page'      => self::PAGE_SLUG,
				'df_status' => $status,
			),
			$extra
		);

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Zeigt die Erfolgs-/Hinweis-Meldung nach dem PRG-Redirect.
	 *
	 * Reiner Anzeige-Read der eigenen Redirect-Flags (kein Formular-Processing) — daher
	 * ohne Nonce, analog zum Core-PRG-Muster; alle Werte werden sanitisiert/escaped.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	private function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect, kein Processing.
		$status = isset( $_GET['df_status'] ) ? sanitize_key( wp_unslash( $_GET['df_status'] ) ) : '';
		if ( '' === $status ) {
			return;
		}

		if ( 'done' === $status ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$deleted = isset( $_GET['deleted'] ) ? absint( wp_unslash( $_GET['deleted'] ) ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$backup = isset( $_GET['backup'] ) ? sanitize_file_name( rawurldecode( wp_unslash( $_GET['backup'] ) ) ) : '';
			$msg    = sprintf(
				/* translators: 1: Anzahl gelöschter Gruppen, 2: Backup-Dateiname. */
				esc_html__( '%1$d Duplikat(e) gelöscht. Backup: %2$s', 'depeur-food' ),
				(int) $deleted,
				esc_html( $backup )
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $msg ) . '</p></div>';
			return;
		}

		if ( 'noop' === $status ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Keine Duplikate vorhanden – es wurde nichts gelöscht.', 'depeur-food' ) . '</p></div>';
			return;
		}

		if ( 'backup_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Backup fehlgeschlagen – es wurde nichts gelöscht.', 'depeur-food' ) . '</p></div>';
		}
	}
}
