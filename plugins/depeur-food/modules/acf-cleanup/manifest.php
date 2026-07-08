<?php
/**
 * Manifest des acf-cleanup-Moduls: Metadaten für die ModuleManager-Discovery.
 *
 * @package Depeur\Food\Modules\AcfCleanup
 * @license GPL-2.0-or-later
 */

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Reine Metadaten, kein Code-Effekt. BEWUSST kein slug-Key: die Discovery keyt
// allein nach Ordnername (Modul-Kanon Punkt 4). Default-OFF – ein Wartungs-Tool,
// das man bei Bedarf aktiviert und nach dem Cutover wieder abschalten kann.
return array(
	'name'        => 'Migration & Aufräumen (ACF)',
	'version'     => '0.2.0',
	'description' => 'Geführtes Aufräumen: entfernt die ACF-Feldgruppen-DB-Duplikate, die das Plugin bereits selbst per Code liefert – mit Vorschau, Auto-Backup und Cap+Nonce-Gate. Fasst CPT-/Taxonomie-Definitionen und nicht abgedeckte Gruppen NIE an. Feldwerte bleiben unberührt.',
);
