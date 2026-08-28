<?php
/**
 * Admin/Settings — globaler Einstellungs-Tab des Newsletter-Moduls.
 *
 * Meldet das Feld-Schema via SettingsRegistry an (ADR-1 Multi-Option). Das Core-Settings-
 * System (SettingsPage) übernimmt Rendern UND Speichern inklusive Capability-Check, Nonce
 * und Sanitisierung — dadurch ist die Nonce-Lücke des Legacy-Admin-Saves
 * (spotlight-subscribe.php:746, ohne wp_verify_nonce) geschlossen, ohne eine eigene
 * Save-Routine zu schreiben. URL- und Positions-Felder tragen einen feldeigenen
 * sanitize-Callback.
 *
 * @package Depeur\Food\Modules\Newsletter\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\Newsletter\Support\Config;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den Newsletter-Settings-Tab.
 *
 * @since 0.2.0
 */
final class Settings {

	/**
	 * Modul-Slug (Options-/Tab-Kontext, aus module.php hereingereicht).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab an (läuft während init, vor dem späteren Render).
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Newsletter', 'depeur-food' ),
			array_merge( $this->fields(), $this->type_default_fields() ),
			$this->intro()
		);
	}

	/**
	 * Tab-Intro (§ 6.2 Admin-UI-Doku): erklärt Zweck und Zusammenspiel mit den Per-Post-Feldern.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	private function intro(): string {
		return __( 'Globale Standard-Einstellungen für das Newsletter-Formular (Flodesk) und die App-Promotion, die automatisch in den Inhalt unterstützter Post-Types eingefügt werden. Diese Werte gelten site-weit; einzelne Beiträge können über die Box „Newsletter-Einstellungen" im Editor abweichen (Formular/App an- oder abschalten, Position ändern). Manuelle Platzierung ist zusätzlich per Shortcode [df_newsletter] bzw. [df_app_promo] möglich.', 'depeur-food' );
	}

	/**
	 * Baut das Feld-Schema aus den Config-Defaults.
	 *
	 * Feldtypen sind auf die vom Core-Renderer unterstützten beschränkt (checkbox/text). URL-
	 * und Positions-Felder erhalten einen feldeigenen sanitize-Callback (SettingsRegistry::
	 * sanitize_field ruft ihn vor dem Speichern auf). 'html'-Zeilen dienen nur als
	 * Abschnitts-Überschriften und werden vom Save-Loop übersprungen.
	 *
	 * @since 0.2.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function fields(): array {
		$defaults = Config::defaults();

		$sanitize_url = static function ( $value ) {
			return esc_url_raw( is_string( $value ) ? $value : '' );
		};

		// Position 1–20 (1-basiert, UI). Als String zurückgeben (Text-Feld-Speicherung).
		$sanitize_position = static function ( $value ) {
			$number = absint( $value );
			if ( $number < 1 ) {
				$number = 1;
			}
			if ( $number > 20 ) {
				$number = 20;
			}
			return (string) $number;
		};

		return array(
			// Abschnitt Newsletter (nur Überschrift, kein Speicher-Wert).
			array(
				'id'    => 'section_newsletter',
				'type'  => 'html',
				'label' => '',
				'html'  => '<h2 class="title">' . esc_html__( 'Newsletter (Flodesk)', 'depeur-food' ) . '</h2>',
			),
			array(
				'id'          => 'newsletter_enabled',
				'label'       => __( 'Newsletter aktivieren', 'depeur-food' ),
				'type'        => 'checkbox',
				'default'     => $defaults['newsletter_enabled'],
				'description' => __( 'Newsletter-Formular automatisch in unterstützte Inhalte einfügen.', 'depeur-food' ),
			),
			array(
				'id'          => 'newsletter_display_mode',
				'label'       => __( 'Darstellung', 'depeur-food' ),
				'type'        => 'select',
				'default'     => $defaults['newsletter_display_mode'],
				'options'     => array(
					'spotlight' => __( 'Spotlight – im Text, sticky, mit Fokus-Abdunklung (einmal weg = weg)', 'depeur-food' ),
					'minimal'   => __( 'Minimal – im Text, sticky wie Spotlight, aber ohne Abdunklung', 'depeur-food' ),
					'popup'     => __( 'Popup – fest positioniert, auf Desktop mittig, ab 50 % Scrolltiefe', 'depeur-food' ),
				),
				'description' => __( '„Spotlight" dunkelt beim Reinscrollen die ganze Seite ab und rückt das Formular in den Fokus; einmal geschlossen wird es (Browser-Merker) nicht erneut gezeigt. „Minimal" verhält sich genau wie Spotlight (im Textfluss, scrollt ein Stück mit), nur OHNE Abdunklung. „Popup" ist fest positioniert (auf Desktop mittig, mobil als Leiste unten), erscheint erst ab 50 % Scrolltiefe und dunkelt nichts ab. Bei „Minimal" und „Popup" gilt Schließen nur für die aktuelle Seite (erscheint bei jedem Aufruf/Refresh wieder – mehr Sichtbarkeit); wer sich einträgt, sieht in allen Varianten künftig nichts mehr. Der Shortcode [df_newsletter] bleibt unverändert inline.', 'depeur-food' ),
			),
			array(
				'id'          => 'flodesk_form_id',
				'label'       => __( 'Flodesk Formular-ID', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['flodesk_form_id'],
				'description' => __( 'Die ID des Flodesk-Inline-Formulars (z. B. 68319b10b61ee160f25775e2).', 'depeur-food' ),
			),
			array(
				'id'          => 'flodesk_form_action',
				'label'       => __( 'Flodesk Formular-Action-URL', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['flodesk_form_action'],
				'sanitize'    => $sanitize_url,
				'description' => __( 'Ziel-URL des Formulars (Flodesk-Submit-Endpoint).', 'depeur-food' ),
			),
			array(
				'id'          => 'newsletter_success_url',
				'label'       => __( 'Erfolgs-URL', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['newsletter_success_url'],
				'sanitize'    => $sanitize_url,
				'description' => __( 'Weiterleitung nach erfolgreicher Anmeldung.', 'depeur-food' ),
			),
			array(
				'id'          => 'newsletter_image',
				'label'       => __( 'Bild-URL', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['newsletter_image'],
				'sanitize'    => $sanitize_url,
				'description' => __( 'Bild neben dem Formular.', 'depeur-food' ),
			),
			array(
				'id'      => 'newsletter_title',
				'label'   => __( 'Titel', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['newsletter_title'],
			),
			array(
				'id'      => 'newsletter_subtitle',
				'label'   => __( 'Untertitel', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['newsletter_subtitle'],
			),
			array(
				'id'      => 'newsletter_button_text',
				'label'   => __( 'Button-Text', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['newsletter_button_text'],
			),
			array(
				'id'      => 'newsletter_placeholder',
				'label'   => __( 'E-Mail-Platzhalter', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['newsletter_placeholder'],
			),
			array(
				'id'          => 'newsletter_position',
				'label'       => __( 'Standard-Position', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['newsletter_position'],
				'sanitize'    => $sanitize_position,
				'description' => __( 'Nach welchem Absatz das Formular erscheint (1–20). Einzelne Beiträge können abweichen.', 'depeur-food' ),
			),
			array(
				'id'      => 'newsletter_show_on_desktop',
				'label'   => __( 'Auf Desktop anzeigen', 'depeur-food' ),
				'type'    => 'checkbox',
				'default' => $defaults['newsletter_show_on_desktop'],
			),
			array(
				'id'      => 'newsletter_show_on_mobile',
				'label'   => __( 'Auf Mobilgeräten anzeigen', 'depeur-food' ),
				'type'    => 'checkbox',
				'default' => $defaults['newsletter_show_on_mobile'],
			),

			// Abschnitt App-Promotion (nur Überschrift, kein Speicher-Wert).
			array(
				'id'    => 'section_app_promo',
				'type'  => 'html',
				'label' => '',
				'html'  => '<h2 class="title">' . esc_html__( 'App-Promotion', 'depeur-food' ) . '</h2>',
			),
			array(
				'id'          => 'app_promo_enabled',
				'label'       => __( 'App-Promotion aktivieren', 'depeur-food' ),
				'type'        => 'checkbox',
				'default'     => $defaults['app_promo_enabled'],
				'description' => __( 'App-Promotion-Block automatisch in unterstützte Inhalte einfügen.', 'depeur-food' ),
			),
			array(
				'id'          => 'app_promo_position',
				'label'       => __( 'Position', 'depeur-food' ),
				'type'        => 'text',
				'default'     => $defaults['app_promo_position'],
				'sanitize'    => $sanitize_position,
				'description' => __( 'Nach welchem Absatz der Block erscheint (1–20).', 'depeur-food' ),
			),
			array(
				'id'      => 'app_promo_title',
				'label'   => __( 'Titel', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['app_promo_title'],
			),
			array(
				'id'      => 'app_promo_subtitle',
				'label'   => __( 'Untertitel', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['app_promo_subtitle'],
			),
			array(
				'id'      => 'app_promo_button_text',
				'label'   => __( 'Button-Text', 'depeur-food' ),
				'type'    => 'text',
				'default' => $defaults['app_promo_button_text'],
			),
			array(
				'id'       => 'app_promo_button_url',
				'label'    => __( 'Download-URL', 'depeur-food' ),
				'type'     => 'text',
				'default'  => $defaults['app_promo_button_url'],
				'sanitize' => $sanitize_url,
			),
			array(
				'id'       => 'app_promo_image',
				'label'    => __( 'Icon-URL', 'depeur-food' ),
				'type'     => 'text',
				'default'  => $defaults['app_promo_image'],
				'sanitize' => $sanitize_url,
			),
			array(
				'id'      => 'app_promo_show_on_desktop',
				'label'   => __( 'Auf Desktop anzeigen', 'depeur-food' ),
				'type'    => 'checkbox',
				'default' => $defaults['app_promo_show_on_desktop'],
			),
			array(
				'id'      => 'app_promo_show_on_mobile',
				'label'   => __( 'Auf Mobilgeräten anzeigen', 'depeur-food' ),
				'type'    => 'checkbox',
				'default' => $defaults['app_promo_show_on_mobile'],
			),
		);
	}

	/**
	 * Baut je unterstütztem Post-Type zwei Standard-Checkboxen (Newsletter + App-Promo).
	 *
	 * WOFÜR (§ 6.2): pro Inhaltstyp festlegen, ob Newsletter/App-Promo standardmäßig eingefügt
	 * werden. Einzelne Beiträge überschreiben das über das 3-Zustand-Feld im Editor. Default: an
	 * (bewahrt das bisherige Verhalten). Typ-Liste = Ziel-Typen des Inserters (filterbar).
	 *
	 * @since 0.3.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function type_default_fields(): array {
		/** This filter is documented in Fields/Overrides.php */
		$types = apply_filters( 'depeur_food/newsletter/post_types', depeur_food()->get_supported_post_types() );
		$types = array_values( array_unique( array_filter( array_map( 'strval', (array) $types ) ) ) );

		if ( empty( $types ) ) {
			return array();
		}

		$fields = array(
			array(
				'id'    => 'section_type_defaults',
				'type'  => 'html',
				'label' => '',
				'html'  => '<h2 class="title">' . esc_html__( 'Standard je Inhaltstyp', 'depeur-food' ) . '</h2>'
					. '<p class="description" style="max-width: 60em;">'
					. esc_html__( 'Legt fest, ob Newsletter-Formular und App-Promotion auf einem Inhaltstyp standardmäßig eingefügt werden. Der Standard gilt für alle Beiträge dieses Typs, die im Editor auf „Standard" stehen; wer dort „An" oder „Aus" wählt, überschreibt den Standard nur für diesen Beitrag — dauerhaft, auch wenn der Typ-Standard später wechselt. Mit „Alle Überschreibungen zurücksetzen" (unten je Typ) setzt du diese individuellen Wahlen wieder auf „Standard".', 'depeur-food' )
					. '</p>',
			),
		);

		foreach ( $types as $type ) {
			$object = get_post_type_object( $type );
			$label  = ( $object && ! empty( $object->labels->singular_name ) ) ? (string) $object->labels->singular_name : $type;

			$fields[] = array(
				'id'      => Config::type_default_key( 'newsletter', $type ),
				/* translators: %s: Name des Inhaltstyps. */
				'label'   => sprintf( __( 'Newsletter-Standard: %s', 'depeur-food' ), $label ),
				'type'    => 'checkbox',
				'default' => true,
			);
			$fields[] = array(
				'id'      => Config::type_default_key( 'app_promo', $type ),
				/* translators: %s: Name des Inhaltstyps. */
				'label'   => sprintf( __( 'App-Promo-Standard: %s', 'depeur-food' ), $label ),
				'type'    => 'checkbox',
				'default' => true,
			);
			// Massen-Reset je Typ: Link (kein Formular — würde das umschließende Settings-Formular
			// verschachteln) auf den nonce-gesicherten admin-post-Handler mit Bestätigungsseite.
			$fields[] = array(
				'id'    => 'reset_overrides_' . $type,
				'type'  => 'html',
				'label' => '',
				'html'  => '<p style="margin:.2em 0 1.6em;">'
					. '<a href="' . esc_url( Reset_Overrides::url( $type ) ) . '" class="button button-secondary">'
					/* translators: %s: Name des Inhaltstyps. */
					. esc_html( sprintf( __( 'Alle Überschreibungen für „%s" zurücksetzen', 'depeur-food' ), $label ) )
					. '</a>'
					. '<span class="description" style="display:block;margin-top:4px;max-width:60em;">'
					. esc_html__( 'Setzt An/Aus in ALLEN Beiträgen dieses Typs auf „Standard" zurück (mit Sicherheitsabfrage; die Position bleibt erhalten).', 'depeur-food' )
					. '</span></p>',
			);
		}

		return $fields;
	}
}
