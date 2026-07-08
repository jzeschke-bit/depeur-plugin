<?php
/**
 * Modul-Manifest: language-selector.
 *
 * @package Depeur\Food\Modules\LanguageSelector
 * @license GPL-2.0-or-later
 */

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'        => 'Sprachumschalter',
	'version'     => '0.2.0',
	'description' => 'Manuelles Cross-Domain-hreflang: legt die Felder link_de/link_en automatisch an (Post + Term, für die aktivierten Inhaltstypen) und gibt hreflang-Tags im <head> + einen Sprachumschalter-Shortcode aus. Ersetzt die Theme-Funktionen LanguageLink()/lang_tag().',
);
