<?php
/**
 * Admin/Settings — Schema-Engine-Tab: Schalter, publishingPrinciples-URL + Diagnose.
 *
 * Meldet den Settings-Tab via SettingsRegistry an (§ 6.2 Modul-Intro + Feld-Beschreibungen)
 * und rendert unter den zwei speicherbaren Feldern eine read-only Diagnose: welche Provider
 * aktiv sind (Rank Math/WPRM/ACF) und welche Felder registriert + REST-sichtbar sind.
 * Cross-Module-Disziplin: nutzt nur Core-Klassen + die modul-eigenen Support-Helfer.
 *
 * @package Depeur\Food\Modules\SchemaEngine\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\SchemaEngine\Support\Dependencies;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den schema-engine-Settings-Tab samt Diagnose.
 *
 * @since 0.2.0
 */
final class Settings {

	/**
	 * Modul-Slug (Options-/Tab-Kontext, aus module.php hereingereicht).
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab an (läuft während init, vor dem späteren Render).
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Schema-Engine', 'depeur-food' ),
			array(
				array(
					'id'          => 'social_profiles',
					'label'       => __( 'Autor-Social-Profile anzeigen', 'depeur-food' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Blendet die Social-Profil-Felder (Facebook, LinkedIn, Instagram, X, YouTube, Website, E-Mail) im Autor-Profil ein und nimmt die befüllten URLs in das sameAs des Autor-Schemas auf. Ausgeschaltet werden die Felder gar nicht erst bereitgestellt.', 'depeur-food' ),
				),
				array(
					'id'          => 'publishing_principles_url',
					'label'       => __( 'Veröffentlichungs-Prinzipien (URL)', 'depeur-food' ),
					'type'        => 'text',
					'default'     => '',
					'description' => __( 'Optionale URL zu den Veröffentlichungs-Prinzipien. Wird als publishingPrinciples an das publisher-Schema gehängt. Leer lassen, um die Angabe wegzulassen.', 'depeur-food' ),
				),
				array(
					'id'    => 'diagnose',
					'type'  => 'html',
					'label' => '',
					'html'  => $this->build_diagnostics(),
				),
			),
			__( 'Strukturierte Daten (Schema.org) über Rank Math: reichert den Autor um jobTitle, alumniOf, knowsAbout und sameAs an und hängt auf Kategorie-/Archiv-Seiten das WPRM-Rezept als CollectionPage ein. Ersetzt das Alt-Plugin „Category Schema" und die Theme-Datei „rank-math.php". Rank Math ist Voraussetzung; WPRM wird nur genutzt, wenn vorhanden.', 'depeur-food' )
		);
	}

	/**
	 * Baut die read-only Diagnose (Provider-Status + registrierte Felder).
	 *
	 * @since 0.2.0
	 *
	 * @return string Sicheres HTML (in render_field zusätzlich via wp_kses_post gefiltert).
	 */
	private function build_diagnostics(): string {
		$fields = require dirname( __DIR__ ) . '/config/fields.php';
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$rank_math  = Dependencies::rank_math_active();
		$wprm       = Dependencies::wprm_active();
		$acf        = function_exists( 'acf_add_local_field_group' );
		$social     = $this->social_enabled();
		$post_types = $this->review_post_types();

		ob_start();
		?>
		<h3><?php esc_html_e( 'Diagnose', 'depeur-food' ); ?></h3>
		<p class="description" style="max-width: 60em;">
			<?php esc_html_e( 'Live-Status der Schema-Engine. „Registriert" prüft die WordPress-Meta-API, „REST" das show_in_rest-Flag. Rank Math ist Voraussetzung für die Schema-Ausgabe; ohne WPRM entfällt der Rezept-Teil.', 'depeur-food' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 60em; margin-bottom: 1em;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Rank Math aktiv (Voraussetzung)', 'depeur-food' ); ?></strong></td>
					<td><?php echo $rank_math ? '✓' : '✗'; ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WP Recipe Maker aktiv (optional)', 'depeur-food' ); ?></strong></td>
					<td><?php echo $wprm ? '✓' : '✗'; ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'ACF aktiv (Editor-UI)', 'depeur-food' ); ?></strong></td>
					<td><?php echo $acf ? '✓' : '✗'; ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Autor-Social-Box eingeschaltet', 'depeur-food' ); ?></strong></td>
					<td><?php echo $social ? '✓' : '✗'; ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Review-Feld für Post-Types', 'depeur-food' ); ?></strong></td>
					<td><code><?php echo esc_html( implode( ', ', $post_types ) ); ?></code></td>
				</tr>
			</tbody>
		</table>
		<table class="widefat striped" style="max-width: 60em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Meta-Key', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Objekt', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Subtypes', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Group', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Registriert', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'REST', 'depeur-food' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $fields as $field ) {
					if ( empty( $field['name'] ) || empty( $field['object'] ) ) {
						continue;
					}

					$is_social = isset( $field['group'] ) && 'author_social' === $field['group'];
					$status    = $this->field_status( $field, $post_types );
					$subtypes  = $this->subtypes_label( $field, $post_types );
					?>
					<tr>
						<td><code><?php echo esc_html( $field['name'] ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', (array) $field['object'] ) ); ?></td>
						<td><?php echo esc_html( $subtypes ); ?></td>
						<td><?php echo esc_html( isset( $field['group'] ) ? (string) $field['group'] : '—' ); ?></td>
						<td>
							<?php
							if ( $is_social && ! $social ) {
								esc_html_e( 'abgeschaltet', 'depeur-food' );
							} else {
								echo $status['registered'] ? '✓' : '✗';
							}
							?>
						</td>
						<td><?php echo $status['rest'] ? '✓' : '✗'; ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Ist die Autor-Social-Box aktiv? (Setting-Default an + Filter) — für die Diagnose.
	 *
	 * Spiegelt bewusst die Logik in Provisioning\Fields::social_enabled(), damit der Tab den
	 * echten Laufzeit-Zustand zeigt (inkl. Filter-Override).
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	private function social_enabled(): bool {
		$options = get_option( SettingsRegistry::option_key( $this->slug ), array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$enabled = ! array_key_exists( 'social_profiles', $options ) || ! empty( $options['social_profiles'] );

		/** This filter is documented in Provisioning/Fields.php */
		return (bool) apply_filters( 'depeur_food/schema_engine/social_profiles', $enabled );
	}

	/**
	 * Die effektiven Post-Types des Review-Felds (ADR-4, gefiltert) — für die Diagnose.
	 *
	 * @since 0.2.0
	 *
	 * @return string[]
	 */
	private function review_post_types(): array {
		$types = depeur_food()->get_supported_post_types();

		/** This filter is documented in Provisioning/Fields.php */
		$types = apply_filters( 'depeur_food/schema_engine/post_types', $types );

		if ( ! is_array( $types ) || empty( $types ) ) {
			return array( 'post' );
		}

		return array_values( array_unique( array_map( 'strval', $types ) ) );
	}

	/**
	 * Live-Status (registriert? REST?) für die primäre Registrierung eines Feldes.
	 *
	 * @since 0.2.0
	 *
	 * @param array    $field      Feld-Definition.
	 * @param string[] $post_types Effektive Review-Post-Types (für reviewed_by).
	 * @return array{registered:bool,rest:bool}
	 */
	private function field_status( array $field, array $post_types ): array {
		$object  = (string) $field['object'][0];
		$subtype = '';

		if ( 'user' !== $object ) {
			// reviewed_by wird post-type-agnostisch registriert → gegen den ersten echten Typ prüfen.
			if ( isset( $field['name'] ) && 'reviewed_by' === $field['name'] && ! empty( $post_types ) ) {
				$subtype = (string) $post_types[0];
			} else {
				$subtype = isset( $field['subtypes'][ $object ][0] ) ? (string) $field['subtypes'][ $object ][0] : '';
			}
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
	 * Lesbare Subtype-Darstellung (reviewed_by zeigt die effektiven Post-Types).
	 *
	 * @since 0.2.0
	 *
	 * @param array    $field      Feld-Definition.
	 * @param string[] $post_types Effektive Review-Post-Types.
	 * @return string
	 */
	private function subtypes_label( array $field, array $post_types ): string {
		$parts = array();

		foreach ( (array) $field['object'] as $object ) {
			if ( 'user' === $object ) {
				$parts[] = 'user';
				continue;
			}

			if ( isset( $field['name'] ) && 'reviewed_by' === $field['name'] && 'post' === $object ) {
				$parts[] = 'post: ' . ( empty( $post_types ) ? '—' : implode( '/', $post_types ) );
				continue;
			}

			$subs    = isset( $field['subtypes'][ $object ] ) ? (array) $field['subtypes'][ $object ] : array();
			$parts[] = $object . ': ' . ( empty( $subs ) ? '—' : implode( '/', $subs ) );
		}

		return implode( ' · ', $parts );
	}
}
