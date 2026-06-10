<?php
/**
 * Beispiel-Modul – Bootstrap-Klasse: Settings-Anmeldung + Demo-Hook.
 *
 * @package Depeur\Food\Modules\ExampleModule\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\ExampleModule\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bootstrap-/Referenz-Klasse des Beispiel-Moduls (Vorlage für alle Module, BRIEF.md).
// Instanziiert ausschließlich von module.php und nur für aktive Module („geladen" ⟺
// „aktiv"; das Gating macht der ModuleManager). Der Konstruktor erledigt beides, was
// ein Modul beim Laden tut: sein Settings-Schema bei der zentralen SettingsRegistry
// anmelden (ADR-1 Multi-Option, Standards-Bibel § 1.1) und seine Hooks verdrahten. Liegt
// bewusst im Subordner Admin/ (FS-Safety, BRIEF.md § 2.7) – keine Klasse am Modul-Root.
//
// Hinweis: Settings-Anmeldung UND Demo-Hook liegen hier zusammen, weil das Demo trivial
// ist (ein Filter). Module mit echter Nicht-Admin-Logik legen dafür eigene Subordner an
// (Frontend/, REST/ …), statt alles in Admin/ zu stapeln (BRIEF.md § 5).
/**
 * Klasse Settings.
 *
 * @since 0.1.0
 */
final class Settings {

	/**
	 * Name des Demo-Filters. Demonstriert nur das Hook-Wiring; bewusst neutral
	 * benannt – NICHT „active" (Aktivierung lebt in depeur_food_modules, keine
	 * Wert-Verdopplung).
	 *
	 * @var string
	 */
	public const FILTER_GREETING = 'depeur_food/example/greeting';

	/**
	 * Modul-Slug (= Ordnername), von module.php hereingereicht.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Konstruktor: meldet Settings an und verdrahtet Hooks (Standards-Bibel § 1.1).
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Modul-Slug (Ordnername), von module.php via basename( __DIR__ ).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		// Schritt 1 – Settings-Schema bei der zentralen Registry anmelden. Läuft am
		// init-Hook (ModuleManager lädt module.php dort), also vor admin_init/Render.
		$this->register_settings();

		// Schritt 2 – Hooks verdrahten. Callback als [ $this, 'method' ] hält die
		// Instanz am Leben (module.php hält keine Referenz). Feuert der Filter nicht,
		// passiert nichts: reiner Demonstrations-Hook ohne Seiteneffekt.
		add_filter( self::FILTER_GREETING, array( $this, 'filter_greeting' ) );
	}

	/**
	 * Meldet das triviale Settings-Schema des Moduls bei der SettingsRegistry an.
	 *
	 * Demonstriert die Modul→Core-Anmeldung (ADR-1): ein Tab + ein Feld. Gespeichert
	 * würde es unter der Option depeur_food_{slug}; ein Render-UI gibt es in dieser
	 * Phase noch nicht (BRIEF.md § 7, Tab-System-Task).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function register_settings(): void {
		$fields = array(
			array(
				'id'          => 'example_enabled',
				'label'       => __( 'Beispiel aktiv', 'depeur-food' ),
				'type'        => 'checkbox',
				'default'     => false,
				'description' => __( 'Reiner Demonstrations-Schalter ohne Wirkung – zeigt nur, wie ein Modul ein Feld anmeldet.', 'depeur-food' ),
			),
		);

		SettingsRegistry::register(
			$this->slug,
			__( 'Beispiel-Modul', 'depeur-food' ),
			$fields,
			__( 'Triviales Vorlagen-Modul. Demonstriert die Modul-Mechanik (Discovery, Laden, Settings-Anmeldung) und hat keine echte Funktion.', 'depeur-food' )
		);
	}

	/**
	 * Demo-Filter-Callback: liefert einen Default-Gruß, wenn noch keiner gesetzt ist.
	 *
	 * Modelliert übliches Filter-Verhalten: einen bestehenden Wert durchreichen und nur
	 * einspringen, wenn er leer ist. So bleibt der Demo-Hook ein realistisches Beispiel
	 * und das „geladen"-Signal im Smoke eindeutig (Default '' → Demo-Gruß).
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $greeting Vom apply_filters hereingereichter aktueller Wert.
	 * @return string Bestehender Wert oder Default-Gruß.
	 */
	public function filter_greeting( mixed $greeting ): string {
		if ( '' !== (string) $greeting ) {
			return (string) $greeting;
		}

		return __( 'Hallo von Depeur Food – Beispiel-Modul.', 'depeur-food' );
	}
}
