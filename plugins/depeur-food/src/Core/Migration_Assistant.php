<?php
/**
 * Migration_Assistant — geführte Admin-Seite für den Theme-Cutover (Alt-Theme → kadence-child).
 *
 * ZWECK (WOFÜR): Eine einzige Übersichtsseite, die VOR dem Umschalten aufs neue Child-Theme
 * ehrlich anzeigt, ob alles bereitsteht: Sind die nötigen Plugin-Module aktiv? Liefern sie
 * ihre Shortcodes? Ist ACF (harte Abhängigkeit) da? Ist das Child-Theme deployt/aktiv? Läuft
 * noch das Alt-Theme? So sieht Jonas auf einen Blick, ob der Cutover sicher ist — statt fünf
 * Dinge einzeln in verschiedenen Menüs zu prüfen.
 *
 * DIAGNOSE READ-ONLY, AKTIONEN EXPLIZIT GEGATED: Die Prüf-Tabellen (Umgebung/Module/Theme) ändern
 * NICHTS — sie sind reine Anzeige. Zusätzlich gibt es zwei ausdrückliche, per Button ausgelöste
 * Schreib-Aktionen für den Theme-Teil: „Child-Theme installieren" (Datei-Kopie aus dem im Plugin
 * gebündelten Theme) und „Child-Theme aktivieren" (switch_theme + Übernahme der Alt-Theme-
 * Einstellungen). Beide laufen über admin-post.php mit Cap → Nonce → Aktion → PRG-Redirect; die
 * eigentliche Arbeit macht Theme_Installer. Modul-Aktivierung bleibt bei ModulesPage, die
 * Rezeptkategorie-Seiten-Migration beim category-pages-Modul — hier nur verlinkt.
 *
 * ARCHITEKTUR-DISZIPLIN (Plugin-Split): Diese Core-Klasse importiert KEINE Modul-Klassen. Sie
 * prüft nur über generische WordPress-APIs (shortcode_exists, class_exists, get_stylesheet, …)
 * und den Core-ModuleManager. Dadurch bleibt sie stabil, egal welche Module existieren, und
 * verletzt nicht die „keine Cross-Modul-Imports"-Regel.
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
 * Registriert und rendert den Migrations-Assistenten (Readiness-Check + Checkliste).
 *
 * @since 0.3.0
 */
final class Migration_Assistant {

	/**
	 * page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-migration';

	/**
	 * Erforderliche Capability (nur Admins sehen den Cutover-Check).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * admin-post-Action „Theme installieren/aktualisieren".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_INSTALL = 'depeur_food_install_theme';

	/**
	 * admin-post-Action „Theme aktivieren".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_ACTIVATE = 'depeur_food_activate_theme';

	/**
	 * Nonce-Name der Installations-Aktion.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_INSTALL = 'depeur_food_install_theme_nonce';

	/**
	 * Nonce-Name der Aktivierungs-Aktion.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_ACTIVATE = 'depeur_food_activate_theme_nonce';

	/**
	 * admin-post-Action „Customizer-Einstellungen übernehmen".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_IMPORT_MODS = 'depeur_food_import_thememods';

	/**
	 * Nonce-Name der Customizer-Übernahme.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_IMPORT_MODS = 'depeur_food_import_thememods_nonce';

	/**
	 * admin-post-Action „Migration abschließen" (deaktiviert Einmal-Module).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ACTION_FINISH = 'depeur_food_finish_migration';

	/**
	 * Nonce-Name der Abschluss-Aktion.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NONCE_FINISH = 'depeur_food_finish_migration_nonce';

	/**
	 * Filter, über den Module ihre einmaligen Migrations-Schritte im Assistenten anmelden.
	 *
	 * WOFÜR: Entkopplung. Der Core-Assistent importiert KEINE Modul-Klassen; stattdessen hängt
	 * sich jedes Migrations-Modul (category-pages, acf-cleanup, …) selbst per add_filter hier ein
	 * und liefert seinen Schritt (Titel, Status, Link zur eigenen Arbeitsseite, one_time-Flag).
	 * So bleiben alle Migrationen in EINEM Cockpit gebündelt, ohne die Plugin-Split-Regel zu
	 * verletzen. Ein Schritt erscheint nur, wenn sein Modul aktiv (= geladen) ist.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const STEPS_FILTER = 'depeur_food/migration/steps';

	/**
	 * Erwarteter Ordnername (Stylesheet) des neuen Child-Themes.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CHILD_THEME = 'kadence-child';

	/**
	 * Erwartetes Parent-Theme (Template) des Child-Themes.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PARENT_THEME = 'kadence';

	/**
	 * Bekannte Alt-Theme-Stylesheets — deren Aktiv-Sein ist ein „noch nicht umgeschaltet"-Signal.
	 *
	 * @since 0.3.0
	 * @var string[]
	 */
	private const LEGACY_THEMES = array( 'alkipedia', 'alkipedia-3.0', 'alkipedia3' );

	/**
	 * Cutover-Matrix: Welche Module MÜSSEN aktiv sein, und welche Shortcodes müssen sie liefern.
	 *
	 * WOFÜR: Single Source of Truth für den Readiness-Check. Jede Zeile = ein Feature, das das
	 * neue Theme vom Plugin erwartet. „shortcodes" = die konkreten Tags, deren Vorhandensein
	 * beweist, dass das Modul wirklich geladen ist (nicht nur in der Options-Liste steht).
	 *
	 * @since 0.3.0
	 * @var array<string, array{label: string, shortcodes: string[], note: string}>
	 */
	private const REQUIRED_MODULES = array(
		'favorites'         => array(
			'label'      => 'Favoriten (Merkliste + Like-Zähler)',
			'shortcodes' => array( 'thumbnail_favorite_button', 'df_favorites_archive' ),
			'note'       => 'Herz-Button auf Loop-Karten + Favoriten-Archiv.',
		),
		'language-selector' => array(
			'label'      => 'Sprachumschalter (de/com hreflang)',
			'shortcodes' => array( 'df_language_switcher' ),
			'note'       => 'hreflang im <head> + Footer-Umschalter.',
		),
		'schema-engine'     => array(
			'label'      => 'Schema-Anreicherung (Autor / Rank Math / WPRM)',
			'shortcodes' => array(), // Rein serverseitig, kein Shortcode → nur Modul-Aktivität zählt.
			'note'       => 'Reichert Rank-Math-/WPRM-Schema an; keine sichtbare Ausgabe.',
		),
		'newsletter'        => array(
			'label'      => 'Newsletter + App-Promo',
			'shortcodes' => array( 'df_newsletter', 'df_app_promo' ),
			'note'       => 'Flodesk-Inserter + App-Box (inkl. Grau-Overlay).',
		),
		'category-pages'    => array(
			'label'      => 'Kategorie-Seiten + „Was koche ich heute"',
			'shortcodes' => array( 'df_category_page', 'df_recipe_filter' ),
			'note'       => 'Multi-Taxonomie-Engine, Auto-Render geflaggter Seiten, Rezept-Filter.',
		),
		'archive-types'     => array(
			'label'      => 'Archiv-Inhaltstypen (CPTs in Tag-/Autor-/…-Archiven)',
			'shortcodes' => array(), // Reines Query-Modul, kein Shortcode.
			'note'       => 'Speist unterstützte Typen in Standard-Archive ein (Alt-Theme-Verhalten).',
		),
	);

	/**
	 * Verdrahtet die Menü-Registrierung.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register(): void {
		// Priorität 20 = nach „Einstellungen" (Standard) und „Module" (15), ganz unten im Menü.
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ), 20 );

		// Button-Aktionen: Theme installieren/aktualisieren + aktivieren (Cap → Nonce → PRG).
		add_action( 'admin_post_' . self::ACTION_INSTALL, array( __CLASS__, 'handle_install' ) );
		add_action( 'admin_post_' . self::ACTION_ACTIVATE, array( __CLASS__, 'handle_activate' ) );

		// Customizer-Einstellungen (theme_mods) manuell vom Alt-Theme übernehmen.
		add_action( 'admin_post_' . self::ACTION_IMPORT_MODS, array( __CLASS__, 'handle_import_mods' ) );

		// Abschluss: deaktiviert die einmaligen Migrations-Module (Cap → Nonce → PRG).
		add_action( 'admin_post_' . self::ACTION_FINISH, array( __CLASS__, 'handle_finish' ) );
	}

	/**
	 * Meldet die Unterseite unter dem Depeur-Food-Menü an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function add_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Migrations-Assistent', 'depeur-food' ),
			__( 'Migrations-Assistent', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Rendert den Readiness-Check und die Cutover-Checkliste.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$module_rows = self::collect_module_rows();
		$env_rows    = self::collect_environment_rows();
		$theme_rows  = self::collect_theme_rows();

		// „Bereit für den Cutover" = keine roten Zeilen in Umgebung + Modulen. Theme-Zeilen sind
		// bewusst nur Hinweise (man kann alles vorbereiten, BEVOR man das Theme umschaltet).
		$blocking = self::count_status( array_merge( $env_rows, $module_rows ), 'error' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Migrations-Assistent — Theme-Cutover', 'depeur-food' ); ?></h1>

			<p style="max-width: 70em;">
				<?php esc_html_e( 'Diese Seite prüft (ohne etwas zu ändern), ob der Umstieg vom Alt-Theme „Alkipedia 3.0" auf das schlanke Child-Theme „kadence-child" sicher ist. Das Theme trägt nur noch die Optik; alle Logik kommt aus diesem Plugin. Erst wenn oben alles grün ist, das Theme wechseln.', 'depeur-food' ); ?>
			</p>

			<?php self::render_action_notice(); ?>

			<?php if ( 0 === $blocking ) : ?>
				<div class="notice notice-success inline"><p><strong><?php esc_html_e( '✓ Bereit: Plugin-Seite ist für den Theme-Wechsel vorbereitet.', 'depeur-food' ); ?></strong></p></div>
			<?php else : ?>
				<div class="notice notice-error inline"><p><strong><?php
					/* translators: %d: Anzahl blockierender Punkte. */
					echo esc_html( sprintf( _n( '%d blockierender Punkt — bitte vor dem Theme-Wechsel lösen.', '%d blockierende Punkte — bitte vor dem Theme-Wechsel lösen.', $blocking, 'depeur-food' ), $blocking ) );
				?></strong></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( '1. Umgebung', 'depeur-food' ); ?></h2>
			<?php self::render_table( $env_rows ); ?>

			<h2><?php esc_html_e( '2. Benötigte Plugin-Module', 'depeur-food' ); ?></h2>
			<p class="description" style="max-width: 70em;">
				<?php esc_html_e( 'Jedes Modul liefert eine Funktion, die das Alt-Theme früher selbst hatte. „Modul aktiv" heißt: in der Modul-Liste angehakt. „Shortcode bereit" heißt: das Modul ist wirklich geladen und liefert seine Ausgabe. Beides muss stimmen.', 'depeur-food' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=depeur-food-modules' ) ); ?>"><?php esc_html_e( '→ Module verwalten', 'depeur-food' ); ?></a>
			</p>
			<?php self::render_table( $module_rows ); ?>

			<h2><?php esc_html_e( '3. Geführte Migrationen', 'depeur-food' ); ?></h2>
			<p class="description" style="max-width: 70em;">
				<?php esc_html_e( 'Einmalige Migrations-Schritte aus den aktiven Modulen — hier gebündelt. Jeder Schritt öffnet seine eigene Arbeitsseite (mit Vorschau, Backup, Ausführen). Erscheint ein Schritt nicht, ist das zuständige Modul nicht aktiv.', 'depeur-food' ); ?>
			</p>
			<?php self::render_steps(); ?>

			<h2><?php esc_html_e( '4. Theme installieren & aktivieren', 'depeur-food' ); ?></h2>
			<?php self::render_table( $theme_rows ); ?>
			<?php self::render_theme_actions( $blocking ); ?>

			<h2><?php esc_html_e( '5. Migration abschließen & aufräumen', 'depeur-food' ); ?></h2>
			<?php self::render_finish(); ?>

			<h2><?php esc_html_e( '6. Was das neue Theme NICHT mehr braucht (Dedup)', 'depeur-food' ); ?></h2>
			<?php self::render_dedup_notice(); ?>

			<h2><?php esc_html_e( '7. Empfohlene Reihenfolge (Cutover)', 'depeur-food' ); ?></h2>
			<?php self::render_checklist(); ?>
		</div>
		<?php
	}

	/**
	 * Baut die Umgebungs-Prüfzeilen (Plugin-Bootstrap, ACF-Hard-Dependency).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array{status: string, label: string, detail: string}>
	 */
	private static function collect_environment_rows(): array {
		$rows = array();

		// Plugin-Helper vorhanden = Plugin gebootet. (Dass diese Seite überhaupt rendert, ist
		// bereits ein starker Indikator; die Prüfung bleibt aus Ehrlichkeit trotzdem explizit.)
		$rows[] = self::row(
			function_exists( 'depeur_food' ) ? 'ok' : 'error',
			__( 'Plugin depeur-food gebootet', 'depeur-food' ),
			function_exists( 'depeur_food' )
				? __( 'Der globale Helper depeur_food() ist verfügbar.', 'depeur-food' )
				: __( 'depeur_food() fehlt — Plugin nicht korrekt geladen.', 'depeur-food' )
		);

		// ACF ist harte Laufzeit-Abhängigkeit des Plugins (Editor-UI der Felder). Ohne ACF
		// bootet das Plugin gar nicht — die Zeile ist Absicherung/Doku.
		$acf_active = class_exists( 'ACF' );
		$rows[]     = self::row(
			$acf_active ? 'ok' : 'error',
			__( 'ACF aktiv (harte Abhängigkeit)', 'depeur-food' ),
			$acf_active
				? __( 'ACF liefert die Editor-UI der Custom Fields. Bleibt dauerhaft aktiv.', 'depeur-food' )
				: __( 'ACF ist nicht aktiv — Plugin-Funktionen stehen still.', 'depeur-food' )
		);

		return $rows;
	}

	/**
	 * Baut je eine Prüfzeile pro benötigtem Modul (aktiv? Shortcodes bereit?).
	 *
	 * Logik: rot, wenn das Modul nicht aktiv ist. Gelb, wenn aktiv, aber ein erwarteter
	 * Shortcode (noch) nicht registriert ist (z. B. Ladereihenfolge/Fehlkonfiguration).
	 * Grün, wenn aktiv und alle Shortcodes da (bzw. das Modul rein serverseitig arbeitet).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array{status: string, label: string, detail: string}>
	 */
	private static function collect_module_rows(): array {
		$active = ModuleManager::get_active_module_slugs();
		$rows   = array();

		foreach ( self::REQUIRED_MODULES as $slug => $meta ) {
			$is_active = in_array( $slug, $active, true );

			if ( ! $is_active ) {
				$rows[] = self::row(
					'error',
					esc_html( $meta['label'] ),
					sprintf(
						/* translators: %s: Modul-Slug. */
						esc_html__( 'Modul „%s" ist nicht aktiv. Unter „Module" anhaken.', 'depeur-food' ),
						$slug
					)
				);
				continue;
			}

			// Modul aktiv → prüfen, ob alle erwarteten Shortcodes wirklich registriert sind.
			$missing = array();
			foreach ( $meta['shortcodes'] as $tag ) {
				if ( ! shortcode_exists( $tag ) ) {
					$missing[] = $tag;
				}
			}

			if ( ! empty( $missing ) ) {
				$rows[] = self::row(
					'warning',
					esc_html( $meta['label'] ),
					sprintf(
						/* translators: %s: kommagetrennte Shortcode-Liste. */
						esc_html__( 'Modul aktiv, aber Shortcode(s) fehlen: %s. Ladereihenfolge prüfen.', 'depeur-food' ),
						esc_html( implode( ', ', $missing ) )
					)
				);
				continue;
			}

			$rows[] = self::row( 'ok', esc_html( $meta['label'] ), esc_html( $meta['note'] ) );
		}

		return $rows;
	}

	/**
	 * Baut die Theme-Prüfzeilen (Child deployt/aktiv, Parent vorhanden, Alt-Theme noch aktiv?).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array{status: string, label: string, detail: string}>
	 */
	private static function collect_theme_rows(): array {
		$rows           = array();
		$current_style  = get_stylesheet(); // Aktiver (Child-)Theme-Ordner.
		$current_parent = get_template();   // Aktives Parent-/Template-Theme.

		// a) Ist das Child-Theme überhaupt auf dem Server (deployt)?
		$child        = wp_get_theme( self::CHILD_THEME );
		$child_exists = $child->exists();
		$rows[]       = self::row(
			$child_exists ? 'ok' : 'warning',
			__( 'Child-Theme „kadence-child" deployt', 'depeur-food' ),
			$child_exists
				? __( 'Der Theme-Ordner ist vorhanden und installierbar.', 'depeur-food' )
				: __( 'Ordner themes/kadence-child noch nicht auf den Server kopiert.', 'depeur-food' )
		);

		// b) Ist das Parent-Theme Kadence vorhanden? (Child braucht es als Basis.)
		$parent        = wp_get_theme( self::PARENT_THEME );
		$parent_exists = $parent->exists();
		$rows[]        = self::row(
			$parent_exists ? 'ok' : 'error',
			__( 'Parent-Theme „Kadence" installiert', 'depeur-food' ),
			$parent_exists
				? __( 'Kadence ist als Basis vorhanden.', 'depeur-food' )
				: __( 'Kadence fehlt — Child-Theme kann ohne Parent nicht laufen.', 'depeur-food' )
		);

		// c) Läuft noch ein Alt-Theme? (Nur ein Hinweis — der Wechsel ist der letzte Schritt.)
		$legacy_active = in_array( strtolower( (string) $current_style ), self::LEGACY_THEMES, true )
			|| in_array( strtolower( (string) $current_parent ), self::LEGACY_THEMES, true );
		if ( $legacy_active ) {
			$rows[] = self::row(
				'info',
				__( 'Aktives Theme', 'depeur-food' ),
				sprintf(
					/* translators: %s: aktiver Theme-Ordnername. */
					esc_html__( 'Noch das Alt-Theme aktiv: „%s". Wechsel ist der letzte Cutover-Schritt.', 'depeur-food' ),
					esc_html( (string) $current_style )
				)
			);
		} elseif ( self::CHILD_THEME === $current_style ) {
			$rows[] = self::row(
				'ok',
				__( 'Aktives Theme', 'depeur-food' ),
				__( 'Das Child-Theme „kadence-child" ist aktiv — Cutover abgeschlossen.', 'depeur-food' )
			);
		} else {
			$rows[] = self::row(
				'info',
				__( 'Aktives Theme', 'depeur-food' ),
				sprintf(
					/* translators: %s: aktiver Theme-Ordnername. */
					esc_html__( 'Aktiv: „%s". Weder Alt-Theme noch kadence-child.', 'depeur-food' ),
					esc_html( (string) $current_style )
				)
			);
		}

		return $rows;
	}

	/**
	 * Zählt Zeilen eines bestimmten Status (z. B. blockierende „error").
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, array{status: string, label: string, detail: string}> $rows   Prüfzeilen.
	 * @param string                                                            $status Gesuchter Status.
	 * @return int Anzahl.
	 */
	private static function count_status( array $rows, string $status ): int {
		$count = 0;
		foreach ( $rows as $r ) {
			if ( $status === $r['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Kleiner Konstruktor für eine Prüfzeile.
	 *
	 * @since 0.3.0
	 *
	 * @param string $status Einer von ok|warning|error|info.
	 * @param string $label  Zeilentitel (bereits escaped, wenn dynamisch).
	 * @param string $detail Detailtext (bereits escaped, wenn dynamisch).
	 * @return array{status: string, label: string, detail: string}
	 */
	private static function row( string $status, string $label, string $detail ): array {
		return array(
			'status' => $status,
			'label'  => $label,
			'detail' => $detail,
		);
	}

	/**
	 * Rendert eine Prüf-Tabelle mit Status-Symbol pro Zeile.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, array{status: string, label: string, detail: string}> $rows Prüfzeilen.
	 * @return void
	 */
	private static function render_table( array $rows ): void {
		$icons = array(
			'ok'      => '✓',
			'warning' => '⚠',
			'error'   => '✗',
			'info'    => 'ℹ',
		);
		$colors = array(
			'ok'      => '#46b450',
			'warning' => '#dba617',
			'error'   => '#d63638',
			'info'    => '#72aee6',
		);
		?>
		<table class="widefat striped" style="max-width: 75em; margin-bottom: 1.5em;">
			<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<?php
					$status = isset( $icons[ $r['status'] ] ) ? $r['status'] : 'info';
					?>
					<tr>
						<td style="width: 2.5em; text-align: center; font-size: 1.3em; color: <?php echo esc_attr( $colors[ $status ] ); ?>;">
							<?php echo esc_html( $icons[ $status ] ); ?>
						</td>
						<td style="width: 22em;"><strong><?php echo wp_kses_post( $r['label'] ); ?></strong></td>
						<td><span class="description"><?php echo wp_kses_post( $r['detail'] ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Erklärt, welche Alt-Theme-Funktionen das neue Theme bewusst NICHT mehr enthält.
	 *
	 * WOFÜR: Vermeidet Doppelung. Wer im neuen Child-Theme diese Dinge sucht, findet hier die
	 * Antwort „liefert jetzt das Plugin". Verhindert versehentliches Zurück-Kopieren aus dem
	 * Alt-Theme (was zu doppeltem hreflang, doppeltem Schema usw. führen würde).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_dedup_notice(): void {
		$items = array(
			__( 'hreflang-Tags im <head> → Modul language-selector', 'depeur-food' ),
			__( 'Footer-Sprachumschalter → [df_language_switcher] (Theme platziert nur)', 'depeur-food' ),
			__( 'Favoriten-Herz + Like-Zähler + AJAX → Modul favorites', 'depeur-food' ),
			__( 'Multi-Taxonomie-Kategorie-Seiten + Pagination → Modul category-pages', 'depeur-food' ),
			__( '„Was koche ich heute" Filter → [df_recipe_filter] (REST)', 'depeur-food' ),
			__( 'Schema-Anreicherung (Autor/Rank Math/WPRM) → Modul schema-engine', 'depeur-food' ),
			__( 'Newsletter-Inserter + App-Promo → Modul newsletter', 'depeur-food' ),
			__( 'Gleich hohe Loop-Karten → Core-CSS df-loop-grid.css (frontend-weit)', 'depeur-food' ),
		);
		?>
		<ul style="max-width: 70em; list-style: disc; padding-left: 1.5em;">
			<?php foreach ( $items as $item ) : ?>
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p class="description" style="max-width: 70em;">
			<?php esc_html_e( 'Diese Funktionen bitte NICHT aus dem Alt-Theme ins Child kopieren — sonst doppelte Ausgabe.', 'depeur-food' ); ?>
		</p>
		<?php
	}

	/**
	 * Rendert die empfohlene Cutover-Reihenfolge als nummerierte Checkliste.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_checklist(): void {
		$steps = array(
			__( 'Backup von Datenbank + wp-content anlegen (Pflicht vor Theme-Wechsel).', 'depeur-food' ),
			__( 'Oben alle Punkte in „Umgebung" und „Module" grün? Sonst zuerst dort lösen.', 'depeur-food' ),
			__( 'Alte Rezeptkategorie-Seiten migrieren (Menü „Kategorie-Seiten migrieren"), falls noch nicht geschehen.', 'depeur-food' ),
			__( 'Unter „3. Theme" auf „Child-Theme installieren" klicken (kopiert das gebündelte Theme).', 'depeur-food' ),
			__( 'Danach „Child-Theme aktivieren" klicken — übernimmt Menüs/Logo vom Alt-Theme und schaltet um.', 'depeur-food' ),
			__( 'Seiten-Cache leeren (RunCache/Cloudflare), damit das neue CSS geladen wird.', 'depeur-food' ),
			__( 'Sichtprüfung: Herz auf Karten, Sprachumschalter im Footer, Kategorie-Seite + „Was koche ich heute", Autoren-Box, Newsletter-Overlay.', 'depeur-food' ),
			__( 'Erst nach erfolgreicher Sichtprüfung das Alt-Theme entfernen (optional, aufräumen).', 'depeur-food' ),
		);
		?>
		<ol style="max-width: 70em; padding-left: 1.5em;">
			<?php foreach ( $steps as $step ) : ?>
				<li style="margin-bottom: 0.4em;"><?php echo esc_html( $step ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Rendert das Aktions-Panel unter der Theme-Tabelle: Installieren / Aktualisieren / Aktivieren.
	 *
	 * WOFÜR: Der eine Ort, an dem der Nutzer das gebündelte Child-Theme per Klick auf den Server
	 * bringt und scharfschaltet — ohne rsync/FTP. Welche Buttons erscheinen, hängt vom Ist-Zustand
	 * ab (nicht installiert → Installieren; installiert+veraltet → Aktualisieren; installiert+
	 * inaktiv → Aktivieren; aktiv → nur Bestätigung).
	 *
	 * @since 0.3.0
	 *
	 * @param int $blocking Anzahl blockierender Punkte aus Umgebung+Modulen (für eine Warnung
	 *                      beim Aktivieren, falls noch nicht alles grün ist).
	 * @return void
	 */
	private static function render_theme_actions( int $blocking ): void {
		// Ohne gebündeltes Theme im Plugin gibt es nichts zu installieren (Deploy unvollständig).
		if ( ! Theme_Installer::bundle_present() ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Das gebündelte Theme fehlt im Plugin — bitte das Plugin vollständig neu deployen.', 'depeur-food' ) . '</p></div>';
			return;
		}

		$installed = Theme_Installer::is_installed();
		$active    = Theme_Installer::is_active();
		$bundled_v = Theme_Installer::bundled_version();
		?>
		<div style="max-width: 75em; padding: 1em 1.25em; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #2271b1; margin-bottom: 1.5em;">
			<p style="margin-top: 0;">
				<strong><?php esc_html_e( 'Child-Theme aus dem Plugin installieren & aktivieren', 'depeur-food' ); ?></strong><br />
				<span class="description">
					<?php
					printf(
						/* translators: %s: Versions-String des gebündelten Themes. */
						esc_html__( 'Das Theme „kadence-child" (v%s) ist in diesem Plugin gebündelt. Kein manuelles Hochladen nötig.', 'depeur-food' ),
						esc_html( $bundled_v )
					);
					?>
				</span>
			</p>

			<?php if ( $active ) : ?>
				<p style="color: #46b450;"><strong><?php esc_html_e( '✓ Das Child-Theme ist aktiv.', 'depeur-food' ); ?></strong></p>
			<?php endif; ?>

			<?php if ( $installed ) : ?>
				<p class="description" style="max-width: 60em;">
					<?php esc_html_e( 'Wichtig: Ein Plugin-Update aktualisiert NICHT automatisch die installierte Theme-Kopie. Nach Änderungen am Theme hier „Theme-Dateien aktualisieren" klicken — das kopiert die gebündelten Dateien neu (Customizer-Einstellungen bleiben erhalten).', 'depeur-food' ); ?>
				</p>
			<?php endif; ?>

			<p>
				<?php // Installieren bzw. Dateien neu kopieren — immer verfügbar (dieselbe Aktion). ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 0.5em;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_INSTALL ); ?>" />
					<?php wp_nonce_field( self::ACTION_INSTALL, self::NONCE_INSTALL ); ?>
					<?php
					submit_button(
						$installed ? __( 'Theme-Dateien aktualisieren', 'depeur-food' ) : __( 'Child-Theme installieren', 'depeur-food' ),
						'primary',
						'submit',
						false
					);
					?>
				</form>

				<?php // Button „Aktivieren" — nur wenn installiert und noch nicht aktiv. ?>
				<?php if ( $installed && ! $active ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;"
						onsubmit="return confirm('<?php echo esc_js( __( 'Child-Theme jetzt aktivieren? Menüs/Logo werden vom aktuellen Theme übernommen.', 'depeur-food' ) ); ?>');">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_ACTIVATE ); ?>" />
						<?php wp_nonce_field( self::ACTION_ACTIVATE, self::NONCE_ACTIVATE ); ?>
						<?php submit_button( __( 'Child-Theme aktivieren', 'depeur-food' ), 'secondary', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</p>

			<?php if ( $installed && ! $active && $blocking > 0 ) : ?>
				<p class="description" style="color: #d63638;">
					<?php esc_html_e( 'Hinweis: Oben sind noch nicht alle Punkte grün. Du kannst trotzdem aktivieren, solltest aber danach genau gegenprüfen.', 'depeur-food' ); ?>
				</p>
			<?php endif; ?>

			<?php self::render_mods_import(); ?>
		</div>
		<?php
	}

	/**
	 * Rendert den Block „Kadence-Customizer-Einstellungen übernehmen".
	 *
	 * WOFÜR: Kadence speichert seine Design-Einstellungen pro Theme (theme_mods). Wer das Child
	 * bereits (z. B. über Design → Themes) aktiviert hat, steht evtl. auf Standardwerten. Hier holt
	 * man die Einstellungen eines Alt-Themes gezielt aufs Child — mit Backup des aktuellen Standes.
	 * Nützlich als Reparatur, falls die automatische Übernahme beim Wechsel nicht griff.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_mods_import(): void {
		$sources = Theme_Installer::mod_sources();
		if ( empty( $sources ) ) {
			return; // Keine Quelle mit gespeicherten Einstellungen vorhanden.
		}
		?>
		<hr style="margin: 1em 0;" />
		<p style="margin-bottom: 0.3em;">
			<strong><?php esc_html_e( 'Kadence-Customizer-Einstellungen übernehmen', 'depeur-food' ); ?></strong><br />
			<span class="description">
				<?php esc_html_e( 'Kopiert die Design-Einstellungen (Header/Footer/Farben/Layout/Menüs/Logo) eines Alt-Themes aufs Child. Der aktuelle Child-Stand wird vorher gesichert.', 'depeur-food' ); ?>
			</span>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			onsubmit="return confirm('<?php echo esc_js( __( 'Einstellungen des gewählten Alt-Themes jetzt aufs Child kopieren? Der aktuelle Stand wird gesichert.', 'depeur-food' ) ); ?>');">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_IMPORT_MODS ); ?>" />
			<?php wp_nonce_field( self::ACTION_IMPORT_MODS, self::NONCE_IMPORT_MODS ); ?>
			<select name="from" style="min-width: 18em; margin-right: 0.5em;">
				<?php foreach ( $sources as $stylesheet => $name ) : ?>
					<option value="<?php echo esc_attr( $stylesheet ); ?>"><?php echo esc_html( $name . ' (' . $stylesheet . ')' ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Einstellungen übernehmen', 'depeur-food' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	/**
	 * Zeigt die Rückmeldung nach einer Theme-Aktion (Erfolg/Fehler) aus dem PRG-Redirect.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_action_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
		$status = isset( $_GET['df_status'] ) ? sanitize_key( wp_unslash( $_GET['df_status'] ) ) : '';
		if ( '' === $status ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Read nach eigenem PRG-Redirect.
		$msg = isset( $_GET['df_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['df_msg'] ) ) : '';

		$map = array(
			'installed' => array( 'success', __( 'Child-Theme installiert.', 'depeur-food' ) ),
			'updated'   => array( 'success', __( 'Child-Theme aktualisiert.', 'depeur-food' ) ),
			'activated' => array( 'success', __( 'Child-Theme aktiviert. Bitte den Seiten-Cache leeren.', 'depeur-food' ) ),
			'mods_imported' => array( 'success', __( 'Customizer-Einstellungen übernommen. Bitte den Seiten-Cache leeren.', 'depeur-food' ) ),
			'finished'  => array( 'success', '' === $msg ? __( 'Migration abgeschlossen.', 'depeur-food' ) : $msg ),
			'nochange'  => array( 'info', __( 'Nichts abzuschließen — keine einmaligen Migrations-Module aktiv.', 'depeur-food' ) ),
			'error'     => array( 'error', '' === $msg ? __( 'Aktion fehlgeschlagen.', 'depeur-food' ) : $msg ),
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

	/**
	 * Handler „Theme installieren/aktualisieren": Cap → Nonce → Theme_Installer::install() → PRG.
	 *
	 * Cap install_themes = das WordPress-Recht, Theme-Dateien zu schreiben.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function handle_install(): void {
		if ( ! current_user_can( 'install_themes' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung zum Installieren von Themes.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_INSTALL, self::NONCE_INSTALL );

		// „updated" statt „installed" melden, wenn schon eine Installation bestand.
		$was_installed = Theme_Installer::is_installed();
		$result        = Theme_Installer::install();

		if ( is_wp_error( $result ) ) {
			self::redirect( 'error', $result->get_error_message() );
		}
		self::redirect( $was_installed ? 'updated' : 'installed' );
	}

	/**
	 * Handler „Theme aktivieren": Cap → Nonce → Theme_Installer::activate() → PRG.
	 *
	 * Cap switch_themes = das WordPress-Recht, das aktive Theme zu wechseln.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function handle_activate(): void {
		if ( ! current_user_can( 'switch_themes' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung zum Wechseln des Themes.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_ACTIVATE, self::NONCE_ACTIVATE );

		$result = Theme_Installer::activate();

		if ( is_wp_error( $result ) ) {
			self::redirect( 'error', $result->get_error_message() );
		}
		self::redirect( 'activated' );
	}

	/**
	 * Handler „Customizer-Einstellungen übernehmen": Cap → Nonce → Quelle prüfen → kopieren → PRG.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function handle_import_mods(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_IMPORT_MODS, self::NONCE_IMPORT_MODS );

		$from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';

		// import_customizer_from() validiert die Quelle zusätzlich gegen die Liste gültiger Themes.
		$result = Theme_Installer::import_customizer_from( $from );

		if ( is_wp_error( $result ) ) {
			self::redirect( 'error', $result->get_error_message() );
		}
		self::redirect( 'mods_imported' );
	}

	/**
	 * Zentraler PRG-Redirect zurück auf die Assistenten-Seite mit Status (+ optionaler Meldung).
	 *
	 * @since 0.3.0
	 *
	 * @param string $status Status-Slug (installed|updated|activated|error).
	 * @param string $msg    Optionale (Fehler-)Meldung.
	 * @return void
	 */
	private static function redirect( string $status, string $msg = '' ): void {
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
	 * Sammelt & normalisiert die von Modulen angemeldeten Migrations-Schritte.
	 *
	 * Die Schritte kommen aus dem Filter STEPS_FILTER (Module hängen sich selbst ein). Jeder
	 * Eintrag wird defensiv auf die erwarteten Schlüssel normalisiert — auch wenn er formal aus
	 * einem Filter (potenziell fremd) stammt, behandeln wir ihn wie unvertraute Eingabe.
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, array{id:string,title:string,description:string,status:string,status_text:string,action_url:string,action_label:string,module:string,one_time:bool}>
	 */
	private static function collect_steps(): array {
		$raw = apply_filters( self::STEPS_FILTER, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$defaults = array(
			'id'           => '',
			'title'        => '',
			'description'  => '',
			'status'       => 'todo',
			'status_text'  => '',
			'action_url'   => '',
			'action_label' => __( 'Öffnen', 'depeur-food' ),
			'module'       => '',
			'one_time'     => false,
		);

		$steps = array();
		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['title'] ) ) {
				continue; // Ohne Titel kein sinnvoller Schritt.
			}
			$step             = wp_parse_args( $entry, $defaults );
			$step['title']    = (string) $step['title'];
			$step['module']   = sanitize_key( (string) $step['module'] );
			$step['one_time'] = (bool) $step['one_time'];
			$steps[]          = $step;
		}

		return $steps;
	}

	/**
	 * Rendert die angemeldeten Migrations-Schritte als Tabelle mit Status + Link zur Arbeitsseite.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_steps(): void {
		$steps = self::collect_steps();

		if ( empty( $steps ) ) {
			echo '<p class="description">' . esc_html__( 'Keine Migrations-Schritte angemeldet (kein Migrations-Modul aktiv).', 'depeur-food' ) . '</p>';
			return;
		}

		$icons = array(
			'todo'     => '○',
			'done'     => '✓',
			'optional' => 'ℹ',
		);
		$colors = array(
			'todo'     => '#dba617',
			'done'     => '#46b450',
			'optional' => '#72aee6',
		);
		?>
		<table class="widefat striped" style="max-width: 75em; margin-bottom: 1.5em;">
			<tbody>
				<?php foreach ( $steps as $step ) : ?>
					<?php $st = isset( $icons[ $step['status'] ] ) ? $step['status'] : 'todo'; ?>
					<tr>
						<td style="width: 2.5em; text-align: center; font-size: 1.3em; color: <?php echo esc_attr( $colors[ $st ] ); ?>;">
							<?php echo esc_html( $icons[ $st ] ); ?>
						</td>
						<td style="width: 24em;">
							<strong><?php echo esc_html( $step['title'] ); ?></strong>
							<?php if ( $step['one_time'] ) : ?>
								<br /><span class="description"><?php esc_html_e( 'Einmal-Werkzeug — wird beim Abschluss deaktiviert.', 'depeur-food' ); ?></span>
							<?php endif; ?>
							<br /><span class="description"><?php echo esc_html( $step['description'] ); ?></span>
						</td>
						<td>
							<?php if ( '' !== $step['status_text'] ) : ?>
								<span class="description"><?php echo esc_html( $step['status_text'] ); ?></span><br />
							<?php endif; ?>
							<?php if ( '' !== $step['action_url'] ) : ?>
								<a class="button button-secondary" href="<?php echo esc_url( $step['action_url'] ); ?>"><?php echo esc_html( $step['action_label'] ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Rendert den Abschluss-Block: deaktiviert per Klick die einmaligen Migrations-Module.
	 *
	 * WOFÜR: Nach getaner Migration sind die Einmal-Werkzeuge (z. B. acf-cleanup) nur noch
	 * Menü-Ballast. Ein bewusster Klick am Ende räumt sie weg — deaktiviert NUR die Module, die
	 * sich selbst als one_time angemeldet haben (Feature-Module bleiben unangetastet). Zeigt vorab
	 * transparent, welche Module das betrifft.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	private static function render_finish(): void {
		$steps    = self::collect_steps();
		$one_time = array();
		foreach ( $steps as $step ) {
			if ( $step['one_time'] && '' !== $step['module'] ) {
				$one_time[ $step['module'] ] = $step['title'];
			}
		}

		if ( empty( $one_time ) ) {
			echo '<p class="description" style="max-width: 70em;">' . esc_html__( 'Aktuell sind keine einmaligen Migrations-Module aktiv, die deaktiviert werden müssten.', 'depeur-food' ) . '</p>';
			return;
		}
		?>
		<div style="max-width: 75em; padding: 1em 1.25em; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid #dba617; margin-bottom: 1.5em;">
			<p style="margin-top: 0;">
				<?php esc_html_e( 'Wenn alle Migrationen erledigt und geprüft sind, deaktiviert dieser Schritt die folgenden Einmal-Werkzeuge (Daten bleiben unberührt, nur die Module werden abgeschaltet):', 'depeur-food' ); ?>
			</p>
			<ul style="list-style: disc; padding-left: 1.5em;">
				<?php foreach ( $one_time as $slug => $title ) : ?>
					<li><strong><?php echo esc_html( $title ); ?></strong> <code><?php echo esc_html( $slug ); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				onsubmit="return confirm('<?php echo esc_js( __( 'Einmalige Migrations-Module jetzt deaktivieren? Du kannst sie bei Bedarf über „Module" wieder aktivieren.', 'depeur-food' ) ); ?>');">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_FINISH ); ?>" />
				<?php wp_nonce_field( self::ACTION_FINISH, self::NONCE_FINISH ); ?>
				<?php submit_button( __( 'Migration abschließen & Einmal-Module deaktivieren', 'depeur-food' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handler „Migration abschließen": deaktiviert die als one_time angemeldeten Module.
	 *
	 * Sicherheit: Cap → Nonce. Es werden AUSSCHLIESSLICH Module aus der Master-Liste entfernt, die
	 * sich selbst (über den Steps-Filter) als one_time deklariert haben — nie ein Feature-Modul.
	 * Danach aufgeschobener Rewrite-Flush (analog ModulesPage) und PRG-Redirect.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function handle_finish(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::ACTION_FINISH, self::NONCE_FINISH );

		// Zu deaktivierende Module = die aktuell als one_time angemeldeten Schritte.
		$to_disable = array();
		foreach ( self::collect_steps() as $step ) {
			if ( $step['one_time'] && '' !== $step['module'] ) {
				$to_disable[] = $step['module'];
			}
		}
		$to_disable = array_values( array_unique( $to_disable ) );

		if ( empty( $to_disable ) ) {
			self::redirect( 'nochange' );
		}

		$active    = ModuleManager::get_active_module_slugs();
		$remaining = array_values( array_diff( $active, $to_disable ) );

		update_option( ModuleManager::OPTION_MODULES, $remaining );
		// Aufgeschobener Flush (ModulesPage-Mechanik), da ein deaktiviertes Modul Rewrites hatte.
		update_option( 'depeur_food_modules_flush', 1, false );

		$msg = sprintf(
			/* translators: %s: kommagetrennte Modul-Slugs. */
			__( 'Migration abgeschlossen. Deaktiviert: %s.', 'depeur-food' ),
			implode( ', ', $to_disable )
		);
		self::redirect( 'finished', $msg );
	}
}
