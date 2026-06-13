<?php
/**
 * Modul-Bootstrap: meta-registry.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (example-module
 * § 2.2/Kanon). Instanziiert die drei Komponenten — jede verdrahtet ihre Hooks im
 * Konstruktor (keine Logik hier). Direkte Multi-Instanziierung statt Wrapper-Klasse, weil
 * FS-Safety (§ 2.7) keine *.php-Klasse am Modul-Root erlaubt (BRIEF § 13, Session-Entscheidung).
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht (register_*_meta) – läuft frontend + admin, ACF-unabhängig.
new \Depeur\Food\Modules\MetaRegistry\Registry\Field_Registrar();

// Editor-UI-Definition (acf_add_local_field_group) – nur wirksam bei aktivem ACF.
new \Depeur\Food\Modules\MetaRegistry\Registry\Group_Registrar();

// Settings-Tab + Diagnose – nur im Admin. Slug = Ordnername (basename), inline statt
// Global (FS-Safety/Prefix-Konvention; example-module-Muster).
if ( is_admin() ) {
	new \Depeur\Food\Modules\MetaRegistry\Admin\Settings( basename( __DIR__ ) );
}
