<?php
/**
 * Fields — provisioniert die Kategorie-Seiten-Konfigurationsfelder (statisch + dynamisch).
 *
 * Statische Felder (Opt-in-Toggle, Titel, per_page) kommen aus config/fields.php; dazu wird
 * PRO kuratierbarer Taxonomie ein ACF-Taxonomie-Multiselect erzeugt (Meta-Key
 * `df_catpage_terms_{taxonomy}`), damit der Redakteur die Terms je Taxonomie zusammenklickt —
 * post-type-agnostisch, ohne ACF-Pro-Repeater. Alles via Core-Field_Provisioner.
 *
 * Läuft auf `init` prio 20 (NACH der Taxonomie-Registrierung durch ACF/Site, die auf init
 * früher passiert) — sonst wäre die Taxonomie-Liste beim Feldaufbau leer.
 *
 * @package Depeur\Food\Modules\CategoryPages\Provisioning
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Provisioning;

use Depeur\Food\Modules\CategoryPages\Support\Taxonomies;
use Depeur\Food\Support\Fields\Field_Provisioner;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut die Feldliste und übergibt sie an den Field_Provisioner.
 *
 * @since 0.3.0
 */
final class Fields {

	/**
	 * Verdrahtet die Provisionierung nach der Taxonomie-Registrierung.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Prio 20: nach der CPT-/Taxonomie-Registrierung (ACF läuft auf init früher).
		add_action( 'init', array( $this, 'provision' ), 20 );
	}

	/**
	 * Stellt statische + dynamische Felder zusammen und provisioniert sie.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function provision(): void {
		$config = require dirname( __DIR__ ) . '/config/fields.php';
		$fields = isset( $config['fields'] ) && is_array( $config['fields'] ) ? $config['fields'] : array();

		foreach ( Taxonomies::supported() as $taxonomy ) {
			$fields[] = $this->taxonomy_field( $taxonomy );
		}

		new Field_Provisioner( $fields, isset( $config['group'] ) ? $config['group'] : null );
	}

	/**
	 * Baut ein ACF-Taxonomie-Multiselect-Feld für eine Taxonomie.
	 *
	 * @since 0.3.0
	 *
	 * @param string $taxonomy Taxonomie-Slug.
	 * @return array<string, mixed>
	 */
	private function taxonomy_field( string $taxonomy ): array {
		$object = get_taxonomy( $taxonomy );
		$label  = ( $object && ! empty( $object->labels->name ) ) ? (string) $object->labels->name : $taxonomy;

		return array(
			'name'     => Taxonomies::meta_key( $taxonomy ),
			'acf_type' => 'taxonomy',
			'object'   => array( 'post' ),
			'subtypes' => array( 'post' => array( 'page' ) ),
			'key'      => 'field_df_catpage_tax_' . $taxonomy,
			/* translators: %s: Taxonomie-Name. */
			'label'    => sprintf( __( 'Terms: %s', 'depeur-food' ), $label ),
			'acf'      => array(
				'taxonomy'          => $taxonomy,
				'field_type'        => 'multi_select',
				'add_term'          => 0,
				// Auswahl NICHT als Term dem Seiten-Objekt zuweisen – nur die IDs speichern.
				'save_terms'        => 0,
				'load_terms'        => 0,
				'return_format'     => 'id',
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_df_catpage_enabled',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			),
		);
	}
}
