<?php
/**
 * Author_Schema — reichert das Rank-Math-Autor-Objekt aus Autor-Meta an.
 *
 * Portiert zwei Legacy-Anreicherungen zu einer Klasse:
 *   - `rank-math.php` → `custom_rankmath_schema_data` (Filter
 *     rank_math/snippet/rich_snippet_article_entity): jobTitle, alumniOf, knowsAbout am
 *     Article-Entity-Autor.
 *   - `category-schema.php` → `custom_wprm_author_metadata` (Filter wprm_recipe_metadata):
 *     jobTitle, url, description, alumniOf, knowsAbout, sameAs am WPRM-Rezept-Autor.
 *
 * Beide teilen sich EINE Quelle (build_author_schema), gelesen ausschließlich via
 * get_user_meta / get_the_author_meta (ADR-5 – wir hängen am Meta-Key, nie an ACF get_field).
 * sameAs vereint same_as + same_as_2 + die befüllten Social-Profil-URLs (Erweiterung, s.
 * Klassen-Kommentar unten). Rank Math ist Hard-Dependency (E1): ohne aktives Rank Math
 * werden keine Hooks gesetzt.
 *
 * @package Depeur\Food\Modules\SchemaEngine\Schema
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Schema;

use Depeur\Food\Modules\SchemaEngine\Support\Dependencies;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verdrahtet die Autor-Schema-Anreicherung an den Rank-Math-/WPRM-Filtern.
 *
 * @since 0.2.0
 */
final class Author_Schema {

	/**
	 * knowsAbout-Meta-Keys in Ausgabereihenfolge (1–4, wie beide Legacy-Konsumenten).
	 *
	 * `author_knowabout_5` existiert live, wurde aber nie ins Schema gezogen (acf-discovery
	 * § 4.1) → bewusst nicht enthalten (faithful port).
	 *
	 * @since 0.2.0
	 * @var string[]
	 */
	private const KNOWS_ABOUT_KEYS = array(
		'author_knowabout',
		'author_knowabout_2',
		'author_knowabout_3',
		'author_knowabout_4',
	);

	/**
	 * Social-Profil-URL-Meta-Keys, die in sameAs einfließen (nur befüllte).
	 *
	 * `email_profile` ist eine E-Mail, keine Profil-URL → NICHT in sameAs (s. Klassen-Kommentar).
	 *
	 * @since 0.2.0
	 * @var string[]
	 */
	private const SOCIAL_URL_KEYS = array(
		'facebook_profile',
		'linkedin_profile',
		'instagram_profile',
		'twitter_profile',
		'youtube_profile',
		'website_profile',
	);

	/**
	 * Setzt die Filter – nur bei aktivem Rank Math (E1, sonst Ruhe).
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		if ( ! Dependencies::rank_math_active() ) {
			return;
		}

		// Article-Entity-Autor (Rank Math). Prio 20 wie im Legacy-Theme.
		add_filter( 'rank_math/snippet/rich_snippet_article_entity', array( $this, 'enrich_article_entity' ), 20 );

		// WPRM-Rezept-Autor. Feuert nur bei aktivem WPRM; Prio 10 / 2 Argumente wie Legacy.
		add_filter( 'wprm_recipe_metadata', array( $this, 'enrich_wprm_author' ), 10, 2 );
	}

	/**
	 * Reichert das Article-Entity-Autor-Objekt an (Rank Math).
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $entity Rank-Math-Entity-Array.
	 * @return mixed Angereichertes Entity (unverändert, wenn kein author-Objekt vorliegt).
	 */
	public function enrich_article_entity( $entity ) {
		if ( ! is_array( $entity ) || ! isset( $entity['author'] ) || ! is_array( $entity['author'] ) ) {
			return $entity;
		}

		$author_id = (int) get_post_field( 'post_author', get_the_ID() );
		if ( $author_id <= 0 ) {
			return $entity;
		}

		$schema = $this->build_author_schema( $author_id );

		if ( '' !== $schema['jobTitle'] ) {
			$entity['author']['jobTitle'] = $schema['jobTitle'];
		}
		if ( null !== $schema['alumniOf'] ) {
			$entity['author']['alumniOf'] = $schema['alumniOf'];
		}
		if ( ! empty( $schema['knowsAbout'] ) ) {
			$entity['author']['knowsAbout'] = $schema['knowsAbout'];
		}
		// sameAs am Entity ist eine Erweiterung gegenüber dem Legacy-Theme (das es nur am
		// WPRM-Rezept setzte) – vom Task ausdrücklich gewünscht.
		if ( ! empty( $schema['sameAs'] ) ) {
			$entity['author']['sameAs'] = $schema['sameAs'];
		}

		return $entity;
	}

	/**
	 * Reichert den Autor eines WPRM-Rezepts an (category-schema.php-Port).
	 *
	 * @since 0.2.0
	 *
	 * @param mixed $metadata WPRM-Rezept-Metadaten-Array.
	 * @param mixed $recipe   WPRM-Recipe-Objekt (WPRM_Recipe).
	 * @return mixed Angereicherte Metadaten.
	 */
	public function enrich_wprm_author( $metadata, $recipe ) {
		if ( ! is_array( $metadata ) || ! isset( $metadata['author'] ) ) {
			return $metadata;
		}
		// Soft-Dependency-Schutz: ohne brauchbares Recipe-Objekt nichts tun.
		if ( ! is_object( $recipe ) || ! method_exists( $recipe, 'ID' ) ) {
			return $metadata;
		}

		$author_id = (int) get_post_field( 'post_author', $recipe->ID() );
		if ( $author_id <= 0 ) {
			return $metadata;
		}

		$schema = $this->build_author_schema( $author_id );

		// jobTitle/url/description setzt der Legacy-Code unbedingt – Verhalten beibehalten.
		$metadata['author']['jobTitle']    = $schema['jobTitle'];
		$metadata['author']['url']         = $schema['url'];
		$metadata['author']['description'] = $schema['description'];

		if ( null !== $schema['alumniOf'] ) {
			$metadata['author']['alumniOf'] = $schema['alumniOf'];
		}
		if ( ! empty( $schema['knowsAbout'] ) ) {
			$metadata['author']['knowsAbout'] = $schema['knowsAbout'];
		}
		if ( ! empty( $schema['sameAs'] ) ) {
			$metadata['author']['sameAs'] = $schema['sameAs'];
		}

		return $metadata;
	}

	/**
	 * Sammelt alle Autor-Schema-Bausteine aus der User-Meta (Single Source für beide Filter).
	 *
	 * Lesequellen (ADR-5, Meta-Key-gebunden):
	 *   - jobTitle/alumniOf/knowsAbout/sameAs/socials: get_user_meta (ACF-provisionierte Keys).
	 *   - url:         get_author_posts_url (WP-Kern).
	 *   - description: get_the_author_meta('description') = WP-Kern-Biografie. Das entspricht
	 *                  dem Legacy (category-schema.php:77) — das ACF-Feld `author_description`
	 *                  wird zwar provisioniert, vom Schema aber (wie im Legacy) NICHT gelesen.
	 *
	 * @since 0.2.0
	 *
	 * @param int $author_id Autor-User-ID.
	 * @return array{jobTitle:string,url:string,description:string,alumniOf:?array,knowsAbout:string[],sameAs:string[]}
	 */
	private function build_author_schema( int $author_id ): array {
		$job_title   = (string) get_user_meta( $author_id, 'author_jobtitle', true );
		$alumni_name = (string) get_user_meta( $author_id, 'author_alumniof', true );
		$alumni_url  = (string) get_user_meta( $author_id, 'author_alumniof_url', true );
		$author_url  = (string) get_author_posts_url( $author_id );
		$description = (string) get_the_author_meta( 'description', $author_id );

		// alumniOf: strukturiert, sobald ein Name vorliegt (sameAs nur bei vorhandener URL).
		// Vereinheitlicht die zwei Legacy-Varianten (Theme verlangte Name+URL, category-schema
		// setzte es auch ohne URL) auf „Name genügt".
		$alumni_of = null;
		if ( '' !== $alumni_name ) {
			$alumni_of = array(
				'@type' => 'EducationalOrganization',
				'name'  => $alumni_name,
			);
			if ( '' !== $alumni_url ) {
				$alumni_of['sameAs'] = $alumni_url;
			}
		}

		return array(
			'jobTitle'    => $job_title,
			'url'         => $author_url,
			'description' => $description,
			'alumniOf'    => $alumni_of,
			'knowsAbout'  => $this->collect_meta_values( $author_id, self::KNOWS_ABOUT_KEYS ),
			'sameAs'      => $this->collect_same_as( $author_id ),
		);
	}

	/**
	 * Baut die sameAs-Liste: same_as + same_as_2 + befüllte Social-Profil-URLs.
	 *
	 * @since 0.2.0
	 *
	 * @param int $author_id Autor-User-ID.
	 * @return string[] Nicht-leere Werte in stabiler Reihenfolge.
	 */
	private function collect_same_as( int $author_id ): array {
		$keys = array_merge( array( 'same_as', 'same_as_2' ), self::SOCIAL_URL_KEYS );
		return $this->collect_meta_values( $author_id, $keys );
	}

	/**
	 * Liest mehrere Meta-Keys und liefert die nicht-leeren Werte (Reihenfolge = $keys).
	 *
	 * @since 0.2.0
	 *
	 * @param int      $author_id Autor-User-ID.
	 * @param string[] $keys      Meta-Keys.
	 * @return string[]
	 */
	private function collect_meta_values( int $author_id, array $keys ): array {
		$values = array();

		foreach ( $keys as $key ) {
			$value = (string) get_user_meta( $author_id, $key, true );
			if ( '' !== $value ) {
				$values[] = $value;
			}
		}

		return $values;
	}
}
