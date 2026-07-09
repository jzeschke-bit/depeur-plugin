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
	 * page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-catpage-migration';

	/**
	 * admin-post-Action (Migration).
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
	 * admin-post-Action (Restore).
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
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_' . self::ACTION_RESTORE, array( $this, 'handle_restore' ) );
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
						<th scope="col"><?php esc_html_e( 'Neuer Titel', 'depeur-food' ); ?></th>
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
		}
	}
}
