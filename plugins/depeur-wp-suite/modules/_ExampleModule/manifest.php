<?php
/**
 * Manifest für das Beispiel-Modul (Template für neue Module).
 *
 * @package Depeur\WPSuite\Modules\ExampleModule
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => '_ExampleModule',
	'name'        => 'Beispiel-Modul',
	'description' => 'Dummy-Modul als Vorlage für neue Module. Enthält nur Beispiel-Einstellungen.',
	'version'     => '1.0.0',
);
