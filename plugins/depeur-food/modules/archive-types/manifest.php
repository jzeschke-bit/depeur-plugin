<?php
/**
 * Manifest des archive-types-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\ArchiveTypes
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key (Discovery keyt nach Ordnername,
// Modul-Kanon Punkt 4). Ersetzt die Alt-Theme-Funktionen alkipedia_add_cpt_to_tag_archives()
// + wpse107459_add_cpt_author(): dort war es Theme-Code, hier ist es post-type-agnostische
// Plugin-Logik (ADR-4) und darum sauber toggelbar.
return array(
	'name'        => 'Archiv-Inhaltstypen',
	'version'     => '0.1.0',
	'description' => 'Zeigt die unterstützten Inhaltstypen (z. B. Cocktails) automatisch auf Standard-Archiven mit an – Tag, Kategorie, eigene Taxonomien, Autor und Datum. Liest die post-type-agnostische Quelle (depeur_food()->get_supported_post_types()); ändert nur die Haupt-Abfrage im Frontend.',
);
