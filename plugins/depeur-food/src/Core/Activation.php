<?php
/**
 * Aktivierung und Deaktivierung des Plugins.
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
 * Klasse Activation.
 *
 * Kapselt die register_activation_hook-/register_deactivation_hook-Callbacks. Bewusst von
 * der Core-Plugin-Klasse getrennt, weil Aktivierungslogik nur einmalig (nicht bei jedem
 * Request) läuft und damit nicht in den init-Pfad gehört.
 *
 * @since 0.1.0
 */
final class Activation {

	/**
	 * Aktivierungs-Callback.
	 *
	 * Prüft zuerst die ACF-Hard-Dependency (BRIEF meta-registry § 11) und bricht die
	 * Aktivierung ab, wenn ACF fehlt. Danach werden die Default-Optionen idempotent angelegt
	 * (add_option überschreibt vorhandene Werte nicht), damit eine Reaktivierung bestehende
	 * Konfiguration nicht zurücksetzt. Beide Optionen sind klein und werden häufig gelesen,
	 * daher Autoload = yes (Default von add_option) – konform zu den Performance-Standards § 4.5.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		// ACF-Hard-Dependency: ohne ACF (Free oder Pro) kann das Plugin seine Custom Fields
		// nicht als Editor-UI registrieren (Doppel-Owner-Pattern, BRIEF meta-registry § 2/§ 11).
		// Aktivierung hart abbrechen, damit kein halb-funktionaler Zustand entsteht. class_exists
		// statt is_plugin_active, weil ACF Free und Pro unter verschiedenen Plugin-Slugs laufen,
		// aber beide dieselbe ACF-Klasse definieren.
		if ( ! class_exists( 'ACF' ) ) {
			// deactivate_plugins lebt in wp-admin/includes/plugin.php – im Aktivierungskontext
			// i. d. R. geladen, defensiv aber sichergestellt.
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( DEPEUR_FOOD_BASENAME );
			wp_die(
				esc_html__( 'Depeur Food benötigt das Plugin „Advanced Custom Fields" (Free oder Pro). Bitte zuerst ACF installieren und aktivieren, anschließend Depeur Food erneut aktivieren.', 'depeur-food' ),
				esc_html__( 'Fehlende Abhängigkeit: Advanced Custom Fields', 'depeur-food' ),
				array( 'back_link' => true )
			);
		}

		// Master-Liste aktiver Module (ADR-1); leeres Array = kein Modul aktiv.
		add_option( 'depeur_food_modules', array() );

		// Unterstützte Post-Types (ADR-4); Default ist der Standard-Post-Type.
		add_option( 'depeur_food_supported_post_types', array( 'post' ) );
	}

	/**
	 * Deaktivierungs-Callback.
	 *
	 * Im Bootstrap-Zustand gibt es nichts abzuräumen: Hooks werden zur Laufzeit via
	 * add_action registriert und überleben den Request nicht. Sobald Module geplante Events
	 * (wp_schedule_event) oder Transients anlegen, werden sie hier entfernt.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Aktuell bewusst leer – siehe Methoden-Doc.
	}
}
