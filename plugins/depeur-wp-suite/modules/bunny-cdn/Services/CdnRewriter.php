<?php
/**
 * Schreibt im Frontend-HTML URLs von Origin auf die BunnyCDN-Pull-Zone um.
 * Konzeptuell orientiert am offiziellen Bunny WordPress Plugin (HtmlRewriter):
 * Nur statische Verzeichnisse (wp-includes, wp-content/themes, wp-content/uploads) werden über das CDN ausgeliefert.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Services
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Services;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ersetzt im HTML-Output die Origin-URL durch den CDN-Hostnamen für statische Ressourcen.
 */
class CdnRewriter {

	/**
	 * Verzeichnisse, die über das CDN ausgeliefert werden (Pfade relativ zur Installation).
	 *
	 * @var string[]
	 */
	const INCLUDED_PATHS = array( 'wp-includes/', 'wp-content/themes/', 'wp-content/uploads/', 'wp-content/plugins/' );

	/**
	 * Registriert den Output-Buffer für die URL-Ersetzung (nur Frontend, wenn aktiviert).
	 */
	public static function register() {
		$opts = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		if ( empty( $opts['enable_bunny_cdn'] ) ) {
			return;
		}
		$hostname = isset( $opts['bunny_cdn_hostname'] ) ? trim( (string) $opts['bunny_cdn_hostname'] ) : '';
		if ( $hostname === '' ) {
			return;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 0 );
		add_filter( 'wp_resource_hints', array( __CLASS__, 'resource_hints' ), 10, 2 );
	}

	/**
	 * Fügt preconnect für den CDN-Hostnamen hinzu (schnellerer Ladebeginn).
	 *
	 * @param array  $urls         URLs.
	 * @param string $relation_type Relation (z. B. preconnect).
	 * @return array
	 */
	public static function resource_hints( $urls, $relation_type ) {
		if ( $relation_type !== 'preconnect' ) {
			return $urls;
		}
		$opts     = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		$hostname = isset( $opts['bunny_cdn_hostname'] ) ? trim( (string) $opts['bunny_cdn_hostname'] ) : '';
		if ( $hostname === '' || empty( $opts['enable_bunny_cdn'] ) ) {
			return $urls;
		}
		$scheme = is_ssl() ? 'https' : 'http';
		$urls[] = $scheme . '://' . $hostname;
		return $urls;
	}

	/**
	 * Startet den Output-Buffer mit Rewrite-Callback.
	 */
	public static function start_buffer() {
		ob_start( array( __CLASS__, 'rewrite' ) );
	}

	/**
	 * CDN-Basis-URL für den Rewrite-Callback (benannte Methode statt anonymer Funktion).
	 *
	 * @var string
	 */
	private static $rewrite_cdn_base = '';

	/**
	 * Ersetzt im HTML die Origin-URL durch die CDN-URL für statische Pfade.
	 * Berücksichtigt: volle URLs (http + https gleicher Host) und relative Pfade (/wp-includes/ usw.).
	 *
	 * @param string $html Aktueller HTML-Output.
	 * @return string
	 */
	public static function rewrite( $html ) {
		$opts     = get_option( \Depeur\WPSuite\Modules\BunnyCDN\Admin\Settings::OPTION_KEY, array() );
		$hostname = isset( $opts['bunny_cdn_hostname'] ) ? trim( (string) $opts['bunny_cdn_hostname'] ) : '';
		if ( $hostname === '' ) {
			return $html;
		}

		$scheme   = is_ssl() ? 'https' : 'http';
		$cdn_base = $scheme . '://' . $hostname . '/';
		self::$rewrite_cdn_base = $cdn_base;

		$origin_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $origin_host === null || $origin_host === '' ) {
			$origin_host = wp_parse_url( site_url(), PHP_URL_HOST );
		}
		if ( $origin_host === null || $origin_host === '' ) {
			$origin_host = 'localhost';
		}

		// Beide Schemas für gleichen Host ersetzen (z. B. Besuch unter http://, HTML enthält teils https://).
		$origin_http  = 'http://' . $origin_host . '/';
		$origin_https = 'https://' . $origin_host . '/';

		$paths     = array_map( 'preg_quote', self::INCLUDED_PATHS, array_fill( 0, count( self::INCLUDED_PATHS ), '#' ) );
		$paths_alt = implode( '|', $paths );

		// 1) Relative URLs ersetzen: nur wenn in Anführungszeichen (href="/wp-includes/..." oder src='/wp-content/...').
		$relative_regex = '#(["\'])(/)(' . $paths_alt . '[^"\']*)(["\'])#';
		$html = preg_replace_callback( $relative_regex, array( __CLASS__, 'rewrite_relative_callback' ), $html );

		// 2) Volle Origin-URLs (http und https) durch CDN ersetzen, wenn gefolgt von statischem Pfad.
		foreach ( array( $origin_http, $origin_https ) as $origin_url ) {
			if ( $origin_url === $cdn_base ) {
				continue;
			}
			if ( strpos( $html, $origin_url ) === false ) {
				continue;
			}
			$origin_esc = preg_quote( $origin_url, '#' );
			$regex      = '#(' . $origin_esc . ')(' . $paths_alt . '[^"\')\s]*)#';
			$html       = preg_replace_callback( $regex, array( __CLASS__, 'rewrite_callback' ), $html );
		}

		return $html;
	}

	/**
	 * Callback für preg_replace_callback bei vollen Origin-URLs (benannt, regelkonform).
	 *
	 * @param array $m Treffer (0 = ganz, 1 = Origin-URL, 2 = Pfad).
	 * @return string
	 */
	public static function rewrite_callback( $m ) {
		$base = self::$rewrite_cdn_base;
		return $base . ( isset( $m[2] ) ? $m[2] : '' );
	}

	/**
	 * Callback für relative URLs: Anführungszeichen + /wp-includes/... → Anführungszeichen + CDN-URL.
	 *
	 * @param array $m Treffer (1 = öffnendes Quote, 2 = Slash, 3 = Pfad, 4 = schließendes Quote).
	 * @return string
	 */
	public static function rewrite_relative_callback( $m ) {
		$base = self::$rewrite_cdn_base;
		$path = isset( $m[3] ) ? $m[3] : '';
		$open = isset( $m[1] ) ? $m[1] : '"';
		$close = isset( $m[4] ) ? $m[4] : '"';
		return $open . $base . $path . $close;
	}
}
