<?php
/**
 * Modul-Manifest: newsletter.
 *
 * Metadaten ohne `slug`-Key (Discovery keyt nach Ordnername, example-module § 2.4).
 *
 * @package Depeur\Food\Modules\Newsletter
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'        => 'Newsletter',
	'version'     => '0.2.0',
	'description' => 'Fügt an konfigurierbarer Absatz-Position ein Flodesk-Newsletter-Formular (+ optionale App-Promotion) in den Inhalt unterstützter Post-Types ein. Ersetzt das Legacy-Plugin „Depeur Spotlight" (spotlight-subscribe). Per-Post-Overrides via ACF, globale Defaults im Settings-Tab, Shortcodes [df_newsletter]/[df_app_promo].',
);
