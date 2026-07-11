<?php
/**
 * Like_Counter — Datenschicht des Favoriten-Moduls: der globale Like-Zähler pro Post.
 *
 * Registriert den (protected) Meta-Key `_my_favorite_post_likes` via den Core-
 * Field_Provisioner: meta-only (keine ACF-Editor-UI), Ganzzahl, REST-sichtbar, mit
 * auth_callback (protected Keys brauchen ihn für REST/Editor). Post-type-agnostisch
 * (ADR-4): die Ziel-Post-Types kommen aus depeur_food()->get_supported_post_types()
 * und sind über den Filter depeur_food/favorites/post_types anpassbar.
 *
 * Zusätzlich die zentrale Lese-/Schreib-Fassade für den Zähler (get_likes/set_likes),
 * die der REST-Controller, die Shortcodes und die Diagnose nutzen – eine Quelle der
 * Wahrheit für den Meta-Key und die Clamp-auf-0-Regel.
 *
 * @package Depeur\Food\Modules\Favorites\Meta
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Meta;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und kapselt den Like-Zähler-Meta-Key.
 *
 * @since 0.2.0
 */
final class Like_Counter {

	/**
	 * Meta-Key des Like-Zählers. Führender Unterstrich = protected Key (aus dem Legacy
	 * 1:1 übernommen, damit bestehende wp_postmeta-Werte weiter zählen).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const META_KEY = '_my_favorite_post_likes';

	/**
	 * Filter, mit dem die vom Modul verarbeiteten Post-Types angepasst werden können.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const FILTER_POST_TYPES = 'depeur_food/favorites/post_types';

	/**
	 * Verdrahtet die Feld-Provisionierung (register_post_meta) über den Core-Provisioner.
	 *
	 * Der Field_Provisioner registriert selbst am init-Hook (bzw. sofort, wenn init schon
	 * lief) – hier nur die Deklaration bauen und übergeben. Keine ACF-Group (meta-only).
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$fields = array(
			array(
				'name'          => self::META_KEY,
				// Ganzzahliger Zähler → integer-Meta + absint-Sanitize mit Clamp min=0.
				'acf_type'      => 'number',
				'object'        => array( 'post' ),
				'subtypes'      => array( 'post' => self::post_types() ),
				// Meta-only: KEIN ACF-Editor-Feld (der Zähler ist keine redaktionelle Eingabe).
				'editor_ui'     => false,
				'default'       => 0,
				// Protected Key (führender _): braucht einen auth_callback, sonst wäre er für
				// REST/Editor gesperrt. Nur Redakteure dürfen ihn über die Core-Meta-Wege
				// ändern; der öffentliche Toggle läuft über den eigenen, geclampten Endpoint.
				'auth_callback' => array( self::class, 'auth_callback' ),
				'acf'           => array( 'min' => 0 ),
			),
		);

		new Field_Provisioner( $fields );
	}

	/**
	 * Liefert die vom Favoriten-Modul verarbeiteten Post-Types (post-type-agnostisch).
	 *
	 * Basis ist die zentrale ADR-4-Quelle; der Filter erlaubt Feintuning pro Site
	 * (z. B. Cocktails ein-, Seiten ausschließen), ohne den Core anzufassen.
	 *
	 * @since 0.2.0
	 *
	 * @return string[] Liste der Post-Type-Slugs (mindestens array( 'post' )).
	 */
	public static function post_types(): array {
		$types = depeur_food()->get_supported_post_types();

		// Optionaler Opt-in: Seiten (page) mit aufnehmen (Settings-Toggle, Default aus). Manche
		// Rezepte/Roundup-Items hängen an Seiten statt an Cocktail-Beiträgen; erst mit diesem
		// Toggle werden sie likebar (REST-Endpoint akzeptiert sie, Herz-Injektion inklusive).
		if ( self::pages_enabled() && ! in_array( 'page', $types, true ) ) {
			$types[] = 'page';
		}

		/**
		 * Filtert die Post-Types, für die Favoriten/Like-Zähler gelten.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $types Standardmäßig die unterstützten Post-Types (ADR-4).
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- FILTER_POST_TYPES ist depeur_food-prefixiert; der Sniff kann die Konstante nicht statisch prüfen.
		$types = apply_filters( self::FILTER_POST_TYPES, $types );

		// Defensiv normalisieren: nur nicht-leere String-Slugs, dedupliziert.
		$types = is_array( $types ) ? array_filter( array_map( 'strval', $types ) ) : array();

		return array_values( array_unique( $types ) );
	}

	/**
	 * Ist der Opt-in „Seiten (page) einbeziehen" im Favoriten-Settings-Tab aktiviert?
	 *
	 * Bewusst Default aus: sonst würden alle Seiten (About/Impressum/…) likebar. Der Toggle
	 * ist für Sites gedacht, deren Cocktail-/Rezept-Inhalte teils auf Seiten liegen.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	private static function pages_enabled(): bool {
		$option = get_option( SettingsRegistry::option_key( 'favorites' ), array() );

		return is_array( $option ) && ! empty( $option['include_pages'] );
	}

	/**
	 * Auth_callback für den protected Meta-Key.
	 *
	 * Gilt nur für die Core-Meta-Wege (REST-Meta-Update, Editor). Der öffentliche
	 * Toggle-Endpoint schreibt server-seitig via update_post_meta und ist davon
	 * unberührt. WordPress reicht weitere Argumente herein, die hier nicht gebraucht werden.
	 *
	 * @since 0.2.0
	 *
	 * @return bool True, wenn der aktuelle Nutzer Beiträge bearbeiten darf.
	 */
	public static function auth_callback(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Liest den aktuellen Like-Zähler eines Posts.
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post-ID.
	 * @return int Zählerstand (>= 0).
	 */
	public static function get_likes( int $post_id ): int {
		return max( 0, (int) get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * Setzt den Like-Zähler eines Posts (nie unter 0).
	 *
	 * @since 0.2.0
	 *
	 * @param int $post_id Post-ID.
	 * @param int $count   Neuer Zählerstand.
	 * @return int Der tatsächlich gespeicherte (geclampte) Wert.
	 */
	public static function set_likes( int $post_id, int $count ): int {
		$count = max( 0, $count );
		update_post_meta( $post_id, self::META_KEY, $count );

		return $count;
	}
}
