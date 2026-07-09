<?php
/**
 * Core-Plugin-Klasse: Singleton, Initialisierung, Post-Type-Quelle. Keine Fachlogik.
 *
 * @package Depeur\Food\Core
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core;

use Depeur\Food\Core\Settings\SettingsPage;

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
	 * Bootet das Singleton und verdrahtet die Core-Klassen (Task 2): den
	 * Modul-Loader und – nur im Backend – Menü plus Settings-Save-Handler.
	 * PostTypeRegistry (ADR-4) braucht hier KEINE eager Verdrahtung; es ist ein
	 * lazy, self-contained Singleton, das beim ersten get_supported() auflöst.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function init(): void {
		self::get_instance();

		// Aktive Module laden (modules/ ist in dieser Phase leer → lädt nichts).
		ModuleManager::init();

		// Gemeinsame Frontend-Styles (Loop_Grid: gleich hohe Karten).
		Frontend_Assets::register();

		// Admin-spezifische Verdrahtung nur im Backend.
		if ( is_admin() ) {
			AdminMenu::register();

			// Modul-Verwaltungsseite (Aktivieren/Deaktivieren per UI, Task 4b).
			ModulesPage::register();

			// Geführter Readiness-Check für den Theme-Cutover (Alt-Theme → kadence-child).
			Migration_Assistant::register();

			// Save-Handler der Settings-Seite an admin_init hängen – muss vor dem
			// Rendern laufen, damit der PRG-Redirect nach dem Speichern greift.
			add_action( 'admin_init', array( SettingsPage::class, 'maybe_handle_save' ) );
		}
	}

	/**
	 * Quelle der Wahrheit für die vom Plugin unterstützten Post-Types (ADR-4).
	 *
	 * Delegiert zustandslos an PostTypeRegistry::get_supported(), das die Option
	 * depeur_food_supported_post_types liest, durch den Filter depeur_food/post_types
	 * reicht, normalisiert und als Graceful-Default mindestens array( 'post' )
	 * garantiert. Die Logik samt Memo wurde mit Task 2 nach PostTypeRegistry
	 * verlagert (eine Quelle der Wahrheit); diese Methode bleibt als stabiler,
	 * von jedem Modul nutzbarer Helfer-Einstieg erhalten.
	 *
	 * @since 0.1.0
	 *
	 * @return string[] Liste der Post-Type-Slugs, mindestens array( 'post' ).
	 */
	public function get_supported_post_types(): array {
		return PostTypeRegistry::get_instance()->get_supported();
	}
}
