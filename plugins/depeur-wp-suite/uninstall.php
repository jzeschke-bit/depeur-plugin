<?php
/**
 * Uninstall: Löscht nur Suite-Optionen (depeur_wp_suite_*). Keine fremden Daten.
 *
 * @package Depeur\WPSuite
 * @license GPL-2.0-or-later
 */

// Nur bei echtem Uninstall durch WordPress aufrufbar.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$prefix = 'depeur_wp_suite_';

global $wpdb;

// Alle Optionen mit Prefix löschen.
$options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( $prefix ) . '%'
	)
);

if ( is_array( $options ) ) {
	foreach ( $options as $option_name ) {
		delete_option( $option_name );
	}
}
