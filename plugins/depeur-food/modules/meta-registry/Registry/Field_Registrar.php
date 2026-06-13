<?php
/**
 * Field_Registrar — Datenschicht-Registrierung (register_*_meta).
 *
 * Liest die Field-Registry (config/fields.php), wendet den Erweiterungs-Filter an und
 * registriert jedes Feld als Post-/User-/Term-Meta mit Sanitize-Callback und REST-Schema
 * (BRIEF meta-registry § 4.1/§ 4.2). ACF-unabhängig (ADR-5): läuft auch ohne aktives ACF.
 *
 * @package Depeur\Food\Modules\MetaRegistry\Registry
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\MetaRegistry\Registry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert alle Discovery-Meta-Keys an der WordPress-Meta-API.
 *
 * @since 0.1.0
 */
final class Field_Registrar {

	/**
	 * Verdrahtet die Registrierung am init-Hook.
	 *
	 * Per did_action-Guard: Der ModuleManager lädt Module bereits AUF init (prio 10). Ein
	 * nachträgliches add_action('init') liefe ins Leere/wäre fragil – wenn init also schon
	 * läuft/lief, sofort registrieren, sonst regulär einhängen (BRIEF § 9.10, analog Groups).
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		if ( did_action( 'init' ) ) {
			$this->register();
		} else {
			add_action( 'init', array( $this, 'register' ) );
		}
	}

	/**
	 * Registriert alle Felder aus der (gefilterten) Registry.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register(): void {
		$fields = require dirname( __DIR__ ) . '/config/fields.php';

		/**
		 * Filtert die Field-Registry vor der Registrierung.
		 *
		 * Zentraler Erweiterungspunkt für Konsumenten-Module und den Future-Wizard
		 * (BRIEF § 5). Erwartet das Registry-Array-Format aus config/fields.php.
		 *
		 * @since 0.1.0
		 *
		 * @param array $fields Liste der Feld-Definitionen.
		 */
		$fields = apply_filters( 'depeur_food/meta/registry', $fields );

		if ( ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $field ) {
			if ( empty( $field['name'] ) || empty( $field['object'] ) ) {
				continue;
			}

			$args = $this->build_args( $field );

			foreach ( (array) $field['object'] as $object_type ) {
				$this->register_for_object( $object_type, $field, $args );
			}
		}

		/**
		 * Feuert, nachdem alle Meta-Keys registriert wurden.
		 *
		 * Andockpunkt für Konsumenten/Wizard, die auf die fertige Registry aufsetzen
		 * (BRIEF § 5).
		 *
		 * @since 0.1.0
		 */
		do_action( 'depeur_food/meta/registered' );
	}

	/**
	 * Registriert ein Feld für einen Objekt-Typ (post/user/term) inkl. Subtypes.
	 *
	 * @since 0.1.0
	 *
	 * @param string $object_type post|user|term.
	 * @param array  $field       Feld-Definition.
	 * @param array  $args        register_*_meta-Argumente.
	 * @return void
	 */
	private function register_for_object( string $object_type, array $field, array $args ): void {
		// User-Meta ist global (kein Subtype). WICHTIG: ein register_user_meta() existiert in
		// WordPress NICHT (nur register_post_meta/register_term_meta) – User-Meta läuft über
		// register_meta( 'user', … ) statt des im BRIEF § 2 genannten register_user_meta.
		if ( 'user' === $object_type ) {
			register_meta( 'user', $field['name'], $args );
			return;
		}

		$subtypes = ( isset( $field['subtypes'][ $object_type ] ) && is_array( $field['subtypes'][ $object_type ] ) )
			? $field['subtypes'][ $object_type ]
			: array();

		foreach ( $subtypes as $subtype ) {
			if ( 'post' === $object_type ) {
				register_post_meta( $subtype, $field['name'], $args );
			} elseif ( 'term' === $object_type ) {
				register_term_meta( $subtype, $field['name'], $args );
			}
		}
	}

	/**
	 * Baut die register_*_meta-Argumente aus der Feld-Definition (Typ-Map § 4.2).
	 *
	 * @since 0.1.0
	 *
	 * @param array $field Feld-Definition.
	 * @return array
	 */
	private function build_args( array $field ): array {
		$acf_type = isset( $field['acf_type'] ) ? $field['acf_type'] : 'text';

		$meta_type = $this->meta_type( $acf_type );

		$args = array(
			'type'              => $meta_type,
			'single'            => true,
			'sanitize_callback' => $this->sanitizer( $acf_type, $field ),
			'show_in_rest'      => $this->rest_config( $acf_type ),
		);

		if ( array_key_exists( 'default', $field ) ) {
			$default = $field['default'];
			$include = true;

			// Der Default MUSS zum Meta-Typ passen, sonst lehnt WP die Registrierung ab
			// (verifiziert: integer + default '' → Registrierung schlägt fehl).
			switch ( $meta_type ) {
				case 'integer':
					// Bei nicht-integer-Default (z. B. '' bei reviewed_by/post_object) weglassen
					// → get_*_meta liefert bei Abwesenheit '' (leer), was Konsumenten via
					// empty() ohnehin erwarten.
					$include = is_int( $default );
					break;
				case 'boolean':
					$default = (bool) $default;
					break;
				case 'array':
				case 'object':
					$default = is_array( $default ) ? $default : array();
					break;
			}

			if ( $include ) {
				$args['default'] = $default;
			}
		}

		return $args;
	}

	/**
	 * Map acf_type → WordPress-Meta-Typ.
	 *
	 * ACF speichert Mehrfach-/Array-Felder als EINE serialisierte Zeile, daher immer
	 * single=true mit Typ array/object (nicht single=false). E5-Koexistenz (§ 4.5).
	 *
	 * @since 0.1.0
	 *
	 * @param string $acf_type ACF-Feldtyp.
	 * @return string
	 */
	private function meta_type( string $acf_type ): string {
		switch ( $acf_type ) {
			case 'number':
			case 'post_object':
			case 'user':
				return 'integer';
			case 'true_false':
				return 'boolean';
			case 'taxonomy':
				return 'array';
			case 'link':
				return 'object';
			default:
				// text, email, url, wysiwyg, select.
				return 'string';
		}
	}

	/**
	 * Baut die show_in_rest-Konfiguration; nicht-skalare Typen brauchen ein Schema.
	 *
	 * @since 0.1.0
	 *
	 * @param string $acf_type ACF-Feldtyp.
	 * @return array|bool
	 */
	private function rest_config( string $acf_type ) {
		if ( 'taxonomy' === $acf_type ) {
			return array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			);
		}

		if ( 'link' === $acf_type ) {
			// ACF-Link speichert {title,url,target}. Leere Legacy-Werte ('') werden vom
			// Sanitize zu array() normalisiert; volle REST-Robustheit für Altbestände
			// schärft P7 (language-selector als realer Konsument, BRIEF § 9.4).
			return array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'title'  => array( 'type' => 'string' ),
						'url'    => array( 'type' => 'string' ),
						'target' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
			);
		}

		return true;
	}

	/**
	 * Liefert den typ-spezifischen Sanitize-Callback (§ 4.2).
	 *
	 * Select/Number brauchen Feld-Kontext (choices bzw. min/max) → Closure. Link normalisiert
	 * auf ein {title,url,target}-Array.
	 *
	 * @since 0.1.0
	 *
	 * @param string $acf_type ACF-Feldtyp.
	 * @param array  $field    Feld-Definition (für choices/min/max).
	 * @return callable
	 */
	private function sanitizer( string $acf_type, array $field ): callable {
		switch ( $acf_type ) {
			case 'email':
				return 'sanitize_email';
			case 'url':
				return 'esc_url_raw';
			case 'wysiwyg':
				return 'wp_kses_post';
			case 'post_object':
			case 'user':
				return 'absint';
			case 'true_false':
				return 'rest_sanitize_boolean';

			case 'number':
				$min = isset( $field['acf']['min'] ) ? (int) $field['acf']['min'] : null;
				$max = isset( $field['acf']['max'] ) ? (int) $field['acf']['max'] : null;
				return static function ( $value ) use ( $min, $max ) {
					$value = absint( $value );
					if ( null !== $min && $value < $min ) {
						$value = $min;
					}
					if ( null !== $max && $value > $max ) {
						$value = $max;
					}
					return $value;
				};

			case 'select':
				$allowed = isset( $field['acf']['choices'] ) && is_array( $field['acf']['choices'] )
					? array_map( 'strval', array_keys( $field['acf']['choices'] ) )
					: array();
				return static function ( $value ) use ( $allowed ) {
					$value = sanitize_text_field( (string) $value );
					return in_array( $value, $allowed, true ) ? $value : '';
				};

			case 'taxonomy':
				return static function ( $value ) {
					return is_array( $value ) ? array_map( 'absint', $value ) : array();
				};

			case 'link':
				return static function ( $value ) {
					if ( ! is_array( $value ) ) {
						return array();
					}
					return array(
						'title'  => isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '',
						'url'    => isset( $value['url'] ) ? esc_url_raw( $value['url'] ) : '',
						'target' => isset( $value['target'] ) ? sanitize_text_field( $value['target'] ) : '',
					);
				};

			case 'text':
			default:
				return 'sanitize_text_field';
		}
	}
}
