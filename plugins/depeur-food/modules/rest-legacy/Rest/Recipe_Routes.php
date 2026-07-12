<?php
/**
 * Recipe_Routes — Port von `wl/v1/posts` + dem `rest_wprm_recipe_query`-Filter (rest-api-wprm).
 *
 * 1:1-Vertragserhalt (BRIEF § 4). Klassifikation „legacy" (E8) – bewusste Tech-Debt bleibt
 * erhalten und ist hier markiert:
 *   - `wl/v1/posts` ist offen (kein Auth) – wie im Legacy.
 *   - `id2` stammt aus der nicht existierenden Property `ParrentID` (Tippfehler von
 *     parent_id) → immer null. 1:1 als null erhalten.
 *   - `content` ist hart „hallo".
 *   - `rest_wprm_recipe_query`: `max(custom_per_page, 200)` – kleinere Werte werden ignoriert.
 *
 * Nicht-gefunden-Fall 1:1: der Legacy erzeugte bei unbekanntem Slug PHP-Warnings, lieferte
 * aber ein Objekt mit null-Werten (content „hallo", Bilder false) – KEIN Fatal (null->prop
 * ist in PHP 8 nur eine Warning). Wir reproduzieren exakt diese Shape null-sicher (?? / Guard)
 * – identische Ausgabe, nur ohne die PHP-Warnings.
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
 * Registriert und bedient `wl/v1/posts` und den Recipe-Query-Filter (Legacy-Port).
 *
 * @since 0.3.0
 */
final class Recipe_Routes {

	/**
	 * REST-Namespace (Legacy, unverändert).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const NS = 'wl/v1';

	/**
	 * Verdrahtet Route + Filter.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		// WPRM-eigener Filter (Drittanbieter): posts_per_page der WPRM-Recipe-REST-Query.
		add_filter( 'rest_wprm_recipe_query', array( $this, 'change_posts_per_page' ), 10, 2 );
	}

	/**
	 * Meldet `wl/v1/posts` an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NS,
			'posts',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_post_by_slug' ),
				// Legacy: offen, kein permission_callback (E8). Explizit __return_true gegen
				// die WP-5.5-_doing_it_wrong-Meldung; Verhalten (offen) identisch.
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_title',
					),
				),
			)
		);
	}

	/**
	 * Filter `rest_wprm_recipe_query`: setzt posts_per_page = max(custom_per_page, 200).
	 *
	 * Bekannte Tech-Debt (E8): das max() ignoriert kleinere Custom-Werte – Ergebnis immer ≥ 200.
	 * 1:1 erhalten.
	 *
	 * @since 0.3.0
	 *
	 * @param array           $args    Query-Args der WPRM-Recipe-REST-Query.
	 * @param WP_REST_Request $request Request.
	 * @return array
	 */
	public function change_posts_per_page( $args, $request ): array {
		$args                   = is_array( $args ) ? $args : array();
		$max                    = max( (int) $request->get_param( 'custom_per_page' ), 200 );
		$args['posts_per_page'] = $max;

		return $args;
	}

	/**
	 * GET `wl/v1/posts?slug=` — Recipe-Slug-Lookup (Legacy-Shape 1:1, auch „nicht gefunden").
	 *
	 * @since 0.3.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array Response-Array – immer dieselbe Shape wie der Legacy.
	 */
	public function get_post_by_slug( WP_REST_Request $request ): array {
		$slug = (string) $request['slug'];

		$posts = get_posts(
			array(
				'name'      => $slug,
				'post_type' => 'wprm_recipe',
			)
		);

		// Null-sicher, damit die Shape auch OHNE Treffer 1:1 dem Legacy entspricht (id=null,
		// content „hallo", Bilder false). Der Legacy erzeugte hier nur zusätzlich PHP-Warnings
		// (kein Fatal – null->prop ist in PHP 8 eine Warning) und lieferte dieselbe Shape.
		$post = isset( $posts[0] ) ? $posts[0] : null;

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Legacy-Property (Tippfehler) bewusst 1:1 erhalten, existiert nicht → null.
		$parrent_id = $post->ParrentID ?? null;

		return array(
			'id'             => $post->ID ?? null,
			'id2'            => $parrent_id,
			'title'          => $post->post_title ?? null,
			// Bekannte Tech-Debt: content war im Legacy hart „hallo" (1:1).
			'content'        => 'hallo',
			'slug'           => $post->post_name ?? null,
			'featured_image' => array(
				'thumbnail' => $post ? get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) : false,
				'medium'    => $post ? get_the_post_thumbnail_url( $post->ID, 'medium' ) : false,
				'large'     => $post ? get_the_post_thumbnail_url( $post->ID, 'large' ) : false,
			),
		);
	}
}
