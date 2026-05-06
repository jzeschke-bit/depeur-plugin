<?php
/**
 * Bewertet Optionen als verdächtig (LOW / MEDIUM / HIGH) mit Begründung.
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
 * Heuristiken für verdächtige Optionen (Plugin nicht installiert, deaktiviert, groß ohne Mapping).
 */
class SuspicionScorer {

	const LEVEL_LOW    = 'low';
	const LEVEL_MEDIUM = 'medium';
	const LEVEL_HIGH   = 'high';

	/**
	 * Schwellwert (Bytes), ab dem „unbekannt aber groß“ als MEDIUM gewertet wird.
	 *
	 * @var int
	 */
	const LARGE_SIZE_THRESHOLD = 200 * 1024;

	/**
	 * Bewertet eine Option und liefert Stufe + Begründung.
	 *
	 * @param string $option_name Option-Name.
	 * @param int    $size_bytes  Größe in Bytes.
	 * @param array  $context     Optional. prefix_map, active_plugins (Slugs), theme_mods_prefixes.
	 * @return array{ level: string, reason: string }
	 */
	public static function score( $option_name, $size_bytes = 0, array $context = array() ) {
		$plugin_slug = RulesStore::get_plugin_slug_for_option( $option_name );
		$active_plugins = isset( $context['active_plugins'] ) && is_array( $context['active_plugins'] ) ? $context['active_plugins'] : array();
		$all_plugins   = isset( $context['all_plugins'] ) && is_array( $context['all_plugins'] ) ? $context['all_plugins'] : array();

		if ( $plugin_slug !== '' ) {
			$is_active = in_array( $plugin_slug, $active_plugins, true );
			$is_installed = isset( $all_plugins[ $plugin_slug ] ) || in_array( $plugin_slug, array_keys( $all_plugins ), true );
			if ( ! $is_installed ) {
				return array(
					'level'  => self::LEVEL_HIGH,
					'reason' => sprintf(
						/* translators: 1: prefix/option context, 2: plugin slug */
						__( 'Prefix passt zu Plugin „%2$s“, Plugin ist nicht installiert.', 'depeur-wp-suite' ),
						$option_name,
						$plugin_slug
					),
				);
			}
			if ( ! $is_active ) {
				return array(
					'level'  => self::LEVEL_MEDIUM,
					'reason' => sprintf(
						/* translators: %s: plugin slug */
						__( 'Prefix passt zu Plugin „%s“, Plugin ist deaktiviert.', 'depeur-wp-suite' ),
						$plugin_slug
					),
				);
			}
			return array(
				'level'  => self::LEVEL_LOW,
				'reason' => __( 'Normale Option (Plugin aktiv).', 'depeur-wp-suite' ),
			);
		}

		// theme_mods_*: vorsichtig, maximal MEDIUM.
		if ( strpos( $option_name, 'theme_mods_' ) === 0 ) {
			return array(
				'level'  => self::LEVEL_MEDIUM,
				'reason' => __( 'Theme-Option (theme_mods). Themes können weiterhin Daten benötigen.', 'depeur-wp-suite' ),
			);
		}

		// Unbekannt aber groß: nur Hinweis, MEDIUM.
		if ( $size_bytes >= self::LARGE_SIZE_THRESHOLD ) {
			return array(
				'level'  => self::LEVEL_MEDIUM,
				'reason' => __( 'Unbekannter Prefix, Option ist sehr groß – prüfen vor dem Löschen.', 'depeur-wp-suite' ),
			);
		}

		return array(
			'level'  => self::LEVEL_LOW,
			'reason' => __( 'Unbekannter Prefix, geringe Größe.', 'depeur-wp-suite' ),
		);
	}

	/**
	 * Liefert aktive Plugin-Slugs (Verzeichnisname der Plugin-Datei).
	 *
	 * @return string[]
	 */
	public static function get_active_plugin_slugs() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all = get_plugins();
		$active = array();
		foreach ( array_keys( $all ) as $file ) {
			if ( strpos( $file, '/' ) !== false ) {
				$slug = dirname( $file );
			} else {
				$slug = pathinfo( $file, PATHINFO_FILENAME );
			}
			if ( is_plugin_active( $file ) ) {
				$active[] = $slug;
			}
		}
		return $active;
	}

	/**
	 * Liefert alle installierten Plugins als [ slug => name ].
	 *
	 * @return array<string, string>
	 */
	public static function get_all_plugins_map() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all = get_plugins();
		$out = array();
		foreach ( $all as $file => $data ) {
			if ( strpos( $file, '/' ) !== false ) {
				$slug = dirname( $file );
			} else {
				$slug = pathinfo( $file, PATHINFO_FILENAME );
			}
			$out[ $slug ] = isset( $data['Name'] ) ? $data['Name'] : $slug;
		}
		return $out;
	}
}
