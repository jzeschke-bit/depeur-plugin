<?php
/**
 * ModulesPage — Admin-UI zum Aktivieren/Deaktivieren der Plugin-Module (Task 4b).
 *
 * Löst die bisher nötige `wp option update depeur_food_modules …`-Handarbeit ab: listet alle
 * per Discovery gefundenen Module (Name/Beschreibung/Version) mit einer Checkbox je Modul und
 * schreibt die Auswahl in die Master-Liste (ADR-1, ModuleManager::OPTION_MODULES). Gate:
 * `manage_options` → Nonce → Whitelist gegen die Discovery. Nach dem Speichern ein
 * aufgeschobener Rewrite-Flush (wp_loaded), damit neu aktivierte Module ihre Rewrites
 * registrieren, bevor geflusht wird.
 *
 * @package Depeur\Food\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Modul-Verwaltungsseite.
 *
 * @since 0.3.0
 */
final class ModulesPage {

	/**
	 * page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-modules';

	/**
	 * admin-post-Action.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION = 'depeur_food_save_modules';

	/**
	 * Nonce-Name.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE = 'depeur_food_save_modules_nonce';

	/**
	 * Options-Flag „Rewrites flushen".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const FLUSH_FLAG = 'depeur_food_modules_flush';

	/**
	 * Erforderliche Capability.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * Verdrahtet Menü, POST-Handler und den aufgeschobenen Flush.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 15 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush' ) );
	}

	/**
	 * Meldet die Unterseite an (früh im Depeur-Food-Menü).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function add_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Module', 'depeur-food' ),
			__( 'Module', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Rendert die Modul-Liste mit Toggle-Checkboxen.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$discovered = ModuleManager::get_discovered_modules();
		$modules    = $discovered['modules'];
		$errors     = $discovered['errors'];
		$active     = ModuleManager::get_active_module_slugs();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Module', 'depeur-food' ); ?></h1>

			<?php self::render_notice(); ?>

			<?php foreach ( $errors as $error ) : ?>
				<div class="notice notice-warning"><p><?php echo esc_html( (string) $error ); ?></p></div>
			<?php endforeach; ?>

			<p style="max-width: 65em;">
				<?php esc_html_e( 'Aktiviere hier die Module des Plugins. Ein Modul wird nur geladen, wenn es angehakt ist. Nach dem Speichern werden Permalinks automatisch aktualisiert.', 'depeur-food' ); ?>
			</p>

			<?php if ( empty( $modules ) ) : ?>
				<p><?php esc_html_e( 'Keine Module gefunden.', 'depeur-food' ); ?></p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
				<table class="widefat striped" style="max-width: 75em;">
					<thead>
						<tr>
							<th scope="col" style="width: 6em;"><?php esc_html_e( 'Aktiv', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Modul', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Beschreibung', 'depeur-food' ); ?></th>
							<th scope="col" style="width: 6em;"><?php esc_html_e( 'Version', 'depeur-food' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $modules as $slug => $meta ) : ?>
							<?php $is_active = in_array( $slug, $active, true ); ?>
							<tr>
								<td>
									<label>
										<input type="checkbox" name="df_modules[]" value="<?php echo esc_attr( (string) $slug ); ?>" <?php checked( $is_active ); ?> />
									</label>
								</td>
								<td>
									<strong><?php echo esc_html( (string) $meta['name'] ); ?></strong>
									<br /><span class="description"><code><?php echo esc_html( (string) $slug ); ?></code></span>
								</td>
								<td><span class="description"><?php echo esc_html( (string) $meta['description'] ); ?></span></td>
								<td><?php echo esc_html( (string) $meta['version'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Module speichern', 'depeur-food' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Verarbeitet das Speichern: Cap → Nonce → Whitelist → Option → Flush-Flag.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function handle(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION, self::NONCE );

		$known = array_keys( ModuleManager::get_discovered_modules()['modules'] );

		$submitted = array();
		if ( isset( $_POST['df_modules'] ) && is_array( $_POST['df_modules'] ) ) {
			$submitted = array_map( 'sanitize_key', array_map( 'strval', wp_unslash( $_POST['df_modules'] ) ) );
		}

		// Nur bekannte (per Discovery vorhandene) Slugs speichern.
		$active = array_values( array_intersect( $submitted, $known ) );

		update_option( ModuleManager::OPTION_MODULES, $active );
		update_option( self::FLUSH_FLAG, 1, false );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'df_status' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Aufgeschobener Rewrite-Flush nach einer Modul-Änderung.
	 *
	 * Läuft auf wp_loaded (nach init/Modul-Load), damit neu aktivierte Module ihre Rewrites
	 * bereits registriert haben, bevor geflusht wird.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function maybe_flush(): void {
		if ( get_option( self::FLUSH_FLAG ) ) {
			flush_rewrite_rules( false );
			delete_option( self::FLUSH_FLAG );
		}
	}

	/**
	 * Zeigt die Erfolgsmeldung nach dem Speichern.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
		$status = isset( $_GET['df_status'] ) ? sanitize_key( wp_unslash( $_GET['df_status'] ) ) : '';
		if ( 'saved' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Module gespeichert.', 'depeur-food' ) . '</p></div>';
		}
	}
}
