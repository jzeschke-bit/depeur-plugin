<?php
/**
 * Migration_Page — geführte Admin-Seite „Rezeptkategorie-Seiten migrieren".
 *
 * Zeigt eine Vorschau der Alt-Seiten (erkannte Terms je Taxonomie, Titel, Alt-Template,
 * bereits migriert?) und migriert sie auf Klick ins category-pages-System — mit Cap-Check →
 * Nonce → Backup → Migration (Legacy_Migration). Read-only bis auf den einen Submit.
 *
 * @package Depeur\Food\Modules\CategoryPages\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Admin;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Modules\CategoryPages\Support\Legacy_Migration;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Migrations-Unterseite.
 *
 * @since 0.3.0
 */
final class Migration_Page {

	/**
	 * Page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-catpage-migration';

	/**
	 * Admin-post-Action (Migration).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION = 'depeur_food_catpage_migrate';

	/**
	 * Nonce-Name (Migration).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE = 'depeur_food_catpage_migrate_nonce';

	/**
	 * Admin-post-Action (Restore).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_RESTORE = 'depeur_food_catpage_restore';

	/**
	 * Nonce-Name (Restore).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_RESTORE = 'depeur_food_catpage_restore_nonce';

	/**
	 * Admin-post-Action (Backups löschen).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_DELETE_BACKUPS = 'depeur_food_catpage_del_backups';

	/**
	 * Nonce-Name (Backups löschen).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_DELETE_BACKUPS = 'depeur_food_catpage_del_backups_nonce';

	/**
	 * Admin-post-Action (Legacy-ACF-Gruppe löschen).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_DELETE_GROUP = 'depeur_food_catpage_del_group';

	/**
	 * Nonce-Name (Legacy-ACF-Gruppe löschen).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_DELETE_GROUP = 'depeur_food_catpage_del_group_nonce';

	/**
	 * Capability.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * Verdrahtet Menü + Handler.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
		// Verschlankung: aus dem Sidebar-Menü ausblenden (Prio 999 = nach allen Registrierungen).
		// Die Seite bleibt über ?page= erreichbar — der Migrations-Assistent verlinkt sie.
		add_action( 'admin_menu', array( $this, 'hide_from_menu' ), 999 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION_RESTORE, array( $this, 'handle_restore' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE_BACKUPS, array( $this, 'handle_delete_backups' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE_GROUP, array( $this, 'handle_delete_group' ) );

		// Diesen Schritt im zentralen Migrations-Assistenten (Core) anmelden. Kopplung nur über
		// den Hook-STRING (kein Core-Klassen-Import nötig) — s. Migration_Assistant::STEPS_FILTER.
		add_filter( 'depeur_food/migration/steps', array( $this, 'register_migration_step' ) );
	}

	/**
	 * Meldet den Rezeptkategorie-Migrationsschritt im Migrations-Assistenten an.
	 *
	 * Status: „offen", solange Alt-Seiten noch nicht migriert sind ODER die alte ACF-Gruppe noch
	 * existiert; sonst „erledigt". one_time=false — das Modul category-pages trägt die LIVE-
	 * Kategorie-Seiten und bleibt nach der Migration aktiv (nicht deaktivieren).
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, array<string, mixed>> $steps Bisherige Schritte.
	 * @return array<int, array<string, mixed>> Ergänzte Schritte.
	 */
	public function register_migration_step( array $steps ): array {
		$rows = Legacy_Migration::scan();
		$open = 0;
		foreach ( $rows as $row ) {
			if ( empty( $row['already'] ) ) {
				++$open;
			}
		}
		$acf_groups = Legacy_Migration::legacy_acf_groups();

		if ( $open > 0 ) {
			$status      = 'todo';
			$status_text = sprintf(
				/* translators: %d: Anzahl offener Alt-Seiten. */
				_n( '%d Alt-Seite noch nicht migriert', '%d Alt-Seiten noch nicht migriert', $open, 'depeur-food' ),
				$open
			);
		} elseif ( ! empty( $acf_groups ) ) {
			$status      = 'todo';
			$status_text = __( 'Seiten migriert — alte ACF-Gruppe kann noch entfernt werden.', 'depeur-food' );
		} else {
			$status      = 'done';
			$status_text = __( 'Keine offenen Alt-Rezeptkategorie-Seiten.', 'depeur-food' );
		}

		$steps[] = array(
			'id'           => 'rezeptkategorie',
			'title'        => __( 'Alte Rezeptkategorie-Seiten migrieren', 'depeur-food' ),
			'description'  => __( 'Überträgt Alt-Seiten (ACF-Terms → Kategorie-Seiten-Felder) inkl. Backup und entfernt die alte ACF-Gruppe.', 'depeur-food' ),
			'status'       => $status,
			'status_text'  => $status_text,
			'action_url'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'action_label' => __( 'Öffnen', 'depeur-food' ),
			'module'       => 'category-pages',
			'one_time'     => false,
		);

		return $steps;
	}

	/**
	 * Meldet die Unterseite an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Kategorie-Seiten migrieren', 'depeur-food' ),
			__( 'Kategorie-Seiten migrieren', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Blendet die Unterseite aus dem Sidebar-Menü aus (bleibt über ?page= erreichbar).
	 *
	 * Verschlankung: Die Migrations-Tools sollen nicht einzeln im Menü stehen, sondern nur über
	 * den Migrations-Assistenten (dessen „Öffnen"-Link) erreichbar sein. remove_submenu_page
	 * entfernt nur den sichtbaren Eintrag; Callback + $_registered_pages bleiben bestehen, die
	 * Seite ist also weiter aufrufbar.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function hide_from_menu(): void {
		remove_submenu_page( AdminMenu::MENU_SLUG, self::PAGE_SLUG );
	}

	/**
	 * Rendert Vorschau + Migrations-Formular.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$rows    = Legacy_Migration::scan();
		$pending = array_filter(
			$rows,
			static function ( $row ) {
				return empty( $row['already'] );
			}
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Rezeptkategorie-Seiten migrieren', 'depeur-food' ); ?></h1>

			<?php $this->render_notice(); ?>

			<p style="max-width: 65em;">
				<?php esc_html_e( 'Überführt die alten Rezeptkategorie-Seiten (altes Theme-Template + rezept_*-Felder) ins Plugin-System: die kuratierten Terms werden je Taxonomie übernommen, der Titel gesetzt, die Seite als Kategorie-Seite aktiviert und das alte Template abgelöst (das Plugin rendert das Raster dann selbst). Vorher wird ein Backup der betroffenen Seiten angelegt. Die alten rezept_*-Werte bleiben unangetastet (nur kopiert).', 'depeur-food' ); ?>
			</p>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'Keine Alt-Rezeptkategorie-Seiten gefunden.', 'depeur-food' ); ?></p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<table class="widefat striped" style="max-width: 75em;">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Seite', 'depeur-food' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Erkannte Terms', 'depeur-food' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Neue H2-Überschrift', 'depeur-food' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Alt-Template', 'depeur-food' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'depeur-food' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( (string) $row['title'] ); ?></strong>
								<br /><span class="description">#<?php echo (int) $row['id']; ?></span>
							</td>
							<td><?php echo esc_html( (string) $row['terms_label'] ); ?></td>
							<td><?php echo esc_html( (string) $row['new_title'] ); ?></td>
							<td><?php echo $row['old_template'] ? esc_html__( 'ja', 'depeur-food' ) : '—'; ?></td>
							<td>
								<?php
								echo $row['already']
									? '<span style="color:#207520;">' . esc_html__( 'bereits migriert', 'depeur-food' ) . '</span>'
									: esc_html__( 'offen', 'depeur-food' );
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $pending ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1.5em;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
					<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
					<p>
						<button type="submit" class="button button-primary"
							onclick="return confirm('<?php echo esc_js( __( 'Backup anlegen und die offenen Rezeptkategorie-Seiten migrieren?', 'depeur-food' ) ); ?>');">
							<?php
							printf(
								/* translators: %d: Anzahl offener Seiten. */
								esc_html__( 'Backup + %d Seite(n) migrieren', 'depeur-food' ),
								(int) count( $pending )
							);
							?>
						</button>
					</p>
				</form>
			<?php else : ?>
				<p style="margin-top:1em;"><em><?php esc_html_e( 'Alle Seiten sind bereits migriert.', 'depeur-food' ); ?></em></p>
			<?php endif; ?>

			<?php if ( Legacy_Migration::has_backup() ) : ?>
				<hr style="margin: 2em 0;" />
				<h2><?php esc_html_e( 'Rückgängig machen', 'depeur-food' ); ?></h2>
				<p class="description" style="max-width: 65em;">
					<?php esc_html_e( 'Stellt den jüngsten Migrations-Backup wieder her: entfernt die vom Plugin gesetzten Kategorie-Seiten-Felder und setzt das ursprüngliche Seiten-Template zurück. Die alten rezept_*-Werte waren nie weg.', 'depeur-food' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_RESTORE ); ?>" />
					<?php wp_nonce_field( self::ACTION_RESTORE, self::NONCE_RESTORE ); ?>
					<p>
						<button type="submit" class="button"
							onclick="return confirm('<?php echo esc_js( __( 'Migration rückgängig machen (jüngster Backup)?', 'depeur-food' ) ); ?>');">
							<?php esc_html_e( 'Migration rückgängig machen', 'depeur-food' ); ?>
						</button>
					</p>
				</form>

				<?php $backups = Legacy_Migration::list_backups(); ?>
				<h3 style="margin-top: 1.5em;"><?php esc_html_e( 'Backups', 'depeur-food' ); ?></h3>
				<p class="description" style="max-width: 65em;">
					<?php esc_html_e( 'Nach erfolgreicher, verifizierter Migration kannst du die Backup-Dateien löschen, damit kein Müll liegen bleibt. Achtung: Danach ist kein „Rückgängig machen" mehr möglich.', 'depeur-food' ); ?>
				</p>
				<ul style="margin: 0 0 1em 1em; list-style: disc;">
					<?php foreach ( $backups as $backup ) : ?>
						<li>
							<code><?php echo esc_html( (string) $backup['name'] ); ?></code>
							<span class="description">(<?php echo esc_html( size_format( (int) $backup['size'] ) ); ?>)</span>
						</li>
					<?php endforeach; ?>
				</ul>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_DELETE_BACKUPS ); ?>" />
					<?php wp_nonce_field( self::ACTION_DELETE_BACKUPS, self::NONCE_DELETE_BACKUPS ); ?>
					<p>
						<button type="submit" class="button button-link-delete"
							onclick="return confirm('<?php echo esc_js( __( 'Alle Migrations-Backups unwiderruflich löschen?', 'depeur-food' ) ); ?>');">
							<?php
							printf(
								/* translators: %d: Anzahl Backup-Dateien. */
								esc_html__( 'Alle %d Backup(s) löschen', 'depeur-food' ),
								(int) count( $backups )
							);
							?>
						</button>
					</p>
				</form>
			<?php endif; ?>

			<?php $legacy_groups = Legacy_Migration::legacy_acf_groups(); ?>
			<?php if ( ! empty( $legacy_groups ) ) : ?>
				<hr style="margin: 2em 0;" />
				<h2><?php esc_html_e( 'Alte ACF-Gruppe entfernen', 'depeur-food' ); ?></h2>
				<p class="description" style="max-width: 65em;">
					<?php esc_html_e( 'Nach erfolgreicher, verifizierter Migration wird die alte ACF-Feldgruppe mit den rezept_*-Feldern nicht mehr gebraucht. Löschen entfernt nur die Editor-UI (Gruppen-Definition); ein Backup der Gruppe wird vorher angelegt, und die rezept_*-Werte auf den Seiten bleiben erhalten.', 'depeur-food' ); ?>
				</p>
				<table class="widefat striped" style="max-width: 65em;">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Gruppe', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Key', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Felder', 'depeur-food' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $legacy_groups as $group ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $group['title'] ); ?></td>
								<td><code><?php echo esc_html( (string) $group['key'] ); ?></code></td>
								<td><?php echo (int) $group['field_count']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 1em;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_DELETE_GROUP ); ?>" />
					<?php wp_nonce_field( self::ACTION_DELETE_GROUP, self::NONCE_DELETE_GROUP ); ?>
					<p>
						<button type="submit" class="button button-link-delete"
							onclick="return confirm('<?php echo esc_js( __( 'Alte ACF-Gruppe(n) löschen? (Backup wird angelegt)', 'depeur-food' ) ); ?>');">
							<?php
							printf(
								/* translators: %d: Anzahl der ACF-Gruppen. */
								esc_html__( '%d ACF-Gruppe(n) löschen', 'depeur-food' ),
								(int) count( $legacy_groups )
							);
							?>
						</button>
					</p>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Verarbeitet die Migration: Cap → Nonce → Re-Scan → Backup → Migration.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION, self::NONCE );

		// Server-seitig neu bestimmen (dem POST nicht vertrauen): offene Seiten migrieren.
		$rows    = Legacy_Migration::scan();
		$pending = array();
		foreach ( $rows as $row ) {
			if ( empty( $row['already'] ) ) {
				$pending[] = (int) $row['id'];
			}
		}

		if ( empty( $pending ) ) {
			$this->redirect( 'noop' );
		}

		$backup = Legacy_Migration::backup( $pending );
		if ( is_wp_error( $backup ) ) {
			$this->redirect( 'backup_failed' );
		}

		$done = Legacy_Migration::migrate_all( $pending );

		// Rewrites für die neuen Kategorie-Seiten neu aufbauen (Pagination /page/N/).
		flush_rewrite_rules( false );

		$this->redirect(
			'done',
			array(
				'migrated' => $done,
				'backup'   => rawurlencode( basename( (string) $backup ) ),
			)
		);
	}

	/**
	 * Verarbeitet den Restore: Cap → Nonce → jüngsten Backup wiederherstellen.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle_restore(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_RESTORE, self::NONCE_RESTORE );

		$result = Legacy_Migration::restore_latest();
		if ( is_wp_error( $result ) ) {
			$this->redirect( 'restore_failed' );
		}

		$this->redirect(
			'restored',
			array(
				'restored' => (int) $result['restored'],
			)
		);
	}

	/**
	 * Verarbeitet das Löschen aller Migrations-Backups: Cap → Nonce → löschen.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle_delete_backups(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_DELETE_BACKUPS, self::NONCE_DELETE_BACKUPS );

		$deleted = Legacy_Migration::delete_backups();

		$this->redirect(
			'backups_deleted',
			array(
				'deleted' => (int) $deleted,
			)
		);
	}

	/**
	 * Verarbeitet das Löschen der Legacy-ACF-Gruppe(n): Cap → Nonce → Backup → löschen.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle_delete_group(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_DELETE_GROUP, self::NONCE_DELETE_GROUP );

		$result = Legacy_Migration::delete_legacy_acf_groups();
		if ( is_wp_error( $result ) ) {
			$this->redirect( 'group_failed' );
		}

		$this->redirect(
			'group_deleted',
			array(
				'deleted' => (int) $result['deleted'],
			)
		);
	}

	/**
	 * PRG-Redirect zurück auf die Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param string               $status Status-Slug.
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
	 * Zeigt die Status-Meldung nach dem PRG-Redirect.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
		$status = isset( $_GET['df_status'] ) ? sanitize_key( wp_unslash( $_GET['df_status'] ) ) : '';
		if ( '' === $status ) {
			return;
		}

		if ( 'done' === $status ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$migrated = isset( $_GET['migrated'] ) ? absint( wp_unslash( $_GET['migrated'] ) ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$backup = isset( $_GET['backup'] ) ? sanitize_file_name( rawurldecode( wp_unslash( $_GET['backup'] ) ) ) : '';
			$msg    = sprintf(
				/* translators: 1: Anzahl migrierter Seiten, 2: Backup-Dateiname. */
				esc_html__( '%1$d Seite(n) migriert. Backup: %2$s', 'depeur-food' ),
				(int) $migrated,
				esc_html( $backup )
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $msg ) . '</p></div>';
			return;
		}

		if ( 'noop' === $status ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Keine offenen Seiten – es wurde nichts migriert.', 'depeur-food' ) . '</p></div>';
			return;
		}

		if ( 'backup_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Backup fehlgeschlagen – es wurde nichts migriert.', 'depeur-food' ) . '</p></div>';
			return;
		}

		if ( 'restored' === $status ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$restored = isset( $_GET['restored'] ) ? absint( wp_unslash( $_GET['restored'] ) ) : 0;
			$msg      = sprintf(
				/* translators: %d: Anzahl wiederhergestellter Seiten. */
				esc_html__( '%d Seite(n) aus dem Backup wiederhergestellt (Migration rückgängig).', 'depeur-food' ),
				(int) $restored
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
			return;
		}

		if ( 'restore_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Wiederherstellung fehlgeschlagen (kein/kaputtes Backup).', 'depeur-food' ) . '</p></div>';
			return;
		}

		if ( 'backups_deleted' === $status ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$deleted = isset( $_GET['deleted'] ) ? absint( wp_unslash( $_GET['deleted'] ) ) : 0;
			$msg     = sprintf(
				/* translators: %d: Anzahl gelöschter Backup-Dateien. */
				esc_html__( '%d Backup-Datei(en) gelöscht.', 'depeur-food' ),
				(int) $deleted
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
			return;
		}

		if ( 'group_deleted' === $status ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
			$deleted = isset( $_GET['deleted'] ) ? absint( wp_unslash( $_GET['deleted'] ) ) : 0;
			$msg     = sprintf(
				/* translators: %d: Anzahl gelöschter ACF-Gruppen. */
				esc_html__( '%d alte ACF-Gruppe(n) gelöscht (Backup wurde angelegt).', 'depeur-food' ),
				(int) $deleted
			);
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
			return;
		}

		if ( 'group_failed' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'ACF-Gruppen-Löschung fehlgeschlagen (Backup nicht möglich?).', 'depeur-food' ) . '</p></div>';
		}
	}
}
