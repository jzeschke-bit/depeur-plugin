<?php
/**
 * Konstanten für das Modul Autoload Inspector (Option-Key).
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Admin;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modul-Identität und Option-Key (Konfiguration unter Depeur Suite → Autoload Inspector).
 */
class Settings {

	/**
	 * Modul-Slug (Ordnername).
	 *
	 * @var string
	 */
	const MODULE_SLUG = 'autoload-cleanup';

	/**
	 * Option-Key für Regeln und UI-Defaults (depeur_wp_suite_autoload_cleanup).
	 *
	 * @var string
	 */
	const OPTION_KEY = 'depeur_wp_suite_autoload_cleanup';
}
