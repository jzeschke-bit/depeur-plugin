<?php
/**
 * Modul-Bootstrap: schema-engine.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (example-module
 * § 2.2/Kanon). Instanziiert die Komponenten – jede verdrahtet ihre Hooks im eigenen
 * Konstruktor (keine Logik hier). Direkte Multi-Instanziierung statt Wrapper-Klasse, weil
 * FS-Safety (§ 2.7) keine *.php-Klasse am Modul-Root erlaubt. Klassen werden ausschließlich
 * über den PSR-4-Autoloader geladen – KEIN Hand-Require (Kanon Punkt 6).
 *
 * @package Depeur\Food\Modules\SchemaEngine
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht + Editor-UI: registriert die Autor-/Social-/Term-/Post-Felder via
// Field_Provisioner (register_*_meta + acf_add_local_field_group). Läuft immer, ist
// von Rank Math unabhängig – die Felder existieren auch, wenn das Schema ruht.
new \Depeur\Food\Modules\SchemaEngine\Provisioning\Fields( basename( __DIR__ ) );

// Autor-Schema-Anreicherung (rank_math-Filter). Der Konstruktor gated auf ein aktives
// Rank Math (E1, Hard-Dependency); ohne Rank Math werden keine Hooks gesetzt (Ruhe).
new \Depeur\Food\Modules\SchemaEngine\Schema\Author_Schema();

// Kategorie-/Archiv-Schema (CollectionPage aus WPRM-Term-Meta). Ebenfalls Rank-Math-gated;
// die WPRM-Nutzung ist zusätzlich als Soft-Dependency gekapselt.
new \Depeur\Food\Modules\SchemaEngine\Schema\Collection_Schema();

// Admin-only: Settings-/Diagnose-Tab + Hinweis bei fehlendem Rank Math. Slug = Ordnername.
if ( is_admin() ) {
	new \Depeur\Food\Modules\SchemaEngine\Admin\Settings( basename( __DIR__ ) );
	new \Depeur\Food\Modules\SchemaEngine\Admin\Notices();
}
