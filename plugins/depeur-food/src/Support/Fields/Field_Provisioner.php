<?php
/**
 * Field_Provisioner — wiederverwendbare Feld-Provisionierung für Feature-Module.
 *
 * Ein Feature-Modul instanziiert diese Klasse mit seinen Feld-Deklarationen (+ optional
 * einer ACF-Group) und bekommt damit BEIDE Seiten des „Plugin legt Felder automatisch an":
 *   1. Datenschicht: register_post_meta / register_term_meta / register_meta('user') mit
 *      Sanitize-Callback und `show_in_rest` (aus `acf_type` abgeleitet).
 *   2. Editor-UI: acf_add_local_field_group (nur wenn ACF aktiv) — so muss NIEMAND die
 *      Felder manuell in ACF anlegen.
 *
 * Post-type-agnostisch (ADR-4): das Feature übergibt die Ziel-Subtypes (typischerweise
 * `depeur_food()->get_supported_post_types()`), damit dieselbe Logik für jeden CPT greift.
 *
 * Ersetzt den zentralen 34-Feld-Spiegel (meta-registry) durch feature-eigene, schlanke
 * Feldsätze. Kein Cross-Module-Import: die Feature-Module hängen nur an dieser Core-Klasse.
 *
 * @package Depeur\Food\Support\Fields
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Support\Fields;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert Meta-Keys (REST + Sanitize) und optional die zugehörige ACF-Field-Group.
 *
 * @since 0.2.0
 */
final class Field_Provisioner {

	/**
	 * Feld-Deklarationen (siehe Klassen-Doku für das Schema).
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $fields;

	/**
	 * Optionale ACF-Group-Metadaten (key/title/location/position) oder null.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $group;

	/**
	 * Verdrahtet Meta-Registrierung (init) und ACF-Group (acf/init).
	 *
	 * did_action-Guards: Feature-Module werden vom ModuleManager AUF init geladen (nach
	 * ACFs acf/init prio 5) — daher sofort registrieren, wenn der Hook schon lief.
	 *
	 * @since 0.2.0
	 *
	 * @param array<int,array<string,mixed>> $fields Feld-Deklarationen.
	 * @param array<string,mixed>|null       $group  Optionale ACF-Group-Metadaten.
	 */
	public function __construct( array $fields, ?array $group = null ) {
		$this->fields = $fields;
		$this->group  = $group;

		if ( did_action( 'init' ) ) {
			$this->register_meta();
		} else {
			add_action( 'init', array( $this, 'register_meta' ) );
		}

		if ( null !== $group ) {
			if ( did_action( 'acf/init' ) ) {
				$this->register_group();
			} else {
				add_action( 'acf/init', array( $this, 'register_group' ) );
			}
		}
	}

	/**
	 * Registriert alle Felder an der WordPress-Meta-API (Datenschicht).
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( $this->fields as $field ) {
			if ( empty( $field['name'] ) || empty( $field['object'] ) ) {
				continue;
			}

			$args = $this->build_args( $field );

			foreach ( (array) $field['object'] as $object_type ) {
				$this->register_for_object( (string) $object_type, $field, $args );
			}
		}
	}

	/**
	 * Registriert die ACF-Field-Group (Editor-UI). No-Op ohne aktives ACF.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_group(): void {
		if ( null === $this->group || empty( $this->group['key'] ) ) {
			return;
		}
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$acf_fields = array();
		foreach ( $this->fields as $field ) {
			// Meta-only-Felder (z. B. Zähler) haben keine Editor-UI.
			if ( isset( $field['editor_ui'] ) && false === $field['editor_ui'] ) {
				continue;
			}
			if ( empty( $field['key'] ) || empty( $field['name'] ) ) {
				continue;
			}
			$acf_fields[] = $this->build_acf_field( $field );
		}

		if ( empty( $acf_fields ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => $this->group['key'],
				'title'                 => isset( $this->group['title'] ) ? $this->group['title'] : '',
				'fields'                => $acf_fields,
				'location'              => isset( $this->group['location'] ) ? $this->group['location'] : array(),
				'menu_order'            => 0,
				'position'              => isset( $this->group['position'] ) ? $this->group['position'] : 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => true,
				'show_in_rest'          => isset( $this->group['show_in_rest'] ) ? (bool) $this->group['show_in_rest'] : true,
			)
		);
	}

	/**
	 * Registriert ein Feld für einen Objekt-Typ (post/user/term) inkl. Subtypes.
	 *
	 * @since 0.2.0
	 *
	 * @param string $object_type post|user|term.
	 * @param array  $field       Feld-Deklaration.
	 * @param array  $args        register_*_meta-Argumente.
	 * @return void
	 */
	private function register_for_object( string $object_type, array $field, array $args ): void {
		// User-Meta ist global (kein Subtype). register_user_meta() existiert in WP NICHT –
		// User-Meta läuft über register_meta( 'user', … ).
		if ( 'user' === $object_type ) {
			register_meta( 'user', (string) $field['name'], $args );
			return;
		}

		$subtypes = ( isset( $field['subtypes'][ $object_type ] ) && is_array( $field['subtypes'][ $object_type ] ) )
			? $field['subtypes'][ $object_type ]
			: array();

		foreach ( $subtypes as $subtype ) {
			if ( 'post' === $object_type ) {
				register_post_meta( (string) $subtype, (string) $field['name'], $args );
			} elseif ( 'term' === $object_type ) {
				register_term_meta( (string) $subtype, (string) $field['name'], $args );
			}
		}
	}

	/**
	 * Baut die register_*_meta-Argumente aus der Feld-Deklaration.
	 *
	 * @since 0.2.0
	 *
	 * @param array $field Feld-Deklaration.
	 * @return array
	 */
	private function build_args( array $field ): array {
		$acf_type  = isset( $field['acf_type'] ) ? (string) $field['acf_type'] : 'text';
		$meta_type = $this->meta_type( $acf_type );

		$args = array(
			'type'              => $meta_type,
			'single'            => true,
			'sanitize_callback' => $this->sanitizer( $acf_type, $field ),
			'show_in_rest'      => $this->rest_config( $acf_type ),
		);

		// Protected Keys (führender _) brauchen einen auth_callback für REST/Editor.
		if ( isset( $field['auth_callback'] ) && is_callable( $field['auth_callback'] ) ) {
			$args['auth_callback'] = $field['auth_callback'];
		}

		if ( array_key_exists( 'default', $field ) ) {
			$default = $field['default'];
			$include = true;

			// Der Default MUSS zum Meta-Typ passen, sonst lehnt WP die Registrierung ab.
			switch ( $meta_type ) {
				case 'integer':
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
	 * @since 0.2.0
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
				return 'string';
		}
	}

	/**
	 * Baut die show_in_rest-Konfiguration; nicht-skalare Typen brauchen ein Schema.
	 *
	 * @since 0.2.0
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
	 * Liefert den typ-spezifischen Sanitize-Callback.
	 *
	 * @since 0.2.0
	 *
	 * @param string $acf_type ACF-Feldtyp.
	 * @param array  $field    Feld-Deklaration (für choices/min/max).
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

	/**
	 * Baut ein einzelnes ACF-Field-Array aus der Feld-Deklaration.
	 *
	 * @since 0.2.0
	 *
	 * @param array $field Feld-Deklaration.
	 * @return array
	 */
	private function build_acf_field( array $field ): array {
		$acf_type = isset( $field['acf_type'] ) ? (string) $field['acf_type'] : 'text';

		$acf_field = array(
			'key'   => $field['key'],
			'label' => isset( $field['label'] ) ? $field['label'] : $field['name'],
			'name'  => $field['name'],
			// email rendert live als Text-Feld; sonst acf_type == ACF-Feldtyp.
			'type'  => ( 'email' === $acf_type ) ? 'text' : $acf_type,
		);

		if ( ! empty( $field['acf'] ) && is_array( $field['acf'] ) ) {
			$acf_field = array_merge( $acf_field, $field['acf'] );
		}

		return $acf_field;
	}
}
