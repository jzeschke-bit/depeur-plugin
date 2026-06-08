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
	 * Legt die Default-Optionen idempotent an (add_option überschreibt vorhandene Werte
	 * nicht), damit eine Reaktivierung bestehende Konfiguration nicht zurücksetzt. Beide
	 * Optionen sind klein und werden häufig gelesen, daher Autoload = yes (Default von
	 * add_option) – konform zu den Performance-Standards § 4.5.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function activate(): void {
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
