<?php
/**
 * Feld-Katalog der Schema-Engine — Single Source für Field_Provisioner.
 *
 * Reine Datendatei (per require geladen, kein Autoloader — Kanon Punkt 6 erlaubt das nur
 * für config-Daten). Jeder Eintrag treibt BEIDE Seiten des Field_Provisioner:
 *   - Datenschicht: register_*_meta (Sanitize + REST aus `acf_type` abgeleitet).
 *   - Editor-UI: acf_add_local_field_group (nur bei aktivem ACF).
 *
 * Meta-Keys (`name`) und ACF-Field-Keys (`key`) sind EXAKT aus der ACF-Discovery
 * übernommen (acf-discovery.md § 1.1/§ 1.2/§ 1.4), damit die Daten in usermeta/termmeta/
 * postmeta koexistenzfähig bleiben (ADR-5/E6) und der Key-Reuse die Legacy-ACF-UI-Group
 * überschattet (kein Doppel-Render, meta-registry § 4.5).
 *
 * Struktur eines Eintrags:
 *   - name       string  Meta-Key (= ACF-Field-Name, strikt identisch zur Live-Site).
 *   - key        string  ACF-Field-Key aus der Discovery.
 *   - label      string  Editor-Label.
 *   - acf_type   string  text|url|wysiwyg|number|true_false|select|post_object|user|taxonomy|link|email.
 *   - object     array   Ziel-Objekte: ('user'), ('term') oder ('post').
 *   - subtypes   array   Map object_type → Subtype-Liste (post_types/taxonomies). User ist global → leer.
 *   - group      string  Interner Group-Slug (Schlüssel in groups.php) — bucketet die Felder je ACF-Group.
 *   - default    mixed   Default-Wert.
 *   - editor_ui  bool    false = nur Meta, KEIN ACF-Editor-Feld.
 *   - acf        array   Typ-spezifische ACF-Field-Settings, in das ACF-Field-Array gemerged.
 *
 * Hinweis zu `reviewed_by`: die post-Subtypes werden in Provisioning\Fields dynamisch aus
 * depeur_food()->get_supported_post_types() gesetzt (post-type-agnostisch, ADR-4); der hier
 * hinterlegte Wert ist nur der Bootstrap-Default.
 *
 * @package Depeur\Food\Modules\SchemaEngine
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	// ───────────────────────── AUTOR-SCHEMA (User-Meta, user_role==all) ─────────────────────────
	array(
		'name'      => 'same_as',
		'key'       => 'field_652420bdd23a0',
		'label'     => 'SameAs',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'same_as_2',
		'key'       => 'field_652420ced23a1',
		'label'     => 'SameAs 2',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_jobtitle',
		'key'       => 'field_64a68771f9008',
		'label'     => 'Job Title',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_alumniof',
		'key'       => 'field_64a6bd8bcfb01',
		'label'     => 'Alumni of',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_alumniof_url',
		'key'       => 'field_64a7c091f93ac',
		'label'     => 'Alumni of URL',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_description',
		'key'       => 'field_64a7c0a4f93ad',
		'label'     => 'Author long description',
		'acf_type'  => 'wysiwyg',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'tabs'         => 'all',
			'toolbar'      => 'full',
			'media_upload' => 1,
		),
	),
	array(
		'name'      => 'author_knowabout',
		'key'       => 'field_64a6871ef9004',
		'label'     => 'Knows about 1',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_knowabout_2',
		'key'       => 'field_64a7bffdf93a8',
		'label'     => 'Knows about 2',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_knowabout_3',
		'key'       => 'field_64a7c00cf93a9',
		'label'     => 'Knows about 3',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_knowabout_4',
		'key'       => 'field_64a7c012f93aa',
		'label'     => 'Knows about 4',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),

	// ───────────────────────── AUTOR-SOCIAL-PROFILE (User-Meta, abschaltbar) ─────────────────────────
	// Eigene Group „Autor-Social-Profile" — per Setting/Filter deaktivierbar (Default: an).
	// Deaktiviert = Provisioning überspringt die ganze Gruppe (weder Meta noch ACF-Feld).
	array(
		'name'      => 'facebook_profile',
		'key'       => 'field_64ae7271f4014',
		'label'     => 'Facebook profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'linkedin_profile',
		'key'       => 'field_64ae7284f4015',
		'label'     => 'LinkedIn Profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'instagram_profile',
		'key'       => 'field_64ae7293f4016',
		'label'     => 'Instagram profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'twitter_profile',
		'key'       => 'field_64ae72a5f4017',
		'label'     => 'Twitter Profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'youtube_profile',
		'key'       => 'field_64ae72b4f4018',
		'label'     => 'Youtube Profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'website_profile',
		'key'       => 'field_64aea8f7e08c9',
		'label'     => 'Website Profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	// acf_type 'email' → Sanitize sanitize_email; ACF-Editor-Feld rendert als 'text'
	// (Field_Provisioner::build_acf_field mappt email→text, wie live exportiert).
	array(
		'name'      => 'email_profile',
		'key'       => 'field_64aea907e08ca',
		'label'     => 'Email profile',
		'acf_type'  => 'email',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_social',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),

	// ───────────────────────── KATEGORIE-SCHEMA (Term-Meta, taxonomy==category) ─────────────────────────
	// WPRM-Rezept-ID je Kategorie → speist die CollectionPage (Collection_Schema).
	array(
		'name'      => 'WPRM',
		'key'       => 'field_653f9974e2ac1',
		'label'     => 'WPRM ID',
		'acf_type'  => 'text',
		'object'    => array( 'term' ),
		'subtypes'  => array( 'term' => array( 'category' ) ),
		'group'     => 'kategorie_schema',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),

	// ───────────────────────── REVIEW (Post-Meta, unterstützte Post-Types) ─────────────────────────
	// post-Subtypes werden in Provisioning\Fields dynamisch überschrieben (ADR-4).
	array(
		'name'      => 'reviewed_by',
		'key'       => 'field_64a3ef30b7aa7',
		'label'     => 'Reviewed by',
		'acf_type'  => 'user',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'post' ) ),
		'group'     => 'review',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'role'          => array( 'contributor', 'author', 'editor', 'administrator' ),
			'return_format' => 'id',
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
);
