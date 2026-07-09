<?php
/**
 * Assets — Frontend-Enqueue des „Was koche ich heute"-Filters (Vanilla JS + CSS).
 *
 * Lädt df-recipe-filter.js/.css NUR auf singulären Ansichten, deren Inhalt den Shortcode
 * `[df_recipe_filter]` enthält, und übergibt REST-URL + per-Page + i18n als window.dfRecipeFilter.
 * Kein jQuery, kein Build-Step (Asset-Convention: Frontend strikt Vanilla).
 *
 * @package Depeur\Food\Modules\CategoryPages\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Frontend;

use Depeur\Food\Modules\CategoryPages\Query\Recipe_Query;
use Depeur\Food\Modules\CategoryPages\Rest\Filter_Controller;
use WP_Post;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und enqueued die Filter-Assets.
 *
 * @since 0.3.0
 */
final class Assets {

	/**
	 * Handle (Skript + Style).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const HANDLE = 'df-recipe-filter';

	/**
	 * Verdrahtet den Enqueue-Hook.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueued Skript + Style, wenn der Shortcode auf der Seite steht.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		$dir = DEPEUR_FOOD_PATH . 'modules/category-pages/assets/';
		$url = DEPEUR_FOOD_URL . 'modules/category-pages/assets/';

		$js_file  = $dir . 'df-recipe-filter.js';
		$css_file = $dir . 'df-recipe-filter.css';

		$js_ver  = is_file( $js_file ) ? (string) filemtime( $js_file ) : DEPEUR_FOOD_VERSION;
		$css_ver = is_file( $css_file ) ? (string) filemtime( $css_file ) : DEPEUR_FOOD_VERSION;

		wp_enqueue_style( self::HANDLE, $url . 'df-recipe-filter.css', array(), $css_ver );
		wp_enqueue_script( self::HANDLE, $url . 'df-recipe-filter.js', array(), $js_ver, true );

		wp_localize_script(
			self::HANDLE,
			'dfRecipeFilter',
			array(
				'restUrl' => rest_url( Filter_Controller::REST_NAMESPACE . Filter_Controller::ROUTE ),
				'perPage' => Recipe_Query::per_page(),
				'i18n'    => array(
					'loadMore' => __( 'Mehr laden', 'depeur-food' ),
					'loading'  => __( 'Lädt …', 'depeur-food' ),
					'empty'    => __( 'Keine Rezepte gefunden.', 'depeur-food' ),
				),
			)
		);
	}

	/**
	 * Nur laden, wenn der aktuelle singuläre Inhalt den Shortcode enthält.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	private function should_enqueue(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();

		return $post instanceof WP_Post && has_shortcode( (string) $post->post_content, Recipe_Filter::TAG );
	}
}
