<?php
/**
 * Bootstrap des Moduls Autoload Inspector. Wird nur geladen, wenn das Modul aktiviert ist.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Depeur\WPSuite\Modules\AutoloadCleanup\Admin\ScreenController::register();
