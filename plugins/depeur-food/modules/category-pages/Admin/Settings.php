<?php
/**
 * Admin/Settings — Settings-Tab des category-pages-Moduls (globale Defaults).
 *
 * Meldet via SettingsRegistry einen Tab „Kategorie-Seiten" an: die site-weiten Standard-Mengen
 * „Beiträge auf Seite 1" und „Beiträge ab Seite 2". Einzelne Kategorie-Seiten können diese über
 * ihre eigenen Felder (df_catpage_per_page_first / df_catpage_per_page) überschreiben; ist dort
 * nichts gesetzt, greifen diese Defaults (statt der harten 4/21).
 *
 * @package Depeur\Food\Modules\CategoryPages\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den category-pages-Settings-Tab.
 *
 * @since 0.3.0
 */
final class Settings {

	/**
	 * Modul-Slug (Options-/Tab-Kontext), von module.php hereingereicht.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab an.
	 *
	 * @since 0.3.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Kategorie-Seiten', 'depeur-food' ),
			array(
				array(
					'id'          => 'default_per_page_first',
					'label'       => __( 'Beiträge auf Seite 1 (Standard)', 'depeur-food' ),
					'type'        => 'text',
					'default'     => '4',
					'description' => __( 'Standard-Anzahl der Vorschau-Beiträge auf Seite 1, wenn die Seite nichts Eigenes vorgibt. Legacy: 4.', 'depeur-food' ),
				),
				array(
					'id'          => 'default_per_page',
					'label'       => __( 'Beiträge ab Seite 2 (Standard)', 'depeur-food' ),
					'type'        => 'text',
					'default'     => '21',
					'description' => __( 'Standard-Anzahl der Beiträge je Folgeseite, wenn die Seite nichts Eigenes vorgibt. Legacy: 21.', 'depeur-food' ),
				),
			),
			__( 'Globale Standardwerte für Kategorie-Seiten. Eine Übersicht aller Kategorie-Seiten liegt unter „Kategorie-Seiten". Einzelne Seiten überschreiben diese Defaults über ihre eigenen Felder.', 'depeur-food' )
		);
	}
}
