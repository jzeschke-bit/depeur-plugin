<?php
/**
 * Modul-Bootstrap: category-pages.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Aktueller Ausbaustand (Build-Schritt 1-3): Feld-Provisionierung der Kategorie-Seiten-
 * Konfiguration (Opt-in-Meta auf `page`) + Shortcode `[df_category_page]` (Multi-Taxonomie-
 * Raster mit Seite-1-Vorschau/Folgeseiten) + enge `/page/N/`-Rewrites. Die weitere Engine
 * („Was koche ich heute"-Filter/REST, Tag-Gruppen, Static-Page-Intro, Admin) folgt — siehe
 * BRIEF § 8 Build-Order. FS-Safety: keine *.php-Klasse am Modul-Root (Modul-Kanon 3).
 *
 * @package Depeur\Food\Modules\CategoryPages
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht + Editor-UI: statische Konfig-Felder (Opt-in/Titel/per_page) + je Taxonomie
// ein Term-Auswahlfeld (df_catpage_terms_{tax}) — auf init prio 20 (nach Taxonomie-Registrierung).
new \Depeur\Food\Modules\CategoryPages\Provisioning\Fields();

// Frontend: kuratiertes Raster als Shortcode + Auto-Render geflaggter Seiten.
new \Depeur\Food\Modules\CategoryPages\Frontend\Category_Page();

// Custom-Titel (H1 + SEO) für geflaggte Kategorie-Seiten.
new \Depeur\Food\Modules\CategoryPages\Frontend\Title();

// „Was koche ich heute": Filter-Shortcode + REST-Endpoint + Assets.
new \Depeur\Food\Modules\CategoryPages\Frontend\Recipe_Filter();
new \Depeur\Food\Modules\CategoryPages\Rest\Filter_Controller();
new \Depeur\Food\Modules\CategoryPages\Frontend\Assets();

// Enge `/page/N/`-Rewrites für geflaggte Kategorie-Seiten (+ deferred Flush).
new \Depeur\Food\Modules\CategoryPages\Hooks\Rewrite();

// Sidebar ab Seite 2 abschalten (Kadence-„NORMAL"-Layout: kein Sidebar, normale Breite).
new \Depeur\Food\Modules\CategoryPages\Hooks\Layout();
