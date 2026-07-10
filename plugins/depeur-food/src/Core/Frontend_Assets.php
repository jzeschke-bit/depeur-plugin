<?php
/**
 * Frontend_Assets — Core-Frontend-Styles des Plugins.
 *
 * Aktuell: das gemeinsame Loop_Grid-Stylesheet (gleich hohe Karten). Bewusst im Core, weil
 * Loop_Grid ein Core-Helfer ist, dessen Raster in mehreren Modulen (favorites, category-pages)
 * erscheint — so muss die Karten-Optik nur einmal definiert/geladen werden. Sehr schlankes CSS,
 * daher frontend-weit registriert.
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
 * Registriert die Core-Frontend-Assets.
 *
 * @since 0.3.0
 */
final class Frontend_Assets {

	/**
	 * Style-Handle.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const HANDLE = 'depeur-food-loop-grid';

	/**
	 * Hängt den Enqueue in den Frontend-Hook.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Registriert das Loop-Grid-Stylesheet.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		$file = DEPEUR_FOOD_PATH . 'assets/df-loop-grid.css';
		$ver  = is_file( $file ) ? (string) filemtime( $file ) : DEPEUR_FOOD_VERSION;

		wp_enqueue_style( self::HANDLE, DEPEUR_FOOD_URL . 'assets/df-loop-grid.css', array(), $ver );
	}
}
