<?php
/**
 * Bootstrap des Moduls BunnyCDN. Wird nur geladen, wenn das Modul aktiviert ist.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Admin: Settings, View (Tab-Intro) und eigenes Untermenü „BunnyCDN“.
Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::register();
Depeur\WPSuite\Modules\BunnyCDN\Admin\SettingsView::register();
Depeur\WPSuite\Modules\BunnyCDN\Admin\DashboardPage::register();

// Hooks (Purge-Trigger, RunCloud-Sync) nur wenn BunnyCDN aktiviert.
Depeur\WPSuite\Modules\BunnyCDN\Hooks\WordPressHooks::register();
