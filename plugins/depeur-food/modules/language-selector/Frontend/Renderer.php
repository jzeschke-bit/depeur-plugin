<?php
/**
 * Renderer — hreflang-Tags im <head> + Sprachumschalter-Shortcode.
 *
 * Portiert die Theme-Funktionen LanguageLink()/lang_tag(), aber ACF-Runtime-frei: liest die
 * URLs via get_post_meta/get_term_meta (der `link`-Feldtyp speichert ein {title,url,target}-
 * Array; wir extrahieren die URL). Post-type-agnostisch (Single = Post-Meta, Term-Archive =
 * Term-Meta). Kein jQuery, kein Enqueue nötig — reines serverseitiges Markup.
 *
 * @package Depeur\Food\Modules\LanguageSelector\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\LanguageSelector\Frontend;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gibt hreflang + Umschalter aus.
 *
 * @since 0.2.0
 */
final class Renderer {

	/**
	 * Verdrahtet wp_head + Shortcode.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'render_hreflang' ) );
		add_shortcode( 'df_language_switcher', array( $this, 'render_switcher_shortcode' ) );
	}

	/**
	 * Gibt die hreflang-Alternate-Links im <head> aus (nur wenn URLs vorhanden).
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_hreflang(): void {
		$links = $this->current_links();

		foreach ( $links as $lang => $url ) {
			if ( '' === $url ) {
				continue;
			}
			printf(
				'<link rel="alternate" hreflang="%1$s" href="%2$s">' . "\n",
				esc_attr( $lang ),
				esc_url( $url )
			);
		}
	}

	/**
	 * Shortcode `[df_language_switcher]` — rendert den Sprachumschalter (theme-agnostisch).
	 *
	 * @since 0.2.0
	 *
	 * @param array|string $atts Shortcode-Attribute (ungenutzt).
	 * @return string
	 */
	public function render_switcher_shortcode( $atts = array() ): string {
		unset( $atts );

		$links = $this->current_links();
		if ( '' === $links['de'] && '' === $links['en'] ) {
			return '';
		}

		ob_start();
		?>
		<div class="df_language_switcher">
			<span class="df_language_switcher__label"><?php esc_html_e( 'Sprache', 'depeur-food' ); ?></span>
			<span class="df_language_switcher__links">
				<?php if ( '' !== $links['de'] ) : ?>
					<a lang="de" hreflang="de" href="<?php echo esc_url( $links['de'] ); ?>" role="option" data-value="German"><?php esc_html_e( 'Deutsch', 'depeur-food' ); ?></a>
				<?php endif; ?>
				<?php
				if ( '' !== $links['de'] && '' !== $links['en'] ) {
					echo ' | ';
				}
				?>
				<?php if ( '' !== $links['en'] ) : ?>
					<a lang="en" hreflang="en" href="<?php echo esc_url( $links['en'] ); ?>" role="option" data-value="English"><?php esc_html_e( 'English', 'depeur-food' ); ?></a>
				<?php endif; ?>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Ermittelt die DE/EN-Ziel-URLs für das aktuelle Objekt (Single = Post, Archiv = Term).
	 *
	 * @since 0.2.0
	 *
	 * @return array{de:string,en:string}
	 */
	private function current_links(): array {
		$de = '';
		$en = '';

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id > 0 ) {
				$de = $this->extract_url( get_post_meta( $post_id, 'link_de', true ) );
				$en = $this->extract_url( get_post_meta( $post_id, 'link_en', true ) );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$de = $this->extract_url( get_term_meta( $term->term_id, 'link_de', true ) );
				$en = $this->extract_url( get_term_meta( $term->term_id, 'link_en', true ) );
			}
		}

		return array(
			'de' => $de,
			'en' => $en,
		);
	}

	/**
	 * Extrahiert die URL aus einem link-Meta-Wert (Array {title,url,target} oder String).
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $value Meta-Wert.
	 * @return string
	 */
	private function extract_url( $value ): string {
		if ( is_array( $value ) ) {
			return isset( $value['url'] ) ? (string) $value['url'] : '';
		}
		return is_string( $value ) ? $value : '';
	}
}
