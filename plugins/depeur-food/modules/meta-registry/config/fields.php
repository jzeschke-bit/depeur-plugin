<?php
/**
 * Field-Registry — NUR NOCH die Legacy-Rezeptkategorie-Quellfelder.
 *
 * Historie: meta-registry war der zentrale 34-Feld-Spiegel VOR dem Feature-Modul-Pivot. Inzwischen
 * hat jedes andere Feld einen Feature-Modul-Owner MIT eigener Editor-UI:
 *   - Autor-/Social-/Review-Felder, WPRM-Term  → schema-engine
 *   - link_de/link_en                          → language-selector
 *   - Newsletter-Overrides                      → newsletter
 *   - tag_group, static_page(_for_author)       → category-pages
 * Diese Felder wurden hier ENTFERNT, damit keine Doppel-Boxen mehr entstehen (gleiche ACF-Keys).
 *
 * Es bleiben ausschließlich die alten `rezept_*` / `rezeptkategorie_titel`-Felder, die KEIN
 * Feature-Modul provisioniert. Sie sind die QUELLE der category-pages-Migration — die sie roh via
 * get_post_meta liest (unabhängig von dieser Registrierung); die Registrierung liefert nur Editor-
 * UI/REST bis zur Migration. Die Meta-WERTE in wp_postmeta bleiben in jedem Fall unangetastet.
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	array(
		'name'      => 'rezept_art_tags',
		'key'       => 'field_682f1db0c62c7',
		'label'     => 'rezept_art_tags',
		'acf_type'  => 'taxonomy',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => 'rezeptkategorie',
		'default'   => array(),
		'editor_ui' => true,
		'acf'       => array(
			'taxonomy'      => 'art',
			'field_type'    => 'multi_select',
			'return_format' => 'id',
			'add_term'      => 0,
			'save_terms'    => 0,
			'load_terms'    => 0,
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
	array(
		'name'      => 'rezept_tags',
		'key'       => 'field_68bc303b79948',
		'label'     => 'rezept_tags',
		'acf_type'  => 'taxonomy',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => 'rezeptkategorie',
		'default'   => array(),
		'editor_ui' => true,
		'acf'       => array(
			'taxonomy'      => 'post_tag',
			'field_type'    => 'multi_select',
			'return_format' => 'id',
			'add_term'      => 0,
			'save_terms'    => 0,
			'load_terms'    => 0,
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
	array(
		'name'      => 'rezeptkategorie_titel',
		'key'       => 'field_682f38b49b171',
		'label'     => 'rezeptkategorie_titel',
		'acf_type'  => 'text',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => 'rezeptkategorie',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'rezept_anlass_tags',
		'key'       => 'field_68bc331afd184',
		'label'     => 'rezept_anlass_tags',
		'acf_type'  => 'taxonomy',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => 'rezeptkategorie',
		'default'   => array(),
		'editor_ui' => true,
		'acf'       => array(
			'taxonomy'      => 'anlass',
			'field_type'    => 'multi_select',
			'return_format' => 'id',
			'add_term'      => 1,
			'save_terms'    => 0,
			'load_terms'    => 0,
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
	array(
		'name'      => 'rezept_herkunft_tags',
		'key'       => 'field_68bc350e9ed2a',
		'label'     => 'rezept_herkunft_tags',
		'acf_type'  => 'taxonomy',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => 'rezeptkategorie',
		'default'   => array(),
		'editor_ui' => true,
		'acf'       => array(
			'taxonomy'      => 'herkunft',
			'field_type'    => 'multi_select',
			'return_format' => 'id',
			'add_term'      => 1,
			'save_terms'    => 0,
			'load_terms'    => 0,
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
	// Orphan (P1 § 4.3): nur in der DB (9 Werte), nicht im ACF-Export → meta-only, KEIN
	// Editor-Feld (kein `key`/keine Group), Backward-Compat-Read bleibt erhalten.
	array(
		'name'      => 'rezept_tag',
		'key'       => '',
		'label'     => 'rezept_tag (Legacy)',
		'acf_type'  => 'taxonomy',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page' ) ),
		'group'     => '',
		'default'   => array(),
		'editor_ui' => false,
		'acf'       => array(),
	),
);
