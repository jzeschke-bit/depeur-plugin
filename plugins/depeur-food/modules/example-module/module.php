<?php
/**
 * Bootstrap des Beispiel-Moduls.
 *
 * Wird von ModuleManager::init() am init-Hook geladen – und ausschließlich für
 * aktive Module (Slug in depeur_food_modules). „Geladen" ist damit gleichbedeutend
 * mit „aktiv"; das Modul prüft die Master-Liste nicht selbst nach.
 *
 * @package Depeur\Food\Modules\ExampleModule
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Einziger Einstiegspunkt des Moduls: instanziiert die Bootstrap-Klasse, deren
// Konstruktor Settings + Hooks verdrahtet (BRIEF.md § 2.5/§ 2.6). Die Klasse liegt
// im PascalCase-Subordner Admin/ (FS-Safety, BRIEF.md § 2.7) und wird ausschließlich
// über den PSR-4-Autoloader geladen – KEIN Hand-Require (BRIEF.md § 2.1/§ 3).
//
// Der Slug wird aus dem Ordnernamen abgeleitet: basename( __DIR__ ) liefert hier den
// Modul-Root (module.php liegt immer im Root) und wird der Klasse hereingereicht –
// Single Source of Truth, kein hartkodierter Literal (BRIEF.md § 2.5). Sonst keine
// Logik – module.php bleibt ein reiner Bootstrap.
new \Depeur\Food\Modules\ExampleModule\Admin\Settings( basename( __DIR__ ) );
