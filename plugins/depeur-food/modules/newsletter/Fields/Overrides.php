<?php
/**
 * Overrides — Per-Post-Override-Felder für das Newsletter-Modul.
 *
 * Deklariert die drei Steuerfelder (show_newsletter_form, newsletter_position,
 * show_app_promo) und übergibt sie dem Core-Field_Provisioner, der beide Seiten anlegt:
 *   1. Datenschicht via register_post_meta (REST + Sanitize),
 *   2. Editor-UI via acf_add_local_field_group (nur bei aktivem ACF).
 *
 * Post-type-agnostisch (ADR-4): Ziel-Subtypes + ACF-Location kommen aus
 * depeur_food()->get_supported_post_types(), filterbar über depeur_food/newsletter/post_types.
 * Ersetzt die programmatischen ACF-Registrierungen aus spotlight-subscribe.php:870–991.
 *
 * @package Depeur\Food\Modules\Newsletter\Fields
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Fields;

use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die Override-Feld-Deklarationen und verdrahtet den Field_Provisioner.
 *
 * @since 0.2.0
 */
final class Overrides {

	/**
	 * ACF-Group-Key der Override-Feldgruppe (eigenständig, kein Legacy-Key-Reuse).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private const GROUP_KEY = 'group_depeur_food_newsletter';

	/**
	 * Verdrahtet die Provisionierung. Der Field_Provisioner registriert die Meta-Keys am
	 * init-Hook und die ACF-Group am acf/init-Hook (mit eigenen did_action-Guards).
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$post_types = $this->target_post_types();

		new Field_Provisioner( $this->fields( $post_types ), $this->group( $post_types ) );
	}

	/**
	 * Ziel-Post-Types: unterstützte Typen (ADR-4), zusätzlich filterbar.
	 *
	 * @since 0.2.0
	 *
	 * @return string[]
	 */
	private function target_post_types(): array {
		/**
		 * Filtert die Post-Types, für die die Newsletter-Override-Felder + der Inserter gelten.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $post_types Standard: depeur_food()->get_supported_post_types().
		 */
		$post_types = apply_filters( 'depeur_food/newsletter/post_types', depeur_food()->get_supported_post_types() );

		// Defensive Normalisierung: nur nicht-leere String-Slugs, dedupliziert.
		$post_types = array_values( array_unique( array_filter( array_map( 'strval', (array) $post_types ) ) ) );

		return empty( $post_types ) ? array( 'post' ) : $post_types;
	}

	/**
	 * Feld-Deklarationen im Field_Provisioner-Schema.
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $post_types Ziel-Subtypes für die Post-Meta-Registrierung.
	 * @return array<int,array<string,mixed>>
	 */
	private function fields( array $post_types ): array {
		$subtypes = array( 'post' => $post_types );

		// 3-Zustand-Auswahl als Button-Group (Toggle-Optik): „Standard" folgt der Typ-Vorgabe,
		// „An"/„Aus" ist eine gespeicherte Per-Post-Überschreibung. Der leere Wert („Standard")
		// wird NICHT als Überschreibung gespeichert → ein späterer Wechsel des Typ-Standards
		// wirkt weiter, während eine manuelle „An"/„Aus"-Wahl erhalten bleibt.
		$visibility_choices = array(
			''  => __( 'Standard', 'depeur-food' ),
			'1' => __( 'An', 'depeur-food' ),
			'0' => __( 'Aus', 'depeur-food' ),
		);

		return array(
			array(
				'name'     => 'show_newsletter_form',
				// Eigener, plugin-namespaced Key (NICHT der Legacy-Key field_show_newsletter):
				// verhindert die ACF-Kollision mit dem Alt-Plugin spotlight-subscribe. Der
				// Meta-NAME bleibt gleich → bestehende 1/0-Werte bleiben als An/Aus erhalten.
				'key'      => 'field_df_nl_newsletter',
				'label'    => __( 'Newsletter-Formular', 'depeur-food' ),
				'acf_type' => 'button_group',
				'object'   => array( 'post' ),
				'subtypes' => $subtypes,
				'default'  => '',
				'acf'      => array(
					'instructions'  => __( '„Standard" folgt der Typ-Vorgabe in den Newsletter-Einstellungen; „An"/„Aus" gilt nur für diesen Beitrag und bleibt auch erhalten, wenn der Typ-Standard später wechselt.', 'depeur-food' ),
					'choices'       => $visibility_choices,
					'default_value' => '',
					'return_format' => 'value',
					'layout'        => 'horizontal',
					'allow_null'    => 0,
				),
			),
			array(
				'name'     => 'newsletter_position',
				'key'      => 'field_df_nl_position',
				'label'    => __( 'Newsletter-Position', 'depeur-food' ),
				'acf_type' => 'number',
				'object'   => array( 'post' ),
				'subtypes' => $subtypes,
				'default'  => 4,
				'acf'      => array(
					'instructions'      => __( 'Nach welchem Absatz soll der Newsletter erscheinen? (Standard: 4)', 'depeur-food' ),
					'default_value'     => 4,
					'min'               => 1,
					'max'               => 20,
					'step'              => 1,
					// Nur sichtbar, wenn das Formular in diesem Beitrag nicht auf „Aus" steht.
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_df_nl_newsletter',
								'operator' => '!=',
								'value'    => '0',
							),
						),
					),
				),
			),
			array(
				'name'     => 'show_app_promo',
				'key'      => 'field_df_nl_app_promo',
				'label'    => __( 'App-Promotion', 'depeur-food' ),
				'acf_type' => 'button_group',
				'object'   => array( 'post' ),
				'subtypes' => $subtypes,
				'default'  => '',
				'acf'      => array(
					'instructions'  => __( '„Standard" folgt der Typ-Vorgabe in den Newsletter-Einstellungen; „An"/„Aus" gilt nur für diesen Beitrag und bleibt auch erhalten, wenn der Typ-Standard später wechselt.', 'depeur-food' ),
					'choices'       => $visibility_choices,
					'default_value' => '',
					'return_format' => 'value',
					'layout'        => 'horizontal',
					'allow_null'    => 0,
				),
			),
		);
	}

	/**
	 * ACF-Group-Metadaten (Editor-UI). Location = OR-Verknüpfung über alle Ziel-Post-Types.
	 *
	 * @since 0.2.0
	 *
	 * @param string[] $post_types Ziel-Post-Types.
	 * @return array<string,mixed>
	 */
	private function group( array $post_types ): array {
		$location = array();
		foreach ( $post_types as $post_type ) {
			$location[] = array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				),
			);
		}

		return array(
			'key'          => self::GROUP_KEY,
			'title'        => __( 'Newsletter-Einstellungen', 'depeur-food' ),
			'position'     => 'side',
			'location'     => $location,
			'show_in_rest' => true,
		);
	}
}
