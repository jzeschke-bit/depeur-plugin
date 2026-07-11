<?php
/**
 * Rating_Routes — Port der WPRM-Rating-REST-Routen aus rest-api-wprm (XWPRM_Api_Rating).
 *
 * 1:1-Vertragserhalt (BRIEF § 4): Namespace `wrm/v1`, dieselben Pfade/Methoden/Response-
 * Shapes, Backend `WPRM_Rating_Database`. Klassifikation „legacy" (E8) – bewusste Tech-Debt:
 *   - KEINE Auth: alle Routen sind offen (Legacy hatte permission_callback auskommentiert).
 *     Hier explizit `__return_true` statt „weglassen", damit WordPress (5.5+) nicht per
 *     _doing_it_wrong meckert – das Verhalten (offen) ist identisch. Härtung = späteres
 *     rest-modern-Modul, NICHT hier (E8).
 *   - `where`-Klauseln konkatenieren die ID – aber der ID-Parameter ist per
 *     validate_callback numerisch (is_numeric), daher über die ID keine Injection.
 *
 * Abweichung R1 (freigegeben): der Legacy referenzierte auf drei Routen einen fehlenden
 * validate_callback `api_validate_numeric` (Tippfehler) → exaktes 1:1 wäre ein sicherer
 * Fatal. Hier durchgängig der korrekte Callback `validate_numeric` (Intention is_numeric).
 * Alle anderen Bugs bleiben unangetastet.
 *
 * @package Depeur\Food\Modules\RestLegacy\Rest
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\RestLegacy\Rest;

use WP_REST_Request;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und bedient die wrm/v1/rating*-Routen (Legacy-Port).
 *
 * @since 0.3.0
 */
final class Rating_Routes {

	/**
	 * REST-Namespace (Legacy, unverändert).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NS = 'wrm/v1';

	/**
	 * Verdrahtet die Registrierung am rest_api_init-Hook.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Meldet die acht Rating-Routen an – nur wenn das WPRM-Rating-Backend vorhanden ist.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Harte Modul-Dependency: ohne WPRM-Rating-Backend keine Routen (graceful, kein Fatal).
		if ( ! class_exists( 'WPRM_Rating_Database' ) ) {
			return;
		}

		$open       = '__return_true'; // Legacy: offen (E8).
		$numeric_id = array(
			'id' => array(
				'validate_callback' => array( $this, 'validate_numeric' ),
			),
		);

		register_rest_route(
			self::NS,
			'rating',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ratings' ),
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'/rating',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_or_update_rating' ),
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'/rating/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rating' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'/rating/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_rating' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'/rating/recipe/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ratings_for_recipe' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'rating/recipe/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_ratings_for_recipe' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'rating/comment/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rating_for_comment' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			self::NS,
			'rating/comment/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_rating_for_comment' ),
				'args'                => $numeric_id,
				'permission_callback' => $open,
			)
		);
	}

	/**
	 * Validiert einen numerischen ID-Parameter (Legacy-Intention is_numeric, R1).
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $param Zu prüfender Wert.
	 * @return bool
	 */
	public function validate_numeric( $param ): bool {
		return is_numeric( $param );
	}

	/**
	 * GET wrm/v1/rating — alle Ratings.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed WPRM-Rating-Datensatz (1:1 wie Legacy).
	 */
	public function get_ratings( WP_REST_Request $request ) {
		unset( $request );

		return \WPRM_Rating_Database::get_ratings( array() );
	}

	/**
	 * POST wrm/v1/rating — Rating anlegen/aktualisieren.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function add_or_update_rating( WP_REST_Request $request ) {
		$params = $request->get_params();
		$rating = isset( $params['rating'] ) ? $params['rating'] : array();

		return \WPRM_Rating_Database::add_or_update_rating( $rating );
	}

	/**
	 * GET wrm/v1/rating/(id) — ein Rating.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function get_rating( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::get_rating( array( 'where' => 'id = ' . (int) $request['id'] ) );
	}

	/**
	 * DELETE wrm/v1/rating/(id) — Rating löschen.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function delete_rating( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::delete_rating( (int) $request['id'] );
	}

	/**
	 * GET wrm/v1/rating/recipe/(id) — Ratings eines Rezepts.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function get_ratings_for_recipe( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::get_ratings( array( 'where' => 'recipe_id = ' . (int) $request['id'] ) );
	}

	/**
	 * DELETE wrm/v1/rating/recipe/(id) — Ratings eines Rezepts löschen.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function delete_ratings_for_recipe( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::delete_ratings_for( (int) $request['id'] );
	}

	/**
	 * GET wrm/v1/rating/comment/(id) — Rating eines Kommentars.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function get_rating_for_comment( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::get_rating( array( 'where' => 'comment_id = ' . (int) $request['id'] ) );
	}

	/**
	 * DELETE wrm/v1/rating/comment/(id) — Rating eines Kommentars löschen.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public function delete_rating_for_comment( WP_REST_Request $request ) {
		return \WPRM_Rating_Database::delete_ratings_for_comment( (int) $request['id'] );
	}
}
