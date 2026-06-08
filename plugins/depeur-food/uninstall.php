<?php
/**
 * Uninstall: Löscht nur Depeur-Food-Optionen (depeur_food_*). Keine fremden Daten.
 *
 * @package Depeur\Food
 * @license GPL-2.0-or-later
 */

// Nur bei echtem Uninstall durch WordPress aufrufbar.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$depeur_food_prefix = 'depeur_food_';

global $wpdb;

// Alle Optionen mit Prefix löschen. Direkte DB-Abfrage ohne Object-Cache ist hier korrekt:
// Uninstall läuft genau einmal, ein persistenter Cache existiert in diesem Kontext nicht.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall: Einmalaufruf, kein Cache-Kontext.
$depeur_food_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( $depeur_food_prefix ) . '%'
	)
);

if ( is_array( $depeur_food_options ) ) {
	foreach ( $depeur_food_options as $depeur_food_option_name ) {
		delete_option( $depeur_food_option_name );
	}
}

// Custom Tabellen (Prefix {$wpdb->prefix}depeur_food_) gibt es im Bootstrap noch nicht;
// sobald ein Modul welche anlegt, werden sie hier per DROP TABLE entfernt.
