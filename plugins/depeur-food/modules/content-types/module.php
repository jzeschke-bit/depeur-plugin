<?php
/**
 * Modul-Bootstrap: content-types.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (example-module
 * § 2.2/Kanon). Instanziiert die Registrierungs-Komponenten — jede verdrahtet ihre Hooks
 * im Konstruktor (keine Logik hier). Direkte Multi-Instanziierung statt Wrapper-Klasse,
 * weil FS-Safety (§ 2.7) keine *.php-Klasse am Modul-Root erlaubt (BRIEF § 13).
 *
 * @package Depeur\Food\Modules\ContentTypes
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Registrierung (immer, Frontend + Admin): Post-Types zuerst (init prio 10), dann
// Taxonomien (init prio 11) — Reihenfolge trägt die object_type-Verknüpfung. Leerer Store
// = No-Op (harte Invariante BRIEF § 3.3/§ 4.5).
new \Depeur\Food\Modules\ContentTypes\Registration\Type_Provider();
new \Depeur\Food\Modules\ContentTypes\Registration\Taxonomy_Provider();

// HINWEIS (Aufbau in Stufen): Die Admin-Komponenten – Orphan_Detector, Admin\Settings
// (read-only Diagnose-Tab) und Admin\Importer_Page (Live-Scan/Import) – werden in den
// Folge-Sessions ergänzt (Plan Session 2–3, BRIEF § 7/§ 13). Bis dahin trägt das Modul
// nur die Registrierung aus einem manuell/per Importer befüllten Store.

/**
 * Read-only Zugriff auf den Definitions-Store (BRIEF § 5).
 *
 * Global definiert (module.php läuft im globalen Namespace), damit Diagnose-Tab und der
 * spätere Setup-Wizard den Store abfragen können, ohne die Modul-Klassen zu importieren
 * (Splitting-Disziplin § 10). function_exists-Guard, weil module.php pro Request geladen
 * wird, solange das Modul aktiv ist.
 *
 * @since 0.1.0
 *
 * @return array{version:int,post_types:array,taxonomies:array}
 */
if ( ! function_exists( 'depeur_food_content_types_get_store' ) ) {
	function depeur_food_content_types_get_store(): array {
		return ( new \Depeur\Food\Modules\ContentTypes\Definitions\Store() )->get();
	}
}
