<?php
/**
 * Kadence Child (Depeur Food) — functions.php
 *
 * BEWUSST MINIMAL. Das Alt-Theme „Alkipedia 3.0" hatte hier 928 Zeilen (Query-Engine,
 * Tag-Gruppen, REST-Filter, hreflang, Favoriten, Pagination-Rewrites). ALL DAS ist ins
 * Plugin depeur-food umgezogen. Hier bleibt nur echte Theme-KOPPLUNG an Kadence, die
 * ein post-type-/theme-agnostisches Plugin bewusst NICHT übernehmen soll:
 *
 *   1) Child-CSS laden (style.css dieses Ordners).
 *   2) Kadence „Ähnliche Beiträge" auf die Plugin-Post-Types erweitern (Kadence-Filter).
 *   3) Den Sprachumschalter des Plugins im Kadence-Footer PLATZIEREN (Shortcode kommt
 *      vom Plugin, die Kadence-spezifische Platzierung ist Theme-Sache).
 *
 * Jede Funktion hat einen „WOFÜR / WARUM (Theme statt Plugin) / PRÜFEN"-Block, damit
 * kritisch nachvollziehbar ist, warum sie hier und nicht im Plugin lebt.
 *
 * @package KadenceChild\DepeurFood
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* ============================================================================
   1) CHILD-STYLESHEET LADEN
   ============================================================================ */

/**
 * Lädt die style.css dieses Child-Themes.
 *
 * WOFÜR: Bindet unser Präsentations-CSS (Tag-Pillen, Author-Box, Rundungen …) ein.
 * WARUM (Theme): CSS ist per Definition Theme-Sache; Kadence lädt Child-CSS nicht
 *   automatisch mit Cache-Busting.
 * PRÜFEN:
 *   - Priorität 100 = spät, damit unsere Regeln nach Kadence gewinnen (wie im Alt-Theme).
 *   - Version = filemtime(): Cache-Buster bei jeder CSS-Änderung. Auf Live mit RunCache/
 *     Cloudflare trotzdem Seiten-Cache leeren, da der <link> in gecachtem HTML steckt.
 *   - Dependency-Handle bewusst leer (false): Kadence bündelt sein CSS teils inline; ein
 *     festes Parent-Handle als Abhängigkeit ist versionsabhängig unzuverlässig. Späte
 *     Priorität reicht, damit wir nach dem Parent laden.
 */
function depeur_child_enqueue_styles(): void {
	$style_path = get_stylesheet_directory() . '/style.css';
	$version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : '0.1.0';

	wp_enqueue_style(
		'depeur-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'depeur_child_enqueue_styles', 100 );


/* ============================================================================
   2) KADENCE „ÄHNLICHE BEITRÄGE" AUF PLUGIN-POST-TYPES ERWEITERN
   ============================================================================ */

/**
 * Erweitert Kadence' Related-Posts-Karussell um alle vom Plugin unterstützten Post-Types.
 *
 * WOFÜR: Unter Single-Beiträgen (auch Cocktails etc.) sollen „Ähnliche Beiträge" aus
 *   ALLEN unterstützten Typen kommen — mit Tag-Treffern zuerst, sonst Zufalls-Fallback.
 * WARUM (Theme): Der Hook `kadence_related_posts_carousel_args` ist Kadence-SPEZIFISCH.
 *   Ein theme-agnostisches Plugin darf sich nicht an einen bestimmten Theme-Hook binden
 *   (Plugin-Split-Regel). Die QUELLE der Post-Types kommt aber sauber aus dem Plugin —
 *   dieses Theme spiegelt sie nur an Kadence.
 * BRÜCKE Theme→Plugin: `depeur_food()->get_supported_post_types()` ist die eine Quelle
 *   der Wahrheit (ADR-4). Fallback auf array('post'), falls das Plugin (noch) nicht aktiv
 *   ist — dann verhält sich Kadence wie im Standard.
 * PRÜFEN:
 *   - Fallback-Schwelle „< 3 Treffer → Zufall" ist aus dem Alt-Theme übernommen.
 *   - posts_per_page wird im Fallback auf min. 6 angehoben, damit das Karussell füllt.
 *
 * @param array $args WP_Query-Argumente, die Kadence fürs Karussell nutzt.
 * @return array Gefilterte Argumente.
 */
function depeur_child_related_posts_for_cpt( array $args ): array {
	// Quelle der Wahrheit: Plugin. Fallback auf 'post', wenn Plugin nicht aktiv.
	if ( function_exists( 'depeur_food' ) ) {
		$post_types = depeur_food()->get_supported_post_types();
	} else {
		$post_types = array( 'post' );
	}

	$args['post_type'] = $post_types;

	// Tags des aktuellen Beitrags einsammeln, um thematisch Ähnliches zu finden.
	$current_tags = wp_get_post_tags( get_the_ID() );

	if ( ! empty( $current_tags ) ) {
		$tag_ids = wp_list_pluck( $current_tags, 'term_id' );
		$args['tag__in'] = $tag_ids;

		// Zu wenige Tag-Treffer? → auf Zufall zurückfallen, damit das Karussell nicht leer wirkt.
		$probe = new WP_Query( $args );
		if ( $probe->post_count < 3 ) {
			unset( $args['tag__in'] );
			$args['orderby']        = 'rand';
			$args['posts_per_page'] = max( 6, (int) ( $args['posts_per_page'] ?? 6 ) );
		}
	} else {
		// Gar keine Tags → Zufalls-Auswahl.
		$args['orderby'] = 'rand';
	}

	return $args;
}
add_filter( 'kadence_related_posts_carousel_args', 'depeur_child_related_posts_for_cpt' );


/* ============================================================================
   3) SPRACHUMSCHALTER DES PLUGINS IM FOOTER PLATZIEREN
   ============================================================================ */

/**
 * Gibt den Plugin-Sprachumschalter im Kadence-Footer aus.
 *
 * WOFÜR: Der de/com-Sprachumschalter (Cross-Domain-hreflang-Partner) soll im Footer
 *   erscheinen — wie im Alt-Theme über `kadence_footer_navigation`.
 * WARUM (Theme): Der Shortcode `[df_language_switcher]` und die hreflang-Tags im <head>
 *   kommen aus dem Plugin-Modul language-selector (theme-agnostisch). Die PLATZIERUNG an
 *   einem Kadence-Footer-Hook ist dagegen Kadence-spezifisch und gehört ins Theme.
 * PRÜFEN:
 *   - shortcode_exists()-Guard: gibt nichts aus, wenn das Modul language-selector nicht
 *     aktiv ist → keine leere Ausgabe / kein „[df_language_switcher]"-Text im Footer.
 *   - Falls der Umschalter woanders erscheinen soll (z. B. in der Sidebar), hier den
 *     Hook tauschen — Rest bleibt Plugin.
 */
function depeur_child_footer_language_switcher(): void {
	if ( shortcode_exists( 'df_language_switcher' ) ) {
		echo do_shortcode( '[df_language_switcher]' );
	}
}
add_action( 'kadence_footer_navigation', 'depeur_child_footer_language_switcher', 100 );
