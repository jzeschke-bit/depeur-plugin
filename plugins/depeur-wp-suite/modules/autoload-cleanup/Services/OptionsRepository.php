<?php
/**
 * Datenbankzugriff auf wp_options mit Pagination und Filtern.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Services;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sichere Abfragen auf wp_options mit LENGTH(option_value) für Größe.
 */
class OptionsRepository {

	/**
	 * Liefert eine Seite von Optionen mit optionalen Filtern.
	 *
	 * @param array $args {
	 *     Optional. Argumente.
	 *     @type int    $per_page     Pro Seite (default 50).
	 *     @type int    $paged        Aktuelle Seite (1-basiert).
	 *     @type string $search       Suchbegriff für option_name (LIKE).
	 *     @type int    $min_bytes    Mindestgröße option_value in Bytes (0 = aus).
	 *     @type string $prefix       option_name beginnt mit (starts_with).
	 *     @type string $autoload     'yes' | 'no' | 'all' (default 'all').
	 *     @type string $orderby     'size' | 'name' (default 'size').
	 *     @type string $order       'ASC' | 'DESC' (default 'DESC').
	 * }
	 * @return array{ items: array, total: int }
	 */
	public static function get_options_paginated( array $args = array() ) {
		global $wpdb;
		$table = $wpdb->options;

		$per_page  = isset( $args['per_page'] ) ? max( 1, min( 500, (int) $args['per_page'] ) ) : 50;
		$paged     = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
		$search    = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
		$min_bytes = isset( $args['min_bytes'] ) ? max( 0, (int) $args['min_bytes'] ) : 0;
		$prefix    = isset( $args['prefix'] ) ? trim( (string) $args['prefix'] ) : '';
		$autoload  = isset( $args['autoload'] ) ? $args['autoload'] : 'all';
		$orderby   = isset( $args['orderby'] ) && $args['orderby'] === 'name' ? 'name' : 'size';
		$order     = isset( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$where = array( '1=1' );
		$values = array();

		if ( $search !== '' ) {
			$where[] = 'option_name LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $search ) . '%';
		}
		if ( $min_bytes > 0 ) {
			$where[] = 'LENGTH(option_value) >= %d';
			$values[] = $min_bytes;
		}
		if ( $prefix !== '' ) {
			$where[] = 'option_name LIKE %s';
			$values[] = $wpdb->esc_like( $prefix ) . '%';
		}
		// WordPress speichert autoload als 'yes' oder 'on' – beide als „autoload aktiv“ behandeln.
		if ( $autoload === 'yes' ) {
			$where[] = "( autoload = 'yes' OR autoload = 'on' )";
		} elseif ( $autoload === 'no' ) {
			$where[] = "( autoload IS NULL OR ( autoload != 'yes' AND autoload != 'on' ) )";
		}

		$where_sql = implode( ' AND ', $where );
		$order_sql = $orderby === 'name' ? 'option_name ' . $order : 'LENGTH(option_value) ' . $order;

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		$offset = ( $paged - 1 ) * $per_page;
		$select_sql = "SELECT option_name, option_value, autoload, LENGTH(option_value) AS size_bytes FROM {$table} WHERE {$where_sql} ORDER BY {$order_sql} LIMIT %d OFFSET %d";
		$query_values = array_merge( $values, array( $per_page, $offset ) );
		$results = $wpdb->get_results( $wpdb->prepare( $select_sql, $query_values ), ARRAY_A );

		if ( ! is_array( $results ) ) {
			$results = array();
		}

		$items = array();
		foreach ( $results as $row ) {
			$items[] = array(
				'option_name' => isset( $row['option_name'] ) ? $row['option_name'] : '',
				'autoload'    => isset( $row['autoload'] ) ? $row['autoload'] : 'yes',
				'size_bytes'  => isset( $row['size_bytes'] ) ? (int) $row['size_bytes'] : 0,
			);
		}

		return array( 'items' => $items, 'total' => $total );
	}

	/**
	 * Zählt Optionen mit gegebenem Prefix (für Bulk-Preview).
	 *
	 * @param string $prefix Prefix für option_name.
	 * @return int
	 */
	public static function count_by_prefix( $prefix ) {
		global $wpdb;
		if ( $prefix === '' ) {
			return 0;
		}
		$like = $wpdb->esc_like( $prefix ) . '%';
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
			$like
		) );
	}

	/**
	 * Liefert die ersten N Option-Namen + Größen für ein Prefix (Preview).
	 *
	 * @param string $prefix Prefix.
	 * @param int    $limit  Max. Anzahl (z. B. 20).
	 * @return array<array{ option_name: string, size_bytes: int }>
	 */
	public static function get_preview_by_prefix( $prefix, $limit = 20 ) {
		global $wpdb;
		if ( $prefix === '' ) {
			return array();
		}
		$like = $wpdb->esc_like( $prefix ) . '%';
		$limit = max( 1, min( 100, (int) $limit ) );
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT option_name, LENGTH(option_value) AS size_bytes FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY LENGTH(option_value) DESC LIMIT %d",
			$like,
			$limit
		), ARRAY_A );
		if ( ! is_array( $results ) ) {
			return array();
		}
		$out = array();
		foreach ( $results as $row ) {
			$out[] = array(
				'option_name' => isset( $row['option_name'] ) ? $row['option_name'] : '',
				'size_bytes'  => isset( $row['size_bytes'] ) ? (int) $row['size_bytes'] : 0,
			);
		}
		return $out;
	}

	/**
	 * Löscht alle Optionen, deren option_name mit dem Prefix beginnt.
	 *
	 * @param string $prefix Prefix.
	 * @return int Anzahl gelöschter Zeilen.
	 */
	public static function delete_by_prefix( $prefix ) {
		global $wpdb;
		if ( $prefix === '' ) {
			return 0;
		}
		$like = $wpdb->esc_like( $prefix ) . '%';
		$names = $wpdb->get_col( $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$like
		) );
		if ( ! is_array( $names ) || empty( $names ) ) {
			return 0;
		}
		$deleted = 0;
		foreach ( $names as $name ) {
			if ( delete_option( $name ) ) {
				$deleted++;
			}
		}
		return $deleted;
	}
}
