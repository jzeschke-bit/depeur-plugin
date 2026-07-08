<?php
/**
 * Favorites_Controller — REST-Endpoints des Favoriten-Moduls.
 *
 * Ersetzt den Legacy-AJAX-Handler `my_favorite_post`, der OHNE Nonce lief (die zu
 * schließende Sicherheitslücke). Zwei Routen:
 *   - POST depeur_food/v1/favorites/toggle : erhöht/senkt den Like-Zähler eines Posts.
 *     Schreibend → permission_callback mit Nonce-Prüfung (X-WP-Nonce, Action wp_rest).
 *   - GET  depeur_food/v1/favorites/list   : liefert Post-Daten für eine ID-Liste
 *     (für das clientseitige Archiv). Rein lesend über veröffentlichte Inhalte → öffentlich.
 *
 * Die Merkliste selbst liegt im localStorage des Clients; der Server hält nur den
 * globalen, aggregierten Zähler. Der Client teilt beim Toggle die Richtung mit
 * (add/remove) – analog zum Legacy-Verhalten (Cookie-basiert, Server nur inkrementell).
 *
 * @package Depeur\Food\Modules\Favorites\Rest
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Rest;

use Depeur\Food\Modules\Favorites\Meta\Like_Counter;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die Favoriten-REST-Routen.
 *
 * @since 0.2.0
 */
final class Favorites_Controller {

	/**
	 * REST-Namespace.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const REST_NAMESPACE = 'depeur_food/v1';

	/**
	 * Route (schreibend): Like-Zähler eines Posts umschalten.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const ROUTE_TOGGLE = '/favorites/toggle';

	/**
	 * Route (lesend): Post-Daten für eine ID-Liste (Archiv).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const ROUTE_LIST = '/favorites/list';

	/**
	 * Obergrenze der pro Archiv-Request aufgelösten IDs (Missbrauchs-/Last-Schutz).
	 *
	 * @since 0.2.0
	 * @var int
	 */
	private const MAX_LIST_IDS = 100;

	/**
	 * Registriert die Routen am rest_api_init-Hook.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Meldet beide Routen bei der REST-API an.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_TOGGLE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_toggle' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'id'        => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) {
							return absint( $value ) > 0;
						},
					),
					'direction' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'add',
						'enum'              => array( 'add', 'remove' ),
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE_LIST,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_list' ),
				// Rein lesend über veröffentlichte Inhalte (wie eine Archivseite) → öffentlich.
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Permission-Callback für den schreibenden Toggle: Nonce-Prüfung (wp_rest).
	 *
	 * Der Legacy-AJAX-Handler hatte KEINE Nonce (offener Schreibzugriff). Hier prüfen wir
	 * die REST-Nonce aus dem X-WP-Nonce-Header (Fallback: _wpnonce-Parameter). Das gilt
	 * für angemeldete UND anonyme Besucher (Favoriten funktionieren ohne Login, wie im
	 * Legacy) – der Nonce ist der CSRF-Schutz, kein Rechte-Gate.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request Der REST-Request.
	 * @return true|WP_Error True bei gültiger Nonce, sonst 403-Fehler.
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) ) {
			$nonce = (string) $request->get_param( '_wpnonce' );
		}

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'df_favorites_invalid_nonce',
				__( 'Sicherheitsprüfung fehlgeschlagen. Bitte die Seite neu laden und erneut versuchen.', 'depeur-food' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Bedient den Toggle: validiert Post + Post-Type und passt den Zähler an.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request Der REST-Request.
	 * @return WP_REST_Response|WP_Error Ergebnis oder Fehler.
	 */
	public function handle_toggle( WP_REST_Request $request ) {
		$post_id   = absint( $request->get_param( 'id' ) );
		$direction = (string) $request->get_param( 'direction' );

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
			return new WP_Error(
				'df_favorites_not_found',
				__( 'Beitrag nicht gefunden.', 'depeur-food' ),
				array( 'status' => 404 )
			);
		}

		// Nur unterstützte Post-Types dürfen den Zähler verändern (post-type-agnostisch, ADR-4).
		if ( ! in_array( $post->post_type, Like_Counter::post_types(), true ) ) {
			return new WP_Error(
				'df_favorites_unsupported_type',
				__( 'Dieser Inhaltstyp unterstützt keine Favoriten.', 'depeur-food' ),
				array( 'status' => 400 )
			);
		}

		$count = Like_Counter::get_likes( $post_id );
		if ( 'remove' === $direction ) {
			$count = max( 0, $count - 1 );
		} else {
			++$count;
		}
		$count = Like_Counter::set_likes( $post_id, $count );

		return new WP_REST_Response(
			array(
				'success'   => true,
				'post_id'   => $post_id,
				'direction' => $direction,
				'likes'     => $count,
			),
			200
		);
	}

	/**
	 * Bedient das Archiv: löst eine ID-Liste in schlanke Post-Karten auf.
	 *
	 * Post-type-agnostisch über die unterstützten Typen; nur veröffentlichte Posts.
	 * Reihenfolge = übergebene ID-Reihenfolge (orderby post__in).
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request Der REST-Request.
	 * @return WP_REST_Response Liste von Post-Datensätzen (ggf. leer).
	 */
	public function handle_list( WP_REST_Request $request ): WP_REST_Response {
		$ids_raw = (string) $request->get_param( 'ids' );

		$ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
		$ids = array_values( array_unique( $ids ) );
		$ids = array_slice( $ids, 0, self::MAX_LIST_IDS );

		if ( empty( $ids ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$query = new WP_Query(
			array(
				'post_type'           => Like_Counter::post_types(),
				'post__in'            => $ids,
				'orderby'             => 'post__in',
				'posts_per_page'      => count( $ids ),
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			$thumbnail = get_the_post_thumbnail_url( $post, 'medium' );

			$items[] = array(
				'id'        => (int) $post->ID,
				'title'     => get_the_title( $post ),
				'url'       => get_permalink( $post ),
				'thumbnail' => is_string( $thumbnail ) ? $thumbnail : '',
				'type'      => $post->post_type,
				'likes'     => Like_Counter::get_likes( (int) $post->ID ),
			);
		}

		return new WP_REST_Response( $items, 200 );
	}
}
