<?php
/**
 * App_Promo — rendert den App-Promotion-Block.
 *
 * Zweites, optionales Inhalts-Element (neben dem Newsletter-Formular). Zustandslos, liest
 * ausschließlich Support\Config. Portiert aus spotlight-subscribe.php:1084–1097, CSS-Klassen
 * auf den df_-Frontend-Prefix umgestellt. Alle dynamischen Werte werden escaped.
 *
 * @package Depeur\Food\Modules\Newsletter\Frontend
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Frontend;

use Depeur\Food\Modules\Newsletter\Support\Config;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut das HTML des App-Promotion-Blocks aus den Modul-Einstellungen.
 *
 * @since 0.2.0
 */
final class App_Promo {

	/**
	 * Rendert den App-Promotion-Block als HTML-String.
	 *
	 * @since 0.2.0
	 *
	 * @return string Fertiges, vollständig escaptes Markup (leer, wenn keine Ziel-URL gesetzt ist).
	 */
	public function render(): string {
		$title       = Config::text( 'app_promo_title' );
		$subtitle    = Config::text( 'app_promo_subtitle' );
		$button_text = Config::text( 'app_promo_button_text' );
		$button_url  = Config::text( 'app_promo_button_url' );
		$image       = Config::text( 'app_promo_image' );

		// Ohne Ziel-URL ist der Block sinnlos → nichts ausgeben.
		if ( '' === $button_url ) {
			return '';
		}

		ob_start();
		?>
		<div class="df-app-promo">
			<div class="df-app-promo__container">
				<?php if ( '' !== $image ) : ?>
					<div class="df-app-promo__icon">
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
					</div>
				<?php endif; ?>
				<div class="df-app-promo__content">
					<b><?php echo esc_html( $title ); ?></b>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</div>
				<div class="df-app-promo__cta">
					<a href="<?php echo esc_url( $button_url ); ?>" class="df-app-promo__button"><?php echo esc_html( $button_text ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
