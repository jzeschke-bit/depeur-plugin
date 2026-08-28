<?php
/**
 * ACF-Field-Group-Metadaten — NUR NOCH die Legacy-Rezeptkategorie-Gruppe.
 *
 * Alle anderen Gruppen (Author fields, Kategorie-Custom, Reviewed by, Übersetzungen, die zwei
 * „Spotlight Promotions"-Newsletter-Gruppen, Tag-Einstellungen) wurden entfernt, weil ihre Felder
 * inzwischen von Feature-Modulen mit EIGENER Editor-Box provisioniert werden — sonst entstünden
 * Doppel-Boxen (gleiche ACF-Keys). Siehe config/fields.php für die Owner-Zuordnung.
 *
 * @package Depeur\Food\Modules\MetaRegistry
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	'rezeptkategorie' => array(
		'key'      => 'group_682f1db019e50',
		'title'    => 'Rezeptkategorie Einstellungen',
		'position' => 'normal',
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
			),
		),
	),
);
