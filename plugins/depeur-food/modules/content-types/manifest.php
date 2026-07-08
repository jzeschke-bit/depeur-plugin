<?php
/**
 * Modul-Manifest: content-types.
 *
 * Metadaten ohne `slug`-Key (Discovery keyt nach Ordnername, example-module § 2.4).
 *
 * @package Depeur\Food\Modules\ContentTypes
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'        => 'Inhaltstypen',
	'version'     => '0.1.0',
	'description' => 'Registriert die von der Site konfigurierten Custom Post Types und Taxonomien (args-treu) aus einem generischen Definitions-Store und löst damit ACF für die Struktur ab. Importer + Orphan-Detektor. Default-OFF – eine leere Konfiguration registriert nichts.',
);
