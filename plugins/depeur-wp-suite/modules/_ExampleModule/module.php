<?php
/**
 * Bootstrap des Beispiel-Moduls. Wird nur geladen, wenn das Modul aktiviert ist.
 *
 * @package Depeur\WPSuite\Modules\ExampleModule
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Admin/Settings.php';

Depeur\WPSuite\Modules\ExampleModule\Admin\Settings::register();
