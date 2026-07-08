<?php
/**
 * Admin/Settings — Settings-Tab des Favoriten-Moduls (Toggle + read-only Diagnose).
 *
 * Meldet via SettingsRegistry einen Tab „Favoriten" an: eine echte Einstellung
 * (WPRM-Button ein/aus) plus einen read-only Diagnose-Block (Meta-Key-Status,
 * Post-Types, WPRM-Präsenz, REST-Endpoints). § 6.2: Modul-Intro + Feld-Beschreibungen.
 * Cross-Module-Disziplin: nutzt nur Core-Klassen + modul-eigene Klassen, keine Fremd-Module.
 *
 * @package Depeur\Food\Modules\Favorites\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;
use Depeur\Food\Modules\Favorites\Integrations\Wprm;
use Depeur\Food\Modules\Favorites\Meta\Like_Counter;
use Depeur\Food\Modules\Favorites\Rest\Favorites_Controller;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert den favorites-Settings-Tab samt Diagnose.
 *
 * @since 0.2.0
 */
final class Settings {

	/**
	 * Modul-Slug (Options-/Tab-Kontext), von module.php hereingereicht.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab an.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Favoriten', 'depeur-food' ),
			array(
				array(
					'id'          => 'wprm_button',
					'label'       => __( 'WPRM-Button automatisch einfügen', 'depeur-food' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Fügt den Favoriten-Button automatisch in die WP-Recipe-Maker-Rezeptbilder ein. Nur wirksam, wenn WP Recipe Maker aktiv ist.', 'depeur-food' ),
				),
				array(
					'id'    => 'diagnose',
					'type'  => 'html',
					'label' => '',
					'html'  => $this->build_diagnostics(),
				),
			),
			__( 'Favoriten-/Merkliste für Besucher (clientseitig via localStorage) plus ein globaler Like-Zähler pro Beitrag. Ersetzt das Legacy-Plugin „Depeur Favoriten": REST statt ungeschütztem AJAX, automatische Cookie→localStorage-Migration, post-type-agnostisch. Buttons via Shortcode [df_favorite_button], das Archiv via [df_favorites_archive].', 'depeur-food' )
		);
	}

	/**
	 * Baut den read-only Diagnose-Block (Meta-Status, Post-Types, WPRM, REST).
	 *
	 * @since 0.2.0
	 *
	 * @return string Sicheres HTML (in render_field zusätzlich via wp_kses_post gefiltert).
	 */
	private function build_diagnostics(): string {
		$post_types = Like_Counter::post_types();
		$first_type = isset( $post_types[0] ) ? $post_types[0] : 'post';

		$registered = registered_meta_key_exists( 'post', Like_Counter::META_KEY, $first_type );
		$rest       = false;
		if ( $registered ) {
			$keys = get_registered_meta_keys( 'post', $first_type );
			$rest = ! empty( $keys[ Like_Counter::META_KEY ]['show_in_rest'] );
		}

		$toggle_route = Favorites_Controller::REST_NAMESPACE . Favorites_Controller::ROUTE_TOGGLE;
		$list_route   = Favorites_Controller::REST_NAMESPACE . Favorites_Controller::ROUTE_LIST;

		$yes = esc_html__( 'ja', 'depeur-food' );
		$no  = esc_html__( 'nein', 'depeur-food' );

		$rows = array(
			array( esc_html__( 'Meta-Key (Like-Zähler)', 'depeur-food' ), '<code>' . esc_html( Like_Counter::META_KEY ) . '</code>' ),
			array( esc_html__( 'Registriert', 'depeur-food' ), $registered ? '✓ ' . $yes : '✗ ' . $no ),
			array( esc_html__( 'REST-sichtbar (show_in_rest)', 'depeur-food' ), $rest ? '✓ ' . $yes : '✗ ' . $no ),
			array( esc_html__( 'Unterstützte Post-Types', 'depeur-food' ), '<code>' . esc_html( implode( ', ', $post_types ) ) . '</code>' ),
			array( esc_html__( 'WP Recipe Maker aktiv', 'depeur-food' ), Wprm::wprm_active() ? '✓ ' . $yes : '✗ ' . $no ),
			array( esc_html__( 'REST-Route (Toggle, POST + Nonce)', 'depeur-food' ), '<code>' . esc_html( $toggle_route ) . '</code>' ),
			array( esc_html__( 'REST-Route (Archiv-Liste, GET)', 'depeur-food' ), '<code>' . esc_html( $list_route ) . '</code>' ),
		);

		ob_start();
		?>
		<p class="description" style="max-width: 60em;">
			<?php esc_html_e( 'Live-Status des Favoriten-Moduls. Der Like-Zähler ist ein globaler Meta-Wert pro Beitrag; die Merkliste selbst liegt clientseitig im localStorage des Besuchers.', 'depeur-food' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 60em;">
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<th scope="row" style="width: 20em;"><?php echo esc_html( $row[0] ); ?></th>
						<td><?php echo wp_kses( $row[1], array( 'code' => array() ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="max-width: 60em;">
			<?php esc_html_e( 'Verwendung: [df_favorite_button style="thumbnail|inline"] für den Button, [df_favorites_archive] für die Merkliste des Besuchers. Der Post-Type-Umfang ist über den Filter depeur_food/favorites/post_types anpassbar.', 'depeur-food' ); ?>
		</p>
		<?php
		return (string) ob_get_clean();
	}
}
