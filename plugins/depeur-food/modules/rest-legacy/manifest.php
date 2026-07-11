<?php
/**
 * Manifest des rest-legacy-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\RestLegacy
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key: die Discovery keyt allein nach
// Ordnername (Modul-Kanon Punkt 4). Ersetzt das Legacy-Plugin „Custom API" (rest-api-wprm).
return array(
	'name'        => 'REST-Legacy',
	'version'     => '0.3.0',
	'description' => 'Legacy-REST-Routen (wl/v1/posts + rest_wprm_recipe_query-Filter + wrm/v1/rating*) 1:1 aus rest-api-wprm – damit das letzte Legacy-Plugin deaktiviert werden kann. Klassifikation „legacy" (E8): bekannte Bugs bleiben erhalten (dokumentiert), werden NICHT gefixt. Voller Refactor bleibt einem künftigen rest-modern-Modul vorbehalten.',
);
