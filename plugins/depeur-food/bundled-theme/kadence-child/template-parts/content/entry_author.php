<?php
/**
 * Override von Kadence' Autoren-Box — ergänzt die Jobtitel-/Berufszeile aus User-Meta.
 *
 * WOFÜR: Unter Single-Beiträgen zeigt Kadence eine Autoren-Box (Avatar, Name, Bio, Social).
 *   Diese Version fügt EINE Zeile hinzu: den Jobtitel des Autors (z. B. „Barkeeper") unter dem
 *   Namen — passend zur CSS-Regel `.entry-author-occupation` im style.css dieses Themes.
 * WARUM (Theme): Es ist Präsentation. Kadence rendert die Box über ein Template-Part; nur ein
 *   Child-Theme kann sie über denselben Pfad ersetzen. Die INHALTE (Autor-Meta) reichert das
 *   Plugin an (meta-registry/schema-engine), die sichtbare Zeile setzt aber das Theme.
 * BRÜCKE Theme→Plugin: Der Jobtitel kommt aus dem User-Meta `author_jobtitle`. Bewusst über
 *   `get_the_author_meta()` gelesen (Core-Weg, ADR-5) — NICHT über ACFs `get_field()`, damit die
 *   Box auch ohne ACF-Runtime funktioniert. Fehlt der Jobtitel, wird die Zeile einfach weggelassen.
 *
 * HERKUNFT: Kopie von Kadence' `template-parts/content/entry_author.php`. Einzige Ergänzung
 *   gegenüber dem Original: der mit „<<< ERGÄNZUNG" markierte Jobtitel-Block. Rest = Kadence-
 *   Struktur (damit Avatar/Bio/Social unverändert bleiben).
 * PRÜFEN:
 *   - Greift nur, wenn in Kadence der Autoren-Box-Stil aktiv ist (Customizer → Beiträge → Autor).
 *   - Bei einem Kadence-Update das Original gegen diese Datei abgleichen (möglicher Drift).
 *
 * @package KadenceChild\DepeurFood
 */

namespace Kadence;

use function Kadence\kadence;
use function get_the_author;
use function get_avatar;
use function get_the_author_meta;
use function the_author_meta;
use function get_the_author_posts_link;

// Kadence-eigene Author-Box-Styles einbinden (wie im Original).
kadence()->print_styles( 'kadence-author-box' );

// Autor-ID einmal ermitteln (für Avatar + Jobtitel-Lookup).
$author_id = get_the_author_meta( 'ID' );
?>
<div class="entry-author entry-author-style-<?php echo esc_attr( kadence()->option( 'post_author_box_style' ) ); ?><?php echo ( kadence()->option( 'post_footer_area_boxed' ) ? ' content-bg entry-content-wrap entry' : '' ); ?>">
	<div class="entry-author-profile author-profile vcard">
		<div class="entry-author-avatar">
			<?php echo get_avatar( $author_id, 80 ); ?>
		</div>
		<b class="entry-author-name author-name fn"><?php echo wp_kses_post( kadence()->option( 'post_author_box_link' ) ? get_the_author_posts_link() : get_the_author() ); ?></b>

		<?php
		// <<< ERGÄNZUNG ggü. Kadence-Original: Jobtitel-Zeile aus dem User-Meta `author_jobtitle`.
		// Core-Weg (kein ACF-Runtime, ADR-5). Fehlt der Wert → keine Zeile.
		$author_jobtitle = (string) get_the_author_meta( 'author_jobtitle', $author_id );
		if ( '' !== $author_jobtitle ) :
			?>
			<p class="entry-author-occupation author-occupation"><?php echo esc_html( $author_jobtitle ); ?></p>
			<?php
		endif;
		?>

		<div class="entry-author-description author-bio">
			<?php the_author_meta( 'description' ); ?>
		</div>
		<div class="entry-author-follow author-follow">
			<?php
			// Social-Links wie im Kadence-Original: nur ausgeben, was der Autor gepflegt hat.
			foreach ( array( 'facebook', 'twitter', 'instagram', 'youtube', 'flickr', 'vimeo', 'linkedin', 'pinterest', 'dribbble', 'amazon', 'medium', 'goodreads', 'bookbub' ) as $social ) {
				if ( get_the_author_meta( $social ) ) {
					?>
					<a href="<?php echo esc_url( get_the_author_meta( $social ) ); ?>" class="<?php echo esc_attr( $social ); ?>-link social-button" target="_blank" rel="noopener" title="<?php /* translators: 1: Author Name, 2: Social Media Name */ echo sprintf( esc_attr__( 'Follow %1$s on %2$s', 'kadence' ), esc_attr( get_the_author_meta( 'display_name' ) ), esc_attr( ucfirst( $social ) ) ); ?>">
						<?php kadence()->print_icon( $social, '', false ); ?>
					</a>
					<?php
				}
			}
			?>
		</div><!-- .author-follow -->
	</div>
</div><!-- .entry-author -->
