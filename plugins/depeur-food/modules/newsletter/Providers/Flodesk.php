<?php
/**
 * Flodesk — rendert das Flodesk-Inline-Newsletter-Formular.
 *
 * Dünne Provider-Naht (E4): EINE Klasse kapselt das Flodesk-spezifische Markup. Bewusst
 * KEINE Provider-Interface/Registry/Factory — die kommt erst mit einem zweiten realen
 * Provider (Disziplin analog cache-bridge). Alle Werte stammen aus Support\Config; jeder
 * dynamische Wert wird beim Ausgeben escaped (esc_attr/esc_url/esc_html/esc_js).
 *
 * Portiert aus spotlight-subscribe.php:1004–1075 (Formular-Markup), CSS-Klassen auf den
 * df_-Frontend-Prefix umgestellt; die inneren `ff-<formid>`-Klassen bleiben unverändert,
 * weil das Flodesk-Universal-Script sie exakt so bindet.
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
 * Baut das HTML des Flodesk-Formulars aus den Modul-Einstellungen.
 *
 * @since 0.2.0
 */
final class Flodesk {

	/**
	 * Rendert das Newsletter-Formular als HTML-String.
	 *
	 * Der Wrapper trägt die Flodesk-Form-ID als data-Attribut; das gebündelte Vanilla-JS
	 * (assets/df-newsletter.js) initialisiert damit `window.fd(...)`, falls das Flodesk-
	 * Universal-Script geladen ist — statt eines Inline-<script> (Asset-Convention).
	 *
	 * @since 0.2.0
	 *
	 * @return string Fertiges, vollständig escaptes Formular-Markup.
	 */
	public function render(): string {
		$form_id     = Config::text( 'flodesk_form_id' );
		$form_action = Config::text( 'flodesk_form_action' );
		$success_url = Config::text( 'newsletter_success_url' );
		$image       = Config::text( 'newsletter_image' );
		$title       = Config::text( 'newsletter_title' );
		$subtitle    = Config::text( 'newsletter_subtitle' );
		$button_text = Config::text( 'newsletter_button_text' );
		$placeholder = Config::text( 'newsletter_placeholder' );

		// Ohne Form-ID kann Flodesk nichts binden → nichts ausgeben (kein halbes Markup).
		if ( '' === $form_id ) {
			return '';
		}

		$ff = 'ff-' . $form_id;

		ob_start();
		?>
		<div class="df-newsletter" data-df-flodesk-form-id="<?php echo esc_attr( $form_id ); ?>">
			<button type="button" class="df-newsletter__close" aria-label="<?php esc_attr_e( 'Newsletter-Formular schließen', 'depeur-food' ); ?>">&times;</button>
			<div class="<?php echo esc_attr( $ff ); ?>" data-ff-el="root" data-ff-version="3" data-ff-type="inline" data-ff-name="inlineImage">
				<div class="<?php echo esc_attr( $ff ); ?>__container">
					<form
						class="<?php echo esc_attr( $ff ); ?>__wrapper"
						action="<?php echo esc_url( $form_action ); ?>"
						method="post"
						data-ff-el="form"
						data-ff-embed="inline"
						data-ff-layout-type="inline"
						data-success-url="<?php echo esc_url( $success_url ); ?>">
						<div class="<?php echo esc_attr( $ff ); ?>__left">
							<div class="<?php echo esc_attr( $ff ); ?>__image">
								<img src="<?php echo esc_url( $image ); ?>" alt="<?php esc_attr_e( 'Newsletter-Anmeldung', 'depeur-food' ); ?>" />
							</div>
						</div>
						<div class="<?php echo esc_attr( $ff ); ?>__right">
							<div class="<?php echo esc_attr( $ff ); ?>__title">
								<div><strong><?php echo esc_html( $title ); ?></strong></div>
							</div>
							<div class="<?php echo esc_attr( $ff ); ?>__subtitle">
								<div><?php echo esc_html( $subtitle ); ?></div>
							</div>
							<div class="<?php echo esc_attr( $ff ); ?>__content fd-form-content" data-ff-el="content">
								<div class="<?php echo esc_attr( $ff ); ?>__fields" data-ff-el="fields">
									<div class="<?php echo esc_attr( $ff ); ?>__field fd-form-group">
										<input
											id="<?php echo esc_attr( $ff ); ?>-email"
											class="<?php echo esc_attr( $ff ); ?>__control fd-form-control"
											type="email"
											maxlength="255"
											name="email"
											placeholder="<?php echo esc_attr( $placeholder ); ?>"
											data-ff-tab="email::submit"
											data-ff-validate="true"
											required />
									</div>
									<?php // Honeypot (Flodesk-Konvention): für Menschen unsichtbar. ?>
									<input type="text" maxlength="255" name="confirm_email_address" style="display: none" tabindex="-1" autocomplete="off" />
								</div>
								<div class="<?php echo esc_attr( $ff ); ?>__footer" data-ff-el="footer">
									<button
										type="submit"
										class="<?php echo esc_attr( $ff ); ?>__button fd-btn kt-btn button kt-btn-size-normal kt-btn-style-primary"
										data-ff-el="submit"
										data-ff-tab="submit">
										<span><?php echo esc_html( $button_text ); ?></span>
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php // Ganzseiten-Grau-Überblendung (fixed, z-index 998): fadet via `.in-view`/Hover ein (CSS). ?>
			<div class="df-newsletter__overlay"></div>
			<?php // Zusätzlicher Scrollraum, damit der Sticky-Effekt des Formulars greift (Legacy). ?>
			<div class="df-newsletter__scroll-space"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
