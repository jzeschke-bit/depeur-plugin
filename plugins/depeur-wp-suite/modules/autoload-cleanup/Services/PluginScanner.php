<?php
/**
 * Scannt installierte Plugins und schlägt Prefix-Mappings vor.
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
 * Leitet aus Plugin-Slug plausible Option-Prefixe ab (Vorschläge, nicht perfekt).
 */
class PluginScanner {

	/**
	 * Liefert Vorschläge für Prefix → Plugin-Slug.
	 * Slug "seo-by-rank-math" → z. B. "rank_math_", "rank_math_" (aus Ordnername).
	 *
	 * @return array<array{ prefix: string, plugin_slug: string, plugin_name: string }>
	 */
	public static function get_suggestions() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all   = get_plugins();
		$rules = RulesStore::get_all();
		$existing = array_keys( $rules['prefix_map'] );
		$suggestions = array();

		foreach ( $all as $file => $data ) {
			if ( strpos( $file, '/' ) !== false ) {
				$slug = dirname( $file );
			} else {
				$slug = pathinfo( $file, PATHINFO_FILENAME );
			}
			$name = isset( $data['Name'] ) ? $data['Name'] : $slug;
			$prefix_candidates = self::slug_to_prefix_candidates( $slug );
			foreach ( $prefix_candidates as $prefix ) {
				if ( $prefix === '' ) {
					continue;
				}
				if ( in_array( $prefix, $existing, true ) ) {
					continue;
				}
				$key = $prefix . '|' . $slug;
				if ( ! isset( $suggestions[ $key ] ) ) {
					$suggestions[ $key ] = array(
						'prefix'      => $prefix,
						'plugin_slug' => $slug,
						'plugin_name' => $name,
					);
				}
			}
		}
		return array_values( $suggestions );
	}

	/**
	 * Leitet aus einem Plugin-Slug plausible Option-Prefixe ab.
	 *
	 * @param string $slug Plugin-Slug (z. B. "seo-by-rank-math" oder "wp-rocket").
	 * @return string[]
	 */
	public static function slug_to_prefix_candidates( $slug ) {
		$candidates = array();
		$slug = trim( (string) $slug );
		if ( $slug === '' ) {
			return $candidates;
		}
		$with_underscore = str_replace( '-', '_', $slug ) . '_';
		$candidates[] = $with_underscore;
		$with_hyphen = $slug . '_';
		if ( $with_hyphen !== $with_underscore ) {
			$candidates[] = $with_hyphen;
		}
		if ( strpos( $slug, '-' ) !== false ) {
			$parts = explode( '-', $slug );
			$short = '';
			foreach ( $parts as $p ) {
				$short .= substr( $p, 0, 1 );
			}
			if ( $short !== '' ) {
				$candidates[] = $short . '_';
			}
		}
		return $candidates;
	}
}
