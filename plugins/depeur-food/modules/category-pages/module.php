<?php
/**
 * Modul-Bootstrap: category-pages.
 *
 * Wird vom ModuleManager `require_once`'t, NUR wenn das Modul aktiv ist (Modul-Kanon).
 * Aktueller Ausbaustand (Build-Schritt 1): Feld-Provisionierung der Kategorie-Seiten-
 * Konfiguration (Opt-in-Meta auf `page`) über den Core-Field_Provisioner. Die Engine
 * (Shortcodes, REST-Filter, Tag-Gruppen, Admin) folgt in den nächsten Schritten — siehe
 * BRIEF § 8 Build-Order. FS-Safety: keine *.php-Klasse am Modul-Root (Modul-Kanon 3).
 *
 * @package Depeur\Food\Modules\CategoryPages
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Datenschicht + Editor-UI: Kategorie-Seiten-Konfig-Felder anlegen (register_post_meta +
// ACF-Group), post-type-agnostisch über den wiederverwendbaren Provisioner.
$df_category_pages_config = require __DIR__ . '/config/fields.php';
new \Depeur\Food\Support\Fields\Field_Provisioner(
	$df_category_pages_config['fields'],
	$df_category_pages_config['group']
);
unset( $df_category_pages_config );
