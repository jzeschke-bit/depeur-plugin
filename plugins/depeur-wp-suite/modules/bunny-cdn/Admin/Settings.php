<?php
/**
 * Einstellungen für BunnyCDN (Registrierung im Core-Settings-Framework).
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\BunnyCDN\Admin;

use Depeur\WPSuite\Core\Settings\SettingsRegistry;
use Depeur\WPSuite\Modules\BunnyCDN\Integrations\RunCloudIntegration;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Einstellungs-Schema für das BunnyCDN-Modul.
 */
class Settings {

	/**
	 * Modul-Slug (Ordnername).
	 *
	 * @var string
	 */
	const MODULE_SLUG = 'bunny-cdn';

	/**
	 * Option-Key (muss mit Core-Slug „bunny-cdn“ übereinstimmen: depeur_wp_suite_bunny-cdn).
	 *
	 * @var string
	 */
	const OPTION_KEY = 'depeur_wp_suite_bunny-cdn';

	/**
	 * Registriert das Modul und seine Felder beim Core.
	 */
	public static function register() {
		$runcloud_detected = RunCloudIntegration::is_runcloud_detected();
		$fields            = self::get_fields( $runcloud_detected );

		SettingsRegistry::register(
			self::MODULE_SLUG,
			__( 'BunnyCDN', 'depeur-wp-suite' ),
			$fields,
			__( 'BunnyCDN Pull Zone, Purge und optionale RunCloud-Synchronisation.', 'depeur-wp-suite' )
		);
	}

	/**
	 * Felddefinitionen (abhängig von RunCloud-Erkennung).
	 *
	 * @param bool $show_runcloud RunCloud-Sync-Felder anzeigen.
	 * @return array
	 */
	private static function get_fields( $show_runcloud ) {
		$fields = array(
			// BunnyCDN
			array(
				'id'      => 'enable_bunny_cdn',
				'label'   => __( 'BunnyCDN aktivieren', 'depeur-wp-suite' ),
				'type'    => 'checkbox',
				'default' => false,
			),
			array(
				'id'          => 'bunny_api_key',
				'label'       => __( 'Bunny API Key', 'depeur-wp-suite' ),
				'type'        => 'password',
				'default'     => '',
				'autoload'    => false,
				'description' => __( 'Den API Key findest du im Bunny-Dashboard.', 'depeur-wp-suite' ) . ' <a href="https://dash.bunny.net/account/api-key" target="_blank" rel="noopener noreferrer">dash.bunny.net/account/api-key</a>',
			),
			array(
				'id'          => 'bunny_pull_zone_id',
				'label'       => __( 'Pull Zone ID', 'depeur-wp-suite' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Die numerische ID deiner Pull Zone findest du im Bunny-Dashboard unter CDN → Pull Zones.', 'depeur-wp-suite' ) . ' <a href="https://dash.bunny.net/cdn" target="_blank" rel="noopener noreferrer">dash.bunny.net/cdn</a>',
			),
			array(
				'id'          => 'bunny_cdn_hostname',
				'label'       => __( 'CDN-Hostname (z. B. meine-zone.b-cdn.net)', 'depeur-wp-suite' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Der Hostname deiner Pull Zone (z. B. deine-zone.b-cdn.net), siehe Hostnames in der Pull Zone.', 'depeur-wp-suite' ) . ' <a href="https://dash.bunny.net/cdn" target="_blank" rel="noopener noreferrer">dash.bunny.net/cdn</a>',
			),
			// Bunny Optimizer: Hinweis (Aktivierung nur im Bunny-Dashboard, keine Zone-ID)
			array(
				'id'          => 'bunny_optimizer_info',
				'label'       => __( 'Bunny Optimizer', 'depeur-wp-suite' ),
				'type'        => 'info',
				'description' => __( 'Bunny Optimizer wird ausschließlich im BunnyCDN-Dashboard aktiviert: Pull Zone wählen → links „Optimizer“ → „Turn on Bunny Optimizer“. Es ist keine zusätzliche Konfiguration oder Zone-ID im Plugin nötig. Optimierte Assets (Bilder, CSS, JS) werden dann automatisch über Ihre Pull-Zone-URL ausgeliefert.', 'depeur-wp-suite' ) . ' <a href="https://support.bunny.net/hc/en-us/articles/22231578241564-How-to-enable-Bunny-Optimizer" target="_blank" rel="noopener noreferrer">' . __( 'Anleitung (Bunny Support)', 'depeur-wp-suite' ) . '</a>',
			),
			// Environment
			array(
				'id'      => 'auto_detect_environment',
				'label'   => __( 'Umgebung automatisch erkennen', 'depeur-wp-suite' ),
				'type'    => 'checkbox',
				'default' => true,
			),
			array(
				'id'      => 'environment_override',
				'label'   => __( 'Umgebung erzwingen', 'depeur-wp-suite' ),
				'type'    => 'select',
				'default' => 'auto',
				'options' => array(
					'auto'    => __( 'Automatisch', 'depeur-wp-suite' ),
					'live'    => __( 'Live', 'depeur-wp-suite' ),
					'staging' => __( 'Staging', 'depeur-wp-suite' ),
				),
			),
		);

		if ( $show_runcloud ) {
			$fields[] = array(
				'id'          => 'enable_runcloud_sync',
				'label'       => __( 'BunnyCDN-Cache automatisch leeren, wenn RunCloud den NGINX-Cache komplett leert', 'depeur-wp-suite' ),
				'type'        => 'checkbox',
				'default'     => false,
				'description' => __( 'Empfohlen, wenn Sie „Cache Everything“ bzw. NGINX Page Cache nutzen: Beim RunCloud „Purge All“ wird auch der BunnyCDN-Cache geleert.', 'depeur-wp-suite' ),
			);
			$fields[] = array(
				'id'          => 'enable_runcloud_sync_single',
				'label'       => __( 'BunnyCDN-Cache auch leeren, wenn RunCloud nur eine Seite/Beitrag/URL leert', 'depeur-wp-suite' ),
				'type'        => 'checkbox',
				'default'     => false,
				'description' => __( 'Bei RunCloud-Purge einer einzelnen Seite oder der Startseite wird der komplette BunnyCDN-Cache mitgeleert (Purge All).', 'depeur-wp-suite' ),
			);
		}

		return $fields;
	}
}
