<?php
/**
 * Manifest des category-pages-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\CategoryPages
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key (Modul-Kanon 4).
// Ersetzt die Kategorie-Seiten-Logik des Alt-Themes (Multi-Taxonomie-Engine,
// „Was koche ich heute", Static-Page-Intro, Tag-Gruppen) — post-type-agnostisch
// und im Admin konfigurierbar statt hartkodiert. Default-OFF.
return array(
	'name'        => 'Kategorie-Seiten',
	'version'     => '0.1.0',
	'description' => 'Konfigurierbare Kategorie-/Rezept-Seiten: Multi-Taxonomie-Engine, „Was koche ich heute"-Filter, Static-Page-Intro und Tag-Gruppen. Ersetzt den Custom-Code des Alt-Themes; die Logik lebt post-type-agnostisch im Plugin, das Child-Theme rendert nur noch.',
);
