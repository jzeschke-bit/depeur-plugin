<?php
/**
 * Zusätzliche Admin-Ansicht für den BunnyCDN-Tab (Einstellungen).
 * Status, Sync-Hinweise und „CDN Cache leeren“ sind nur in der BunnyCDN-Ansicht (Untermenü).
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Admin;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tab-Intro für BunnyCDN-Einstellungen (optional: nur kurzer Verweis auf BunnyCDN-Ansicht).
 */
class SettingsView {

	/**
	 * Registriert den Filter für das Tab-Intro.
	 */
	public static function register() {
		add_filter( 'depeur_wp_suite_settings_tab_intro_bunny-cdn', array( __CLASS__, 'render_tab_intro' ), 10, 2 );
	}

	/**
	 * Kein Status/Sync/Purge auf der Einstellungsseite – dafür gibt es die BunnyCDN-Ansicht.
	 *
	 * @param string $intro   Leer (Filter-Output).
	 * @param string $tab_key Tab-Slug.
	 * @return string Leer oder kurzer Hinweis.
	 */
	public static function render_tab_intro( $intro, $tab_key ) {
		if ( $tab_key !== 'bunny-cdn' ) {
			return $intro;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return $intro;
		}
		$bunny_url = admin_url( 'admin.php?page=' . DashboardPage::PAGE_SLUG );
		$link      = '<a href="' . esc_url( $bunny_url ) . '">' . esc_html__( 'Depeur Suite → BunnyCDN', 'depeur-wp-suite' ) . '</a>';
		return '<p class="description">' . sprintf(
			/* translators: %s: Link to BunnyCDN submenu page */
			esc_html__( 'Status, Cache testen und Cache leeren: %s', 'depeur-wp-suite' ),
			$link
		) . '</p>';
	}
}
