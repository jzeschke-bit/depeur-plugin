<?php
/**
 * ACF-Field-Group-Metadaten (BRIEF meta-registry § 4.4).
 *
 * NUR Gruppen-Metadaten (key, title, location, position) — KEINE Feld-Listen. Der
 * Group_Registrar sammelt die Felder anhand ihrer `group`-Zugehörigkeit aus fields.php
 * (Single Source, § 3.3). Keyed nach internem Group-Slug (= `group`-Wert in fields.php).
 *
 * `key` = exakter ACF-Group-Key aus der Discovery → ACF-local-Override der DB-/UI-Group
 * (kein Doppel-Render, § 4.5). `location` = ACF-Location-Regeln (Array von OR-Gruppen,
 * je eine Liste von AND-Regeln).
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	'author_fields'    => array(
		'key'      => 'group_64a6871dc3795',
		'title'    => 'Author fields',
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
	),

	'kategorie_custom' => array(
		'key'      => 'group_6516b8d64a7b3',
		'title'    => 'Kategorie-Custom',
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
	),

	'reviewed_by'      => array(
		'key'      => 'group_64a3ef3013119',
		'title'    => 'Reviewed by',
		'position' => 'normal',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'post',
				),
			),
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
		),
	),

	'rezeptkategorie'  => array(
		'key'      => 'group_682f1db019e50',
		'title'    => 'Rezeptkategorie Einstellungen',
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

	'uebersetzungen'   => array(
		'key'      => 'group_5f29db788a4f8',
		'title'    => 'Übersetzungen',
		'position' => 'acf_after_title',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'post',
				),
			),
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
			array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => 'category',
				),
			),
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'tests',
				),
			),
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'blog',
				),
			),
		),
	),

	// Newsletter: zwei Groups teilen sich dieselben drei Felder (§ 4.5). Default-Differenz
	// (show_app_promo 1/0) via group_overrides in fields.php.
	'newsletter_pages' => array(
		'key'      => 'group_spotlight_options_pages',
		'title'    => 'Spotlight Promotions',
		'position' => 'side',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
			array(
				array(
					'param'    => 'page_template',
					'operator' => '==',
					'value'    => 'single-rezeptkategorie-template.php',
				),
			),
		),
	),

	'newsletter_cpt'   => array(
		'key'      => 'group_spotlight_options_cpt',
		'title'    => 'Spotlight Promotions',
		'position' => 'side',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'blog',
				),
			),
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'tests',
				),
			),
		),
	),

	'tag_settings'     => array(
		'key'      => 'group_tag_settings',
		'title'    => 'Tag-Einstellungen',
		'position' => 'normal',
		'location' => array(
			array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => 'post_tag',
				),
			),
		),
	),
);
