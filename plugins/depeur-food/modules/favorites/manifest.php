<?php
/**
 * Manifest des favorites-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\Favorites
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key: die Discovery keyt
// allein nach Ordnername (Modul-Kanon Punkt 4). Ersetzt das Legacy-Plugin
// „my-favorite-posts-plugin".
return array(
	'name'        => 'Favoriten',
	'version'     => '0.2.0',
	'description' => 'Merkliste (localStorage) + globaler Like-Zähler pro Beitrag. Ersetzt das Legacy-Plugin „Depeur Favoriten" (my-favorite-posts-plugin) – REST statt ungeschütztem AJAX, Cookie→localStorage-Migration, post-type-agnostisch.',
);
