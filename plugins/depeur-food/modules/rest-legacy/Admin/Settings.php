<?php
/**
 * Admin/Settings — read-only Diagnose-Tab für rest-legacy.
 *
 * Zeigt die registrierten Legacy-Routen + den WPRM-Status. Keine speicherbaren Felder
 * (SettingsPage unterdrückt den Submit). Bewusst mit deutlichem Hinweis auf die
 * „legacy"-Klassifikation (offene Auth = akzeptierte Tech-Debt, E8).
 *
 * @package Depeur\Food\Modules\RestLegacy\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\RestLegacy\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meldet den Diagnose-Tab an.
 *
 * @since 0.3.0
 */
final class Settings {

	/**
	 * Modul-Slug.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Meldet den Settings-Tab des rest-legacy-Moduls an.
	 *
	 * @since 0.3.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'REST-Legacy', 'depeur-food' ),
			array(
				array(
					'id'    => 'diagnose',
					'type'  => 'html',
					'label' => '',
					'html'  => $this->build_diagnostics(),
				),
			),
			__( 'Stellt die Legacy-REST-Routen von „rest-api-wprm" 1:1 bereit (wl/v1/posts, rest_wprm_recipe_query-Filter, wrm/v1/rating*), damit das alte Plugin deaktiviert werden kann. Klassifikation „legacy" (E8): bekannte Bugs bleiben erhalten und die Rating-Routen sind bewusst OHNE Auth (interner App-Kreis) – Härtung erst in einem künftigen rest-modern-Modul. Dieser Tab ist eine read-only Diagnose.', 'depeur-food' )
		);
	}

	/**
	 * Baut die Diagnose (Routen-Liste + WPRM-Status).
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private function build_diagnostics(): string {
		$wprm = class_exists( 'WPRM_Rating_Database' );

		$routes = array(
			array( 'GET', 'wl/v1/posts?slug=', __( 'Recipe-Slug-Lookup (offen)', 'depeur-food' ) ),
			array( 'FILTER', 'rest_wprm_recipe_query', __( 'posts_per_page = max(custom_per_page, 200)', 'depeur-food' ) ),
			array( 'GET', 'wrm/v1/rating', __( 'alle Ratings', 'depeur-food' ) ),
			array( 'POST', 'wrm/v1/rating', __( 'Rating anlegen/aktualisieren (offen)', 'depeur-food' ) ),
			array( 'GET/DELETE', 'wrm/v1/rating/{id}', __( 'ein Rating', 'depeur-food' ) ),
			array( 'GET/DELETE', 'wrm/v1/rating/recipe/{id}', __( 'Ratings eines Rezepts', 'depeur-food' ) ),
			array( 'GET/DELETE', 'wrm/v1/rating/comment/{id}', __( 'Rating eines Kommentars', 'depeur-food' ) ),
		);

		$yes = esc_html__( 'ja', 'depeur-food' );
		$no  = esc_html__( 'nein', 'depeur-food' );

		ob_start();
		?>
		<p class="description" style="max-width: 62em;">
			<?php esc_html_e( 'WP Recipe Maker aktiv (Voraussetzung für die Rating-Routen):', 'depeur-food' ); ?>
			<strong><?php echo $wprm ? esc_html( '✓ ' . $yes ) : esc_html( '✗ ' . $no ); ?></strong>
		</p>
		<table class="widefat striped" style="max-width: 62em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Methode', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Route', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Zweck', 'depeur-food' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $routes as $route ) : ?>
					<tr>
						<td><code><?php echo esc_html( $route[0] ); ?></code></td>
						<td><code><?php echo esc_html( $route[1] ); ?></code></td>
						<td><?php echo esc_html( $route[2] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="max-width: 62em;">
			<?php esc_html_e( 'Deployment: dieses Modul aktivieren → App-Endpoints gegen die neuen Routen prüfen → „rest-api-wprm" deaktivieren → re-testen. Die Rating-Routen sind bewusst ohne Auth (Legacy-Vertrag, E8).', 'depeur-food' ); ?>
		</p>
		<?php
		return (string) ob_get_clean();
	}
}
