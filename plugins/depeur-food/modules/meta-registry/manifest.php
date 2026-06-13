<?php
/**
 * Modul-Manifest: meta-registry.
 *
 * Metadaten ohne `slug`-Key (Discovery keyt nach Ordnername, example-module § 2.4).
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'        => 'Meta-Registry',
	'version'     => '0.1.0',
	'description' => 'Registriert alle Custom Fields aus der ACF-Discovery als Post-/User-/Term-Meta (REST + Sanitize) und definiert die ACF-Field-Groups (Editor-UI). Datenschicht-Fundament für alle Feature-Module.',
);
