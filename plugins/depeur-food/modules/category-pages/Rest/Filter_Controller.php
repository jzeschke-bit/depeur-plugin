<?php
/**
 * Filter_Controller — REST-Endpoint des „Was koche ich heute"-Filters (Nachladen/Filtern).
 *
 * Ersetzt den Legacy-`admin-ajax`-Handler `filter_recipes` (der ohne Nonce lief). Der Endpoint
 * ist rein LESEND über veröffentlichte Inhalte → bewusst öffentlich (wie das favorites-List-
 * Pendant), damit er auf vollgecachten Seiten für anonyme Besucher funktioniert (kein
 * Nonce-Stale-Problem hinter RunCache/Cloudflare). Missbrauchsschutz stattdessen über harte
 * Caps: max. Tags + feste posts_per_page (kein user-gesteuertes Limit).
 *
 * @package Depeur\Food\Modules\CategoryPages\Rest
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Rest;

use Depeur\Food\Modules\CategoryPages\Query\Recipe_Query;
use Depeur\Food\Support\Loop_Grid;
use WP_REST_Request;
use WP_REST_Response;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Rezept-Filter-Route.
 *
 * @since 0.3.0
 */
final class Filter_Controller {

	/**
	 * REST-Namespace.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const REST_NAMESPACE = 'depeur_food/v1';

	/**
	 * Route.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const ROUTE = '/recipe-filter';

	/**
	 * Obergrenze der ausgewerteten Tags (Missbrauchsschutz).
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const MAX_TAGS = 20;

	/**
	 * Verdrahtet die Registrierung.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Meldet die Route an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle' ),
				// Rein lesend über veröffentlichte Inhalte → öffentlich (cache-freundlich), gecappt.
				'permission_callback' => '__return_true',
				'args'                => array(
					'tags'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'paged' => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'match' => array(
						'type'              => 'string',
						'default'           => 'and',
						'enum'              => array( 'and', 'or' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Bedient den Filter: rendert das gefilterte Raster + Titel + hasMore.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Der REST-Request.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$slugs = self::parse_slugs( (string) $request->get_param( 'tags' ) );
		$paged = max( 1, (int) $request->get_param( 'paged' ) );
		$match = ( 'or' === $request->get_param( 'match' ) ) ? 'or' : 'and';

		$query = Recipe_Query::build( $slugs, $match, $paged );
		$html  = Loop_Grid::render_query( $query );

		return new WP_REST_Response(
			array(
				'content' => $html,
				'title'   => Recipe_Query::title( $slugs ),
				'hasMore' => $paged < (int) $query->max_num_pages,
			),
			200
		);
	}

	/**
	 * Zerlegt + sanitisiert die Tag-Slug-Liste und cappt sie.
	 *
	 * @since 0.3.0
	 *
	 * @param string $raw CSV der Tag-Slugs.
	 * @return array<int, string>
	 */
	private static function parse_slugs( string $raw ): array {
		$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) );
		$slugs = array_values( array_unique( $slugs ) );

		return array_slice( $slugs, 0, self::MAX_TAGS );
	}
}
