<?php
/**
 * Admin/Settings — Settings-Tab des archive-types-Moduls.
 *
 * Meldet via SettingsRegistry einen Tab „Archiv-Inhaltstypen" an: drei Schalter, auf WELCHEN
 * Archiv-Arten die unterstützten Inhaltstypen mit eingespeist werden (Taxonomie-, Autor-,
 * Datums-Archive). Standard: alle an (= bisheriges Verhalten). Der Archive_Injector liest genau
 * diese Werte.
 *
 * @package Depeur\Food\Modules\ArchiveTypes\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\ArchiveTypes\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den archive-types-Settings-Tab.
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
			__( 'Archiv-Inhaltstypen', 'depeur-food' ),
			array(
				array(
					'id'          => 'on_taxonomy',
					'label'       => __( 'Auf Tag-/Kategorie-/Taxonomie-Archiven', 'depeur-food' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Zeigt die unterstützten Inhaltstypen auch auf Schlagwort-, Kategorie- und eigenen Taxonomie-Archiven (nur Typen, die die jeweilige Taxonomie führen).', 'depeur-food' ),
				),
				array(
					'id'          => 'on_author',
					'label'       => __( 'Auf Autor-Archiven', 'depeur-food' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Zeigt die unterstützten Inhaltstypen auch auf Autoren-Archivseiten.', 'depeur-food' ),
				),
				array(
					'id'          => 'on_date',
					'label'       => __( 'Auf Datums-Archiven', 'depeur-food' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Zeigt die unterstützten Inhaltstypen auch auf Datums-Archivseiten.', 'depeur-food' ),
				),
			),
			__( 'Steuert, auf welchen Standard-Archiven die vom Plugin unterstützten Inhaltstypen (z. B. Cocktails) mit angezeigt werden — statt nur Standard-Beiträgen. Alles aus = Archive verhalten sich wie ohne dieses Modul.', 'depeur-food' )
		);
	}
}
