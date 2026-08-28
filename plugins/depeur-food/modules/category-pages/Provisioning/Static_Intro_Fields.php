<?php
/**
 * Static_Intro_Fields — provisioniert die zwei „Seite 1"-Felder (Kategorie- + Autor-Intro).
 *
 * Übernimmt die Feld-Hoheit vom Alt-Modul meta-registry (keine Überschneidung mehr): das
 * category-pages-Modul besitzt die „Seite 1"-Semantik und legt die Felder selbst an, damit sie
 * auch OHNE meta-registry existieren.
 *
 *   - `static_page`            (Term-Meta auf `category`) → optionale Intro-Seite je Kategorie.
 *   - `static_page_for_author` (User-Meta)               → optionale Intro-Seite je Autor.
 *
 * Beide behalten Meta-Key + ACF-Field-Key aus der Discovery (bestehende Auswahl bleibt erhalten,
 * Werte liegen namens-identisch in der DB). Jeweils eine eigene ACF-Group (verschiedene
 * Locations Taxonomie vs. User → zwei Field_Provisioner-Instanzen). Der Konsument, der die
 * Auswahl tatsächlich rendert, ist Frontend\Static_Intro.
 *
 * @package Depeur\Food\Modules\CategoryPages\Provisioning
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Provisioning;

use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legt die beiden „Seite 1"-Felder (Term + User) via Core-Field_Provisioner an.
 *
 * @since 0.3.0
 */
final class Static_Intro_Fields {

	/**
	 * Meta-Key des Kategorie-Intro-Feldes (Term-Meta auf `category`).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const CATEGORY_META = 'static_page';

	/**
	 * Meta-Key des Autor-Intro-Feldes (User-Meta).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const AUTHOR_META = 'static_page_for_author';

	/**
	 * Provisioniert beide Felder (je eigene ACF-Group).
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Kategorie-Intro (Term-Meta auf category). Field_Provisioner hängt sich selbst an
		// init (Meta) + acf/init (ACF-Group), inkl. did_action-Guards.
		new Field_Provisioner(
			array(
				array(
					'name'      => self::CATEGORY_META,
					'key'       => 'field_6516c1ef9cfc3',
					'label'     => __( 'Seite 1 (Intro-Seite)', 'depeur-food' ),
					'acf_type'  => 'post_object',
					'object'    => array( 'term' ),
					'subtypes'  => array( 'term' => array( 'category' ) ),
					'default'   => '',
					'editor_ui' => true,
					'acf'       => array(
						'instructions'  => __( 'Optional: Diese Seite wird auf Seite 1 des Kategorie-Archivs statt der Beitragsliste angezeigt.', 'depeur-food' ),
						'post_type'     => array( 'page' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'multiple'      => 0,
					),
				),
			),
			array(
				'key'      => 'group_df_catpage_static_category',
				'title'    => __( 'Seite 1 (Kategorie-Intro)', 'depeur-food' ),
				'position' => 'normal',
				'location' => array(
					array(
						array(
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'category',
						),
					),
				),
			)
		);

		// Autor-Intro (User-Meta, global).
		new Field_Provisioner(
			array(
				array(
					'name'      => self::AUTHOR_META,
					'key'       => 'field_6523ed75c2965',
					'label'     => __( 'Seite 1 (Intro-Seite)', 'depeur-food' ),
					'acf_type'  => 'post_object',
					'object'    => array( 'user' ),
					'subtypes'  => array(),
					'default'   => '',
					'editor_ui' => true,
					'acf'       => array(
						'instructions'  => __( 'Optional: Diese Seite wird auf Seite 1 des Autor-Archivs statt der Beitragsliste angezeigt.', 'depeur-food' ),
						'post_type'     => array( 'page' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'multiple'      => 0,
					),
				),
			),
			array(
				'key'      => 'group_df_catpage_static_author',
				'title'    => __( 'Seite 1 (Autor-Intro)', 'depeur-food' ),
				'position' => 'normal',
				'location' => array(
					array(
						array(
							'param'    => 'user_role',
							'operator' => '==',
							'value'    => 'all',
						),
					),
				),
			)
		);
	}
}
