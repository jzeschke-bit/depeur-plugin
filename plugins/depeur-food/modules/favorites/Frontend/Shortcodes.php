<?php
/**
 * Shortcodes — Frontend-Ausgabe des Favoriten-Moduls.
 *
 * Ersetzt die drei Legacy-Shortcodes (thumbnail_favorite_button / inline_favorite_button /
 * favorite_posts_archive) durch zwei df-präfixierte, post-type-agnostische Shortcodes:
 *   - [df_favorite_button style="thumbnail|inline" post_id="…" count="auto|1|0"]
 *   - [df_favorites_archive]
 *
 * Der Button wird server-seitig IMMER im neutralen Zustand (♡) und mit dem globalen
 * Zählerstand gerendert; den gemerkten Zustand (♥) setzt das JS aus dem localStorage
 * (der Server kennt die clientseitige Merkliste nicht). Das Archiv liefert nur einen
 * Container – die Liste baut das JS aus localStorage + REST (list-Endpoint).
 *
 * @package Depeur\Food\Modules\Favorites\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Frontend;

use Depeur\Food\Modules\Favorites\Meta\Like_Counter;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert die Favoriten-Shortcodes.
 *
 * @since 0.2.0
 */
final class Shortcodes {

	/**
	 * Shortcode-Tag für den Favoriten-Button.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const TAG_BUTTON = 'df_favorite_button';

	/**
	 * Shortcode-Tag für das Favoriten-Archiv.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const TAG_ARCHIVE = 'df_favorites_archive';

	/**
	 * Registriert die Shortcodes – inkl. der Legacy-Aliase.
	 *
	 * Die Theme-Templates (Karten/„Ähnliche Beiträge", Overlay überm Beitragsbild) und alte
	 * Inhalte rufen weiterhin die Legacy-Tags auf (`thumbnail_favorite_button` etc.). Ohne
	 * diese Aliase liefen sie nach dem Ablösen des Alt-Plugins leer → Button verschwindet.
	 * Die Aliase rendern dasselbe df-Markup (CSS/JS/REST identisch), sind also 1:1-Ersatz.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_shortcode( self::TAG_BUTTON, array( $this, 'render_button' ) );
		add_shortcode( self::TAG_ARCHIVE, array( $this, 'render_archive' ) );

		// Legacy-Aliase (my-favorite-posts-plugin) für Theme-Templates/Bestandsinhalte.
		add_shortcode( 'thumbnail_favorite_button', array( $this, 'render_legacy_thumbnail' ) );
		add_shortcode( 'inline_favorite_button', array( $this, 'render_legacy_inline' ) );
		add_shortcode( 'wprm-favorite-button', array( $this, 'render_legacy_wprm' ) );
		add_shortcode( 'favorite_posts_archive', array( $this, 'render_archive' ) );
	}

	/**
	 * Legacy-Alias `[thumbnail_favorite_button post_id="…"]` → Overlay-Button überm Bild.
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string
	 */
	public function render_legacy_thumbnail( $atts ): string {
		$atts = shortcode_atts( array( 'post_id' => get_the_ID() ), $atts, 'thumbnail_favorite_button' );
		return self::button_markup( absint( $atts['post_id'] ), 'thumbnail', false );
	}

	/**
	 * Legacy-Alias `[inline_favorite_button post_id="…"]` → Inline-Herz im Textfluss.
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string
	 */
	public function render_legacy_inline( $atts ): string {
		$atts = shortcode_atts( array( 'post_id' => get_the_ID() ), $atts, 'inline_favorite_button' );
		return self::button_markup( absint( $atts['post_id'] ), 'inline', false );
	}

	/**
	 * Legacy-Alias `[wprm-favorite-button id="…"]` (WPRM nutzt die Recipe-ID).
	 *
	 * Der Favorit gehört zum angezeigten Beitrag, nicht zum WPRM-Recipe-CPT: darum zielt der
	 * Button primär auf `post_id`/den Loop-Post; nur wenn beide fehlen, dient `id` als
	 * Fallback. So rendert der Button im Karten-/Roundup-Kontext auf den richtigen Beitrag.
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string
	 */
	public function render_legacy_wprm( $atts ): string {
		$atts    = shortcode_atts( array( 'id' => 0, 'post_id' => get_the_ID() ), $atts, 'wprm-favorite-button' );
		$post_id = absint( $atts['post_id'] );
		if ( $post_id < 1 || ! in_array( get_post_type( $post_id ), Like_Counter::post_types(), true ) ) {
			$post_id = absint( $atts['id'] );
		}
		return self::button_markup( $post_id, 'thumbnail', false );
	}

	/**
	 * Rendert [df_favorite_button].
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute.
	 * @return string Button-HTML (leer, wenn kein/kein unterstützter Post).
	 */
	public function render_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
				'style'   => 'thumbnail',
				// auto = Zähler nur in der Inline-Variante zeigen; 1/0 erzwingt/verbirgt.
				'count'   => 'auto',
			),
			$atts,
			self::TAG_BUTTON
		);

		$post_id = absint( $atts['post_id'] );
		if ( $post_id < 1 ) {
			return '';
		}

		$style = 'inline' === $atts['style'] ? 'inline' : 'thumbnail';

		if ( 'auto' === $atts['count'] ) {
			$show_count = ( 'inline' === $style );
		} else {
			$show_count = ! empty( $atts['count'] ) && '0' !== (string) $atts['count'];
		}

		return self::button_markup( $post_id, $style, $show_count );
	}

	/**
	 * Baut das Button-Markup (auch von der WPRM-Integration wiederverwendet).
	 *
	 * Rendert nur für unterstützte, existierende Post-Types – sonst leer (graceful).
	 * Sämtliche dynamischen Werte werden escaped.
	 *
	 * @since 0.2.0
	 *
	 * @param int    $post_id    Ziel-Post.
	 * @param string $style      'thumbnail' oder 'inline'.
	 * @param bool   $show_count Ob der globale Zählerstand angezeigt wird.
	 * @return string Button-HTML oder leerer String.
	 */
	public static function button_markup( int $post_id, string $style, bool $show_count ): string {
		if ( $post_id < 1 ) {
			return '';
		}

		$post_type = get_post_type( $post_id );
		if ( false === $post_type || ! in_array( $post_type, Like_Counter::post_types(), true ) ) {
			return '';
		}

		$style   = 'inline' === $style ? 'inline' : 'thumbnail';
		$classes = 'df-favorite-button df-favorite-button--' . $style;

		$aria_label = sprintf(
			/* translators: %s: Beitragstitel. */
			__( '„%s" zu Favoriten hinzufügen oder entfernen', 'depeur-food' ),
			get_the_title( $post_id )
		);

		$button = sprintf(
			'<button type="button" class="%1$s" data-post-id="%2$d" data-style="%3$s" aria-pressed="false" aria-label="%4$s"><span class="df-favorite-icon" aria-hidden="true">%5$s</span>',
			esc_attr( $classes ),
			$post_id,
			esc_attr( $style ),
			esc_attr( $aria_label ),
			esc_html( '♡' )
		);

		if ( $show_count ) {
			$button .= sprintf(
				' <span class="df-favorite-count">%d</span>',
				(int) Like_Counter::get_likes( $post_id )
			);
		}

		$button .= '</button>';

		// Thumbnail-Variante wird für die Overlay-Positionierung umschlossen.
		if ( 'thumbnail' === $style ) {
			$button = '<div class="df-favorite-wrapper">' . $button . '</div>';
		}

		return $button;
	}

	/**
	 * Rendert [df_favorites_archive] als leeren Container.
	 *
	 * Da die Merkliste clientseitig lebt, füllt das JS den Container aus localStorage +
	 * REST (list-Endpoint). Die Statustexte werden als data-Attribute übergeben (bereits
	 * hier lokalisiert), damit das JS ohne weitere Übersetzung auskommt.
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute (aktuell keine).
	 * @return string Container-HTML.
	 */
	public function render_archive( $atts ): string {
		unset( $atts );

		return sprintf(
			'<div class="df-favorites-archive" data-empty="%1$s" data-loading="%2$s" data-error="%3$s"><p class="df-favorites-archive__status">%4$s</p></div>',
			esc_attr__( 'Du hast noch keine Beiträge gespeichert.', 'depeur-food' ),
			esc_attr__( 'Favoriten werden geladen …', 'depeur-food' ),
			esc_attr__( 'Die Favoriten konnten nicht geladen werden.', 'depeur-food' ),
			esc_html__( 'Favoriten werden geladen …', 'depeur-food' )
		);
	}
}
