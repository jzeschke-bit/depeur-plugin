<?php
/**
 * Modul-Manifest: schema-engine.
 *
 * Metadaten ohne `slug`-Key (Discovery keyt nach Ordnername, example-module § 2.4).
 *
 * @package Depeur\Food\Modules\SchemaEngine
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'        => 'Schema-Engine',
	'version'     => '0.2.0',
	'description' => 'Reichert das Rank-Math-Schema um Autor-Angaben (jobTitle/alumniOf/knowsAbout/sameAs) an und hängt auf Kategorie-/Archiv-Seiten das WPRM-Rezept als CollectionPage ein. Ersetzt das Legacy-Plugin „Category Schema" und die Theme-Datei „rank-math.php". Provisioniert die zugehörigen Autor-, Social- und Review-Felder selbst.',
);
