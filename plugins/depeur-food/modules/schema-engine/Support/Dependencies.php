<?php
/**
 * Dependencies — zentrale Erkennung von Rank Math (Hard-Dep) und WPRM (Soft-Dep).
 *
 * Reine statische Helfer, keine Hooks, keine Instanz. Ein einziger Ort für die
 * class_exists-Checks, damit Schema- und Admin-Klassen konsistent dieselbe Definition
 * von „aktiv" nutzen (§ 2.5 Wartbarkeit).
 *
 * @package Depeur\Food\Modules\SchemaEngine\Support
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Support;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Erkennt die (optionalen) Schema-Provider im WordPress-Kontext.
 *
 * @since 0.2.0
 */
final class Dependencies {

	/**
	 * Ist Rank Math SEO aktiv? (E1: Hard-Dependency der Schema-Anreicherung.)
	 *
	 * Rank Math free wie Pro definieren die Haupt-Klasse `RankMath`. Ohne sie feuern die
	 * rank_math/*-Filter nie – die Schema-Klassen setzen dann gar keine Hooks (Ruhe).
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function rank_math_active(): bool {
		return class_exists( 'RankMath' );
	}

	/**
	 * Ist WP Recipe Maker aktiv? (E2: Soft-Dependency – Rezept-Metadaten optional.)
	 *
	 * Geprüft wird der Recipe-Manager, den sowohl die CollectionPage- als auch die
	 * WPRM-Author-Anreicherung brauchen. Fehlt WPRM, überspringen die Konsumenten den
	 * Rezept-Teil sauber (graceful skip), statt zu fataln.
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function wprm_active(): bool {
		return class_exists( 'WPRM_Recipe_Manager' );
	}
}
