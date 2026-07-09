<?php
/**
 * Recipe_Filter — Shortcode `[df_recipe_filter]`: „Was koche ich heute" (interaktiver Filter).
 *
 * Ersetzt das Legacy-`was-koche-ich-heute.php`-Template: rendert die nach Tag-Gruppen
 * sortierten Filter-Bubbles + das initiale Beitragsraster + einen „Mehr laden"-Button.
 * Die Erstausgabe läuft server-seitig (SEO/no-JS); das interaktive Filtern/Nachladen
 * übernimmt df-recipe-filter.js über den REST-Endpoint (Filter_Controller).
 *
 * Post-type-agnostisch (ADR-4); Query identisch zum REST-Pfad (Recipe_Query).
 *
 * @package Depeur\Food\Modules\CategoryPages\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Frontend;

use Depeur\Food\Modules\CategoryPages\Query\Recipe_Query;
use Depeur\Food\Modules\CategoryPages\Support\Tag_Groups;
use Depeur\Food\Support\Loop_Grid;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert den Rezept-Filter-Shortcode.
 *
 * @since 0.3.0
 */
final class Recipe_Filter {

	/**
	 * Shortcode-Tag.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const TAG = 'df_recipe_filter';

	/**
	 * Verdrahtet den Shortcode.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Rendert Filter-UI + Ergebnis-Raster + „Mehr laden".
	 *
	 * @since 0.3.0
	 *
	 * @param array|string $atts Shortcode-Attribute (`match` = and|or).
	 * @return string
	 */
	public function render( $atts ): string {
		$atts     = shortcode_atts( array( 'match' => 'and' ), $atts, self::TAG );
		$match    = ( 'or' === $atts['match'] ) ? 'or' : 'and';
		$selected = self::selected_from_request();

		$query    = Recipe_Query::build( $selected, $match, 1 );
		$grid     = Loop_Grid::render_query( $query );
		$has_more = 1 < (int) $query->max_num_pages;

		ob_start();
		echo '<div class="df-recipe-filter" data-match="' . esc_attr( $match ) . '">';

		echo $this->render_filter_ui( $selected ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intern gebaut + escaped.

		echo '<h2 class="df-recipe-filter__title">' . esc_html( Recipe_Query::title( $selected ) ) . '</h2>';

		// $grid ist server-gerendertes, escaptes Loop-HTML.
		echo '<div class="df-recipe-filter__results">' . $grid . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Loop_Grid liefert escaptes HTML.

		printf(
			'<div class="df-recipe-filter__more"><button type="button" class="df-recipe-filter__more-btn button"%1$s>%2$s</button></div>',
			$has_more ? '' : ' hidden',
			esc_html__( 'Mehr laden', 'depeur-food' )
		);

		echo '</div>';

		return (string) ob_get_clean();
	}

	/**
	 * Rendert die nach Gruppen sortierten Filter-Bubbles.
	 *
	 * @since 0.3.0
	 *
	 * @param array<int, string> $selected Vorausgewählte Tag-Slugs (aus der URL).
	 * @return string
	 */
	private function render_filter_ui( array $selected ): string {
		$groups = Tag_Groups::grouped_tags();
		if ( empty( $groups ) ) {
			return '';
		}

		$selected_lookup = array_flip( $selected );

		ob_start();
		echo '<form class="df-recipe-filter__form">';
		foreach ( $groups as $group ) {
			if ( empty( $group['tags'] ) ) {
				continue;
			}
			echo '<fieldset class="df-recipe-filter__group">';
			echo '<legend class="df-recipe-filter__legend">' . esc_html( (string) $group['label'] ) . '</legend>';
			echo '<div class="df-recipe-filter__bubbles">';
			foreach ( $group['tags'] as $tag ) {
				$active = isset( $selected_lookup[ $tag->slug ] );
				printf(
					'<button type="button" class="df-recipe-filter__bubble%1$s" data-slug="%2$s" aria-pressed="%3$s">%4$s</button>',
					$active ? ' is-active' : '',
					esc_attr( $tag->slug ),
					$active ? 'true' : 'false',
					esc_html( $tag->name )
				);
			}
			echo '</div></fieldset>';
		}
		echo '</form>';

		return (string) ob_get_clean();
	}

	/**
	 * Liest die vorausgewählten Tag-Slugs aus der URL (?tags=slug1,slug2).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int, string>
	 */
	private static function selected_from_request(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lesender Filter-Zustand aus der URL, kein Formular-Processing.
		$raw   = isset( $_GET['tags'] ) ? sanitize_text_field( wp_unslash( $_GET['tags'] ) ) : '';
		$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) );

		return array_values( array_unique( $slugs ) );
	}
}
