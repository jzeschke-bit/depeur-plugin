<?php
/**
 * Modul-Bootstrap: favorites.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Instanziiert die Komponenten – jede verdrahtet ihre Hooks im Konstruktor (keine Logik
 * hier). Direkte Multi-Instanziierung statt Wrapper-Klasse, weil die FS-Safety-Konvention
 * (Modul-Kanon Punkt 3) keine *.php-Klasse am Modul-Root erlaubt. Alle Klassen liegen in
 * PascalCase-Subordnern und werden über den PSR-4-Autoloader geladen (KEIN Hand-Require).
 *
 * @package Depeur\Food\Modules\Favorites
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht: Like-Zähler-Meta (_my_favorite_post_likes) via Field_Provisioner
// registrieren – frontend + admin, meta-only (keine ACF-UI).
new \Depeur\Food\Modules\Favorites\Meta\Like_Counter();

// REST-Endpoints: Toggle (schreibend, nonce-geschützt) + List (lesend, öffentlich).
new \Depeur\Food\Modules\Favorites\Rest\Favorites_Controller();

// Frontend: Shortcodes (Buttons + Archiv) und Asset-Enqueue (Vanilla JS/CSS).
new \Depeur\Food\Modules\Favorites\Frontend\Shortcodes();
new \Depeur\Food\Modules\Favorites\Frontend\Assets();

// WPRM-Integration (Soft-Dependency, E2): verdrahtet sich nur, wenn WPRM aktiv ist.
new \Depeur\Food\Modules\Favorites\Integrations\Wprm( basename( __DIR__ ) );

// Settings-Tab + Diagnose – nur im Admin. Slug = Ordnername (basename).
if ( is_admin() ) {
	new \Depeur\Food\Modules\Favorites\Admin\Settings( basename( __DIR__ ) );

	// Likes-Rangliste (macht den internen Zähler sichtbar).
	new \Depeur\Food\Modules\Favorites\Admin\Likes_Dashboard();
}
