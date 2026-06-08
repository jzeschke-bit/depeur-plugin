<?php
/**
 * Core-Plugin-Klasse: Singleton, Initialisierung, Post-Type-Quelle. Keine Fachlogik.
 *
 * @package Depeur\Food\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse Plugin.
 *
 * Singleton-Accessor des Plugins. Bewusste Abweichung von der Depeur-WP-Suite-Vorlage:
 * Die Suite kommt mit rein statischen Methoden aus, depeur-food braucht dagegen einen
 * instanzbasierten Zugriff, damit ADR-4 (post-type-agnostische Quelle der Wahrheit) über
 * den globalen Helper depeur_food()->get_supported_post_types() von jedem Modul aufrufbar
 * ist. Instanziiert wird ausschließlich über get_instance(); der Konstruktor ist privat.
 *
 * @since 0.1.0
 */
final class Plugin {

	/**
	 * Einzige Instanz (Singleton).
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Memoisierte Liste der unterstützten Post-Types.
	 *
	 * Leeres Array = noch nicht aufgelöst (nicht: keine Typen) – siehe
	 * get_supported_post_types() für die Lazy-Auflösung.
	 *
	 * @var string[]
	 */
	private array $supported_post_types = array();

	/**
	 * Privater Konstruktor erzwingt den Singleton-Zugriff über get_instance().
	 */
	private function __construct() {}

	/**
	 * Liefert die Singleton-Instanz und erzeugt sie beim ersten Aufruf.
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
	 * Einmalige Initialisierung am init-Hook.
	 *
	 * Bootet das Singleton. Die Verdrahtung von ModuleManager, AdminMenu und
	 * Settings-Registry gehört in Task 2 (Core-Klassen) und wird hier bewusst NICHT
	 * vorweggenommen – dieser Bootstrap soll ohne weitere Klassen lauffähig und
	 * aktivierbar sein.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Quelle der Wahrheit für die vom Plugin unterstützten Post-Types (ADR-4).
	 *
	 * Liest die in den Core-Settings gespeicherte Liste (Option
	 * depeur_food_supported_post_types, Default array( 'post' )) und reicht sie durch den
	 * Filter depeur_food/post_types, damit Sites weitere CPTs ergänzen können, ohne
	 * Plugin-Code zu ändern. Kein Modul darf Post-Types hardcoden – stattdessen immer über
	 * diese Methode gehen. Das Ergebnis wird pro Request memoisiert, weil es in vielen Hooks
	 * wiederholt gebraucht wird.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] Liste der Post-Type-Slugs, mindestens array( 'post' ).
	 */
	public function get_supported_post_types(): array {
		if ( ! empty( $this->supported_post_types ) ) {
			return $this->supported_post_types;
		}

		$saved = get_option( 'depeur_food_supported_post_types', array( 'post' ) );

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

		// Graceful Default: niemals mit leerer Liste zurückkommen.
		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		$this->supported_post_types = $post_types;

		return $this->supported_post_types;
	}
}
