<?php
/**
 * Modul-Bootstrap: language-selector.
 *
 * Instanziiert die drei Komponenten (Konstruktoren verdrahten ihre Hooks). Direkte
 * Multi-Instanziierung statt Wrapper-Klasse (FS-Safety — keine *.php-Klasse am Modul-Root).
 *
 * @package Depeur\Food\Modules\LanguageSelector
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Feld-Provisionierung (link_de/link_en – Post + Term, CPT-agnostisch). Läuft frontend + admin.
new \Depeur\Food\Modules\LanguageSelector\Provisioning\Fields();

// Frontend: hreflang im <head> + Sprachumschalter-Shortcode.
new \Depeur\Food\Modules\LanguageSelector\Frontend\Renderer();

// Diagnose-Tab (nur Admin).
if ( is_admin() ) {
	new \Depeur\Food\Modules\LanguageSelector\Admin\Settings( basename( __DIR__ ) );
}
