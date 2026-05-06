<?php
/**
 * Einstellungen für das Beispiel-Modul (Registrierung im Settings-Framework).
 *
 * @package Depeur\WPSuite\Modules\ExampleModule\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\ExampleModule\Admin;

use Depeur\WPSuite\Core\Settings\SettingsRegistry;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse Settings (Beispiel-Modul).
 */
class Settings {

	/**
	 * Modul-Slug (muss mit manifest.php übereinstimmen).
	 *
	 * @var string
	 */
	const MODULE_SLUG = '_ExampleModule';

	/**
	 * Registriert das Modul und seine Einstellungsfelder beim Core.
	 */
	public static function register() {
		SettingsRegistry::register(
			self::MODULE_SLUG,
			__( 'Beispiel-Modul', 'depeur-wp-suite' ),
			array(
				array(
					'id'      => 'example_text',
					'label'   => __( 'Beispiel-Text', 'depeur-wp-suite' ),
					'type'    => 'text',
					'default' => '',
				),
				array(
					'id'      => 'example_checkbox',
					'label'   => __( 'Beispiel aktivieren', 'depeur-wp-suite' ),
					'type'    => 'checkbox',
					'default' => false,
				),
				array(
					'id'      => 'example_select',
					'label'   => __( 'Beispiel-Auswahl', 'depeur-wp-suite' ),
					'type'    => 'select',
					'default' => 'a',
					'options' => array(
						'a' => __( 'Option A', 'depeur-wp-suite' ),
						'b' => __( 'Option B', 'depeur-wp-suite' ),
					),
				),
			)
		);
	}
}
