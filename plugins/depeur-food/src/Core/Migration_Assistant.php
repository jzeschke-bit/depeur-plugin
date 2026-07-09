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
 * BEWUSST READ-ONLY: Diese Seite ändert NICHTS. Sie schreibt keine Optionen, aktiviert keine
 * Module, wechselt kein Theme. Sie ist reine Diagnose + Anleitung. Deshalb braucht sie keinen
 * Nonce/Save-Pfad — nur einen Capability-Check beim Rendern. Das Aktivieren von Modulen macht
 * die Modul-Seite (ModulesPage), das Theme wechselt man in Design → Themes. Diese Trennung ist
 * Absicht: ein Readiness-Check, der selbst Zustände ändert, würde seine eigene Aussage verfälschen.
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

			<h2><?php esc_html_e( '3. Theme', 'depeur-food' ); ?></h2>
			<?php self::render_table( $theme_rows ); ?>

			<h2><?php esc_html_e( '4. Was das neue Theme NICHT mehr braucht (Dedup)', 'depeur-food' ); ?></h2>
			<?php self::render_dedup_notice(); ?>

			<h2><?php esc_html_e( '5. Empfohlene Reihenfolge (Cutover)', 'depeur-food' ); ?></h2>
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
			__( 'Ordner kadence-child nach wp-content/themes/ deployen (Zeile in „Theme" wird grün).', 'depeur-food' ),
			__( 'Child-Theme aktivieren (Design → Themes). Alt-Theme wird inaktiv.', 'depeur-food' ),
			__( 'Permalinks neu speichern (Einstellungen → Permalinks) — Rewrites der Kategorie-Seiten.', 'depeur-food' ),
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
}
