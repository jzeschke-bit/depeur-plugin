<?php
/**
 * Manifest für das Modul BunnyCDN Integration.
 *
 * @package Depeur\WPSuite\Modules\BunnyCDN
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'bunny-cdn',
	'name'        => 'BunnyCDN Integration',
	'description' => 'BunnyCDN Pull Zone, Cache-Purge (inkl. RunCloud-Sync) und optionale Bunny-Optimizer-Anbindung.',
	'version'     => '1.0.0',
);
