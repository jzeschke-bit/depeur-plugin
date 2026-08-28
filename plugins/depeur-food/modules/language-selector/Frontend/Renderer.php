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

use Depeur\Food\Core\Settings\SettingsRegistry;

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
	 * Modul-Slug (für den Options-Key der Site-Sprache).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const MODULE_SLUG = 'language-selector';

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
	 * Gibt ein vollständiges hreflang-Cluster im <head> aus.
	 *
	 * Ein gültiges Cluster verlangt, dass JEDE Sprachversion sich selbst mit-referenziert
	 * (Google ignoriert das Cluster sonst). Deshalb wird IMMER eine Selbstreferenz in der
	 * eingestellten Site-Sprache (auf die eigene Canonical-URL) ausgegeben, dazu die
	 * gepflegte(n) Gegenstück-URL(en) aus link_de/link_en und ein x-default auf die deutsche
	 * Version. Ohne echtes Gegenstück (nur Selbstreferenz) wird NICHTS ausgegeben — eine
	 * einsprachige Seite braucht kein hreflang.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_hreflang(): void {
		$self_url = $this->self_url();
		if ( '' === $self_url ) {
			return; // Nur auf singularem Content und Term-Archiven sinnvoll.
		}

		$site_lang = $this->site_language();
		$meta      = $this->current_links();

		// Selbstreferenz: die aktuelle Seite ist in ihrer eigenen Sprache.
		$alternates = array( $site_lang => $self_url );

		// Gegenstück-Sprachen aus den gepflegten Meta-URLs (die eigene Sprache nie überschreiben).
		foreach ( array( 'de', 'en' ) as $lang ) {
			if ( $lang === $site_lang ) {
				continue;
			}
			if ( '' !== $meta[ $lang ] ) {
				$alternates[ $lang ] = $meta[ $lang ];
			}
		}

		// Nur Selbstreferenz ⇒ kein Gegenstück ⇒ hreflang wäre wertlos → nichts ausgeben.
		if ( count( $alternates ) < 2 ) {
			return;
		}

		foreach ( $alternates as $lang => $url ) {
			$this->print_alternate( $lang, $url );
		}

		// x-default → deutsche Version (Haupt-/Einstiegssprache); Fallback auf die aktuelle Seite.
		$x_default = isset( $alternates['de'] ) ? $alternates['de'] : $self_url;
		$this->print_alternate( 'x-default', $x_default );
	}

	/**
	 * Gibt einen einzelnen hreflang-Alternate-Link aus.
	 *
	 * @since 0.3.0
	 *
	 * @param string $lang hreflang-Wert (z. B. de, en, x-default).
	 * @param string $url  Ziel-URL.
	 * @return void
	 */
	private function print_alternate( string $lang, string $url ): void {
		printf(
			'<link rel="alternate" hreflang="%1$s" href="%2$s">' . "\n",
			esc_attr( $lang ),
			esc_url( $url )
		);
	}

	/**
	 * Canonical-URL des aktuellen Objekts (Selbstreferenz-Ziel).
	 *
	 * @since 0.3.0
	 *
	 * @return string Permalink (Single) bzw. Term-Link (Archiv), sonst '' (kein hreflang).
	 */
	private function self_url(): string {
		if ( is_singular() ) {
			$post_id = (int) get_queried_object_id();
			return $post_id > 0 ? (string) get_permalink( $post_id ) : '';
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$link = get_term_link( $term );
				return is_string( $link ) ? $link : '';
			}
		}
		return '';
	}

	/**
	 * Eingestellte Sprache dieser Website ('de'|'en', Default 'de', filterbar).
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private function site_language(): string {
		$options = get_option( SettingsRegistry::option_key( self::MODULE_SLUG ), array() );
		$lang    = ( is_array( $options ) && isset( $options['site_language'] ) ) ? (string) $options['site_language'] : 'de';
		$lang    = in_array( $lang, array( 'de', 'en' ), true ) ? $lang : 'de';

		/**
		 * Überschreibt die Site-Sprache für die hreflang-Selbstreferenz.
		 *
		 * @since 0.3.0
		 *
		 * @param string $lang 'de' oder 'en'.
		 */
		$lang = (string) apply_filters( 'depeur_food/language_selector/site_language', $lang );

		return in_array( $lang, array( 'de', 'en' ), true ) ? $lang : 'de';
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
