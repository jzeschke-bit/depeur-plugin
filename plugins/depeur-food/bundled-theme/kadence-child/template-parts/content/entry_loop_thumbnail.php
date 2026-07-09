<?php
/**
 * Override von Kadence' Loop-Beitragsbild — fügt den Favoriten-Herz-Button über dem Bild ein.
 *
 * WOFÜR: In Archiv-/Loop-Karten (Blog, Cocktails, Kategorie-Seiten, „Was koche ich heute")
 *   soll oben links auf dem Beitragsbild ein Herz-Button sitzen (Merkliste + Like-Zähler).
 * WARUM (Theme): Kadence lädt Template-Parts über `get_template_part()`; ein Child-Theme
 *   ersetzt sie, indem es die Datei unter GLEICHEM Pfad bereitstellt. Das Plugin kann ein
 *   Kadence-Template-Part NICHT ersetzen, ohne Kadence-Interna zu kennen — deshalb Theme.
 * BRÜCKE Theme→Plugin: Der Button selbst kommt aus dem Plugin-Modul favorites über den
 *   (Legacy-kompatiblen) Shortcode `[thumbnail_favorite_button]`. Guard via
 *   shortcode_exists(): ist favorites nicht aktiv, erscheint einfach kein Herz.
 *
 * HERKUNFT: Diese Datei ist eine 1:1-Kopie von Kadence'
 *   `template-parts/content/entry_loop_thumbnail.php` — mit GENAU EINER Ergänzung:
 *   der Zeile `do_shortcode('[thumbnail_favorite_button]')` im verlinkten Bild-Zweig
 *   (siehe Kommentar „<<< EINZIGE ÄNDERUNG"). Der Rest ist Kadence-Original, damit
 *   Ratio/Größe/Alt-Text-Verhalten unverändert bleiben.
 * PRÜFEN:
 *   - Bei einem Kadence-Update das Original mit dieser Datei abgleichen (Drift möglich).
 *   - Das Herz erscheint nur im „verlinkten Bild"-Zweig (imageLink=true, der Normalfall).
 *     Im Nicht-verlinkten Zweig bewusst nicht (dort ist kein <a>-Kontext für den Button).
 *
 * @package KadenceChild\DepeurFood
 */

namespace Kadence;

// Kein Bild rendern, wenn passwortgeschützt, Typ ohne Thumbnail-Support oder ohne Bild.
if ( post_password_required() || ! post_type_supports( get_post_type(), 'thumbnail' ) || ! has_post_thumbnail() ) {
	return;
}

$defaults = array(
	'enabled'   => true,
	'ratio'     => '2-3',
	'size'      => 'medium_large',
	'imageLink' => true,
);

// Kadence-Option je Post-Type (bzw. „search" in Suchergebnissen).
$slug            = ( is_search() ? 'search' : get_post_type() );
$feature_element = kadence()->option( $slug . '_archive_element_feature', $defaults );

if ( isset( $feature_element ) && is_array( $feature_element ) && true === $feature_element['enabled'] ) {
	$feature_element = wp_parse_args( $feature_element, $defaults );
	$ratio           = ( isset( $feature_element['ratio'] ) && ! empty( $feature_element['ratio'] ) ? $feature_element['ratio'] : '2-3' );
	$size            = ( isset( $feature_element['size'] ) && ! empty( $feature_element['size'] ) ? $feature_element['size'] : 'medium_large' );
	$thumbnail_id    = get_post_thumbnail_id();
	$alt             = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );

	if ( isset( $feature_element['imageLink'] ) && ! $feature_element['imageLink'] ) {
		// Zweig OHNE Link ums Bild (Kadence-Original, unverändert, kein Herz).
		?>
		<div class="post-thumbnail kadence-thumbnail-ratio-<?php echo esc_attr( $ratio ); ?>">
			<div class="post-thumbnail-inner">
				<?php
				the_post_thumbnail(
					$size,
					array(
						'alt' => ! empty( $alt ) ? $alt : the_title_attribute( array( 'echo' => false ) ),
					)
				);
				?>
			</div>
		</div><!-- .post-thumbnail -->
		<?php
	} else {
		// Zweig MIT Link ums Bild (Normalfall) — hier sitzt der Herz-Button.
		?>
		<a class="post-thumbnail kadence-thumbnail-ratio-<?php echo esc_attr( $ratio ); ?>" href="<?php the_permalink(); ?>">
			<?php
			// <<< EINZIGE ÄNDERUNG ggü. Kadence-Original: Favoriten-Herz aus dem Plugin.
			// Guard: nur ausgeben, wenn das favorites-Modul den Shortcode bereitstellt.
			if ( shortcode_exists( 'thumbnail_favorite_button' ) ) {
				echo do_shortcode( '[thumbnail_favorite_button]' );
			}
			?>
			<div class="post-thumbnail-inner">
				<?php
				the_post_thumbnail(
					$size,
					array(
						'alt' => ! empty( $alt ) ? $alt : the_title_attribute( array( 'echo' => false ) ),
					)
				);
				?>
			</div>
		</a><!-- .post-thumbnail -->
		<?php
	}
}
