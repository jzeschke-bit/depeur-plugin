<?php
/**
 * ACF-Group-Metadaten der Schema-Engine.
 *
 * Reine Datendatei. NUR Gruppen-Metadaten (key, title, location, position, show_in_rest) —
 * KEINE Feld-Listen; Provisioning\Fields sammelt die Felder anhand ihres `group`-Slugs aus
 * fields.php. Keyed nach internem Group-Slug (= `group`-Wert in fields.php).
 *
 * Group-Key-Wahl (bewusst, meta-registry § 4.5 „Key-Reuse-Override"):
 *   - author_schema / kategorie_schema / review REUSEN den exakten Discovery-Group-Key.
 *     Damit überschattet die per PHP registrierte Group die gleichnamige Legacy-ACF-UI-Group
 *     (kein Doppel-Render), auch wenn der Titel hier neu vergeben ist (ACF identifiziert über
 *     den Key, nicht den Titel).
 *   - author_social bekommt einen EIGENEN, plugin-eigenen Key: die Social-Felder waren live
 *     Teil der Author-Group (group_64a6871dc3795); da diese oben von author_schema überschattet
 *     (und dabei auf 10 Felder reduziert) wird, rendern die Social-Felder nur noch aus DIESER
 *     neuen Group → weiterhin kein Doppel-Render.
 *
 * `location` = ACF-Location-Regeln (Array aus OR-Gruppen, je eine Liste von AND-Regeln).
 * Die Location der review-Group wird in Provisioning\Fields dynamisch aus den unterstützten
 * Post-Types gebaut (ADR-4); der hier hinterlegte Wert ist nur der Bootstrap-Default.
 *
 * @package Depeur\Food\Modules\SchemaEngine
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	'author_schema'    => array(
		'key'          => 'group_64a6871dc3795',
		'title'        => 'Autor-Schema',
		'position'     => 'normal',
		'show_in_rest' => true,
		'location'     => array(
			array(
				array(
					'param'    => 'user_role',
					'operator' => '==',
					'value'    => 'all',
				),
			),
		),
	),

	'author_social'    => array(
		'key'          => 'group_depeur_food_author_social',
		'title'        => 'Autor-Social-Profile',
		'position'     => 'normal',
		'show_in_rest' => true,
		'location'     => array(
			array(
				array(
					'param'    => 'user_role',
					'operator' => '==',
					'value'    => 'all',
				),
			),
		),
	),

	'kategorie_schema' => array(
		'key'          => 'group_6516b8d64a7b3',
		'title'        => 'Kategorie-Schema',
		'position'     => 'normal',
		'show_in_rest' => true,
		'location'     => array(
			array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => 'category',
				),
			),
		),
	),

	'review'           => array(
		'key'          => 'group_64a3ef3013119',
		'title'        => 'Review',
		'position'     => 'normal',
		'show_in_rest' => true,
		'location'     => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'post',
				),
			),
		),
	),
);
