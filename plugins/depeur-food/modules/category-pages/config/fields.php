<?php
/**
 * Feld-Definitionen des category-pages-Moduls (Single Source) für den Field_Provisioner.
 *
 * Konfig-Ansatz A (BRIEF § 4.1): Eine normale `page` wird per Opt-in-Toggle
 * `df_catpage_enabled` zur Kategorie-Seite. Erst wenn der Toggle an ist, erscheinen die
 * weiteren Felder (ACF conditional_logic) — minimalistisch, kein Feld-Wust per Default.
 *
 * Weitere Felder (Term-Auswahl je Taxonomie, AND/OR-Modus, Intro-Seite, Filter-Gruppen)
 * kommen in den folgenden Build-Schritten dazu; diese Datei ist die wachsende Single Source.
 *
 * @package Depeur\Food\Modules\CategoryPages
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Conditional-Logic-Regel: Feld nur zeigen, wenn der Opt-in-Toggle aktiv ist.
$df_catpage_when_enabled = array(
	array(
		array(
			'field'    => 'field_df_catpage_enabled',
			'operator' => '==',
			'value'    => '1',
		),
	),
);

return array(
	'group'  => array(
		'key'      => 'group_depeur_food_category_page',
		'title'    => 'Kategorie-Seite',
		'position' => 'normal',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
		),
	),
	'fields' => array(
		array(
			'name'     => 'df_catpage_enabled',
			'acf_type' => 'true_false',
			'object'   => array( 'post' ),
			'subtypes' => array( 'post' => array( 'page' ) ),
			'key'      => 'field_df_catpage_enabled',
			'label'    => 'Diese Seite ist eine Kategorie-Seite',
			'default'  => false,
			'acf'      => array(
				'ui'           => 1,
				'instructions' => 'Aktivieren, um diese Seite als kuratierte Kategorie-/Rezept-Seite zu betreiben. Erst dann erscheinen die weiteren Einstellungen.',
			),
		),
		array(
			'name'     => 'df_catpage_title',
			'acf_type' => 'text',
			'object'   => array( 'post' ),
			'subtypes' => array( 'post' => array( 'page' ) ),
			'key'      => 'field_df_catpage_title',
			'label'    => 'Custom-Titel (H1 / SEO)',
			'acf'      => array(
				'instructions'      => 'Optionaler Titel; leer = Seitentitel.',
				'conditional_logic' => $df_catpage_when_enabled,
			),
		),
		array(
			'name'     => 'df_catpage_per_page_first',
			'acf_type' => 'number',
			'object'   => array( 'post' ),
			'subtypes' => array( 'post' => array( 'page' ) ),
			'key'      => 'field_df_catpage_per_page_first',
			'label'    => 'Beiträge auf Seite 1 (Vorschau)',
			'default'  => 4,
			'acf'      => array(
				'min'               => 0,
				'instructions'      => 'Wie viele Beiträge die Vorschau auf Seite 1 unter dem Intro zeigt.',
				'conditional_logic' => $df_catpage_when_enabled,
			),
		),
		array(
			'name'     => 'df_catpage_per_page',
			'acf_type' => 'number',
			'object'   => array( 'post' ),
			'subtypes' => array( 'post' => array( 'page' ) ),
			'key'      => 'field_df_catpage_per_page',
			'label'    => 'Beiträge ab Seite 2',
			'default'  => 21,
			'acf'      => array(
				'min'               => 1,
				'conditional_logic' => $df_catpage_when_enabled,
			),
		),
	),
);
