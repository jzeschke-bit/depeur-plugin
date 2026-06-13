<?php
/**
 * Einstellungsseite (Core): „Unterstützte Inhaltstypen“ als einziges Feld, manueller Save-Pfad.
 *
 * @package Depeur\Food\Core\Settings
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core\Settings;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Core\ModuleManager;
use Depeur\Food\Core\PostTypeRegistry;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Settings-Page mit Tab-Navigation. Bewusst KEINE WordPress-Settings-API/options.php,
// sondern ein manueller Self-POST mit explizitem Nonce, eigenem Save-Handler und
// PRG-Redirect. Grund: der Core-Tab führt eine Multi-Checkbox (Liste von
// Post-Types), die sich nicht sauber auf ein skalares register_setting abbilden
// lässt, und wir brauchen nach dem Speichern einen klaren Ort für
// PostTypeRegistry::flush() (Memo-Invalidierung). Der aktive Tab kommt aus ?tab=
// (Default „core“); Modul-Tabs rendern die via SettingsRegistry gemeldeten Schemata.
/**
 * Klasse SettingsPage.
 *
 * @since 0.1.0
 */
final class SettingsPage {

	/**
	 * Nonce-Action des Save-Formulars.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'depeur_food_save_settings';

	/**
	 * Nonce-Feldname im Formular.
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'depeur_food_settings_nonce';

	/**
	 * Slug des Core-Tabs und Default, wenn kein gültiger ?tab=-Wert vorliegt.
	 *
	 * @var string
	 */
	private const CORE_TAB = 'core';

	/**
	 * Nonce-Feldname des Modul-Save-Formulars (unterscheidet Modul- vom Core-Save).
	 *
	 * @var string
	 */
	private const MODULE_NONCE_NAME = 'depeur_food_module_nonce';

	/**
	 * Prefix der slug-gebundenen Nonce-Action: Prefix + Modul-Slug.
	 *
	 * Das save-Verb hält Raum für künftige modul-spezifische Actions (z. B. delete);
	 * die Slug-Bindung verhindert den Replay eines Nonce über Modul-Grenzen hinweg.
	 *
	 * @var string
	 */
	private const MODULE_NONCE_ACTION_PREFIX = 'depeur_food_save_module_';

	/**
	 * Hidden-Feldname, der den Ziel-Modul-Slug explizit in den POST trägt.
	 *
	 * @var string
	 */
	private const MODULE_SLUG_FIELD = 'depeur_food_module_slug';

	/**
	 * Behandelt den Self-POST der Settings-Seite: nonce + cap + speichern + flush + PRG.
	 *
	 * Hängt an admin_init und reagiert nur, wenn das eigene Formular abgeschickt
	 * wurde (Nonce-Feld vorhanden). Speichert die ausgewählten Post-Types, verwirft
	 * danach das Registry-Memo und leitet per Post/Redirect/Get zurück, damit ein
	 * Reload nicht erneut speichert.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function maybe_handle_save(): void {
		// Modul-Save hat ein eigenes Nonce-Feld; erkannt → Modul-Zweig, danach Schluss.
		// Sonst fällt der Request unverändert in den bestehenden Core-Zweig darunter.
		if ( isset( $_POST[ self::MODULE_NONCE_NAME ] ) ) {
			self::handle_module_save();
			return;
		}

		// Nur auf das eigene Formular reagieren – sonst läuft admin_init normal weiter.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		// Whitelisting: nur Slugs akzeptieren, die tatsächlich als UI-Option
		// verfügbar sind (get_available()). Schützt vor manipulierten POST-Werten.
		$available = array_keys( PostTypeRegistry::get_instance()->get_available() );

		$submitted = array();
		if ( isset( $_POST[ PostTypeRegistry::OPTION ] ) ) {
			$submitted = array_map( 'sanitize_key', (array) wp_unslash( $_POST[ PostTypeRegistry::OPTION ] ) );
		}

		$clean = array_values( array_unique( array_intersect( $submitted, $available ) ) );

		update_option( PostTypeRegistry::OPTION, $clean );

		// Belt-and-Suspenders: Memo verwerfen, auch wenn gleich ein PRG-Redirect
		// folgt – garantiert konsistenten Read-After-Write im selben Request.
		PostTypeRegistry::flush();

		$redirect = add_query_arg(
			array(
				'page'    => AdminMenu::MENU_SLUG,
				'updated' => 'true',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Verarbeitet den Self-POST eines Modul-Tabs: Slug-Whitelist, Nonce, Felder, PRG.
	 *
	 * Aufgerufen aus maybe_handle_save(), sobald das Modul-Nonce-Feld vorliegt.
	 * Gespeichert werden ausschließlich die im Schema deklarierten Felder – die
	 * Iteration läuft über das Schema, nicht über die POST-Keys, sodass keine
	 * fremden Options-Keys eingeschleust werden können.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private static function handle_module_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Sicherheits-Reihenfolge: Slug ZUERST lesen + gegen die aktiven Module prüfen,
		// DANN die Nonce verifizieren. Grund: die Nonce-Action ist slug-gebunden
		// (MODULE_NONCE_ACTION_PREFIX + $slug). Würde man die Nonce vor dem Whitelist-
		// Check prüfen, könnte ein manipulierter Slug die Prüfung auf eine beliebige
		// Action lenken. Reihenfolge hier: read → sanitize_key → Whitelist gegen
		// get_active_module_slugs() → erst danach check_admin_referer. Ein ungültiger
		// Slug fällt in der Whitelist durch, bevor die Nonce-Prüfung erreicht wird.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- bewusster Slug-Read vor der slug-gebundenen Nonce-Action; Whitelist-Check unmittelbar danach (Begründung siehe oben).
		$slug = isset( $_POST[ self::MODULE_SLUG_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::MODULE_SLUG_FIELD ] ) ) : '';

		// Save-Whitelist: nur AKTIVE Module – NICHT 'core' (Core hat den eigenen Zweig).
		$allowed = ModuleManager::get_active_module_slugs();
		if ( '' === $slug || ! in_array( $slug, $allowed, true ) ) {
			// Defense-in-Depth: return zusätzlich zum exit in redirect_with_flag, falls
			// dort jemals das exit entfernt wird – ein ungültiger Slug darf dann nicht
			// bis zur Nonce-/Save-Logik durchfallen.
			self::redirect_with_flag( self::CORE_TAB, 'error' );
			return;
		}

		// Erst nach bestandener Whitelist die slug-gebundene Nonce prüfen.
		check_admin_referer( self::MODULE_NONCE_ACTION_PREFIX . $slug, self::MODULE_NONCE_NAME );

		// Schema-Felder sind die Quelle der Wahrheit fürs Speichern.
		$schemas = SettingsRegistry::get_schemas_for_active_modules( $allowed );
		$fields  = ( isset( $schemas[ $slug ]['fields'] ) && is_array( $schemas[ $slug ]['fields'] ) ) ? $schemas[ $slug ]['fields'] : array();

		$option_key = SettingsRegistry::option_key( $slug );
		$existing   = get_option( $option_key, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// Roh-Werte des Moduls aus dem POST. Die Sanitisierung erfolgt deferred pro Feld
		// im Loop darunter via SettingsRegistry::sanitize_field() (typ-spezifisch).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array-Read; pro-Feld-Sanitisierung via SettingsRegistry::sanitize_field() im Loop, schema-getrieben (kein POST-Key-Durchgriff). Nonce oben via check_admin_referer geprüft.
		$raw = isset( $_POST[ $option_key ] ) ? (array) wp_unslash( $_POST[ $option_key ] ) : array();

		$clean    = array();
		$autoload = true;

		foreach ( $fields as $field ) {
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? $field['type'] : 'text';

			// Symmetrie zu render_field(): unbekannte Typen überspringen (kein bogus Key).
			if ( ! in_array( $type, array( 'checkbox', 'text', 'select', 'password' ), true ) ) {
				continue;
			}

			$fid = $field['id'];

			if ( 'password' === $type ) {
				// Boundary: sanitize_field normalisiert nur den NEUEN Wert und kennt den
				// alten nicht. Die Entscheidung „leerer/whitespace-Submit ⇒ alten Wert
				// behalten“ trifft daher hier – sonst löschte ein leeres Feld das Secret.
				$submitted = isset( $raw[ $fid ] ) ? $raw[ $fid ] : '';
				if ( ! is_string( $submitted ) || '' === trim( $submitted ) ) {
					if ( array_key_exists( $fid, $existing ) ) {
						$clean[ $fid ] = $existing[ $fid ];
					}
				} else {
					$clean[ $fid ] = SettingsRegistry::sanitize_field( $submitted, $field );
				}
			} else {
				$value         = array_key_exists( $fid, $raw ) ? $raw[ $fid ] : null;
				$clean[ $fid ] = SettingsRegistry::sanitize_field( $value, $field );
			}

			// ADR-1: enthält das Modul ein Secret (Feld autoload=false), wird die ganze
			// Option nicht autoloadet.
			if ( isset( $field['autoload'] ) && false === $field['autoload'] ) {
				$autoload = false;
			}
		}

		update_option( $option_key, $clean, $autoload );

		// Kein nachgestelltes return: dies ist die letzte Anweisung, redirect_with_flag
		// beendet via exit. Der schützende return sitzt im Fehler-Zweig oben, wo ein
		// Durchfallen real wäre (phpcs flaggt ein return hier zu Recht als redundant).
		self::redirect_with_flag( $slug, 'updated' );
	}

	/**
	 * Leitet nach einem Modul-Save per Post/Redirect/Get zurück auf den Ziel-Tab.
	 *
	 * @since 0.1.0
	 *
	 * @param string $tab  Ziel-Tab (Modul-Slug bei Erfolg, „core“ bei Ablehnung).
	 * @param string $flag 'updated' für die Erfolgs-Notice, sonst eine Fehler-Notice.
	 * @return void
	 */
	private static function redirect_with_flag( string $tab, string $flag ): void {
		$args = array(
			'page' => AdminMenu::MENU_SLUG,
			'tab'  => $tab,
		);

		if ( 'updated' === $flag ) {
			$args['updated'] = 'true';
		} else {
			$args['df_error'] = 'invalid_module';
		}

		// wp_safe_redirect prüft die Ziel-URL gegen allowed_redirect_hosts und verhindert
		// Open-Redirect-Vektoren. Wir bauen die URL zwar selbst aus festen Werten –
		// wp_safe_redirect ist hier Defense-in-Depth (statt rohem wp_redirect).
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Rendert die Settings-Seite (Submenu-Callback): Tab-Navigation + aktiver Tab.
	 *
	 * Liest den aktiven Tab aus der URL (?tab=, Default „core“), zeigt die
	 * gemeinsame Erfolgs-Notice und delegiert den Inhalt: Core-Tab → eigene
	 * Post-Type-Maske, Modul-Tab → das via SettingsRegistry gemeldete Schema.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active = self::get_active_tab();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Depeur Food – Einstellungen', 'depeur-food' ); ?></h1>

			<?php
			// Erfolgs-Notice nach PRG-Redirect (reines Anzeige-Flag, kein State-Change).
			// Gilt für Core- wie Modul-Save, daher tab-übergreifend hier oben.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Flag nach eigenem Redirect, keine Statusänderung.
			if ( isset( $_GET['updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['updated'] ) ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'depeur-food' ); ?></p>
				</div>
				<?php
			endif;

			// Fehler-Notice nach abgelehntem Modul-Save (unbekanntes/inaktives Modul).
			// Bewertet df_error, NICHT den Tab – der Redirect landet auf „core“, die
			// eigentliche Information steckt im Flag.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Flag nach eigenem Redirect, keine Statusänderung.
			if ( isset( $_GET['df_error'] ) ) :
				?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Speichern fehlgeschlagen: unbekanntes oder inaktives Modul.', 'depeur-food' ); ?></p>
				</div>
				<?php
			endif;

			self::render_tab_nav( $active );

			// Dispatch: Core-Maske oder das Schema des gewählten Moduls.
			// render_module_tab() liefert Block B3; solange kein Modul aktiv ist,
			// kann get_active_tab() ohnehin nur „core“ zurückgeben.
			if ( self::CORE_TAB === $active ) :
				self::render_core_tab();
			else :
				self::render_module_tab( $active );
			endif;
			?>
		</div>
		<?php
	}

	/**
	 * Liefert die erlaubten Tab-Slugs: „core“ plus alle aktiven Module.
	 *
	 * Whitelist für das Tab-Routing (GET). Der Modul-Save-Pfad nutzt später eine
	 * engere Liste OHNE „core“, weil „core“ einen eigenen Save-Zweig besitzt.
	 *
	 * @since 0.1.0
	 *
	 * @return string[]
	 */
	private static function get_allowed_tabs(): array {
		return array_merge( array( self::CORE_TAB ), ModuleManager::get_active_module_slugs() );
	}

	/**
	 * Ermittelt den aktiven Tab aus der URL, streng gegen die Whitelist geprüft.
	 *
	 * Unbekannte oder manipulierte ?tab=-Werte fallen hart auf „core“ zurück
	 * (in_array mit strict-Vergleich), damit kein fremder Slug durchrutscht.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	private static function get_active_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reines Anzeige-Routing (welcher Tab), keine Statusänderung.
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : self::CORE_TAB;

		return in_array( $requested, self::get_allowed_tabs(), true ) ? $requested : self::CORE_TAB;
	}

	/**
	 * Rendert die Tab-Navigation: Core-Tab plus je ein Tab pro aktivem Modul.
	 *
	 * Die Modul-Beschriftung stammt aus dem via SettingsRegistry gemeldeten Schema
	 * (tab_label); nur aktive Module erscheinen.
	 *
	 * @since 0.1.0
	 *
	 * @param string $active Slug des aktiven Tabs (bereits whitelist-geprüft).
	 * @return void
	 */
	private static function render_tab_nav( string $active ): void {
		$active_modules = ModuleManager::get_active_module_slugs();
		$schemas        = SettingsRegistry::get_schemas_for_active_modules( $active_modules );

		// Tab-Liste: Core zuerst, dann je ein Tab pro aktivem Modul (Label aus Schema).
		// Eine Schleife statt dupliziertem <a>-Markup, damit URL-Bau und Aktiv-Klasse
		// nur an einer Stelle leben.
		$tabs = array( self::CORE_TAB => __( 'Allgemein', 'depeur-food' ) );
		foreach ( $schemas as $slug => $schema ) {
			$tabs[ $slug ] = $schema['tab_label'];
		}

		$base = admin_url( 'admin.php' );
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $slug => $label ) :
				$url = add_query_arg(
					array(
						'page' => AdminMenu::MENU_SLUG,
						'tab'  => $slug,
					),
					$base
				);
				$css = 'nav-tab' . ( $slug === $active ? ' nav-tab-active' : '' );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $css ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php
			endforeach;
			?>
		</h2>
		<?php
	}

	/**
	 * Rendert den Core-Tab: „Unterstützte Inhaltstypen“ als Multi-Checkbox.
	 *
	 * Inhaltlich unveränderte Maske aus Task 2 – nur aus render() in eine eigene
	 * Tab-Methode verschoben. Die Markup-Einrückung ist bewusst 1:1 belassen,
	 * damit der Diff ein reiner Move bleibt (kein Verhaltenswechsel zu prüfen).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private static function render_core_tab(): void {
		$available = PostTypeRegistry::get_instance()->get_available();
		$current   = PostTypeRegistry::get_instance()->get_supported();
		?>
			<?php // § 6.2 Admin-UI-Doku: Intro für den Site-Owner, bewusst nicht-technisch. ?>
			<p class="description" style="max-width: 50em;">
				<?php
				esc_html_e(
					'Lege fest, für welche Inhaltstypen die Funktionen von Depeur Food gelten sollen. Standardmäßig sind das Beiträge. Auf Seiten mit eigenen Inhaltstypen (z. B. Rezepte oder Cocktails) wähle hier zusätzlich den passenden Typ aus – nur die hier aktivierten Typen werden von den Modulen verarbeitet.',
					'depeur-food'
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'page', AdminMenu::MENU_SLUG, admin_url( 'admin.php' ) ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Unterstützte Inhaltstypen', 'depeur-food' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Unterstützte Inhaltstypen', 'depeur-food' ); ?></span>
								</legend>
								<?php
								foreach ( $available as $slug => $label ) :
									$field_id = 'depeur-food-pt-' . $slug;
									?>
									<label for="<?php echo esc_attr( $field_id ); ?>" style="display:block; margin-bottom:4px;">
										<input
											type="checkbox"
											id="<?php echo esc_attr( $field_id ); ?>"
											name="<?php echo esc_attr( PostTypeRegistry::OPTION ); ?>[]"
											value="<?php echo esc_attr( $slug ); ?>"
											<?php checked( in_array( $slug, $current, true ) ); ?>
										/>
										<?php echo esc_html( $label ); ?>
										<code><?php echo esc_html( $slug ); ?></code>
									</label>
									<?php
								endforeach;
								?>
								<p class="description">
									<?php esc_html_e( 'Mindestens ein Typ sollte aktiv sein. Wird nichts ausgewählt, fällt das Plugin automatisch auf „Beiträge“ (post) zurück.', 'depeur-food' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Einstellungen speichern', 'depeur-food' ) ); ?>
			</form>
		<?php
	}

	/**
	 * Rendert den Tab eines aktiven Moduls: dessen via SettingsRegistry gemeldete Felder.
	 *
	 * Liest die Modul-Option (ADR-1: depeur_food_{slug}), löst pro Feld den
	 * aktuellen Wert auf (gespeichert, sonst Feld-Default) und delegiert die Ausgabe
	 * an render_field(). Der POST wird hier NICHT verarbeitet – das Formular trägt
	 * nur Nonce + Hidden-Slug; der Save-Handler folgt in B4.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Modul-Slug (bereits whitelist-geprüft via get_active_tab()).
	 * @return void
	 */
	private static function render_module_tab( string $slug ): void {
		$schemas = SettingsRegistry::get_schemas_for_active_modules( ModuleManager::get_active_module_slugs() );

		// Defensiv: Die Dispatch-Whitelist lässt nur aktive Module hierher, aber ein
		// aktives Modul ohne registriertes Schema (Bootstrap-Fehler) darf nicht fatal sein.
		if ( ! isset( $schemas[ $slug ] ) ) {
			?>
			<p class="description"><?php esc_html_e( 'Für dieses Modul liegen keine Einstellungen vor.', 'depeur-food' ); ?></p>
			<?php
			return;
		}

		$schema = $schemas[ $slug ];
		$option = get_option( SettingsRegistry::option_key( $slug ), array() );
		if ( ! is_array( $option ) ) {
			$option = array();
		}

		if ( ! empty( $schema['description'] ) ) :
			?>
			<p class="description" style="max-width: 50em;"><?php echo esc_html( $schema['description'] ); ?></p>
			<?php
		endif;

		$fields = ( isset( $schema['fields'] ) && is_array( $schema['fields'] ) ) ? $schema['fields'] : array();

		// Graceful: Modul ohne Felder bekommt einen Hinweis statt eines leeren Formulars.
		if ( empty( $fields ) ) :
			?>
			<p class="description"><?php esc_html_e( 'Dieses Modul hat keine Einstellungen.', 'depeur-food' ); ?></p>
			<?php
			return;
		endif;

		$action = add_query_arg(
			array(
				'page' => AdminMenu::MENU_SLUG,
				'tab'  => $slug,
			),
			admin_url( 'admin.php' )
		);
		$prefix = SettingsRegistry::option_key( $slug );
		?>
		<form method="post" action="<?php echo esc_url( $action ); ?>">
			<?php wp_nonce_field( self::MODULE_NONCE_ACTION_PREFIX . $slug, self::MODULE_NONCE_NAME ); ?>
			<input type="hidden" name="<?php echo esc_attr( self::MODULE_SLUG_FIELD ); ?>" value="<?php echo esc_attr( $slug ); ?>" />

			<table class="form-table" role="presentation">
				<?php
				foreach ( $fields as $field ) :
					if ( ! isset( $field['id'] ) ) {
						continue;
					}
					$value = array_key_exists( $field['id'], $option ) ? $option[ $field['id'] ] : ( $field['default'] ?? '' );
					$name  = $prefix . '[' . $field['id'] . ']';
					self::render_field( $field, $value, $name );
				endforeach;
				?>
			</table>

			<?php
			// Reine Read-only-Tabs (nur 'html'-Felder, z. B. der meta-registry-Diagnose-Tab)
			// bekommen keinen Speichern-Button – nichts zu speichern (BRIEF meta-registry § 7.2).
			$has_saveable = false;
			foreach ( $fields as $f ) {
				$ftype = isset( $f['type'] ) ? $f['type'] : 'text';
				if ( in_array( $ftype, array( 'checkbox', 'text', 'select', 'password' ), true ) ) {
					$has_saveable = true;
					break;
				}
			}
			if ( $has_saveable ) {
				submit_button( __( 'Einstellungen speichern', 'depeur-food' ) );
			}
			?>
		</form>
		<?php
	}

	/**
	 * Rendert ein einzelnes Settings-Feld als Form-Table-Zeile (<tr>).
	 *
	 * Reiner Renderer ohne Option-Zugriff: Wert und name-Attribut reicht der
	 * Aufrufer herein. Unterstützt die vier Form-Feldtypen, die
	 * SettingsRegistry::sanitize_field kennt (checkbox, text, select, password),
	 * plus den read-only Typ 'html' (volle Breite, kein Bedienelement, kein
	 * Save-Wert) für Diagnose-/Info-Blöcke. Unbekannte Typen werden – als
	 * Modul-API-Missbrauch – gemeldet und übersprungen, ohne eine leere Zeile zu
	 * hinterlassen.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $field Felddefinition (id, label, type, options, default, description, …).
	 * @param mixed  $value Aktueller Wert (vom Aufrufer aus der Option gelesen).
	 * @param string $name  Vollständiger HTML-name-Attribut-Wert, gruppiert unter dem
	 *                      Modul-Options-Key (z. B. 'depeur_food_example-module[active]').
	 *                      Wird vom Aufrufer (render_module_tab) konstruiert, weil der
	 *                      Renderer den Slug-Kontext nicht kennt.
	 * @return void
	 */
	private static function render_field( array $field, mixed $value, string $name ): void {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		// Read-only HTML-Block (z. B. Diagnose-Tabellen): volle Breite, kein Label/
		// Bedienelement, kein Save-Wert. Inhalt ist plugin-generiertes Markup, via
		// wp_kses_post abgesichert; der Save-Loop (handle_module_save) überspringt 'html'
		// als Nicht-Form-Typ. Field_Renderer-Wachstumsfuge (CLAUDE.md › Architecture Notes).
		if ( 'html' === $type ) {
			$html = isset( $field['html'] ) ? $field['html'] : '';
			if ( '' === $html ) {
				return;
			}
			?>
			<tr>
				<td colspan="2"><?php echo wp_kses_post( $html ); ?></td>
			</tr>
			<?php
			return;
		}

		// Unbekannte Typen früh aussortieren, BEVOR eine Zeile geöffnet wird – sonst
		// bliebe ein Label ohne Bedienelement stehen. _doing_it_wrong respektiert
		// WP_DEBUG automatisch und wird vom WordPress.PHP.DevelopmentFunctions-Sniff
		// nicht geflaggt. Ein Modul mit unbekanntem Feldtyp ist ein API-Missbrauch
		// (nicht-fatale Konfiguration), nicht eine Exception-würdige Situation.
		$known = array( 'checkbox', 'text', 'select', 'password' );
		if ( ! in_array( $type, $known, true ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: field type, 2: field id. */
					esc_html__( 'Unbekannter Feldtyp „%1$s“ (Feld „%2$s“) – wird nicht gerendert.', 'depeur-food' ),
					esc_html( $type ),
					esc_html( isset( $field['id'] ) ? $field['id'] : '' )
				),
				'0.1.0'
			);
			return;
		}

		$label  = isset( $field['label'] ) ? $field['label'] : '';
		$dom_id = 'df-field-' . sanitize_html_class( isset( $field['id'] ) ? $field['id'] : '' );
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $dom_id ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<?php
				switch ( $type ) {
					case 'checkbox':
						?>
						<input
							type="checkbox"
							id="<?php echo esc_attr( $dom_id ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="1"
							<?php checked( (bool) $value ); ?>
						/>
						<?php
						break;

					case 'select':
						$options = ( isset( $field['options'] ) && is_array( $field['options'] ) ) ? $field['options'] : array();
						?>
						<select id="<?php echo esc_attr( $dom_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
							<?php foreach ( $options as $opt_key => $opt_label ) : ?>
								<option value="<?php echo esc_attr( (string) $opt_key ); ?>" <?php selected( (string) $value, (string) $opt_key ); ?>>
									<?php echo esc_html( $opt_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php
						break;

					case 'password':
						// Secrets nie vorausfüllen: leeres Feld; der Placeholder signalisiert
						// nur, dass bereits ein Wert gespeichert ist. Der Save-Zweig (B4)
						// ignoriert einen leeren Submit und überschreibt damit nichts.
						$placeholder = ! empty( $value ) ? __( '(unverändert)', 'depeur-food' ) : '';
						?>
						<input
							type="password"
							id="<?php echo esc_attr( $dom_id ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value=""
							autocomplete="new-password"
							placeholder="<?php echo esc_attr( $placeholder ); ?>"
							class="regular-text"
						/>
						<?php
						break;

					case 'text':
					default:
						?>
						<input
							type="text"
							id="<?php echo esc_attr( $dom_id ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( (string) $value ); ?>"
							class="regular-text"
						/>
						<?php
						break;
				}

				if ( ! empty( $field['description'] ) ) :
					?>
					<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
					<?php
				endif;
				?>
			</td>
		</tr>
		<?php
	}
}
