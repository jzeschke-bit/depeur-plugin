<?php
/**
 * Cutover_Page — geführter, reversibler Umstieg der REST-App aufs Plugin.
 *
 * ZWECK (WOFÜR): Der Cutover „Alt-Plugin rest-api-wprm AUS, Plugin-Routen AN" darf auf der
 * Produktion (wo die echte App hängt) nicht per wp-cli/Hand passieren, sondern geführt und
 * mit SOFORTIGEM Rückgängig. Diese Seite deaktiviert per Klick das Alt-Plugin (die identischen
 * Routen dieses Moduls übernehmen dann) und bietet einen „Rückgängig"-Knopf, der das Alt-Plugin
 * wieder aktiviert. Beide Aktionen: Cap → Nonce → Aktion → PRG-Redirect.
 *
 * WICHTIG (Kollision): Alt-Plugin und Modul registrieren dieselben Routen — nie beide lange
 * gleichzeitig aktiv. Darum ist der Cutover genau das Umschalten: Alt AUS ⇒ Modul trägt.
 *
 * Die Seite ist aus dem Sidebar-Menü ausgeblendet und nur über den Migrations-Assistenten
 * (Schritt „REST-App umschalten") erreichbar. Kopplung an den Core nur über den Hook-STRING
 * depeur_food/migration/steps (kein Core-Klassen-Import außer AdminMenu fürs Submenü).
 *
 * @package Depeur\Food\Modules\RestLegacy\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\RestLegacy\Admin;

use Depeur\Food\Core\AdminMenu;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Cutover-/Rollback-Unterseite.
 *
 * @since 0.3.0
 */
final class Cutover_Page {

	/**
	 * Page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-rest-legacy-cutover';

	/**
	 * Admin-post-Action (Cutover = Alt-Plugin deaktivieren).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_CUTOVER = 'depeur_food_restlegacy_cutover';

	/**
	 * Nonce-Name (Cutover).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_CUTOVER = 'depeur_food_restlegacy_cutover_nonce';

	/**
	 * Admin-post-Action (Rollback = Alt-Plugin wieder aktivieren).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_ROLLBACK = 'depeur_food_restlegacy_rollback';

	/**
	 * Nonce-Name (Rollback).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_ROLLBACK = 'depeur_food_restlegacy_rollback_nonce';

	/**
	 * Capability zum Anzeigen der Seite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP_VIEW = 'manage_options';

	/**
	 * Capability zum Schalten von Plugins (Cutover/Rollback).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP_ACT = 'activate_plugins';

	/**
	 * Verdrahtet Menü + Handler + Assistenten-Schritt.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
		// Verschlankung: aus dem Sidebar-Menü ausblenden — nur über den Assistenten erreichbar.
		add_action( 'admin_menu', array( $this, 'hide_from_menu' ), 999 );
		add_action( 'admin_post_' . self::ACTION_CUTOVER, array( $this, 'handle_cutover' ) );
		add_action( 'admin_post_' . self::ACTION_ROLLBACK, array( $this, 'handle_rollback' ) );

		// Diesen Schritt im zentralen Migrations-Assistenten (Core) anmelden — Kopplung nur über
		// den Hook-STRING, kein Core-Klassen-Import (Migration_Assistant::STEPS_FILTER).
		add_filter( 'depeur_food/migration/steps', array( $this, 'register_migration_step' ) );
	}

	/**
	 * Ermittelt die Plugin-Datei von „rest-api-wprm" (Ordner rest-api-wprm/ oder wl-api.php).
	 *
	 * @since 0.3.0
	 *
	 * @return string Plugin-Basename (z. B. „rest-api-wprm/wl-api.php") oder '' wenn nicht installiert.
	 */
	private function legacy_plugin_file(): string {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $file ) {
			$file = (string) $file;
			if ( 0 === strpos( $file, 'rest-api-wprm/' ) || false !== strpos( $file, 'wl-api.php' ) ) {
				return $file;
			}
		}

		return '';
	}

	/**
	 * Ist das Alt-Plugin installiert UND aktiv?
	 *
	 * @since 0.3.0
	 *
	 * @param string $file Plugin-Basename (aus legacy_plugin_file()).
	 * @return bool
	 */
	private function legacy_active( string $file ): bool {
		if ( '' === $file ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $file );
	}

	/**
	 * Meldet den Cutover-Schritt im Migrations-Assistenten an (one_time=false — Modul bleibt).
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, array<string, mixed>> $steps Bisherige Schritte.
	 * @return array<int, array<string, mixed>> Ergänzte Schritte.
	 */
	public function register_migration_step( array $steps ): array {
		$file   = $this->legacy_plugin_file();
		$active = $this->legacy_active( $file );

		if ( $active ) {
			$status      = 'todo';
			$status_text = __( 'Alt-Plugin „rest-api-wprm" noch aktiv — Cutover ausstehend.', 'depeur-food' );
		} elseif ( '' !== $file ) {
			$status      = 'done';
			$status_text = __( 'Alt-Plugin deaktiviert — die App läuft über die Plugin-Routen.', 'depeur-food' );
		} else {
			$status      = 'done';
			$status_text = __( 'Alt-Plugin nicht installiert — nichts umzuschalten.', 'depeur-food' );
		}

		$steps[] = array(
			'id'           => 'rest-legacy',
			'title'        => __( 'REST-App auf Plugin-Routen umschalten', 'depeur-food' ),
			'description'  => __( 'Deaktiviert das Alt-Plugin „rest-api-wprm"; die App nutzt dann die identischen Routen aus diesem Modul. Mit sofortigem Rückgängig.', 'depeur-food' ),
			'status'       => $status,
			'status_text'  => $status_text,
			'action_url'   => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'action_label' => __( 'Öffnen', 'depeur-food' ),
			'module'       => 'rest-legacy',
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
			__( 'REST-App umschalten', 'depeur-food' ),
			__( 'REST-App umschalten', 'depeur-food' ),
			self::CAP_VIEW,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Blendet die Unterseite aus dem Sidebar-Menü aus (bleibt über ?page= erreichbar).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function hide_from_menu(): void {
		remove_submenu_page( AdminMenu::MENU_SLUG, self::PAGE_SLUG );
	}

	/**
	 * Rendert Status + Cutover-/Rollback-Formulare.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAP_VIEW ) ) {
			return;
		}

		$file      = $this->legacy_plugin_file();
		$installed = ( '' !== $file );
		$active    = $this->legacy_active( $file );
		$wprm      = class_exists( 'WPRM_Rating_Database' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'REST-App auf Plugin-Routen umschalten', 'depeur-food' ); ?></h1>

			<?php $this->render_notice(); ?>

			<p style="max-width: 68em;">
				<?php esc_html_e( 'Dieses Modul stellt die REST-Routen von „rest-api-wprm" 1:1 bereit (wl/v1/posts, rest_wprm_recipe_query, wrm/v1/rating*). Der Cutover schaltet das Alt-Plugin ab, damit die App diese Routen aus dem Plugin nutzt. Weil beide dieselben Routen registrieren, dürfen sie nie lange gleichzeitig aktiv sein — deshalb ist der Cutover genau dieses Umschalten. Alles ist mit einem Klick rückgängig.', 'depeur-food' ); ?>
			</p>

			<table class="widefat striped" style="max-width: 68em; margin-bottom: 1.5em;">
				<tbody>
					<tr>
						<td style="width: 26em;"><strong><?php esc_html_e( 'Alt-Plugin „rest-api-wprm" installiert', 'depeur-food' ); ?></strong></td>
						<td><?php echo $installed ? esc_html__( '✓ ja', 'depeur-food' ) . ' — <code>' . esc_html( $file ) . '</code>' : esc_html__( '– nicht installiert', 'depeur-food' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Teilstrings sind mit esc_html gebaut. ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Alt-Plugin aktiv', 'depeur-food' ); ?></strong></td>
						<td><?php echo $active ? '<span style="color:#dba617;">' . esc_html__( '⚠ aktiv — Cutover ausstehend', 'depeur-food' ) . '</span>' : '<span style="color:#46b450;">' . esc_html__( '✓ deaktiviert', 'depeur-food' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- feste, escapte Strings. ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin-Routen dieses Moduls', 'depeur-food' ); ?></strong></td>
						<td>
							<?php
							echo $wprm
								? '<span style="color:#46b450;">' . esc_html__( '✓ aktiv (WP Recipe Maker vorhanden)', 'depeur-food' ) . '</span>'
								: '<span style="color:#d63638;">' . esc_html__( '✗ WP Recipe Maker fehlt — die Rating-Routen ruhen', 'depeur-food' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- feste, escapte Strings.
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( ! $installed ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'Das Alt-Plugin ist nicht installiert — es gibt nichts umzuschalten. Die Plugin-Routen sind bereits die einzige Quelle.', 'depeur-food' ); ?></p></div>
			<?php elseif ( $active ) : ?>
				<div style="max-width: 68em; padding: 1em 1.25em; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #dba617; margin-bottom: 1.5em;">
					<p style="margin-top: 0;">
						<strong><?php esc_html_e( 'Cutover ausführen', 'depeur-food' ); ?></strong><br />
						<span class="description"><?php esc_html_e( 'Deaktiviert „rest-api-wprm". Danach beantwortet dieses Modul die Routen. Direkt danach die App testen — falls etwas klemmt, unten „Rückgängig".', 'depeur-food' ); ?></span>
					</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						onsubmit="return confirm('<?php echo esc_js( __( 'Alt-Plugin „rest-api-wprm" jetzt deaktivieren? Die Plugin-Routen übernehmen. (Rückgängig jederzeit möglich.)', 'depeur-food' ) ); ?>');">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_CUTOVER ); ?>" />
						<?php wp_nonce_field( self::ACTION_CUTOVER, self::NONCE_CUTOVER ); ?>
						<?php submit_button( __( 'Cutover: Alt-Plugin deaktivieren', 'depeur-food' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			<?php else : ?>
				<div style="max-width: 68em; padding: 1em 1.25em; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #46b450; margin-bottom: 1.5em;">
					<p style="margin-top: 0; color:#46b450;"><strong><?php esc_html_e( '✓ Cutover erledigt — die App läuft über die Plugin-Routen.', 'depeur-food' ); ?></strong></p>
					<p class="description"><?php esc_html_e( 'Falls die App nicht wie erwartet reagiert, hier sofort zurückschalten (aktiviert „rest-api-wprm" wieder):', 'depeur-food' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						onsubmit="return confirm('<?php echo esc_js( __( 'Alt-Plugin „rest-api-wprm" wieder aktivieren (Cutover rückgängig)?', 'depeur-food' ) ); ?>');">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_ROLLBACK ); ?>" />
						<?php wp_nonce_field( self::ACTION_ROLLBACK, self::NONCE_ROLLBACK ); ?>
						<?php submit_button( __( 'Rückgängig: Alt-Plugin wieder aktivieren', 'depeur-food' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>
			<?php endif; ?>

			<p class="description" style="max-width: 68em;">
				<?php esc_html_e( 'Empfehlung: den Wechsel zu einer verkehrsarmen Zeit ausführen und die App direkt gegen die Routen testen (z. B. /wp-json/wrm/v1/rating). Der Seiten-Cache betrifft REST-Antworten in der Regel nicht.', 'depeur-food' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handler „Cutover": Cap → Nonce → Alt-Plugin deaktivieren → PRG.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle_cutover(): void {
		if ( ! current_user_can( self::CAP_ACT ) ) {
			wp_die( esc_html__( 'Keine Berechtigung, Plugins zu schalten.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_CUTOVER, self::NONCE_CUTOVER );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$file = $this->legacy_plugin_file();

		if ( '' === $file ) {
			$this->redirect( 'not_found' );
		}
		if ( is_plugin_active( $file ) ) {
			deactivate_plugins( $file );
		}

		$this->redirect( 'cutover_done' );
	}

	/**
	 * Handler „Rollback": Cap → Nonce → Alt-Plugin wieder aktivieren → PRG.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle_rollback(): void {
		if ( ! current_user_can( self::CAP_ACT ) ) {
			wp_die( esc_html__( 'Keine Berechtigung, Plugins zu schalten.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_ROLLBACK, self::NONCE_ROLLBACK );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$file = $this->legacy_plugin_file();

		if ( '' === $file ) {
			$this->redirect( 'not_found' );
		}
		if ( ! is_plugin_active( $file ) ) {
			$result = activate_plugin( $file );
			if ( is_wp_error( $result ) ) {
				$this->redirect( 'error', $result->get_error_message() );
			}
		}

		$this->redirect( 'rollback_done' );
	}

	/**
	 * PRG-Redirect zurück auf die Seite mit Status.
	 *
	 * @since 0.3.0
	 *
	 * @param string $status Status-Slug.
	 * @param string $msg    Optionale Fehlermeldung.
	 * @return void
	 */
	private function redirect( string $status, string $msg = '' ): void {
		$args = array(
			'page'      => self::PAGE_SLUG,
			'df_status' => $status,
		);
		if ( '' !== $msg ) {
			$args['df_msg'] = rawurlencode( $msg );
		}
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
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
		$msg = isset( $_GET['df_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['df_msg'] ) ) : '';

		$map = array(
			'cutover_done'  => array( 'success', __( 'Cutover ausgeführt: „rest-api-wprm" deaktiviert. Die Plugin-Routen sind aktiv — bitte die App testen.', 'depeur-food' ) ),
			'rollback_done' => array( 'success', __( 'Zurückgeschaltet: „rest-api-wprm" wieder aktiv.', 'depeur-food' ) ),
			'not_found'     => array( 'info', __( 'Alt-Plugin nicht gefunden — nichts umzuschalten.', 'depeur-food' ) ),
			'error'         => array( 'error', '' === $msg ? __( 'Aktion fehlgeschlagen.', 'depeur-food' ) : $msg ),
		);
		if ( ! isset( $map[ $status ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $map[ $status ][0] ),
			esc_html( $map[ $status ][1] )
		);
	}
}
