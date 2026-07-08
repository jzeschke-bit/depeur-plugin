<?php
/**
 * Modul-Bootstrap: newsletter.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (example-module
 * § 2.2/Kanon). Instanziiert die Komponenten — jede verdrahtet ihre Hooks im Konstruktor
 * (keine Logik hier). Direkte Multi-Instanziierung statt Wrapper-Klasse, weil FS-Safety
 * (§ 2.7) keine *.php-Klasse am Modul-Root erlaubt.
 *
 * @package Depeur\Food\Modules\Newsletter
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht + Editor-UI: Per-Post-Override-Felder (show_newsletter_form,
// newsletter_position, show_app_promo) via Core-Field_Provisioner. Läuft Frontend + Admin,
// da die Felder auch im Frontend als register_post_meta gelesen werden.
new \Depeur\Food\Modules\Newsletter\Fields\Overrides();

// Render-Provider (zustandslos, lesen ausschließlich Config). Per Dependency-Injection an
// die Konsumenten gereicht — eine Instanz je Provider, kein Global.
$depeur_food_newsletter_flodesk   = new \Depeur\Food\Modules\Newsletter\Providers\Flodesk();
$depeur_food_newsletter_app_promo = new \Depeur\Food\Modules\Newsletter\Frontend\App_Promo();

// Frontend: the_content-Inserter, Shortcodes und (bedingt) Assets. Shortcodes werden nicht
// auf !is_admin() gegatet, weil sie auch in Editor-Vorschauen evaluieren können.
new \Depeur\Food\Modules\Newsletter\Frontend\Content_Inserter( $depeur_food_newsletter_flodesk, $depeur_food_newsletter_app_promo );
new \Depeur\Food\Modules\Newsletter\Frontend\Shortcodes( $depeur_food_newsletter_flodesk, $depeur_food_newsletter_app_promo );
new \Depeur\Food\Modules\Newsletter\Frontend\Assets();

// Settings-Tab + Diagnose — nur im Admin. Slug = Ordnername (basename), example-module-Muster.
if ( is_admin() ) {
	new \Depeur\Food\Modules\Newsletter\Admin\Settings( basename( __DIR__ ) );
}
