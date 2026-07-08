<?php
/**
 * Config — zentraler Zugriff auf die globalen Newsletter-Einstellungen.
 *
 * Einzige Quelle der Wahrheit für die Default-Werte (aus dem Legacy `spotlight_settings`
 * portiert) UND der Merge-Layer über die gespeicherte Modul-Option `depeur_food_newsletter`
 * (ADR-1 Multi-Option). Admin/Settings baut sein Feld-Schema aus diesen Defaults; die
 * Frontend-Komponenten (Flodesk, App_Promo, Content_Inserter) lesen die effektiven Werte
 * hierüber — so leben die Defaults an EINER Stelle statt doppelt in Admin und Frontend.
 *
 * @package Depeur\Food\Modules\Newsletter\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Support;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liest/merged die Newsletter-Modul-Optionen und liefert typsichere Accessoren.
 *
 * @since 0.2.0
 */
final class Config {

	/**
	 * Modul-Slug (= Ordnername). Bildet den Options-Key-Suffix `depeur_food_{slug}` (ADR-1).
	 * Muss mit dem Verzeichnisnamen modules/newsletter/ übereinstimmen — bewusst als Konstante
	 * gehalten, weil die zustandslosen Frontend-Accessoren keinen Slug hereingereicht bekommen.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const SLUG = 'newsletter';

	/**
	 * Liefert den vollständigen Options-Key (ADR-1: depeur_food_newsletter).
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	public static function option_key(): string {
		return SettingsRegistry::option_key( self::SLUG );
	}

	/**
	 * Default-Werte für alle globalen Einstellungen (aus dem Legacy `spotlight_settings`).
	 *
	 * Positionen sind Strings, weil sie über Text-Felder gespeichert werden; die Accessoren
	 * casten bei Bedarf. URLs zeigen bewusst auf die Live-Produktiv-Assets (wie im Legacy) —
	 * pro Site im Settings-Tab überschreibbar.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			// Newsletter (Flodesk).
			'newsletter_enabled'         => true,
			'flodesk_form_id'            => '68319b10b61ee160f25775e2',
			'flodesk_form_action'        => 'https://form.flodesk.com/forms/68319b10b61ee160f25775e2/submit',
			'newsletter_success_url'     => 'https://alkipedia.com/newsletter-danke/?subscribed=true',
			'newsletter_image'           => 'https://alkipedia.com/wp-content/uploads/Newsletter-Slide-In.jpg',
			'newsletter_title'           => 'Inspiration direkt in deinem Postfach!',
			'newsletter_subtitle'        => 'Erhalte kostenlos jede Woche neue Rezepte, die wirklich schmecken. Direkt in deinem Postfach. Einfach, gesund und perfekt für deinen Alltag.',
			'newsletter_button_text'     => 'Jetzt kostenlos anmelden',
			'newsletter_placeholder'     => 'Deine E-Mail Adresse',
			'newsletter_position'        => '4',
			'newsletter_show_on_mobile'  => true,
			'newsletter_show_on_desktop' => true,

			// App-Promotion.
			'app_promo_enabled'          => true,
			'app_promo_position'         => '1',
			'app_promo_title'            => 'FitTasteTic App',
			'app_promo_subtitle'         => 'Rezepte für unterwegs',
			'app_promo_button_text'      => 'Download',
			'app_promo_button_url'       => 'https://fittastetic.app.link/download',
			'app_promo_image'            => 'https://alkipedia.com/wp-content/uploads/2019/11/cropped-app_icon_red_orange.png',
			'app_promo_show_on_mobile'   => true,
			'app_promo_show_on_desktop'  => true,
		);
	}

	/**
	 * Effektive Einstellungen: gespeicherte Option über die Defaults gemerged.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string,mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::option_key(), array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Einzelwert (roh) mit Fallback.
	 *
	 * @since 0.2.0
	 *
	 * @param string $key      Einstellungs-Schlüssel.
	 * @param mixed  $fallback Rückgabe, wenn der Schlüssel fehlt.
	 * @return mixed
	 */
	public static function get( string $key, $fallback = null ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Einzelwert als bool.
	 *
	 * @since 0.2.0
	 *
	 * @param string $key Einstellungs-Schlüssel.
	 * @return bool
	 */
	public static function flag( string $key ): bool {
		return (bool) self::get( $key, false );
	}

	/**
	 * Einzelwert als Ganzzahl.
	 *
	 * @since 0.2.0
	 *
	 * @param string $key      Einstellungs-Schlüssel.
	 * @param int    $fallback Rückgabe, wenn der Schlüssel fehlt.
	 * @return int
	 */
	public static function number( string $key, int $fallback = 0 ): int {
		return (int) self::get( $key, $fallback );
	}

	/**
	 * Einzelwert als bereinigter String.
	 *
	 * @since 0.2.0
	 *
	 * @param string $key      Einstellungs-Schlüssel.
	 * @param string $fallback Rückgabe, wenn der Schlüssel fehlt.
	 * @return string
	 */
	public static function text( string $key, string $fallback = '' ): string {
		$value = self::get( $key, $fallback );

		return is_scalar( $value ) ? (string) $value : $fallback;
	}
}
