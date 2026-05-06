<?php
/**
 * Controller für die Admin-Seite „Autoload Inspector“: Aktionen verarbeiten, Daten laden, View aufrufen.
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Admin;

use Depeur\WPSuite\Modules\AutoloadCleanup\Services\OptionsRepository;
use Depeur\WPSuite\Modules\AutoloadCleanup\Services\RulesStore;
use Depeur\WPSuite\Modules\AutoloadCleanup\Services\SizeCalculator;
use Depeur\WPSuite\Modules\AutoloadCleanup\Services\SuspicionScorer;
use Depeur\WPSuite\Modules\AutoloadCleanup\Services\PluginScanner;
use Depeur\WPSuite\Modules\AutoloadCleanup\Actions\DeleteOptionAction;
use Depeur\WPSuite\Modules\AutoloadCleanup\Actions\BulkDeletePrefixAction;
use Depeur\WPSuite\Modules\AutoloadCleanup\Actions\UpdateRulesAction;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert Untermenü und verarbeitet Aktionen (Nonce + Capability).
 */
class ScreenController {

	const PAGE_SLUG = 'depeur-wp-suite-autoload-cleanup';
	const NONCE_DELETE = 'depeur_autoload_delete';
	const NONCE_BULK_PREFIX = 'depeur_autoload_bulk_prefix';
	const NONCE_RULES = 'depeur_autoload_rules';

	/**
	 * Registriert Hooks.
	 */
	public static function register() {
		add_action( 'depeur_wp_suite_register_submenus', array( __CLASS__, 'add_submenu' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Fügt Untermenü „Autoload Inspector“ hinzu.
	 *
	 * @param string $parent_slug Parent-Menü-Slug.
	 * @param string $cap         Capability.
	 */
	public static function add_submenu( $parent_slug, $cap ) {
		$active = get_option( 'depeur_wp_suite_modules', array() );
		if ( ! is_array( $active ) || ! in_array( 'autoload-cleanup', $active, true ) ) {
			return;
		}
		add_submenu_page(
			$parent_slug,
			__( 'Autoload Inspector', 'depeur-wp-suite' ),
			__( 'Autoload Inspector', 'depeur-wp-suite' ),
			$cap,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Verarbeitet POST/GET-Aktionen (Nonce + Capability).
	 */
	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
			return;
		}

		$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );

		// Einzeln löschen.
		if ( ! empty( $_GET['delete_option'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE_DELETE ) ) {
				wp_safe_redirect( add_query_arg( 'message', 'nonce_error', $base_url ) );
				exit;
			}
			$result = DeleteOptionAction::run_single( sanitize_text_field( wp_unslash( $_GET['delete_option'] ) ) );
			$msg = $result['success'] ? 'delete_ok' : 'delete_error';
			wp_safe_redirect( add_query_arg( array( 'message' => $msg, 'msg_text' => rawurlencode( $result['message'] ) ), $base_url ) );
			exit;
		}

		// Bulk löschen (POST).
		if ( ! empty( $_POST['action'] ) && $_POST['action'] === 'bulk_delete' && ! empty( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_DELETE ) ) {
				wp_safe_redirect( add_query_arg( 'message', 'nonce_error', $base_url ) );
				exit;
			}
			$names = isset( $_POST['option_names'] ) && is_array( $_POST['option_names'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['option_names'] ) ) : array();
			$result = DeleteOptionAction::run_bulk( $names );
			wp_safe_redirect( add_query_arg( array( 'message' => 'bulk_ok', 'msg_text' => rawurlencode( $result['message'] ) ), $base_url ) );
			exit;
		}

		// Prefix-Bulk löschen (POST mit Bestätigung).
		if ( ! empty( $_POST['action'] ) && $_POST['action'] === 'bulk_delete_prefix' && ! empty( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_BULK_PREFIX ) ) {
				wp_safe_redirect( add_query_arg( array( 'tab' => 'rules', 'message' => 'nonce_error' ), $base_url ) );
				exit;
			}
			if ( empty( $_POST['confirm_prefix'] ) || $_POST['confirm_prefix'] !== '1' ) {
				wp_safe_redirect( add_query_arg( array( 'tab' => 'rules', 'message' => 'prefix_confirm_required' ), $base_url ) );
				exit;
			}
			$prefix = isset( $_POST['prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['prefix'] ) ) : '';
			$result = BulkDeletePrefixAction::run( $prefix );
			$msg = $result['success'] ? 'prefix_ok' : 'prefix_error';
			wp_safe_redirect( add_query_arg( array( 'tab' => 'rules', 'message' => $msg, 'msg_text' => rawurlencode( $result['message'] ) ), $base_url ) );
			exit;
		}

		// Regeln speichern (POST): prefix_map aus Key/Val-Arrays und Textareas bauen.
		if ( ! empty( $_POST['action'] ) && $_POST['action'] === 'save_rules' && ! empty( $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_RULES ) ) {
				wp_safe_redirect( add_query_arg( array( 'tab' => 'rules', 'message' => 'nonce_error' ), $base_url ) );
				exit;
			}
			$keys = isset( $_POST['prefix_map_key'] ) && is_array( $_POST['prefix_map_key'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['prefix_map_key'] ) ) : array();
			$vals = isset( $_POST['prefix_map_val'] ) && is_array( $_POST['prefix_map_val'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['prefix_map_val'] ) ) : array();
			$prefix_map = array();
			foreach ( $keys as $i => $k ) {
				$v = isset( $vals[ $i ] ) ? $vals[ $i ] : '';
				if ( $k !== '' ) {
					$prefix_map[ $k ] = $v;
				}
			}
			if ( ! empty( $_POST['suggest_accept'] ) && is_array( $_POST['suggest_accept'] ) ) {
				foreach ( wp_unslash( $_POST['suggest_accept'] ) as $prefix => $slug ) {
					$p = sanitize_text_field( $prefix );
					$s = sanitize_text_field( $slug );
					if ( $p !== '' ) {
						$prefix_map[ $p ] = $s;
					}
				}
			}
			$ignored_p = isset( $_POST['ignored_prefixes'] ) ? preg_split( '/\r\n|\r|\n/', wp_unslash( $_POST['ignored_prefixes'] ), -1, PREG_SPLIT_NO_EMPTY ) : array();
			$ignored_o = isset( $_POST['ignored_options'] ) ? preg_split( '/\r\n|\r|\n/', wp_unslash( $_POST['ignored_options'] ), -1, PREG_SPLIT_NO_EMPTY ) : array();
			$input = array(
				'prefix_map'       => $prefix_map,
				'ignored_prefixes' => array_map( 'sanitize_text_field', $ignored_p ),
				'ignored_options'  => array_map( 'sanitize_text_field', $ignored_o ),
				'per_page'         => isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 50,
				'min_size_bytes'   => isset( $_POST['min_size_bytes'] ) ? (int) $_POST['min_size_bytes'] : 0,
			);
			$result = UpdateRulesAction::run( $input );
			$msg = $result['success'] ? 'rules_ok' : 'rules_error';
			wp_safe_redirect( add_query_arg( array( 'tab' => 'rules', 'message' => $msg, 'msg_text' => rawurlencode( $result['message'] ) ), $base_url ) );
			exit;
		}
	}

	/**
	 * Rendert die Admin-Seite (lädt Daten, ruft Views auf).
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'autoload';
		$allowed_tabs = array( 'autoload', 'all', 'suspicious', 'rules' );
		if ( ! in_array( $tab, $allowed_tabs, true ) ) {
			$tab = 'autoload';
		}
		$rules = RulesStore::get_all();
		$per_page = $rules['ui']['per_page'];
		$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$search = isset( $_GET['s'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : '';
		$min_bytes = isset( $_GET['min_bytes'] ) ? max( 0, (int) $_GET['min_bytes'] ) : $rules['ui']['min_size_bytes'];
		$filter_autoload = isset( $_GET['autoload'] ) ? sanitize_key( wp_unslash( $_GET['autoload'] ) ) : 'all';
		if ( ! in_array( $filter_autoload, array( 'yes', 'no', 'all' ), true ) ) {
			$filter_autoload = 'all';
		}
		$filter_prefix = isset( $_GET['prefix'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['prefix'] ) ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'size';
		if ( ! in_array( $orderby, array( 'size', 'name' ), true ) ) {
			$orderby = 'size';
		}
		$order = isset( $_GET['order'] ) && strtoupper( $_GET['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$args = array(
			'per_page'  => $per_page,
			'paged'     => $paged,
			'search'    => $search,
			'min_bytes' => $min_bytes,
			'prefix'    => $filter_prefix,
			'autoload'  => $tab === 'autoload' ? 'yes' : $filter_autoload,
			'orderby'   => $orderby,
			'order'     => $order,
		);
		$result = OptionsRepository::get_options_paginated( $args );
		$items = $result['items'];
		$total = $result['total'];

		$active_plugins = SuspicionScorer::get_active_plugin_slugs();
		$all_plugins = SuspicionScorer::get_all_plugins_map();
		$context = array( 'active_plugins' => $active_plugins, 'all_plugins' => $all_plugins );
		foreach ( $items as &$row ) {
			if ( RulesStore::is_ignored( $row['option_name'] ) ) {
				$row['suspicion'] = array( 'level' => 'low', 'reason' => __( 'Ignoriert (Whitelist).', 'depeur-wp-suite' ) );
			} else {
				$row['suspicion'] = SuspicionScorer::score( $row['option_name'], $row['size_bytes'], $context );
			}
			$row['plugin_slug'] = RulesStore::get_plugin_slug_for_option( $row['option_name'] );
		}
		unset( $row );

		$suggestions = array();
		if ( $tab === 'rules' ) {
			$suggestions = PluginScanner::get_suggestions();
		}

		$message = isset( $_GET['message'] ) ? sanitize_key( wp_unslash( $_GET['message'] ) ) : '';
		$msg_text = isset( $_GET['msg_text'] ) ? sanitize_text_field( wp_unslash( $_GET['msg_text'] ) ) : '';

		$prefix_preview = null;
		if ( $tab === 'rules' && ! empty( $_GET['preview_prefix'] ) ) {
			$preview_prefix = trim( sanitize_text_field( wp_unslash( $_GET['preview_prefix'] ) ) );
			if ( $preview_prefix !== '' ) {
				$prefix_preview = array(
					'prefix' => $preview_prefix,
					'count'  => OptionsRepository::count_by_prefix( $preview_prefix ),
					'items'  => OptionsRepository::get_preview_by_prefix( $preview_prefix, 20 ),
				);
			}
		}

		Views::render( array(
			'tab'             => $tab,
			'items'           => $items,
			'total'           => $total,
			'paged'           => $paged,
			'per_page'        => $per_page,
			'search'          => $search,
			'min_bytes'       => $min_bytes,
			'filter_autoload' => $filter_autoload,
			'filter_prefix'   => $filter_prefix,
			'orderby'         => $orderby,
			'order'           => $order,
			'rules'           => $rules,
			'suggestions'     => $suggestions,
			'prefix_preview'  => $prefix_preview,
			'message'         => $message,
			'msg_text'        => $msg_text,
			'base_url'        => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'nonce_delete'    => wp_create_nonce( self::NONCE_DELETE ),
			'nonce_bulk_prefix' => wp_create_nonce( self::NONCE_BULK_PREFIX ),
			'nonce_rules'     => wp_create_nonce( self::NONCE_RULES ),
		) );
	}
}
