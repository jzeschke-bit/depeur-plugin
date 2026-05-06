<?php
/**
 * Eigenes Untermenü „BunnyCDN“ in der Depeur Suite: Status, Cache-Synchronisation, Aktionen.
 * Orientierung an der RunCloud Cloudflare-Erweiterung (Purge Cache + Actions).
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Admin;

use Depeur\WPSuite\Modules\BunnyCDN\Services\PurgeService;
use Depeur\WPSuite\Modules\BunnyCDN\Services\CacheTest;
use Depeur\WPSuite\Modules\BunnyCDN\Integrations\RunCloudIntegration;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BunnyCDN-Dashboard-Seite (Status, Cache-Sync, Aktionen: Cache testen, Alles leeren).
 */
class DashboardPage {

	/**
	 * Slug der Dashboard-Seite (für Menü und Referrer).
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'depeur-wp-suite-bunny-cdn';

	/**
	 * Registriert das Untermenü sowie Handler für Purge, Cache-Test und Sync-Test.
	 */
	public static function register() {
		add_action( 'depeur_wp_suite_register_submenus', array( __CLASS__, 'add_submenu' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'handle_test_cache_request' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_test_sync_request' ) );
	}

	/**
	 * Verarbeitet die Aktion „Cache testen“ (Nonce-Link).
	 */
	public static function handle_test_cache_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['depeur_bunny_test_cache'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'depeur_bunny_test_cache' ) ) {
			return;
		}
		$opts   = get_option( Settings::OPTION_KEY, array() );
		$host   = isset( $opts['bunny_cdn_hostname'] ) ? trim( (string) $opts['bunny_cdn_hostname'] ) : '';
		$result = CacheTest::test( $host !== '' ? $host : null );
		$type   = $result['success'] ? 'success' : 'error';
		$msg    = $result['message'];
		if ( $result['details'] !== '' ) {
			$msg .= ' (' . $result['details'] . ')';
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                      => self::PAGE_SLUG,
					'depeur_bunny_test_done'    => 1,
					'depeur_bunny_test_msg'     => rawurlencode( $msg ),
					'depeur_bunny_test_type'    => $type,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Verarbeitet die Aktion „Sync testen“: führt Purge aus wie bei RunCloud-Purge und zeigt Ergebnis.
	 */
	public static function handle_test_sync_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['depeur_bunny_test_sync'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'depeur_bunny_test_sync' ) ) {
			return;
		}
		$purge   = new PurgeService();
		$result  = $purge->purge_all_if_allowed();
		$success = ! empty( $result['success'] );
		if ( $success ) {
			$msg = __( 'Sync-Test: Purge erfolgreich. Bei RunCloud-Purge wird derselbe Vorgang automatisch ausgeführt.', 'depeur-wp-suite' );
		} else {
			$msg = __( 'Sync-Test: ', 'depeur-wp-suite' ) . ( isset( $result['message'] ) ? $result['message'] : __( 'Purge fehlgeschlagen.', 'depeur-wp-suite' ) );
		}
		$type = $success ? 'success' : 'error';
		if ( ! empty( $result['skipped_debounce'] ) ) {
			$msg = __( 'Sync-Test: Purge wurde kürzlich ausgeführt (Debounce). Bitte in 30 Sekunden erneut testen.', 'depeur-wp-suite' );
			$type = 'info';
		}
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                   => self::PAGE_SLUG,
					'depeur_bunny_purge_done' => 1,
					'depeur_bunny_msg'       => rawurlencode( $msg ),
					'depeur_bunny_type'      => $type,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Fügt „BunnyCDN“ als Untermenü unter Depeur Suite hinzu (nur wenn Modul aktiv).
	 *
	 * @param string $parent_slug Slug des Hauptmenüs.
	 * @param string $cap         Capability.
	 */
	public static function add_submenu( $parent_slug, $cap ) {
		$active = get_option( 'depeur_wp_suite_modules', array() );
		if ( ! is_array( $active ) || ! in_array( 'bunny-cdn', $active, true ) ) {
			return;
		}

		add_submenu_page(
			$parent_slug,
			__( 'BunnyCDN', 'depeur-wp-suite' ),
			__( 'BunnyCDN', 'depeur-wp-suite' ),
			$cap,
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Rendert die BunnyCDN-Dashboard-Seite.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts         = get_option( Settings::OPTION_KEY, array() );
		$api_ok       = ! empty( $opts['bunny_api_key'] );
		$zone_ok      = ! empty( trim( (string) ( $opts['bunny_pull_zone_id'] ?? '' ) ) );
		$host_ok      = ! empty( trim( (string) ( $opts['bunny_cdn_hostname'] ?? '' ) ) );
		$runcloud     = RunCloudIntegration::is_runcloud_detected();
		$sync_all     = ! empty( $opts['enable_runcloud_sync'] );
		$sync_single  = ! empty( $opts['enable_runcloud_sync_single'] );
		$last         = PurgeService::get_last_purge();
		$enabled      = ! empty( $opts['enable_bunny_cdn'] );
		$settings_url  = admin_url( 'admin.php?page=depeur-wp-suite-settings&tab=bunny-cdn' );
		$purge_url     = wp_nonce_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'depeur_bunny_purge' => '1' ), admin_url( 'admin.php' ) ), 'depeur_bunny_purge_all' );
		$test_url      = wp_nonce_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'depeur_bunny_test_cache' => '1' ), admin_url( 'admin.php' ) ), 'depeur_bunny_test_cache' );
		$test_sync_url = wp_nonce_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'depeur_bunny_test_sync' => '1' ), admin_url( 'admin.php' ) ), 'depeur_bunny_test_sync' );
		$sync_status   = self::get_sync_status_text( $runcloud, $sync_all, $sync_single );

		$notice_msg = null;
		$notice_cls = 'notice-success';
		if ( ! empty( $_GET['depeur_bunny_purge_done'] ) && ! empty( $_GET['depeur_bunny_msg'] ) ) {
			$notice_msg = sanitize_text_field( wp_unslash( $_GET['depeur_bunny_msg'] ) );
			$t          = isset( $_GET['depeur_bunny_type'] ) ? sanitize_key( wp_unslash( $_GET['depeur_bunny_type'] ) ) : 'success';
			$notice_cls = ( $t === 'error' ) ? 'notice-error' : ( ( $t === 'info' ) ? 'notice-info' : 'notice-success' );
		} elseif ( ! empty( $_GET['depeur_bunny_test_done'] ) && ! empty( $_GET['depeur_bunny_test_msg'] ) ) {
			$notice_msg = sanitize_text_field( wp_unslash( $_GET['depeur_bunny_test_msg'] ) );
			$t          = isset( $_GET['depeur_bunny_test_type'] ) ? sanitize_key( wp_unslash( $_GET['depeur_bunny_test_type'] ) ) : 'success';
			$notice_cls = ( $t === 'error' ) ? 'notice-error' : 'notice-success';
		}
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'BunnyCDN', 'depeur-wp-suite' ); ?></h1>

			<?php if ( $notice_msg !== null ) : ?>
				<div class="notice <?php echo esc_attr( $notice_cls ); ?> is-dismissible"><p><?php echo esc_html( $notice_msg ); ?></p></div>
				<?php
				// Bei MISS: Hinweis, dass BunnyCDN HTML standardmäßig nicht cached (Smart Cache).
				if ( $notice_cls === 'notice-success' && $notice_msg !== null && stripos( $notice_msg, 'MISS' ) !== false ) :
					?>
					<div class="notice notice-info is-dismissible" style="margin-top: 0.5em;">
						<p><strong><?php esc_html_e( 'Warum immer MISS?', 'depeur-wp-suite' ); ?></strong><br />
						<?php
						echo esc_html(
							__( 'BunnyCDN cached mit Smart Cache standardmäßig keine HTML-Seiten (text/html). Damit die Startseite als HIT ausgeliefert wird, in der Pull Zone unter „Caching“ → „Edge Rules“ eine Regel „Override Cache Time“ anlegen: Bedingung z. B. „Response Header“ → „Content-Type“ enthält „text/html“, Aktion „Override Cache Time“ (z. B. 3600 Sekunden).', 'depeur-wp-suite' )
						);
						?>
						<br />
						<a href="https://support.bunny.net/hc/en-us/articles/5779976842770-Understanding-Smart-Cache" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'BunnyCDN: Smart Cache (Dokumentation)', 'depeur-wp-suite' ); ?></a></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="depeur-bunny-dashboard" style="max-width: 800px;">
				<section class="card" style="padding: 1.5em; margin: 1em 0;">
					<h2 class="title"><?php esc_html_e( 'Status', 'depeur-wp-suite' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<tr>
								<td><?php esc_html_e( 'BunnyCDN aktiviert', 'depeur-wp-suite' ); ?></td>
								<td><?php echo $enabled ? esc_html__( 'Ja', 'depeur-wp-suite' ) : esc_html__( 'Nein', 'depeur-wp-suite' ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'API Key', 'depeur-wp-suite' ); ?></td>
								<td><?php echo $api_ok ? esc_html__( 'Gesetzt', 'depeur-wp-suite' ) : esc_html__( 'Nicht gesetzt', 'depeur-wp-suite' ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Pull Zone ID', 'depeur-wp-suite' ); ?></td>
								<td><?php echo $zone_ok ? esc_html__( 'Gesetzt', 'depeur-wp-suite' ) : esc_html__( 'Nicht gesetzt', 'depeur-wp-suite' ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'CDN-Hostname', 'depeur-wp-suite' ); ?></td>
								<td><?php echo $host_ok ? esc_html__( 'Gesetzt', 'depeur-wp-suite' ) : esc_html__( 'Nicht gesetzt', 'depeur-wp-suite' ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'RunCloud', 'depeur-wp-suite' ); ?></td>
								<td><?php echo $runcloud ? esc_html__( 'Erkannt', 'depeur-wp-suite' ) : esc_html__( 'Nicht erkannt', 'depeur-wp-suite' ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Sync-Status (RunCloud)', 'depeur-wp-suite' ); ?></td>
								<td><?php echo esc_html( $sync_status ); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Letzter Purge', 'depeur-wp-suite' ); ?></td>
								<td>
									<?php
									if ( $last ) {
										echo esc_html(
											sprintf(
												/* translators: 1: Datum/Zeit, 2: Erfolg/Fehler, 3: Meldung */
												__( '%1$s – %2$s (%3$s)', 'depeur-wp-suite' ),
												wp_date( 'd.m.Y H:i', (int) $last['time'] ),
												! empty( $last['success'] ) ? __( 'Erfolg', 'depeur-wp-suite' ) : __( 'Fehler', 'depeur-wp-suite' ),
												isset( $last['message'] ) ? $last['message'] : '–'
											)
										);
									} else {
										esc_html_e( 'Noch keiner', 'depeur-wp-suite' );
									}
									?>
								</td>
							</tr>
						</tbody>
					</table>
				</section>

				<?php if ( $runcloud ) : ?>
				<section class="card" style="padding: 1.5em; margin: 1em 0;">
					<h2 class="title"><?php esc_html_e( 'BunnyCDN Cache-Synchronisation', 'depeur-wp-suite' ); ?></h2>
					<p><?php esc_html_e( 'Nutzen Sie RunCloud NGINX Page Cache, können Sie den BunnyCDN-Cache automatisch mitleeren, wenn RunCloud den NGINX-Cache leert – entweder beim „Purge All“ oder auch bei Purge einer einzelnen Seite/Beitrag.', 'depeur-wp-suite' ); ?></p>
					<p>
						<strong><?php esc_html_e( 'Sync bei Purge All:', 'depeur-wp-suite' ); ?></strong> <?php echo $sync_all ? esc_html__( 'Aktiviert', 'depeur-wp-suite' ) : esc_html__( 'Deaktiviert', 'depeur-wp-suite' ); ?>
						<br />
						<strong><?php esc_html_e( 'Sync bei Purge einer Seite/Startseite:', 'depeur-wp-suite' ); ?></strong> <?php echo $sync_single ? esc_html__( 'Aktiviert', 'depeur-wp-suite' ) : esc_html__( 'Deaktiviert', 'depeur-wp-suite' ); ?>
					</p>
					<p>
						<a href="<?php echo esc_url( $test_sync_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Sync & Purge testen', 'depeur-wp-suite' ); ?></a>
						<span class="description" style="margin-left: 0.5em;"><?php esc_html_e( 'Führt denselben Purge aus wie bei RunCloud – bei Erfolg funktioniert auch die automatische Sync.', 'depeur-wp-suite' ); ?></span>
					</p>
					<p><a href="<?php echo esc_url( $settings_url ); ?>" class="button"><?php esc_html_e( 'Sync in Einstellungen anpassen', 'depeur-wp-suite' ); ?></a></p>
				</section>
				<?php endif; ?>

				<section class="card" style="padding: 1.5em; margin: 1em 0;">
					<h2 class="title"><?php esc_html_e( 'BunnyCDN Aktionen', 'depeur-wp-suite' ); ?></h2>

					<div style="margin-bottom: 1.5em;">
						<h3 style="margin-top: 0;"><?php esc_html_e( 'BunnyCDN Page Cache testen', 'depeur-wp-suite' ); ?></h3>
						<p><?php esc_html_e( 'Prüft, ob Ihre Seite über BunnyCDN ausgeliefert wird und der CDN-Cache aktiv ist (per Request an den CDN-Hostnamen oder die Startseite).', 'depeur-wp-suite' ); ?></p>
						<p><a href="<?php echo esc_url( $test_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Cache testen', 'depeur-wp-suite' ); ?></a></p>
						<p class="description" style="margin-top: 0.75em; max-width: 42em;">
							<strong><?php esc_html_e( 'Immer MISS?', 'depeur-wp-suite' ); ?></strong>
							<?php
							echo esc_html(
								__( 'BunnyCDN cached mit Smart Cache standardmäßig keine HTML-Seiten. Damit Seiten als HIT gecacht werden: In der Pull Zone unter Caching → Edge Rules eine Regel „Override Cache Time“ für Content-Type text/html anlegen (z. B. 3600 Sekunden).', 'depeur-wp-suite' )
							);
							?>
							<a href="https://support.bunny.net/hc/en-us/articles/5779976842770-Understanding-Smart-Cache" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Dokumentation', 'depeur-wp-suite' ); ?></a>
						</p>
					</div>

					<div>
						<h3 style="margin-top: 0;"><?php esc_html_e( 'BunnyCDN Cache leeren', 'depeur-wp-suite' ); ?></h3>
						<p><?php esc_html_e( 'Leert alle zwischengespeicherten Dateien der Pull Zone. Nach dem Leeren liefert BunnyCDN beim nächsten Aufruf frische Inhalte vom Origin.', 'depeur-wp-suite' ); ?></p>
						<p><a href="<?php echo esc_url( $purge_url ); ?>" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'CDN-Cache wirklich komplett leeren?', 'depeur-wp-suite' ) ); ?>');"><?php esc_html_e( 'Alles leeren', 'depeur-wp-suite' ); ?></a></p>
					</div>
				</section>

				<section class="card" style="padding: 1.5em; margin: 1em 0;">
					<h2 class="title"><?php esc_html_e( 'HTML-Seiten bei BunnyCDN cachen', 'depeur-wp-suite' ); ?></h2>
					<p><?php esc_html_e( 'Standardmäßig cached BunnyCDN (Smart Cache) keine HTML-Seiten. In der Pull Zone unter „Caching“ → „Edge Rules“ eine Regel anlegen:', 'depeur-wp-suite' ); ?></p>
					<p><strong><?php esc_html_e( 'Bedingungen („Match all“):', 'depeur-wp-suite' ); ?></strong></p>
					<ul style="list-style: disc; margin-left: 1.5em;">
						<li><?php esc_html_e( 'Response Header → Header Name: Content-Type, Wert: text/html', 'depeur-wp-suite' ); ?></li>
						<li><?php esc_html_e( 'Request URL → Wert: * (trifft alle URLs; es gibt keinen separaten „Trigger Path“, die Request-URL-Bedingung übernimmt das).', 'depeur-wp-suite' ); ?></li>
					</ul>
					<p><strong><?php esc_html_e( 'Aktion (wichtig):', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( '„Override Cache Time“, z. B. 3600 Sekunden (1 Stunde). Ohne diese Aktion wird nicht gecacht.', 'depeur-wp-suite' ); ?></p>
					<p><strong><?php esc_html_e( 'Regel stimmt, aber HTML kommt trotzdem vom „normalen“ Server?', 'depeur-wp-suite' ); ?></strong></p>
					<ul style="list-style: disc; margin-left: 1.5em;">
						<li><strong><?php esc_html_e( 'Läuft Ihre Domain durch das CDN?', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( 'Damit die Edge Rule überhaupt greift, muss die Anfrage bei Bunny ankommen. Wenn Sie z. B. mywptest.de aufrufen und diese Domain per DNS (A-Record) auf Ihren Origin-Server zeigt, geht der Request nie durch Bunny – dann liefert immer der Origin die HTML-Seite. Lösung: Ihre Hauptdomain oder www per CNAME auf die Pull Zone zeigen (z. B. auf mywptest.b-cdn.net) und in der Pull Zone als Custom Hostname eintragen. Dann geht der gesamte Traffic (inkl. HTML) durchs CDN und die Edge Rule kann cachen.', 'depeur-wp-suite' ); ?></li>
						<li><?php esc_html_e( 'Zum Prüfen: „Cache testen“ nutzt die CDN-URL (z. B. mywptest.b-cdn.net). Zeigt der Test dort HIT, funktioniert die Regel – Besucher müssen dann aber über die CDN-URL oder eine per CNAME umgeleitete Domain auf die Seite kommen.', 'depeur-wp-suite' ); ?></li>
						<li><?php esc_html_e( 'Nach dem Anlegen oder Ändern: einmal „Alles leeren“ ausführen, dann „Cache testen“ – der nächste Aufruf holt die Seite vom Origin und die Regel wird angewendet.', 'depeur-wp-suite' ); ?></li>
						<li><?php esc_html_e( 'Reihenfolge der Regeln: Wenn eine andere Regel zuerst greift (z. B. Bypass für Cookies), diese HTML-Cache-Regel nach oben ziehen.', 'depeur-wp-suite' ); ?></li>
					</ul>
					<p><a href="https://support.bunny.net/hc/en-us/articles/5779976842770-Understanding-Smart-Cache" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'BunnyCDN: Smart Cache', 'depeur-wp-suite' ); ?></a> · <a href="https://www.gulshankumar.net/cache-wordpress-html-bunnycdn/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Tutorial: WordPress HTML bei BunnyCDN cachen', 'depeur-wp-suite' ); ?></a></p>
				</section>

				<section class="card" style="padding: 1.5em; margin: 1em 0; border-left: 4px solid #d63638;">
					<h2 class="title"><?php esc_html_e( '508 Loop Detected (Custom Hostname + CNAME)', 'depeur-wp-suite' ); ?></h2>
					<p><?php esc_html_e( 'Eine Redirect-Schleife zwischen CDN und Origin. Typische Ursachen und Lösungen:', 'depeur-wp-suite' ); ?></p>
					<ul style="list-style: disc; margin-left: 1.5em;">
						<li><strong><?php esc_html_e( 'Origin URL = Server-IP, nicht die Domain:', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( 'In der Pull Zone unter Origin als „Origin URL“ die IP-Adresse Ihres Servers eintragen, z. B. https://123.45.67.89 (mit https falls der Server SSL hat). Nicht die Domain (z. B. https://www.mywptest.de) – sonst kann eine Redirect-Schleife entstehen. Unter „Host header (optional)“ die Domain eintragen (z. B. www.mywptest.de), damit der Server weiß, welche Website ausgeliefert werden soll.', 'depeur-wp-suite' ); ?></li>
						<li><strong><?php esc_html_e( 'WordPress-URL passt nicht:', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( 'Einstellungen → Allgemein: WordPress- und Website-Adresse exakt die Domain, die Besucher nutzen (z. B. https://www.mywptest.de).', 'depeur-wp-suite' ); ?></li>
						<li><strong><?php esc_html_e( 'Host-Header per Edge Rule (falls nötig):', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( 'Falls der Origin trotzdem falsch reagiert: Unter Edge Rules eine Regel mit Aktion „Add Request Header“, Header Name „host“, Wert = Ihre Domain (z. B. www.mywptest.de).', 'depeur-wp-suite' ); ?></li>
						<li><strong><?php esc_html_e( 'Nur eine Domain per CNAME auf Bunny:', 'depeur-wp-suite' ); ?></strong> <?php esc_html_e( 'Vorerst nur www ODER nur die Hauptdomain per CNAME auf die Pull Zone zeigen.', 'depeur-wp-suite' ); ?></li>
					</ul>
				</section>

				<p><a href="<?php echo esc_url( $settings_url ); ?>" class="button"><?php esc_html_e( 'BunnyCDN-Einstellungen bearbeiten', 'depeur-wp-suite' ); ?></a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Beschreibungstext für Sync-Status (RunCloud).
	 *
	 * @param bool $runcloud   RunCloud erkannt.
	 * @param bool $sync_all   Sync bei Purge All aktiviert.
	 * @param bool $sync_single Sync bei Purge Single/Home aktiviert.
	 * @return string
	 */
	private static function get_sync_status_text( $runcloud, $sync_all, $sync_single ) {
		if ( ! $runcloud ) {
			return __( 'RunCloud nicht erkannt – Sync inaktiv', 'depeur-wp-suite' );
		}
		if ( ! $sync_all && ! $sync_single ) {
			return __( 'Sync deaktiviert (in Einstellungen aktivierbar)', 'depeur-wp-suite' );
		}
		$parts = array();
		if ( $sync_all ) {
			$parts[] = __( 'Purge All', 'depeur-wp-suite' );
		}
		if ( $sync_single ) {
			$parts[] = __( 'Single/Home', 'depeur-wp-suite' );
		}
		return sprintf(
			/* translators: %s: list of sync types, e.g. "Purge All, Single/Home" */
			__( 'Aktiv (%s)', 'depeur-wp-suite' ),
			implode( ', ', $parts )
		);
	}
}
