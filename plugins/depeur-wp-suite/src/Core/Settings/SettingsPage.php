<?php
/**
 * Einstellungsseite: Tabs pro Modul, WordPress Settings API.
 *
 * @package Depeur\WPSuite\Core\Settings
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Core\Settings;

use Depeur\WPSuite\Core\ModuleManager;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klasse SettingsPage.
 */
class SettingsPage {

	/**
	 * Aktiver Tab (GET-Parameter).
	 *
	 * @var string
	 */
	private static $active_tab = '';

	/**
	 * Beschreibungen der Modul-Sektionen in Reihenfolge (für benannten Callback).
	 *
	 * @var string[]
	 */
	private static $section_descriptions = array();

	/**
	 * Index für section_descriptions beim Rendern.
	 *
	 * @var int
	 */
	private static $section_description_index = 0;

	/**
	 * Ausstehende Feld-Argumente für add_settings_field (option_key, field, value).
	 *
	 * @var array<string, mixed>[]
	 */
	private static $pending_field_args = array();

	/**
	 * Index für pending_field_args beim Rendern.
	 *
	 * @var int
	 */
	private static $field_args_index = 0;

	/**
	 * Rendert die Einstellungsseite mit Tabs.
	 */
	/**
	 * Registriert alle Einstellungen (Core + Module) auf admin_init.
	 * Muss vor options.php laufen, damit die Optionen-Seite in der erlaubten Liste steht.
	 */
	public static function register_all_settings() {
		$active_slugs = ModuleManager::get_active_module_slugs();
		$schemas      = SettingsRegistry::get_schemas_for_active_modules( $active_slugs );

		self::$section_descriptions = array();
		self::$pending_field_args   = array();

		self::register_core_settings();
		foreach ( array_keys( $schemas ) as $slug ) {
			self::register_module_settings( $slug, $schemas[ $slug ] );
		}
	}

	/**
	 * Rendert die Einstellungsseite mit Tabs (Settings sind bereits auf admin_init registriert).
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_slugs = ModuleManager::get_active_module_slugs();
		$schemas      = SettingsRegistry::get_schemas_for_active_modules( $active_slugs );

		self::$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'core';
		$all_tabs         = self::get_tabs( $schemas );

		if ( ! array_key_exists( self::$active_tab, $all_tabs ) ) {
			self::$active_tab = array_key_first( $all_tabs );
		}
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'Einstellungen', 'depeur-wp-suite' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php
				$base_url = admin_url( 'admin.php?page=' . \Depeur\WPSuite\Core\AdminMenu::MENU_SLUG . '-settings' );
				foreach ( $all_tabs as $tab_key => $tab_label ) :
					$url = add_query_arg( 'tab', $tab_key, $base_url );
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="nav-tab <?php echo self::$active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<?php
			if ( self::$active_tab !== 'core' ) {
				$intro = apply_filters( 'depeur_wp_suite_settings_tab_intro_' . self::$active_tab, '', self::$active_tab );
				if ( $intro !== '' ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Modul liefert bereits escaped HTML
					echo $intro;
				}
			}
			?>
			<?php
			self::$section_description_index = 0;
			self::$field_args_index          = 0;
			?>
			<form method="post" action="options.php" id="depeur-suite-settings-form">
				<?php
				if ( self::$active_tab === 'core' ) {
					settings_fields( 'depeur_wp_suite_core' );
					do_settings_sections( 'depeur_wp_suite_settings_core' );
				} else {
					$option_key = SettingsRegistry::option_key( self::$active_tab );
					settings_fields( $option_key );
					do_settings_sections( 'depeur_wp_suite_settings_' . self::$active_tab );
				}
				submit_button( __( 'Einstellungen speichern', 'depeur-wp-suite' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Tabs für die Seite (Core + Modul-Tabs).
	 *
	 * @param array<string, array{ tab_label: string }> $schemas Schemata aktivierter Module.
	 * @return array<string, string> tab_slug => label.
	 */
	private static function get_tabs( array $schemas ) {
		$tabs = array( 'core' => __( 'Allgemein', 'depeur-wp-suite' ) );
		foreach ( $schemas as $slug => $schema ) {
			$tabs[ $slug ] = $schema['tab_label'];
		}
		return $tabs;
	}

	/**
	 * Setzt nach dem Speichern autoload=no für die zuletzt gespeicherte Modul-Option (für Secrets).
	 * Wird nur für Optionen registriert, die mindestens ein Feld mit autoload=false haben.
	 */
	public static function force_autoload_no_after_save() {
		$hook = current_filter();
		if ( strpos( $hook, 'update_option_' ) !== 0 ) {
			return;
		}
		$option_key = substr( $hook, strlen( 'update_option_' ) );
		$value      = get_option( $option_key );
		if ( $value !== false ) {
			update_option( $option_key, $value, false );
		}
	}

	/**
	 * Registriert Core-Einstellungen (Logging).
	 */
	private static function register_core_settings() {
		$option = 'depeur_wp_suite_logging_enabled';
		register_setting(
			'depeur_wp_suite_core',
			$option,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( __CLASS__, 'sanitize_logging_enabled' ),
			)
		);
		add_settings_section(
			'depeur_wp_suite_core_section',
			__( 'Logging', 'depeur-wp-suite' ),
			array( __CLASS__, 'render_core_logging_section' ),
			'depeur_wp_suite_settings_core'
		);
		add_settings_field(
			$option,
			__( 'Logging aktivieren', 'depeur-wp-suite' ),
			array( __CLASS__, 'render_core_logging_field' ),
			'depeur_wp_suite_settings_core',
			'depeur_wp_suite_core_section',
			array( 'option_name' => $option )
		);
	}

	/**
	 * Sanitize-Callback für die Logging-Option.
	 *
	 * @param mixed $value Eingabewert.
	 * @return bool
	 */
	public static function sanitize_logging_enabled( $value ) {
		return (bool) $value;
	}

	/**
	 * Beschreibung der Logging-Sektion (Allgemein).
	 */
	public static function render_core_logging_section() {
		echo '<p>' . esc_html__( 'Aktiviere Logging für Diagnose. Logs liegen in uploads/depeur-wp-suite-logs/.', 'depeur-wp-suite' ) . '</p>';
	}

	/**
	 * Checkbox „Logging aktivieren“ rendern.
	 *
	 * @param array $args Enthält 'option_name'.
	 */
	public static function render_core_logging_field( $args ) {
		$option_name = isset( $args['option_name'] ) ? $args['option_name'] : 'depeur_wp_suite_logging_enabled';
		$val         = (bool) get_option( $option_name, false );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>" value="1" <?php checked( $val ); ?> />
			<?php esc_html_e( 'Logging einschalten', 'depeur-wp-suite' ); ?>
		</label>
		<?php
	}

	/**
	 * Registriert Einstellungen für ein Modul (WordPress Settings API).
	 *
	 * @param string $module_slug Modul-Slug.
	 * @param array  $schema      Schema mit tab_label und fields.
	 */
	private static function register_module_settings( $module_slug, array $schema ) {
		$option_key = SettingsRegistry::option_key( $module_slug );
		$fields     = isset( $schema['fields'] ) ? $schema['fields'] : array();

		$has_no_autoload = false;
		foreach ( $fields as $field ) {
			if ( isset( $field['autoload'] ) && $field['autoload'] === false ) {
				$has_no_autoload = true;
				break;
			}
		}

		$sanitizer = new ModuleOptionsSanitizer( $module_slug, $fields );
		register_setting(
			$option_key,
			$option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => $sanitizer,
			)
		);

		// Option mit autoload=no speichern, wenn mindestens ein Feld (z. B. API-Key) autoload=false hat.
		if ( $has_no_autoload ) {
			add_action( 'update_option_' . $option_key, array( __CLASS__, 'force_autoload_no_after_save' ), 10, 0 );
		}

		self::$section_descriptions[] = isset( $schema['description'] ) ? $schema['description'] : '';
		$section_id = 'depeur_wp_suite_' . $module_slug . '_section';
		add_settings_section(
			$section_id,
			$schema['tab_label'],
			array( __CLASS__, 'render_module_section_callback' ),
			'depeur_wp_suite_settings_' . $module_slug
		);

		$saved = get_option( $option_key, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		foreach ( $fields as $field ) {
			$id       = isset( $field['id'] ) ? $field['id'] : '';
			$label    = isset( $field['label'] ) ? $field['label'] : $id;
			$default  = isset( $field['default'] ) ? $field['default'] : '';
			$value    = array_key_exists( $id, $saved ) ? $saved[ $id ] : $default;
			self::$pending_field_args[] = array(
				'option_key' => $option_key,
				'field'      => $field,
				'value'      => $value,
			);
			add_settings_field(
				$id,
				$label,
				array( __CLASS__, 'render_module_field_callback' ),
				'depeur_wp_suite_settings_' . $module_slug,
				$section_id,
				array( 'label_for' => $option_key . '_' . $field['id'] )
			);
		}
	}

	/**
	 * Beschreibung der Modul-Sektion rendern (benannter Callback für add_settings_section).
	 */
	public static function render_module_section_callback() {
		$idx = self::$section_description_index;
		self::$section_description_index++;
		$desc = isset( self::$section_descriptions[ $idx ] ) ? self::$section_descriptions[ $idx ] : '';
		if ( $desc !== '' ) {
			echo '<p>' . esc_html( $desc ) . '</p>';
		}
	}

	/**
	 * Einzelnes Modul-Feld rendern (benannter Callback für add_settings_field).
	 */
	public static function render_module_field_callback() {
		$idx  = self::$field_args_index;
		self::$field_args_index++;
		$args = isset( self::$pending_field_args[ $idx ] ) ? self::$pending_field_args[ $idx ] : null;
		if ( $args !== null ) {
			self::render_field( $args['option_key'], $args['field'], $args['value'] );
		}
	}

	/**
	 * Sanitized Modul-Optionen (öffentlich für ModuleOptionsSanitizer). Behält leere Passwörter.
	 *
	 * @param array  $input       Formulareingabe.
	 * @param string $module_slug Modul-Slug.
	 * @param array  $fields      Felddefinitionen.
	 * @return array
	 */
	public static function sanitize_module_options( $input, $module_slug, array $fields ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$option_key = SettingsRegistry::option_key( $module_slug );
		$saved      = get_option( $option_key, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$out = array();
		foreach ( $fields as $field ) {
			$id   = isset( $field['id'] ) ? $field['id'] : '';
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			if ( $id === '' || $type === 'info' ) {
				continue;
			}
			$raw = isset( $input[ $id ] ) ? $input[ $id ] : ( isset( $field['default'] ) ? $field['default'] : null );
			// Passwort-Felder: leere Eingabe = bestehenden Wert beibehalten (Secret nicht überschreiben).
			$type = isset( $field['type'] ) ? $field['type'] : 'text';
			if ( $type === 'password' && ( $raw === '' || $raw === null ) && isset( $saved[ $id ] ) ) {
				$raw = $saved[ $id ];
			}
			$out[ $id ] = SettingsRegistry::sanitize_field( $raw, $field );
		}
		return $out;
	}

	/**
	 * Einzelnes Feld rendern (checkbox, text, select).
	 *
	 * @param string $option_key Options-Key (depeur_wp_suite_{slug}).
	 * @param array  $field      Felddefinition.
	 * @param mixed  $value      Aktueller Wert.
	 */
	private static function render_field( $option_key, array $field, $value ) {
		$id      = isset( $field['id'] ) ? $field['id'] : '';
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$name    = $option_key . '[' . esc_attr( $id ) . ']';
		$attr_id = $option_key . '_' . $id;

		switch ( $type ) {
			case 'info':
				// Nur Hinweistext, kein Eingabefeld (wird nicht gespeichert).
				if ( ! empty( $field['description'] ) ) {
					echo '<p class="description" style="margin: 0;">' . wp_kses_post( $field['description'] ) . '</p>';
				}
				return;
			case 'checkbox':
				?>
				<label for="<?php echo esc_attr( $attr_id ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( $attr_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( ! empty( $value ) ); ?> />
				</label>
				<?php
				break;
			case 'select':
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
				?>
				<select id="<?php echo esc_attr( $attr_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<?php foreach ( $options as $opt_val => $opt_label ) : ?>
						<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>><?php echo esc_html( $opt_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'password':
				// Wert nie im HTML ausgeben (Security). Placeholder nur anzeigen, wenn bereits gesetzt.
				$placeholder = ( $value !== '' && $value !== null ) ? '••••••••' : '';
				?>
				<input type="password" id="<?php echo esc_attr( $attr_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" class="regular-text" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" />
				<?php
				break;
			case 'text':
			default:
				?>
				<input type="text" id="<?php echo esc_attr( $attr_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( is_string( $value ) ? $value : '' ); ?>" class="regular-text" />
				<?php
				break;
		}
		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . wp_kses_post( $field['description'] ) . '</p>';
		}
	}
}

/**
 * Invokable Sanitizer für Modul-Optionen (regelkonform ohne anonyme Funktion für Hooks).
 *
 * @package Depeur\WPSuite\Core\Settings
 */
class ModuleOptionsSanitizer {

	/**
	 * Modul-Slug.
	 *
	 * @var string
	 */
	private $module_slug;

	/**
	 * Feld-Definitionen.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * @param string $module_slug Modul-Slug.
	 * @param array  $fields     Feld-Definitionen.
	 */
	public function __construct( $module_slug, array $fields ) {
		$this->module_slug = $module_slug;
		$this->fields      = $fields;
	}

	/**
	 * Sanitized die Eingabe und gibt das Array zurück.
	 *
	 * @param array $input Formulareingabe.
	 * @return array
	 */
	public function __invoke( $input ) {
		return SettingsPage::sanitize_module_options( $input, $this->module_slug, $this->fields );
	}
}
