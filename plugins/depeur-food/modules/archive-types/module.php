<?php
/**
 * Modul-Bootstrap: archive-types.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Reines Frontend-Query-Modul: hängt die Inhaltstyp-Einspeisung in `pre_get_posts`. Kein Admin,
 * keine Assets, keine Feld-Registrierung. FS-Safety: keine *.php-Klasse am Modul-Root
 * (Modul-Kanon 3), Klasse liegt in Query/ und wird über den PSR-4-Autoloader geladen.
 *
 * @package Depeur\Food\Modules\ArchiveTypes
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Speist die unterstützten Inhaltstypen in die Standard-Archiv-Abfragen ein.
new \Depeur\Food\Modules\ArchiveTypes\Query\Archive_Injector();

// Settings-Tab (welche Archiv-Arten) – nur im Admin. Slug = Ordnername.
if ( is_admin() ) {
	new \Depeur\Food\Modules\ArchiveTypes\Admin\Settings( basename( __DIR__ ) );
}
