<?php
/**
 * Theme_Installer — installiert & aktiviert das im Plugin gebündelte Child-Theme „kadence-child".
 *
 * ZWECK (WOFÜR): Der Nutzer soll das neue Child-Theme NICHT mehr manuell per rsync/FTP auf den
 * Server schieben müssen. Weil das Theme im Plugin mitgeliefert wird (Ordner
 * bundled-theme/kadence-child), reicht ein Klick im Migrations-Assistenten:
 *   1) INSTALLIEREN  = die gebündelten Theme-Dateien nach wp-content/themes/kadence-child kopieren.
 *   2) AKTIVIEREN    = das Theme scharfschalten (switch_theme) + die alten Theme-Einstellungen
 *                      (Menüs/Logo/… via theme_mods) vom bisher aktiven Theme übernehmen.
 *
 * WARUM GEBÜNDELT (statt GitHub-Download): Das Repo ist privat → ein Runtime-Download bräuchte
 * einen GitHub-Token in der DB (Sicherheits-Risiko) + Netz-Abhängigkeit + Versions-Drift.
 * Das Plugin wird ohnehin deployt; das Theme reist einfach mit. Eine Quelle der Wahrheit:
 * bundled-theme/kadence-child (kein zweiter themes/-Ordner mehr, kein Drift).
 *
 * SICHERHEIT: Reines Kopieren aus einem plugin-internen, vertrauenswürdigen Verzeichnis über die
 * WordPress-Dateisystem-Abstraktion (WP_Filesystem). Keine Uploads, keine externen Quellen. Die
 * Aufrufer (Migration_Assistant) setzen Capability- + Nonce-Gates davor.
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
 * Installiert/aktualisiert/aktiviert das gebündelte Child-Theme.
 *
 * @since 0.3.0
 */
final class Theme_Installer {

	/**
	 * Ordnername (Stylesheet) des Child-Themes.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const CHILD_SLUG = 'kadence-child';

	/**
	 * Ordnername des benötigten Parent-Themes.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const PARENT_SLUG = 'kadence';

	/**
	 * Option, in der wir den Child-theme_mods-Stand VOR einer Übernahme sichern.
	 *
	 * Dient doppelt: (1) Sicherheitsnetz zum Zurücksetzen, (2) „schon einmal migriert"-Sentinel,
	 * damit die automatische Übernahme beim Theme-Wechsel nur EINMAL läuft und danach manuelle
	 * Anpassungen nicht überschreibt.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const MODS_BACKUP_OPTION = 'depeur_food_thememods_backup';

	/**
	 * Verdrahtet den Theme-Wechsel-Listener (greift bei JEDER Aktivierung, auch über Design → Themes).
	 *
	 * WOFÜR: Kadence speichert seine Customizer-Einstellungen (Header/Footer/Farben/Layout/…) in
	 * `theme_mods_{stylesheet}` — also PRO Theme. Beim Wechsel auf ein anderes (Child-)Theme sind
	 * sie sonst „weg". Dieser Listener überträgt sie vom vorherigen Theme aufs Child. Wird aus
	 * Plugin::init immer registriert (Theme-Wechsel passieren im Admin, der Hook ist billig).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'after_switch_theme', array( __CLASS__, 'on_after_switch' ), 10, 2 );
	}

	/**
	 * Läuft NACH jedem Theme-Wechsel: überträgt Customizer-Einstellungen aufs Child (einmalig).
	 *
	 * @since 0.3.0
	 *
	 * @param string         $old_name  Name des vorherigen Themes.
	 * @param \WP_Theme|null $old_theme WP_Theme-Objekt des vorherigen Themes.
	 * @return void
	 */
	public static function on_after_switch( $old_name, $old_theme = null ): void {
		// Nur reagieren, wenn JETZT unser Child aktiv ist.
		if ( self::CHILD_SLUG !== get_stylesheet() ) {
			return;
		}
		$from = ( $old_theme instanceof \WP_Theme ) ? $old_theme->get_stylesheet() : '';
		self::migrate_theme_mods_once( $from );
	}

	/**
	 * Absoluter Pfad zum im Plugin gebündelten Theme (Quelle der Kopie).
	 *
	 * @since 0.3.0
	 *
	 * @return string Pfad ohne Trailing-Slash.
	 */
	private static function bundle_dir(): string {
		return untrailingslashit( DEPEUR_FOOD_PATH . 'bundled-theme/' . self::CHILD_SLUG );
	}

	/**
	 * Ist das gebündelte Theme im Plugin überhaupt vorhanden? (Deploy-Vollständigkeits-Check.)
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function bundle_present(): bool {
		return is_file( self::bundle_dir() . '/style.css' );
	}

	/**
	 * Ist das Child-Theme bereits in wp-content/themes installiert?
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function is_installed(): bool {
		return wp_get_theme( self::CHILD_SLUG )->exists();
	}

	/**
	 * Ist das Child-Theme aktuell das aktive Theme?
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return self::CHILD_SLUG === get_stylesheet();
	}

	/**
	 * Ist das Parent-Theme Kadence installiert? (Child kann ohne Parent nicht laufen.)
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function parent_installed(): bool {
		return wp_get_theme( self::PARENT_SLUG )->exists();
	}

	/**
	 * Version aus der GEBÜNDELTEN style.css (ohne Installation lesbar).
	 *
	 * @since 0.3.0
	 *
	 * @return string Versions-String oder '' wenn unbekannt.
	 */
	public static function bundled_version(): string {
		$data = get_file_data( self::bundle_dir() . '/style.css', array( 'Version' => 'Version' ) );

		return isset( $data['Version'] ) ? (string) $data['Version'] : '';
	}

	/**
	 * Version des INSTALLIERTEN Child-Themes.
	 *
	 * @since 0.3.0
	 *
	 * @return string Versions-String oder '' wenn nicht installiert.
	 */
	public static function installed_version(): string {
		if ( ! self::is_installed() ) {
			return '';
		}

		return (string) wp_get_theme( self::CHILD_SLUG )->get( 'Version' );
	}

	/**
	 * Liegt eine neuere gebündelte Version vor als installiert? → Update anbieten.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function needs_update(): bool {
		if ( ! self::is_installed() ) {
			return false;
		}
		$bundled   = self::bundled_version();
		$installed = self::installed_version();
		if ( '' === $bundled || '' === $installed ) {
			return false;
		}

		return version_compare( $bundled, $installed, '!=' );
	}

	/**
	 * Kopiert das gebündelte Theme nach wp-content/themes/kadence-child.
	 *
	 * Ablauf: Parent-Check → WP_Filesystem initialisieren → Ziel frisch leeren (damit im Repo
	 * gelöschte Dateien nicht als Leichen zurückbleiben) → rekursiv kopieren → Theme-Cache leeren.
	 * Nutzt copy_dir() (WordPress-Core), das über WP_Filesystem arbeitet — auf diesem Server per
	 * „direct"-Methode (Dateien gehören dem PHP-User), also ohne FTP-Zugangsdaten.
	 *
	 * @since 0.3.0
	 *
	 * @return true|\WP_Error True bei Erfolg, sonst WP_Error mit Ursache.
	 */
	public static function install() {
		if ( ! self::bundle_present() ) {
			return new \WP_Error(
				'depeur_food_no_bundle',
				__( 'Das gebündelte Theme fehlt im Plugin — bitte das Plugin vollständig neu deployen.', 'depeur-food' )
			);
		}
		if ( ! self::parent_installed() ) {
			return new \WP_Error(
				'depeur_food_no_parent',
				__( 'Parent-Theme „Kadence" ist nicht installiert. Zuerst Kadence installieren.', 'depeur-food' )
			);
		}

		// WordPress-Dateisystem-API laden + initialisieren.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( false === \WP_Filesystem() ) {
			return new \WP_Error(
				'depeur_food_fs',
				__( 'Kein direkter Dateisystem-Zugriff möglich (FTP-Zugangsdaten nötig?).', 'depeur-food' )
			);
		}

		global $wp_filesystem;
		$dest = untrailingslashit( get_theme_root() ) . '/' . self::CHILD_SLUG;

		// Ziel frisch: bestehende Installation entfernen, damit die Kopie deckungsgleich ist.
		if ( $wp_filesystem->exists( $dest ) ) {
			$wp_filesystem->delete( $dest, true );
		}
		if ( ! $wp_filesystem->mkdir( $dest ) ) {
			return new \WP_Error(
				'depeur_food_mkdir',
				__( 'Der Theme-Zielordner konnte nicht angelegt werden.', 'depeur-food' )
			);
		}

		// Rekursive Kopie Bundle → Ziel (WordPress-Core-Funktion).
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$result = copy_dir( self::bundle_dir(), $dest );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Theme-Liste-Cache leeren, damit WordPress das neue Theme sofort erkennt.
		wp_clean_themes_cache();

		return true;
	}

	/**
	 * Aktiviert das Child-Theme (nach Installation) und übernimmt die alten Theme-Einstellungen.
	 *
	 * Reihenfolge bewusst: ERST theme_mods vom aktuell aktiven (alten) Theme übernehmen, DANN
	 * umschalten — damit Menüs/Logo/Customizer-Werte nicht am alten Stylesheet „hängen bleiben".
	 * Nach dem Umschalten Rewrites flushen (Kategorie-Seiten-Pagination des Plugins).
	 *
	 * @since 0.3.0
	 *
	 * @return true|\WP_Error
	 */
	public static function activate() {
		if ( ! self::is_installed() ) {
			return new \WP_Error(
				'depeur_food_not_installed',
				__( 'Das Child-Theme ist noch nicht installiert.', 'depeur-food' )
			);
		}

		$theme = wp_get_theme( self::CHILD_SLUG );
		if ( ! $theme->exists() || $theme->errors() ) {
			return new \WP_Error(
				'depeur_food_broken_theme',
				__( 'Das Child-Theme ist fehlerhaft (Stylesheet/Parent prüfen).', 'depeur-food' )
			);
		}

		// Customizer-Einstellungen des Alt-Themes übernehmen (einmalig, vor dem Umschalten, damit
		// der Quell-Stand feststeht). switch_theme() selbst würde danach nur nav_menu_locations setzen.
		self::migrate_theme_mods_once( get_stylesheet() );

		switch_theme( self::CHILD_SLUG );

		// Kategorie-Seiten-Rewrites neu schreiben, damit /page/N/ sofort greift.
		flush_rewrite_rules( false );

		return true;
	}

	/**
	 * Überträgt die Customizer-Einstellungen (theme_mods) EINMALIG von $from aufs Child.
	 *
	 * WOFÜR: Kadence legt Header/Footer/Farben/Layout/Menüs/Logo in `theme_mods_{stylesheet}` ab —
	 * pro Theme. Ohne Übernahme steht das neue Child auf Kadence-Standardwerten (der „Fail"). Diese
	 * Methode kopiert die Mods des Quell-Themes 1:1 aufs Child (beide sind Kadence-Kinder, gleiche
	 * registrierten Slots → kompatibel).
	 *
	 * EINMALIG: Läuft nur, solange noch KEIN Sicherungs-/Sentinel-Eintrag existiert
	 * (MODS_BACKUP_OPTION). So wird bei einem späteren erneuten Wechsel eine inzwischen im
	 * Customizer angepasste Child-Konfiguration NICHT überschrieben. Für ein bewusstes erneutes
	 * Übernehmen gibt es import_customizer_from() (Force + Backup).
	 *
	 * @since 0.3.0
	 *
	 * @param string $from Stylesheet des Quell-Themes.
	 * @return void
	 */
	public static function migrate_theme_mods_once( string $from ): void {
		// Schon einmal migriert? → nichts tun (Sentinel = Backup-Option existiert).
		if ( is_array( get_option( self::MODS_BACKUP_OPTION ) ) ) {
			return;
		}
		self::copy_theme_mods( $from );
	}

	/**
	 * Übernimmt die Customizer-Einstellungen von $from aufs Child — ERZWUNGEN, mit Backup.
	 *
	 * Für den manuellen „Einstellungen übernehmen"-Button im Migrations-Assistenten: kopiert immer
	 * (auch wenn das Child schon Mods hat) und sichert vorher den bisherigen Child-Stand.
	 *
	 * @since 0.3.0
	 *
	 * @param string $from Stylesheet des Quell-Themes.
	 * @return true|\WP_Error
	 */
	public static function import_customizer_from( string $from ) {
		if ( ! array_key_exists( $from, self::mod_sources() ) ) {
			return new \WP_Error(
				'depeur_food_bad_source',
				__( 'Ungültige Quelle: dieses Theme hat keine gespeicherten Einstellungen.', 'depeur-food' )
			);
		}

		return self::copy_theme_mods( $from );
	}

	/**
	 * Kern-Kopie: sichert den aktuellen Child-Stand in MODS_BACKUP_OPTION und schreibt $from → Child.
	 *
	 * @since 0.3.0
	 *
	 * @param string $from Stylesheet des Quell-Themes.
	 * @return true|\WP_Error
	 */
	private static function copy_theme_mods( string $from ) {
		if ( '' === $from || self::CHILD_SLUG === $from ) {
			return new \WP_Error( 'depeur_food_bad_source', __( 'Keine gültige Quelle angegeben.', 'depeur-food' ) );
		}

		$source = get_option( 'theme_mods_' . $from );
		if ( ! is_array( $source ) || empty( $source ) ) {
			return new \WP_Error( 'depeur_food_empty_source', __( 'Die Quelle hat keine gespeicherten Einstellungen.', 'depeur-food' ) );
		}

		// Backup des bisherigen Child-Standes (Sicherheitsnetz + Einmalig-Sentinel).
		update_option(
			self::MODS_BACKUP_OPTION,
			array(
				'from' => $from,
				'mods' => get_option( 'theme_mods_' . self::CHILD_SLUG ),
			),
			false
		);

		update_option( 'theme_mods_' . self::CHILD_SLUG, $source );

		return true;
	}

	/**
	 * Liefert die Themes, die als Customizer-Quelle taugen (haben gespeicherte theme_mods).
	 *
	 * WOFÜR: Für die Auswahl im Assistenten („von welchem Alt-Theme übernehmen?"). Schließt das
	 * Child selbst aus; nimmt nur Themes mit nicht-leeren theme_mods (sonst gäbe es nichts zu holen).
	 *
	 * @since 0.3.0
	 *
	 * @return array<string, string> Map stylesheet ⇒ Anzeigename.
	 */
	public static function mod_sources(): array {
		$out = array();
		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			if ( self::CHILD_SLUG === $stylesheet ) {
				continue;
			}
			$mods = get_option( 'theme_mods_' . $stylesheet );
			if ( is_array( $mods ) && ! empty( $mods ) ) {
				$out[ (string) $stylesheet ] = (string) $theme->get( 'Name' );
			}
		}

		return $out;
	}
}
