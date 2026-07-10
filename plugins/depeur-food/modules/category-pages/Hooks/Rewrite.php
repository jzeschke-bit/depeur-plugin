<?php
/**
 * Rewrite — macht `/page/N/` auf Kategorie-Seiten (normale `page`) nutzbar.
 *
 * Ersetzt das Legacy `alkipedia_page_pagination_rewrite()`, aber **eng gefasst**: statt der
 * greedy Global-Regel `^(.+?)/page/…` (kollisionsanfällig) wird pro *geflaggter* Seite genau
 * eine Regel `^{page-uri}/page/(N)/` registriert. Die URI-Liste ist transient-gecached und
 * wird bei Seiten-Speicherung invalidiert; ein deferred Flush (wp_loaded) zieht neue Regeln
 * genau einmal nach — nie ein Flush pro Request.
 *
 * @package Depeur\Food\Modules\CategoryPages\Hooks
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Hooks;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die per-Seite-Pagination-Rewrites für Kategorie-Seiten.
 *
 * @since 0.3.0
 */
final class Rewrite {

	/**
	 * Meta-Flag, das eine Seite zur Kategorie-Seite macht.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const META_ENABLED = 'df_catpage_enabled';

	/**
	 * Transient mit der Liste der Kategorie-Seiten-URIs.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const TRANSIENT = 'df_category_pages_uris';

	/**
	 * Options-Flag „Rewrites müssen geflusht werden".
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const FLUSH_FLAG = 'df_category_pages_flush';

	/**
	 * Verdrahtet Rule-Registrierung, deferred Flush und Cache-Invalidierung.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Module werden auf `init` geladen — did_action-Guard wie beim Field_Provisioner.
		if ( did_action( 'init' ) ) {
			$this->add_rules();
		} else {
			add_action( 'init', array( $this, 'add_rules' ), 12 );
		}

		add_action( 'wp_loaded', array( $this, 'maybe_flush' ) );
		add_action( 'save_post_page', array( $this, 'on_page_save' ) );
	}

	/**
	 * Registriert je Kategorie-Seite eine `/page/N/`-Rewrite-Regel.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function add_rules(): void {
		foreach ( $this->page_uris() as $uri ) {
			add_rewrite_rule(
				'^' . $uri . '/page/([0-9]+)/?$',
				'index.php?pagename=' . $uri . '&paged=$matches[1]',
				'top'
			);
		}
	}

	/**
	 * Führt einen einmaligen, aufgeschobenen Flush aus, wenn nötig.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function maybe_flush(): void {
		if ( get_option( self::FLUSH_FLAG ) ) {
			flush_rewrite_rules( false );
			delete_option( self::FLUSH_FLAG );
		}
	}

	/**
	 * Invalidiert Cache + markiert Flush-Bedarf bei Seiten-Speicherung.
	 *
	 * @since 0.3.0
	 *
	 * @param int $post_id Gespeicherte Post-ID.
	 * @return void
	 */
	public function on_page_save( $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		delete_transient( self::TRANSIENT );
		update_option( self::FLUSH_FLAG, 1, false );
	}

	/**
	 * Liefert die URIs aller (geflaggten) Kategorie-Seiten (transient-gecached).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, string>
	 */
	private function page_uris(): array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'meta_key'         => self::META_ENABLED, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- gezielte, gecachte Einmal-Abfrage der wenigen Kategorie-Seiten.
				'suppress_filters' => false,
			)
		);

		$uris = array();
		foreach ( $ids as $id ) {
			if ( ! get_post_meta( (int) $id, self::META_ENABLED, true ) ) {
				continue;
			}
			$uri = get_page_uri( (int) $id );
			if ( is_string( $uri ) && '' !== $uri ) {
				$uris[] = $uri;
			}
		}

		set_transient( self::TRANSIENT, $uris, DAY_IN_SECONDS );

		return $uris;
	}
}
