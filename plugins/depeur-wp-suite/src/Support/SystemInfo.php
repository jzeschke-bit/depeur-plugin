<?php
/**
 * Systembericht für Support: WP, PHP, Theme, Plugins, Suite-Version, aktive Module.
 *
 * @package Depeur\WPSuite\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Support;

use Depeur\WPSuite\Core\ModuleManager;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse SystemInfo.
 */
class SystemInfo {

	/**
	 * Erstellt den Systembericht (deutsch, zeilenbasiert).
	 *
	 * @return string
	 */
	public static function get_report() {
		global $wp_version;

		$lines = array();
		$lines[] = '=== Depeur WP Suite – Systembericht ===';
		$lines[] = '';
		$lines[] = '--- WordPress ---';
		$lines[] = 'WordPress-Version: ' . $wp_version;
		$lines[] = 'Blog-URL: ' . get_bloginfo( 'url' );
		$lines[] = 'Sprache: ' . get_bloginfo( 'language' );
		$lines[] = '';
		$lines[] = '--- Server ---';
		$lines[] = 'PHP-Version: ' . PHP_VERSION;
		$lines[] = 'Memory Limit: ' . ini_get( 'memory_limit' );
		$lines[] = '';
		$lines[] = '--- Theme ---';
		$theme = wp_get_theme();
		$lines[] = 'Name: ' . $theme->get( 'Name' );
		$lines[] = 'Version: ' . $theme->get( 'Version' );
		$lines[] = '';
		$lines[] = '--- Depeur WP Suite ---';
		$lines[] = 'Plugin-Version: ' . ( defined( 'DEPEUR_WP_SUITE_VERSION' ) ? DEPEUR_WP_SUITE_VERSION : '–' );
		$active = ModuleManager::get_active_module_slugs();
		$discovered = ModuleManager::get_discovered_modules();
		$lines[] = 'Aktive Module: ' . implode( ', ', $active );
		foreach ( $active as $slug ) {
			if ( isset( $discovered['modules'][ $slug ] ) ) {
				$lines[] = '  - ' . $slug . ': ' . $discovered['modules'][ $slug ]['version'];
			}
		}
		$lines[] = '';
		$lines[] = '--- Aktive Plugins ---';
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		foreach ( $active_plugins as $plugin_file ) {
			$name = isset( $all_plugins[ $plugin_file ]['Name'] ) ? $all_plugins[ $plugin_file ]['Name'] : $plugin_file;
			$ver  = isset( $all_plugins[ $plugin_file ]['Version'] ) ? $all_plugins[ $plugin_file ]['Version'] : '–';
			$lines[] = $name . ' (Version ' . $ver . ')';
		}
		$lines[] = '';
		$lines[] = '=== Ende Systembericht ===';

		return implode( "\n", $lines );
	}
}
