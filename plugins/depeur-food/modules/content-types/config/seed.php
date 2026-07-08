<?php
/**
 * Seed-Pack: content-types (Recovery-/Bootstrap-Import-Set).
 *
 * Gefrorenes, NICHT auto-registriertes Import-Set der alkipedia-CPTs + Taxonomien
 * (BRIEF § 4.4). Wird per `require` als reine Daten geladen (keine Klasse), ausschließlich
 * vom Importer genutzt (Seed-Restore, § 3.4). Die Provider lesen es NIE direkt — nur der
 * DB-Store treibt die Registrierung (harte Invariante § 3.3: leerer Store ⇒ nichts).
 *
 * ⚠️ NOCH NICHT BEFÜLLT. Die args-treuen Definitionen werden nach dem ersten Importer-
 * Live-Scan auf der alkipedia-Staging aus dem Store exportiert und hier eingefroren
 * (Plan Session 2 / BRIEF § 4.4). Bewusst KEIN manuelles Abtippen der § 2-Zusammenfassung
 * (labels = 33-Key-Arrays, rewrite/supports als Structs → Transkriptionsfehler wären fast
 * sicher, brächen URLs/Editor).
 *
 * @package Depeur\Food\Modules\ContentTypes
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'version'    => 1,
	'post_types' => array(),
	'taxonomies' => array(),
);
