<?php
/**
 * Modul-Bootstrap: acf-cleanup (Migrations-Aufräumer).
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Reines Admin-Werkzeug: registriert die geführte Aufräum-Seite + den POST-Handler. Kein
 * Frontend, keine Feld-Registrierung. Direkte Instanziierung (FS-Safety, Modul-Kanon 3),
 * Klasse über den PSR-4-Autoloader (KEIN Hand-Require).
 *
 * @package Depeur\Food\Modules\AcfCleanup
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Nur im Admin (Seite via admin_menu, Verarbeitung via admin-post.php – beides Admin-Kontext).
if ( is_admin() ) {
	new \Depeur\Food\Modules\AcfCleanup\Admin\Cleanup_Page();
}
