<?php
/**
 * Static_Intro — rendert die zugewiesene „Seite 1" auf Kategorie-/Autor-Archiven (nur Seite 1).
 *
 * Ersetzt das Alt-Theme-Verhalten (`template-parts/content/archive.php` + `custom_css_for_
 * static_page`): ist einer Kategorie (`static_page`) oder einem Autor (`static_page_for_author`)
 * eine Seite zugewiesen, zeigt das Archiv auf Seite 1 den Inhalt DIESER Seite statt der
 * Beitragsliste; ab Seite 2 wieder die normale Liste. Im schlanken Child-Theme fehlte dieser
 * Konsument — das Feld war wählbar, aber ohne Wirkung.
 *
 * Umsetzung theme-portabel per `template_include`: nur wenn die Bedingung zutrifft, wird ein
 * Plugin-Template geladen (das get_header()/get_footer() des aktiven Themes nutzt). Sonst bleibt
 * das normale Archiv unangetastet. Kein Kadence-Template-Override.
 *
 * @package Depeur\Food\Modules\CategoryPages\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Frontend;

use Depeur\Food\Modules\CategoryPages\Provisioning\Static_Intro_Fields;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schaltet auf Archiv-Seite 1 das Intro-Seiten-Template ein.
 *
 * @since 0.3.0
 */
final class Static_Intro {

	/**
	 * Verdrahtet Template-Override + Intro-CSS.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Späte Priorität: nach der Template-Wahl des Themes greifen und gewinnen.
		add_filter( 'template_include', array( $this, 'maybe_intro_template' ), 99 );
		add_action( 'wp_head', array( $this, 'intro_css' ) );
	}

	/**
	 * Ermittelt die zugewiesene Intro-Seiten-ID für das aktuelle Archiv (0 = keine).
	 *
	 * Greift NUR auf Seite 1 eines Kategorie- oder Autor-Archivs (Haupt-Query) und nur, wenn die
	 * Zielseite existiert + veröffentlicht ist.
	 *
	 * @since 0.3.0
	 *
	 * @return int Seiten-ID oder 0.
	 */
	public function intro_page_id(): int {
		if ( is_admin() || ! is_main_query() ) {
			return 0;
		}
		// Nur Seite 1 (Folgeseiten zeigen die normale Beitragsliste).
		if ( max( 1, (int) get_query_var( 'paged' ) ) > 1 ) {
			return 0;
		}

		$page_id = 0;
		if ( is_category() ) {
			$page_id = (int) get_term_meta( get_queried_object_id(), Static_Intro_Fields::CATEGORY_META, true );
		} elseif ( is_author() ) {
			$page_id = (int) get_user_meta( get_queried_object_id(), Static_Intro_Fields::AUTHOR_META, true );
		}

		if ( $page_id <= 0 ) {
			return 0;
		}

		// Zielseite muss existieren + veröffentlicht sein.
		return ( 'publish' === get_post_status( $page_id ) ) ? $page_id : 0;
	}

	/**
	 * Lädt das Intro-Template, wenn eine Intro-Seite greift; sonst das normale Template.
	 *
	 * @since 0.3.0
	 *
	 * @param string $template Vom Theme gewähltes Template.
	 * @return string
	 */
	public function maybe_intro_template( $template ): string {
		if ( $this->intro_page_id() > 0 ) {
			return dirname( __DIR__ ) . '/templates/static-intro.php';
		}

		return (string) $template;
	}

	/**
	 * Kleines Intro-CSS (oberen Abstand des Content-Bereichs entfernen, wie im Alt-Theme).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function intro_css(): void {
		if ( $this->intro_page_id() <= 0 ) {
			return;
		}

		echo '<style>.content-area{margin-top:0!important;}</style>';
	}
}
