<?php
/**
 * Admin-Menü: Top-Level „Depeur Food“ mit einer Einstellungs-Unterseite.
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

// Bewusst schlank: ein Top-Level-Menü + genau eine Unterseite (Einstellungen),
// die die SettingsPage rendert. Die Tab-Navigation und die Modul-Settings rendert
// die SettingsPage selbst (Tabs via ?tab= unter demselben page-Parameter);
// AdminMenu bleibt bei einem Menü + einer Unterseite. MENU_SLUG ist zugleich der
// page-Parameter, auf den die SettingsPage verweist (Form-Action, PRG-Redirect);
// daher public und stabil zu halten.
/**
 * Klasse AdminMenu.
 *
 * @since 0.1.0
 */
final class AdminMenu {

	/**
	 * Slug des Top-Level-Menüs und zugleich page-Parameter der Settings-Seite.
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'depeur-food-settings';

	/**
	 * Hängt die Menü-Registrierung in den admin_menu-Hook.
	 *
	 * Wird aus Plugin::init() nur im Admin-Kontext aufgerufen. admin_menu ist der
	 * vorgesehene Zeitpunkt, zu dem WordPress Menüseiten erwartet.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
	}

	/**
	 * Registriert das Top-Level-Menü und die Einstellungs-Unterseite.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function add_menu_pages(): void {
		$cap = 'manage_options';

		add_menu_page(
			__( 'Depeur Food', 'depeur-food' ),
			__( 'Depeur Food', 'depeur-food' ),
			$cap,
			self::MENU_SLUG,
			array( SettingsPage::class, 'render' ),
			'dashicons-food',
			58
		);

		// Erste Unterseite teilt sich den MENU_SLUG, damit der Top-Level-Eintrag
		// direkt auf die Settings-Seite zeigt und als „Einstellungen“ beschriftet ist.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Einstellungen', 'depeur-food' ),
			__( 'Einstellungen', 'depeur-food' ),
			$cap,
			self::MENU_SLUG,
			array( SettingsPage::class, 'render' )
		);
	}
}
