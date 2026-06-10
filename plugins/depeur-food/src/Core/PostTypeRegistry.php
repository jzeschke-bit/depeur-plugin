<?php
/**
 * Quelle der Wahrheit für die unterstützten Post-Types (ADR-4).
 *
 * @package Depeur\Food\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolver-Pattern (NICHT Registrar): zentralisiert ADR-4 – „welche Post-Types
// unterstützt das Plugin?". Diese Klasse registriert selbst KEINE Post-Types
// (CPTs wie cocktail bringt die Site bzw. ein anderes Plugin vor der Aktivierung
// mit), sie löst nur die konfigurierte Liste auf. Bewusst Instanz-Singleton wie
// Plugin (nicht statisch wie SettingsRegistry), weil ein per-Request-Memo
// gehalten wird. get_supported() ist self-contained/lazy: es funktioniert auch
// ohne vorherige Plugin::init()-Verdrahtung, damit ein früher Helper-Aufruf
// (depeur_food()->get_supported_post_types()) nie auf eine unfertige Registry
// trifft (Init-Order-Schutz).
/**
 * Klasse PostTypeRegistry.
 *
 * @since 0.1.0
 */
final class PostTypeRegistry {

	/**
	 * Kanonischer Options-Key der unterstützten Post-Types (ADR-4). Default array( 'post' ).
	 *
	 * Public, damit externe Konsumenten (Tests, WP-CLI, künftige Migrations-Module)
	 * PostTypeRegistry::OPTION referenzieren, statt den String zu duplizieren.
	 *
	 * @var string
	 */
	public const OPTION = 'depeur_food_supported_post_types';

	/**
	 * Einzige Instanz (Singleton).
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Per-Request-Memo der aufgelösten Liste. null = noch nicht aufgelöst
	 * (nicht: keine Typen). Statisch, damit der statische flush() es verwerfen kann.
	 *
	 * @var string[]|null
	 */
	private static ?array $supported = null;

	/**
	 * Privater Konstruktor erzwingt den Singleton-Zugriff über get_instance().
	 */
	private function __construct() {}

	/**
	 * Liefert die Singleton-Instanz und erzeugt sie lazy beim ersten Aufruf.
	 *
	 * Bewusst ohne Abhängigkeit zu Plugin::init(), damit frühe Aufrufe des
	 * globalen Helpers nie auf eine noch nicht verdrahtete Registry treffen.
	 *
	 * @since 0.1.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Kanonische Liste der effektiv unterstützten Post-Types (ADR-4).
	 *
	 * Liest die Option self::OPTION (Default array( 'post' )), reicht sie durch den
	 * Filter depeur_food/post_types, normalisiert auf nicht-leere String-Slugs und
	 * garantiert als Graceful-Default mindestens array( 'post' ). Das Ergebnis wird
	 * pro Request memoisiert, weil es in vielen Hooks wiederholt gebraucht wird.
	 * Plugin::get_supported_post_types() delegiert zustandslos hierher.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] Liste der Post-Type-Slugs, mindestens array( 'post' ).
	 */
	public function get_supported(): array {
		// Memo: nach erster Auflösung pro Request nicht erneut lesen/filtern.
		if ( null !== self::$supported ) {
			return self::$supported;
		}

		$saved = get_option( self::OPTION, array( 'post' ) );

		// Filter ursprünglich in Plugin::get_supported_post_types() definiert,
		// mit Task 2 hierher umgezogen (kein neuer Hook).
		/**
		 * Filtert die Liste der unterstützten Post-Types.
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $post_types Aktuell konfigurierte Post-Type-Slugs.
		 */
		$post_types = apply_filters( 'depeur_food/post_types', (array) $saved );

		// Auf nicht-leere String-Slugs normalisieren und Indizes neu setzen.
		$post_types = array_values( array_filter( array_map( 'strval', $post_types ) ) );

		// Graceful Default: niemals mit leerer Liste zurückkommen (ADR-4).
		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		self::$supported = $post_types;

		return self::$supported;
	}

	/**
	 * Verfügbare öffentliche Post-Types als UI-Optionen für das Multi-Checkbox.
	 *
	 * NICHT mit get_supported() verwechseln: „verfügbar" (alles Öffentliche der
	 * Installation) ≠ „konfiguriert" (die in der Option gespeicherte Auswahl).
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, string> Map slug => Anzeige-Label.
	 */
	public function get_available(): array {
		// Öffentliche Typen als Objekte holen, um an die Labels zu kommen.
		$objects = get_post_types( array( 'public' => true ), 'objects' );

		// attachment wird im UI ausgeblendet – Medien-Anhänge werden nicht von
		// Modulen wie Schema/Favoriten/Recipe verarbeitet, das wäre UI-Noise. Die
		// Option selbst akzeptiert weiterhin attachment, falls programmatisch
		// gesetzt (ADR-4 post-type-agnostisch). Künftiger UI-Bedarf wird über einen
		// Filter auf dieses Ergebnis gelöst, nicht durch Entfernen des unset().
		unset( $objects['attachment'] );

		$out = array();

		foreach ( $objects as $slug => $object ) {
			$out[ $slug ] = isset( $object->labels->name ) ? $object->labels->name : $slug;
		}

		return $out;
	}

	/**
	 * Verwirft das Memo, sodass der nächste get_supported() frisch liest.
	 *
	 * Aufruf nach jedem Schreibpfad auf die Option (SettingsPage-Save, WP-CLI,
	 * REST, Migrations-Module), damit ein Re-Read im selben Request nicht veraltet.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$supported = null;
	}
}
