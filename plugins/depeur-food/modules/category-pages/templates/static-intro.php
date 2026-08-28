<?php
/**
 * Plugin-Template: Intro-Seite auf Kategorie-/Autor-Archiv (Seite 1).
 *
 * Wird von Frontend\Static_Intro über `template_include` NUR dann geladen, wenn eine
 * veröffentlichte Intro-Seite zugewiesen ist. Nutzt Header/Footer/Sidebar des aktiven Themes und
 * rendert dazwischen den Seiteninhalt (durch `the_content` gefiltert). Content-Wrapper-Klassen
 * wie im Alt-Theme, damit die Optik passt.
 *
 * @package Depeur\Food\Modules\CategoryPages
 * @license GPL-2.0-or-later
 */

use Depeur\Food\Modules\CategoryPages\Provisioning\Static_Intro_Fields;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Intro-Seite bestimmen (Bedingung ist durch Static_Intro bereits sichergestellt). Prefix
// depeur_food_, weil Template-Variablen im globalen Scope liegen (WPCS PrefixAllGlobals).
$depeur_food_intro_page_id = 0;
if ( is_category() ) {
	$depeur_food_intro_page_id = (int) get_term_meta( get_queried_object_id(), Static_Intro_Fields::CATEGORY_META, true );
} elseif ( is_author() ) {
	$depeur_food_intro_page_id = (int) get_user_meta( get_queried_object_id(), Static_Intro_Fields::AUTHOR_META, true );
}

$depeur_food_intro_page = $depeur_food_intro_page_id ? get_post( $depeur_food_intro_page_id ) : null;

get_header();
?>
<div id="primary" class="content-area">
	<div class="content-container site-container">
		<main id="main" class="site-main" role="main">
			<?php
			if ( $depeur_food_intro_page instanceof WP_Post ) {
				// the_content ist der Core-Ausgabepfad für Post-Content (Shortcodes/Blöcke) und
				// liefert bereits sanitiertes HTML → Ausgabe absichtlich unescaped.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core-Filter the_content, Standard-Ausgabepfad.
				echo apply_filters( 'the_content', $depeur_food_intro_page->post_content );
			} else {
				echo esc_html__( 'Inhalt nicht gefunden.', 'depeur-food' );
			}
			?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php
get_footer();
