<?php
/**
 * Field-Registry — Single Source of Truth für alle migrierten Custom Fields.
 *
 * Ein Eintrag pro Meta-Key (BRIEF meta-registry § 4.1/§ 4.3). Treibt BEIDE Seiten des
 * Doppel-Owner-Patterns (§ 3.2/§ 3.3):
 *   - Field_Registrar liest `name`/`acf_type`/`object`/`subtypes`/`default` → register_*_meta
 *     (Sanitize + REST-Schema werden in Field_Registrar aus `acf_type` abgeleitet, § 4.2).
 *   - Group_Registrar sammelt Einträge per `group` + nutzt `key`/`label`/`acf_type`/`acf`
 *     → acf_add_local_field_group.
 *
 * Struktur eines Eintrags:
 *   - name       string  Meta-Key (= ACF-Field-Name, ADR-5/E5 strikt identisch zur Live-Site).
 *   - key        string  ACF-Field-Key aus der Discovery (Reuse → ACF-local-Override, § 4.5).
 *   - label      string  Editor-Label.
 *   - acf_type   string  text|url|wysiwyg|number|true_false|select|post_object|user|taxonomy|link.
 *   - object     array   Ziel-Objekte: ('post'), ('user'), ('term') oder ('post','term').
 *   - subtypes   array   Map object_type → Liste der Subtypes (post_types bzw. taxonomies).
 *                        User ist global → leer. (BRIEF § 3.4: migrierte Locations, keine Feature-Logik.)
 *   - group      mixed   ACF-Group-Key (string) oder mehrere (array) für geteilte Felder.
 *   - default    mixed   Default-Wert ('' bzw. array() für Mehrfachfelder).
 *   - editor_ui  bool    false = nur register_*_meta, KEIN ACF-Editor-Feld (Orphan, § 4.5).
 *   - acf        array   Typ-spezifische ACF-Field-Settings (choices/min/max/return_format/…),
 *                        werden von Group_Registrar in das ACF-Field-Array gemerged.
 *   - group_overrides array  optional: pro-Group-Override einzelner acf-Settings (§ 4.5 Newsletter).
 *
 * Dead-Code-Felder (5× rezept_* ohne DB-Werte, P1 § 4.3) sind bewusst NICHT enthalten.
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	// ───────────────────────── USER-META (Group author_fields, user_role==all) ─────────────────────────
	array(
		'name'      => 'same_as',
		'key'       => 'field_652420bdd23a0',
		'label'     => 'SameAs',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	array(
		'name'      => 'author_knowabout',
		'key'       => 'field_64a6871ef9004',
		'label'     => 'Knows about 1',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	// `_5` wird von den Schema-Konsumenten nicht gelesen (Loops nur _1.._4, P1 § 4.1) –
	// trotzdem registriert (billig, falls Daten vorhanden).
	array(
		'name'      => 'author_knowabout_5',
		'key'       => 'field_64a7c014f93ab',
		'label'     => 'Knows about 5',
		'acf_type'  => 'text',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'tabs'         => 'all',
			'toolbar'      => 'full',
			'media_upload' => 1,
		),
	),
	array(
		'name'      => 'facebook_profile',
		'key'       => 'field_64ae7271f4014',
		'label'     => 'Facebook profile',
		'acf_type'  => 'url',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
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
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	// ACF-Typ ist text, Sanitize aber sanitize_email (semantisch E-Mail) – Field_Registrar
	// behandelt 'email' als eigenen acf_type-Alias mit sanitize_email + string-Schema.
	array(
		'name'      => 'email_profile',
		'key'       => 'field_64aea907e08ca',
		'label'     => 'Email profile',
		'acf_type'  => 'email',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		// acf_type 'email' → Sanitize sanitize_email (Field_Registrar); ACF-Editor-Feld bleibt
		// 'text' wie live exportiert (acf_type→field_type-Map im Group_Registrar: email→text).
		'acf'       => array(),
	),
	array(
		'name'      => 'static_page_for_author',
		'key'       => 'field_6523ed75c2965',
		'label'     => 'Seite 1',
		'acf_type'  => 'post_object',
		'object'    => array( 'user' ),
		'subtypes'  => array(),
		'group'     => 'author_fields',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'post_type'     => array( 'page' ),
			'post_status'   => array( 'private' ),
			'return_format' => 'object',
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),

	// ───────────────────────── TERM-META ─────────────────────────
	// Kategorie-Custom (taxonomy==category; Live-Location "all" → category, § 4.5).
	array(
		'name'      => 'static_page',
		'key'       => 'field_6516c1ef9cfc3',
		'label'     => 'Seite 1',
		'acf_type'  => 'post_object',
		'object'    => array( 'term' ),
		'subtypes'  => array( 'term' => array( 'category' ) ),
		'group'     => 'kategorie_custom',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'post_type'     => array( 'page' ),
			'return_format' => 'id',
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
	array(
		'name'      => 'WPRM',
		'key'       => 'field_653f9974e2ac1',
		'label'     => 'WPRM ID',
		'acf_type'  => 'text',
		'object'    => array( 'term' ),
		'subtypes'  => array( 'term' => array( 'category' ) ),
		'group'     => 'kategorie_custom',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(),
	),
	// Tag-Einstellungen (taxonomy==post_tag).
	array(
		'name'      => 'tag_group',
		'key'       => 'field_tag_group',
		'label'     => 'Tag-Gruppe',
		'acf_type'  => 'select',
		'object'    => array( 'term' ),
		'subtypes'  => array( 'term' => array( 'post_tag' ) ),
		'group'     => 'tag_settings',
		'default'   => 'zutaten',
		'editor_ui' => true,
		'acf'       => array(
			'required'      => 1,
			'choices'       => array(
				'anlass'          => 'Anlass',
				'zubereitung'     => 'Zubereitung',
				'zutaten'         => 'Zutaten',
				'saisonales'      => 'Saisonales',
				'ernaehrung_ziel' => 'Ernährung & Ziel',
				'herkunft'        => 'Herkunft',
			),
			'default_value' => 'zutaten',
			'return_format' => 'value',
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),

	// ───────────────────────── POST-META ─────────────────────────
	array(
		'name'      => 'reviewed_by',
		'key'       => 'field_64a3ef30b7aa7',
		'label'     => 'Reviewed by',
		'acf_type'  => 'user',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'post', 'page' ) ),
		'group'     => 'reviewed_by',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'role'          => array( 'contributor', 'author', 'editor', 'administrator' ),
			'return_format' => 'id',
			'allow_null'    => 0,
			'multiple'      => 0,
		),
	),
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
	// Newsletter-Overrides: 1 Meta-Registrierung je Key, 2 ACF-Groups (pages/cpt, § 4.5).
	array(
		'name'      => 'show_newsletter_form',
		'key'       => 'field_show_newsletter',
		'label'     => 'Newsletter-Formular anzeigen',
		'acf_type'  => 'true_false',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page', 'blog', 'tests' ) ),
		'group'     => array( 'newsletter_pages', 'newsletter_cpt' ),
		'default'   => true,
		'editor_ui' => true,
		'acf'       => array(
			'ui'            => 1,
			'default_value' => 1,
		),
	),
	array(
		'name'      => 'newsletter_position',
		'key'       => 'field_newsletter_position',
		'label'     => 'Newsletter Position',
		'acf_type'  => 'number',
		'object'    => array( 'post' ),
		'subtypes'  => array( 'post' => array( 'page', 'blog', 'tests' ) ),
		'group'     => array( 'newsletter_pages', 'newsletter_cpt' ),
		'default'   => 4,
		'editor_ui' => true,
		'acf'       => array(
			'default_value'     => 4,
			'min'               => 1,
			'max'               => 20,
			'step'              => 1,
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
	// show_app_promo: Default 1 auf Pages, 0 auf CPT (group_overrides, § 4.5).
	array(
		'name'            => 'show_app_promo',
		'key'             => 'field_show_app_promo',
		'label'           => 'App-Promotion anzeigen',
		'acf_type'        => 'true_false',
		'object'          => array( 'post' ),
		'subtypes'        => array( 'post' => array( 'page', 'blog', 'tests' ) ),
		'group'           => array( 'newsletter_pages', 'newsletter_cpt' ),
		'default'         => true,
		'editor_ui'       => true,
		'acf'             => array(
			'ui'            => 1,
			'default_value' => 1,
		),
		'group_overrides' => array(
			'newsletter_cpt' => array(
				'default_value' => 0,
			),
		),
	),

	// ───────────────────────── MIXED: POST + TERM (Übersetzungen) ─────────────────────────
	array(
		'name'      => 'link_de',
		'key'       => 'field_5f29e0d3a8543',
		'label'     => 'link_de',
		'acf_type'  => 'link',
		'object'    => array( 'post', 'term' ),
		'subtypes'  => array(
			'post' => array( 'post', 'page', 'tests', 'blog' ),
			'term' => array( 'category' ),
		),
		'group'     => 'uebersetzungen',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'instructions'  => 'Deutsche URL',
			'return_format' => 'url',
		),
	),
	array(
		'name'      => 'link_en',
		'key'       => 'field_5f29e36e1144d',
		'label'     => 'link_en',
		'acf_type'  => 'link',
		'object'    => array( 'post', 'term' ),
		'subtypes'  => array(
			'post' => array( 'post', 'page', 'tests', 'blog' ),
			'term' => array( 'category' ),
		),
		'group'     => 'uebersetzungen',
		'default'   => '',
		'editor_ui' => true,
		'acf'       => array(
			'instructions'  => 'Englische URL',
			'return_format' => 'url',
		),
	),
);
