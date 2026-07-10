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

		return array(
			array(
				'name'     => 'show_newsletter_form',
				'key'      => 'field_show_newsletter',
				'label'    => __( 'Newsletter-Formular anzeigen', 'depeur-food' ),
				'acf_type' => 'true_false',
				'object'   => array( 'post' ),
				'subtypes' => $subtypes,
				'default'  => true,
				'acf'      => array(
					'instructions'  => __( 'Blendet das Newsletter-Formular in diesem Beitrag ein.', 'depeur-food' ),
					'ui'            => 1,
					'default_value' => 1,
				),
			),
			array(
				'name'     => 'newsletter_position',
				'key'      => 'field_newsletter_position',
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
					// Nur sichtbar, wenn das Formular in diesem Beitrag aktiviert ist.
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_show_newsletter',
								'operator' => '==',
								'value'    => '1',
							),
						),
					),
				),
			),
			array(
				'name'     => 'show_app_promo',
				'key'      => 'field_show_app_promo',
				'label'    => __( 'App-Promotion anzeigen', 'depeur-food' ),
				'acf_type' => 'true_false',
				'object'   => array( 'post' ),
				'subtypes' => $subtypes,
				'default'  => true,
				'acf'      => array(
					'instructions'  => __( 'Blendet den App-Promotion-Block in diesem Beitrag ein.', 'depeur-food' ),
					'ui'            => 1,
					'default_value' => 1,
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
