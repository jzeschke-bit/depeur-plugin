<?php
/**
 * Override von Kadence' Single-Content-Template — Autoren-Box + Related Posts + Navigation
 * AUCH für Custom Post Types (nicht nur `post`).
 *
 * DAS PROBLEM: Kadence zeigt Autoren-Box, „Ähnliche Beiträge" und Beitrags-Navigation
 *   standardmäßig NUR für den Post-Type `post` an (harte `'post' === get_post_type()`-Prüfung).
 *   Auf Cocktail-/CPT-Singles fehlen sie dadurch — genau das war im Alt-Theme bewusst gelöst.
 * DIE LÖSUNG (einzige Abweichung ggü. Kadence-Original): Die Post-Type-Prüfung ist zu
 *   `in_array( get_post_type(), $supported_post_types )` erweitert — für ALLE vom Plugin
 *   unterstützten Typen (ADR-4). Voraussetzung: In Kadence sind Autoren-Box (`post_author_box`)
 *   und Related Posts (`post_related`) aktiviert (Customizer → Beiträge). Die Related-Posts-
 *   QUERY wird zusätzlich in functions.php (kadence_related_posts_carousel_args) auf die CPTs
 *   erweitert.
 *
 * WARUM (Theme): Kadence-Template-Part → nur ein Child-Theme kann es über denselben Pfad ersetzen.
 * BRÜCKE Theme→Plugin: Die Liste der unterstützten Typen kommt aus dem Plugin
 *   (depeur_food()->get_supported_post_types()); Fallback auf `post`, wenn das Plugin fehlt.
 *
 * HERKUNFT: Kopie von Kadence' `template-parts/content/single-entry.php`. Bei Kadence-Updates
 *   gegen das Original abgleichen (möglicher Drift).
 *
 * @package KadenceChild\DepeurFood
 */

namespace Kadence;

// Unterstützte Post-Typen (Autoren-Box/Related/Navigation/Tags). Quelle: Plugin (ADR-4),
// Fallback `post`, falls das Plugin nicht aktiv ist.
$supported_post_types = function_exists( 'depeur_food' ) ? depeur_food()->get_supported_post_types() : array( 'post' );

if ( kadence()->show_feature_above() ) {
	get_template_part( 'template-parts/content/entry_thumbnail', get_post_type() );
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry content-bg single-entry' . ( kadence()->option( 'post_footer_area_boxed' ) ? ' post-footer-area-boxed' : '' ) ); ?>>
	<div class="entry-content-wrap">
		<?php
		do_action( 'kadence_single_before_inner_content' );

		if ( kadence()->show_in_content_title() ) {
			get_template_part( 'template-parts/content/entry_header', get_post_type() );
		}
		if ( kadence()->show_feature_below() ) {
			get_template_part( 'template-parts/content/entry_thumbnail', get_post_type() );
		}

		get_template_part( 'template-parts/content/entry_content', get_post_type() );

		// Tags auch für unterstützte CPTs (Original: nur `post`).
		if ( in_array( get_post_type(), $supported_post_types, true ) && kadence()->option( 'post_tags' ) ) {
			get_template_part( 'template-parts/content/entry_footer', get_post_type() );
		}

		do_action( 'kadence_single_after_inner_content' );
		?>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->

<?php
/**
 * Hook for anything after single content.
 */
do_action( 'kadence_single_after_content' );

if ( is_singular( get_post_type() ) ) {
	// Autoren-Box auch für unterstützte CPTs (Original: nur `post`).
	if ( in_array( get_post_type(), $supported_post_types, true ) && kadence()->option( 'post_author_box' ) ) {
		get_template_part( 'template-parts/content/entry_author', get_post_type() );
	}

	// Beitrags-Navigation für unterstützte CPTs (bzw. Typen mit Archiv).
	if ( ( in_array( get_post_type(), $supported_post_types, true ) || get_post_type_object( get_post_type() )->has_archive ) && kadence()->show_post_navigation() ) {
		if ( kadence()->option( 'post_footer_area_boxed' ) ) {
			echo '<div class="post-navigation-wrap content-bg entry-content-wrap entry">';
		}
		the_post_navigation(
			apply_filters(
				'kadence_post_navigation_args',
				array(
					'prev_text' => '<div class="post-navigation-sub"><small>' . kadence()->get_icon( 'arrow-left-alt' ) . esc_html__( 'Previous', 'kadence' ) . '</small></div>%title',
					'next_text' => '<div class="post-navigation-sub"><small>' . esc_html__( 'Next', 'kadence' ) . kadence()->get_icon( 'arrow-right-alt' ) . '</small></div>%title',
				)
			)
		);
		if ( kadence()->option( 'post_footer_area_boxed' ) ) {
			echo '</div>';
		}
	}

	// Related Posts (Karussell) auch für unterstützte CPTs (Original: nur `post`).
	// Die zugehörige QUERY erweitert functions.php via kadence_related_posts_carousel_args.
	if ( in_array( get_post_type(), $supported_post_types, true ) && kadence()->option( 'post_related' ) ) {
		get_template_part( 'template-parts/content/entry_related', get_post_type() );
	}

	// Kommentare (unverändert ggü. Kadence-Original).
	if ( kadence()->show_comments() ) {
		comments_template();
	}
}
