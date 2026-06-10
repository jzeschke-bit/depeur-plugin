<?php
/**
 * Manifest des Beispiel-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\ExampleModule
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key: die Discovery keyt
// allein nach Ordnername (BRIEF.md § 2.4). Ein slug-Key wäre eine zweite Quelle
// der Wahrheit und würde bei Ordner-Umbenennung inkonsistent.
return array(
	'name'        => 'Beispiel-Modul',
	'version'     => '0.1.0',
	'description' => 'Triviales Referenz-/Template-Modul. Demonstriert die Modul-Mechanik (Discovery, Lazy-Load, SettingsRegistry-Anmeldung) ohne echte Funktionalität.',
);
