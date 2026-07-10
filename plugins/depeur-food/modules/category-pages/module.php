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

// Schema: auf Folgeseiten (Seite 2+) CollectionPage statt Recipe/ItemList (Rank-Math-gated).
new \Depeur\Food\Modules\CategoryPages\Frontend\Schema();

// „Was koche ich heute": Filter-Shortcode + REST-Endpoint + Assets.
new \Depeur\Food\Modules\CategoryPages\Frontend\Recipe_Filter();
new \Depeur\Food\Modules\CategoryPages\Rest\Filter_Controller();
new \Depeur\Food\Modules\CategoryPages\Frontend\Assets();

// Enge `/page/N/`-Rewrites für geflaggte Kategorie-Seiten (+ deferred Flush).
new \Depeur\Food\Modules\CategoryPages\Hooks\Rewrite();

// Sidebar ab Seite 2 abschalten (Kadence-„NORMAL"-Layout: kein Sidebar, normale Breite).
new \Depeur\Food\Modules\CategoryPages\Hooks\Layout();

// Schlagwort-Gruppen: Term-Meta `tag_group` IMMER registrieren (REST/Reads); das Edit-Feld
// und die Admin-Spalte greifen nur im Admin (die zugehörigen Hooks sind im Frontend inert).
new \Depeur\Food\Modules\CategoryPages\Admin\Tag_Groups_Admin();

// Admin: zentrale Übersicht aller Kategorie-Seiten (sichtbar) + Settings-Tab (globale Defaults)
// + geführte Migration der Alt-Rezeptkategorie-Seiten (aus der Sidebar ausgeblendet).
if ( is_admin() ) {
	new \Depeur\Food\Modules\CategoryPages\Admin\Overview_Page();
	new \Depeur\Food\Modules\CategoryPages\Admin\Settings( basename( __DIR__ ) );
	new \Depeur\Food\Modules\CategoryPages\Admin\Migration_Page();
}
