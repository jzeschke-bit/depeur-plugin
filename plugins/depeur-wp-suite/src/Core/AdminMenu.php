<?php
/**
 * Admin-Menü: Depeur Suite mit Dashboard, Module, Einstellungen, Support.
 *
 * @package Depeur\WPSuite\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Core;

use Depeur\WPSuite\Core\Settings\SettingsPage;
use Depeur\WPSuite\Support\SystemInfo;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse AdminMenu.
 */
class AdminMenu {

	/**
	 * Slug des Hauptmenüs.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'depeur-wp-suite';

	/**
	 * Registriert Menü und Unterseiten.
	 */
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Fügt Hauptmenü und Unterseiten hinzu.
	 */
	public static function add_menu_pages() {
		$cap = 'manage_options';

		add_menu_page(
			__( 'Depeur Suite', 'depeur-wp-suite' ),
			__( 'Depeur Suite', 'depeur-wp-suite' ),
			$cap,
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-admin-generic',
			80
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'depeur-wp-suite' ),
			__( 'Dashboard', 'depeur-wp-suite' ),
			$cap,
			self::MENU_SLUG,
			array( __CLASS__, 'render_dashboard' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Module', 'depeur-wp-suite' ),
			__( 'Module', 'depeur-wp-suite' ),
			$cap,
			self::MENU_SLUG . '-modules',
			array( __CLASS__, 'render_modules' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Einstellungen', 'depeur-wp-suite' ),
			__( 'Einstellungen', 'depeur-wp-suite' ),
			$cap,
			self::MENU_SLUG . '-settings',
			array( SettingsPage::class, 'render' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Support', 'depeur-wp-suite' ),
			__( 'Support', 'depeur-wp-suite' ),
			$cap,
			self::MENU_SLUG . '-support',
			array( __CLASS__, 'render_support' )
		);

		// Module können eigene Untermenüpunkte registrieren (z. B. BunnyCDN-Dashboard).
		do_action( 'depeur_wp_suite_register_submenus', self::MENU_SLUG, $cap );
	}

	/**
	 * Lädt CSS/JS nur auf Plugin-Seiten.
	 *
	 * @param string $hook_suffix Aktuelle Admin-Seite.
	 */
	public static function enqueue_assets( $hook_suffix ) {
		$allowed = array(
			'toplevel_page_' . self::MENU_SLUG,
			self::MENU_SLUG . '_page_' . self::MENU_SLUG . '-modules',
			self::MENU_SLUG . '_page_' . self::MENU_SLUG . '-settings',
			self::MENU_SLUG . '_page_' . self::MENU_SLUG . '-support',
			self::MENU_SLUG . '_page_' . self::MENU_SLUG . '-autoload-cleanup',
		);

		if ( ! in_array( $hook_suffix, $allowed, true ) ) {
			return;
		}

		wp_enqueue_style(
			'depeur-wp-suite-admin',
			DEPEUR_WP_SUITE_URL . 'assets/admin/admin.css',
			array(),
			DEPEUR_WP_SUITE_VERSION
		);
	}

	/**
	 * Rendert die Dashboard-Seite (Status, aktive Module).
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active = ModuleManager::get_active_module_slugs();
		$count  = count( $active );
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'Depeur Suite', 'depeur-wp-suite' ); ?></h1>
			<p><?php esc_html_e( 'Modulare Plugin-Suite. Hier siehst du den Status und die aktiven Module.', 'depeur-wp-suite' ); ?></p>
			<div class="depeur-suite-dashboard">
				<p>
					<strong><?php esc_html_e( 'Plugin-Version:', 'depeur-wp-suite' ); ?></strong>
					<?php echo esc_html( DEPEUR_WP_SUITE_VERSION ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Aktive Module:', 'depeur-wp-suite' ); ?></strong>
					<?php echo esc_html( (string) $count ); ?>
					<?php
					if ( $count > 0 ) {
						echo ' (' . esc_html( implode( ', ', $active ) ) . ')';
					}
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG . '-modules' ) ); ?>" class="button">
						<?php esc_html_e( 'Module verwalten', 'depeur-wp-suite' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Rendert die Module-Seite (Liste, aktivieren/deaktivieren).
	 */
	public static function render_modules() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$discovered = ModuleManager::get_discovered_modules();
		$valid_slugs = array_keys( $discovered['modules'] );

		// Nonce prüfen bei Formularabsendung.
		$message = '';
		if ( isset( $_POST['depeur_wp_suite_modules_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['depeur_wp_suite_modules_nonce'] ) ), 'depeur_wp_suite_save_modules' ) ) {
				$raw = isset( $_POST['depeur_wp_suite_modules'] ) && is_array( $_POST['depeur_wp_suite_modules'] )
					? wp_unslash( $_POST['depeur_wp_suite_modules'] )
					: array();
				// Nur bekannte Modul-Slugs übernehmen, Schreibweise (Ordnername) beibehalten.
				$posted = array();
				foreach ( $raw as $slug ) {
					$slug = is_string( $slug ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $slug ) : '';
					if ( $slug !== '' && in_array( $slug, $valid_slugs, true ) ) {
						$posted[] = $slug;
					}
				}
				update_option( 'depeur_wp_suite_modules', $posted );
				$message = __( 'Module gespeichert.', 'depeur-wp-suite' );
			} else {
				$message = __( 'Sicherheitsprüfung fehlgeschlagen. Bitte erneut versuchen.', 'depeur-wp-suite' );
			}
		}
		$active     = ModuleManager::get_active_module_slugs();
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'Module', 'depeur-wp-suite' ); ?></h1>
			<?php if ( $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! empty( $discovered['errors'] ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Hinweis: Einige Module konnten nicht geladen werden:', 'depeur-wp-suite' ); ?></p>
					<ul>
						<?php foreach ( $discovered['errors'] as $err ) : ?>
							<li><?php echo esc_html( $err ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'depeur_wp_suite_save_modules', 'depeur_wp_suite_modules_nonce' ); ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Modul', 'depeur-wp-suite' ); ?></th>
							<th><?php esc_html_e( 'Beschreibung', 'depeur-wp-suite' ); ?></th>
							<th><?php esc_html_e( 'Version', 'depeur-wp-suite' ); ?></th>
							<th><?php esc_html_e( 'Aktiv', 'depeur-wp-suite' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $discovered['modules'] as $slug => $info ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $info['name'] ); ?></strong></td>
								<td><?php echo esc_html( $info['description'] ); ?></td>
								<td><?php echo esc_html( $info['version'] ); ?></td>
								<td>
									<label>
										<input type="checkbox" name="depeur_wp_suite_modules[]" value="<?php echo esc_attr( $slug ); ?>"
											<?php checked( in_array( $slug, $active, true ) ); ?> />
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Module speichern', 'depeur-wp-suite' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Rendert die Support-Seite (Systeminfo, Copy-Button).
	 */
	public static function render_support() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = SystemInfo::get_report();
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'Support', 'depeur-wp-suite' ); ?></h1>
			<p><?php esc_html_e( 'Systembericht für Support-Anfragen. Inhalt kopieren und an den Support senden.', 'depeur-wp-suite' ); ?></p>
			<textarea id="depeur-suite-system-report" class="large-text code" rows="20" readonly><?php echo esc_textarea( $report ); ?></textarea>
			<p>
				<button type="button" id="depeur-suite-copy-report" class="button button-primary">
					<?php esc_html_e( 'Bericht kopieren', 'depeur-wp-suite' ); ?>
				</button>
			</p>
			<script>
			document.getElementById('depeur-suite-copy-report').addEventListener('click', function() {
				var ta = document.getElementById('depeur-suite-system-report');
				ta.select();
				document.execCommand('copy');
				this.textContent = '<?php echo esc_js( __( 'Kopiert!', 'depeur-wp-suite' ) ); ?>';
				setTimeout(function() {
					this.textContent = '<?php echo esc_js( __( 'Bericht kopieren', 'depeur-wp-suite' ) ); ?>';
				}.bind(this), 2000);
			});
			</script>
		</div>
		<?php
	}
}
