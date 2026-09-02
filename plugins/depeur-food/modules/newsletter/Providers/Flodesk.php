<?php
/**
 * Flodesk — baut das Newsletter-Formular-Markup (zwei Wege, je nach Darstellungs-Modus).
 *
 * 1) render() — EIGENES DESIGN (Modi spotlight/minimal/popup): unser eigenes Formular-Markup
 *    (Titel/Bild/Button aus Support\Config). Es sendet NICHT an das Flodesk-Widget, sondern an
 *    unseren REST-Endpoint (Rest\Subscribe_Controller), der serverseitig über die Flodesk-API
 *    einträgt. Dadurch entfällt Flodesks „I am not a robot"-Captcha; Spam-Schutz via Nonce +
 *    Honeypot + Rate-Limit. Styling: generische df-newsletter__*-Klassen (form-id-unabhängig).
 *
 * 2) embed_container() — NATIVES FLODESK-EMBED (Modus flodesk_inline): leerer Container, den
 *    Flodesks Universal-Script selbst füllt (window.fd('form', { formId, containerEl })).
 *
 * Dünne Provider-Naht (E4): EINE Klasse kapselt beides. Jeder dynamische Wert wird beim
 * Ausgeben escaped (esc_attr/esc_url/esc_html).
 *
 * @package Depeur\Food\Modules\Newsletter\Providers
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Providers;

use Depeur\Food\Modules\Newsletter\Support\Config;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut das HTML des Newsletter-Formulars aus den Modul-Einstellungen.
 *
 * @since 0.2.0
 */
final class Flodesk {

	/**
	 * Rendert das EIGENE Newsletter-Formular (Modi spotlight/minimal/popup) als HTML-String.
	 *
	 * Das Formular sendet per JS (assets/df-newsletter.js) an den Plugin-REST-Endpoint —
	 * NICHT an Flodesks Widget. Kein Flodesk-Universal-Script, kein Captcha.
	 *
	 * @since 0.2.0
	 *
	 * @param string $mode Darstellungs-Variante:
	 *                     - 'spotlight' (Standard): inline, sticky, mit Vollbild-Abdunklung.
	 *                     - 'minimal': inline + sticky wie spotlight, aber OHNE Abdunklung.
	 *                     - 'popup': fixed, auf Desktop mittig, per JS erst ab Scroll-Tiefe.
	 *                     Unbekannte Werte fallen auf 'spotlight' zurück.
	 * @return string Fertiges, vollständig escaptes Formular-Markup.
	 */
	public function render( string $mode = 'spotlight' ): string {
		$image       = Config::text( 'newsletter_image' );
		$title       = Config::text( 'newsletter_title' );
		$subtitle    = Config::text( 'newsletter_subtitle' );
		$button_text = Config::text( 'newsletter_button_text' );
		$placeholder = Config::text( 'newsletter_placeholder' );

		// Nur bekannte Varianten; alles andere = Standard (Spotlight).
		$mode = in_array( $mode, array( 'spotlight', 'minimal', 'popup' ), true ) ? $mode : 'spotlight';

		// Overlay (Vollbild-Abdunklung) nur im Spotlight. Sticky-Scrollraum in den beiden
		// INLINE-Varianten (spotlight, minimal); der Popup ist fixed und braucht ihn nicht.
		$render_overlay      = ( 'spotlight' === $mode );
		$render_scroll_space = ( 'popup' !== $mode );

		ob_start();
		?>
		<div class="df-newsletter df-newsletter--<?php echo esc_attr( $mode ); ?>" data-df-mode="<?php echo esc_attr( $mode ); ?>">
			<button type="button" class="df-newsletter__close" aria-label="<?php esc_attr_e( 'Newsletter-Formular schließen', 'depeur-food' ); ?>">&times;</button>
			<div class="df-newsletter__root">
				<div class="df-newsletter__container">
					<div class="df-newsletter__wrapper">
						<?php if ( '' !== $image ) : ?>
							<div class="df-newsletter__col-left">
								<div class="df-newsletter__image">
									<img src="<?php echo esc_url( $image ); ?>" alt="<?php esc_attr_e( 'Newsletter-Anmeldung', 'depeur-food' ); ?>" />
								</div>
							</div>
						<?php endif; ?>
						<div class="df-newsletter__col-right">
							<?php if ( '' !== $title ) : ?>
								<div class="df-newsletter__title"><strong><?php echo esc_html( $title ); ?></strong></div>
							<?php endif; ?>
							<?php if ( '' !== $subtitle ) : ?>
								<div class="df-newsletter__subtitle"><?php echo esc_html( $subtitle ); ?></div>
							<?php endif; ?>
							<form class="df-newsletter__form" method="post">
								<div class="df-newsletter__field">
									<input
										type="email"
										class="df-newsletter__control"
										name="email"
										placeholder="<?php echo esc_attr( $placeholder ); ?>"
										autocomplete="email"
										required />
								</div>
								<?php // Honeypot: per CSS unsichtbar; von Bots gefüllt → serverseitig verworfen. ?>
								<input type="text" class="df-newsletter__hp" name="df_hp" tabindex="-1" autocomplete="off" aria-hidden="true" />
								<div class="df-newsletter__footer">
									<button type="submit" class="df-newsletter__button kt-btn button kt-btn-size-normal kt-btn-style-primary">
										<span><?php echo esc_html( $button_text ); ?></span>
									</button>
								</div>
								<div class="df-newsletter__message" role="status" aria-live="polite"></div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<?php if ( $render_overlay ) : ?>
				<div class="df-newsletter__overlay"></div>
			<?php endif; ?>
			<?php if ( $render_scroll_space ) : ?>
				<div class="df-newsletter__scroll-space"></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Leerer Container für das offizielle Flodesk-Inline-Embed (Modus 'flodesk_inline').
	 *
	 * Flodesk rendert die in Flodesk gestaltete Form komplett selbst hinein
	 * (`window.fd('form', { formId, containerEl })`, siehe Frontend\Assets) — inklusive Design,
	 * config-Block und Anti-Bot-Token. Deshalb KEIN eigenes Markup/CSS und damit kein Captcha.
	 *
	 * @since 0.3.0
	 *
	 * @return string Container-`<div>` oder '' ohne konfigurierte Form-ID.
	 */
	public function embed_container(): string {
		$form_id = Config::text( 'flodesk_form_id' );
		if ( '' === $form_id ) {
			return '';
		}

		return sprintf(
			'<div id="fd-form-%1$s" class="df-newsletter-embed"></div>',
			esc_attr( $form_id )
		);
	}
}
