<?php
/**
 * Modul-Bootstrap: rest-legacy.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Instanziiert die Route-Registrare – jeder verdrahtet seine Hooks im Konstruktor. Direkte
 * Multi-Instanziierung statt Wrapper-Klasse (FS-Safety, Modul-Kanon 3): alle Klassen liegen
 * in PascalCase-Subordnern und werden über den PSR-4-Autoloader geladen (KEIN Hand-Require).
 *
 * Klassifikation „legacy" (E8): die Routen sind ein 1:1-Port von rest-api-wprm inkl. der
 * bekannten Bugs (siehe BRIEF § 4/§ 5). WPRM ist harte Modul-Dependency; ohne WPRM
 * registrieren die Registrare ihre WPRM-abhängigen Routen nicht (graceful, kein Fatal).
 *
 * @package Depeur\Food\Modules\RestLegacy
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// wrm/v1/rating* – Rating-CRUD (Port von XWPRM_Api_Rating).
new \Depeur\Food\Modules\RestLegacy\Rest\Rating_Routes();

// wl/v1/posts + rest_wprm_recipe_query-Filter.
new \Depeur\Food\Modules\RestLegacy\Rest\Recipe_Routes();

// Admin: read-only Diagnose-Tab (Routen-Übersicht + WPRM-Status) + geführter, reversibler
// Cutover (Alt-Plugin rest-api-wprm aus/an) im Migrations-Assistenten.
if ( is_admin() ) {
	new \Depeur\Food\Modules\RestLegacy\Admin\Settings( basename( __DIR__ ) );
	new \Depeur\Food\Modules\RestLegacy\Admin\Cutover_Page();
}
