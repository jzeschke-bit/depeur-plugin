<?php
/**
 * Manifest für das Modul Autoload Inspector & Options Cleanup.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'autoload-cleanup',
	'name'        => 'Autoload Inspector',
	'description' => 'Autoload-Optionen anzeigen, Größen prüfen, verdächtige Optionen erkennen, gezielt löschen (einzeln/Bulk/Prefix) und Regeln/Ignore verwalten.',
	'version'     => '1.0.0',
);
