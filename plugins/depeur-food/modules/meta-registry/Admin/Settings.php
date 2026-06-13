<?php
/**
 * Admin/Settings — Modul-Tab mit read-only Diagnose (BRIEF meta-registry § 7).
 *
 * Meldet einen Settings-Tab via SettingsRegistry an (Modul-Intro + Diagnose) und rendert eine
 * read-only Tabelle, die zur Laufzeit zeigt, welche Felder registriert und REST-sichtbar sind.
 * Keine speicherbaren Felder → kein Save-Button (SettingsPage unterdrückt ihn bei reinen
 * Read-only-Tabs). Cross-Module-Disziplin: nutzt nur Core-Klassen, keine Fremd-Module.
 *
 * @package Depeur\Food\Modules\MetaRegistry\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\MetaRegistry\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den meta-registry-Settings-Tab samt Diagnose.
 *
 * @since 0.1.0
 */
final class Settings {

	/**
	 * Modul-Slug (Options-/Tab-Kontext, aus module.php hereingereicht).
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab an.
	 *
	 * Läuft während init (ModuleManager-Load) – vor dem späteren Settings-Render. Die
	 * Diagnose wird hier einmalig gebaut (leichtgewichtig, nur Admin-Requests).
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Meta-Registry', 'depeur-food' ),
			array(
				array(
					'id'    => 'diagnose',
					'type'  => 'html',
					'label' => '',
					'html'  => $this->build_diagnostics(),
				),
			),
			__( 'Datenschicht-Fundament: registriert alle Custom Fields aus der ACF-Discovery als Post-/User-/Term-Meta (REST + Sanitize) und definiert die zugehörigen ACF-Field-Groups (Editor-UI). Doppel-Owner-Pattern: das Plugin definiert, ACF rendert, Feature-Module schreiben/lesen. Dieser Tab ist eine read-only Diagnose.', 'depeur-food' )
		);
	}

	/**
	 * Baut die read-only Diagnose-Tabelle (registrierte Felder + REST-Status).
	 *
	 * @since 0.1.0
	 *
	 * @return string Sicheres HTML (wird in render_field zusätzlich via wp_kses_post gefiltert).
	 */
	private function build_diagnostics(): string {
		$fields = require dirname( __DIR__ ) . '/config/fields.php';
		/** This filter is documented in Registry/Field_Registrar.php */
		$fields = apply_filters( 'depeur_food/meta/registry', $fields );
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$groups     = require dirname( __DIR__ ) . '/config/groups.php';
		$acf_active = function_exists( 'acf_add_local_field_group' );
		$reg_count  = 0;
		$rest_count = 0;

		ob_start();
		?>
		<p class="description" style="max-width: 60em;">
			<?php esc_html_e( 'Live-Status der registrierten Meta-Keys. „Registriert" prüft die WordPress-Meta-API, „REST" das show_in_rest-Flag, „Editor-UI" ob ein ACF-Feld existiert (Orphans laufen meta-only).', 'depeur-food' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 60em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Meta-Key', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Objekt', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Subtypes', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'ACF-Group', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Registriert', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'REST', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Editor-UI', 'depeur-food' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $fields as $field ) {
					if ( empty( $field['name'] ) || empty( $field['object'] ) ) {
						continue;
					}

					$status = $this->field_status( $field );
					if ( $status['registered'] ) {
						++$reg_count;
					}
					if ( $status['rest'] ) {
						++$rest_count;
					}

					$editor_ui = ! ( isset( $field['editor_ui'] ) && false === $field['editor_ui'] );
					$group_str = '';
					if ( ! empty( $field['group'] ) ) {
						$group_str = implode( ', ', (array) $field['group'] );
					}
					?>
					<tr>
						<td><code><?php echo esc_html( $field['name'] ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', (array) $field['object'] ) ); ?></td>
						<td><?php echo esc_html( $this->subtypes_label( $field ) ); ?></td>
						<td><?php echo '' === $group_str ? '—' : esc_html( $group_str ); ?></td>
						<td><?php echo $status['registered'] ? '✓' : '✗'; ?></td>
						<td><?php echo $status['rest'] ? '✓' : '✗'; ?></td>
						<td><?php echo $editor_ui ? '✓' : '✗'; ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<p class="description" style="max-width: 60em;">
			<?php
			printf(
				/* translators: 1: registered count, 2: total count, 3: REST-visible count, 4: ACF active, 5: ACF group count. */
				esc_html__( 'Felder registriert: %1$d/%2$d · REST-sichtbar: %3$d · ACF aktiv: %4$s · ACF-Groups definiert: %5$d.', 'depeur-food' ),
				(int) $reg_count,
				count( $fields ),
				(int) $rest_count,
				$acf_active ? esc_html__( 'ja', 'depeur-food' ) : esc_html__( 'nein', 'depeur-food' ),
				is_array( $groups ) ? count( $groups ) : 0
			);
			?>
		</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Live-Status (registriert? REST?) für die primäre Registrierung eines Feldes.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field Feld-Definition.
	 * @return array{registered:bool,rest:bool}
	 */
	private function field_status( array $field ): array {
		$object  = (string) $field['object'][0];
		$subtype = '';
		if ( 'user' !== $object ) {
			$subtype = isset( $field['subtypes'][ $object ][0] ) ? (string) $field['subtypes'][ $object ][0] : '';
		}

		$registered = registered_meta_key_exists( $object, $field['name'], $subtype );
		$rest       = false;
		if ( $registered ) {
			$keys = get_registered_meta_keys( $object, $subtype );
			$rest = ! empty( $keys[ $field['name'] ]['show_in_rest'] );
		}

		return array(
			'registered' => $registered,
			'rest'       => $rest,
		);
	}

	/**
	 * Lesbare Subtype-Darstellung (z. B. "post: post/page · term: category").
	 *
	 * @since 0.1.0
	 *
	 * @param array $field Feld-Definition.
	 * @return string
	 */
	private function subtypes_label( array $field ): string {
		$parts = array();
		foreach ( (array) $field['object'] as $object ) {
			if ( 'user' === $object ) {
				$parts[] = 'user';
				continue;
			}
			$subs    = isset( $field['subtypes'][ $object ] ) ? (array) $field['subtypes'][ $object ] : array();
			$parts[] = $object . ': ' . ( empty( $subs ) ? '—' : implode( '/', $subs ) );
		}
		return implode( ' · ', $parts );
	}
}
