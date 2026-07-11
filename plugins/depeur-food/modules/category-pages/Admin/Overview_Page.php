<?php
/**
 * Overview_Page — zentrale Admin-Liste aller Kategorie-Seiten (OE-1).
 *
 * WOFÜR: Kategorie-Seiten sind normale `page`s mit gesetztem Opt-in-Meta `df_catpage_enabled`
 * (bewusst KEIN CPT — Permalink-Stabilität). Dadurch sind sie über den Seiten-Baum verstreut und
 * schwer zu überblicken. Diese Seite listet sie an EINEM Ort: Titel, kuratierte Terms je Taxonomie,
 * Beiträge-pro-Seite (Seite 1 / Folgeseiten), H2-Vorschau-Überschrift — jeweils mit Ansehen-/
 * Bearbeiten-Link.
 *
 * BEWUSST READ-ONLY: reine Übersicht, kein Schreibpfad → nur Capability-Check beim Rendern.
 * Konfiguriert werden Kategorie-Seiten weiterhin in der jeweiligen Seite (ACF-Felder); von hier aus
 * führt der „bearbeiten"-Link direkt dorthin.
 *
 * @package Depeur\Food\Modules\CategoryPages\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Admin;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Modules\CategoryPages\Query\Term_Resolver;
use WP_Term;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert die Kategorie-Seiten-Übersicht.
 *
 * @since 0.3.0
 */
final class Overview_Page {

	/**
	 * Page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-category-pages';

	/**
	 * Opt-in-Meta, das eine Seite zur Kategorie-Seite macht.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const ENABLED_META = 'df_catpage_enabled';

	/**
	 * Erforderliche Capability.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * Verdrahtet die Menü-Registrierung (sichtbar — laufendes Verwaltungs-Werkzeug).
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
	}

	/**
	 * Meldet die Unterseite „Kategorie-Seiten" an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Kategorie-Seiten', 'depeur-food' ),
			__( 'Kategorie-Seiten', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Rendert die Liste aller aktiven Kategorie-Seiten.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$pages = $this->collect_pages();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Kategorie-Seiten', 'depeur-food' ); ?></h1>
			<p style="max-width: 70em;">
				<?php esc_html_e( 'Alle Seiten, die als Kategorie-Seite markiert sind (Opt-in in der Seite selbst). Eine Kategorie-Seite kuratiert Beiträge über Taxonomie-Terms und rendert sie als Raster mit Pagination. Zum Ändern die jeweilige Seite bearbeiten.', 'depeur-food' ); ?>
			</p>

			<?php if ( empty( $pages ) ) : ?>
				<p><?php esc_html_e( 'Noch keine Kategorie-Seiten. Öffne eine Seite und aktiviere dort „Kategorie-Seite".', 'depeur-food' ); ?></p>
			<?php else : ?>
				<p class="description">
					<?php
					printf(
						/* translators: %d: Anzahl Kategorie-Seiten. */
						esc_html( _n( '%d Kategorie-Seite', '%d Kategorie-Seiten', count( $pages ), 'depeur-food' ) ),
						count( $pages )
					);
					?>
				</p>
				<table class="widefat striped" style="max-width: 80em;">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Seite', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Kuratierte Terms', 'depeur-food' ); ?></th>
							<th scope="col" style="width: 12em;"><?php esc_html_e( 'Beiträge/Seite', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'H2-Überschrift (Seite 1)', 'depeur-food' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pages as $page_id ) : ?>
							<?php
							$first   = $this->meta_int( $page_id, 'df_catpage_per_page_first', 4 );
							$per     = $this->meta_int( $page_id, 'df_catpage_per_page', 21 );
							$heading = trim( (string) get_post_meta( $page_id, 'df_catpage_related_heading', true ) );
							$status  = get_post_status( $page_id );
							$view    = get_permalink( $page_id );
							$edit    = get_edit_post_link( $page_id );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( get_the_title( $page_id ) ); ?></strong>
									<?php if ( 'publish' !== $status ) : ?>
										<span class="description">(<?php echo esc_html( (string) $status ); ?>)</span>
									<?php endif; ?>
									<div class="row-actions">
										<?php if ( $edit ) : ?>
											<span><a href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'bearbeiten', 'depeur-food' ); ?></a> | </span>
										<?php endif; ?>
										<?php if ( $view ) : ?>
											<span><a href="<?php echo esc_url( (string) $view ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'ansehen', 'depeur-food' ); ?></a></span>
										<?php endif; ?>
									</div>
								</td>
								<td>
								<?php
								echo wp_kses(
									$this->describe_terms( $page_id ),
									array(
										'strong' => array(),
										'br'     => array(),
										'em'     => array(),
									)
								);
								?>
									</td>
								<td>
									<?php
									printf(
										/* translators: 1: Anzahl Seite 1, 2: Anzahl Folgeseiten. */
										esc_html__( 'Seite 1: %1$d · Folgeseiten: %2$d', 'depeur-food' ),
										(int) $first,
										(int) $per
									);
									?>
								</td>
								<td>
									<?php
									echo '' !== $heading
										? esc_html( $heading )
										: '<span class="description">' . esc_html__( '— keine —', 'depeur-food' ) . '</span>';
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Sammelt die IDs aller Seiten mit gesetztem (truthy) df_catpage_enabled, alphabetisch.
	 *
	 * @since 0.3.0
	 *
	 * @return int[]
	 */
	private function collect_pages(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin-Übersicht, bewusster Meta-Filter.
				'meta_key'         => self::ENABLED_META,
			)
		);

		// Nur wirklich aktivierte (df_catpage_enabled truthy) behalten.
		$enabled = array();
		foreach ( (array) $ids as $id ) {
			if ( get_post_meta( (int) $id, self::ENABLED_META, true ) ) {
				$enabled[] = (int) $id;
			}
		}

		return $enabled;
	}

	/**
	 * Beschreibt die kuratierten Terms einer Seite gruppiert nach Taxonomie.
	 *
	 * @since 0.3.0
	 *
	 * @param int $page_id Seiten-ID.
	 * @return string Sicheres HTML (strong/br/em).
	 */
	private function describe_terms( int $page_id ): string {
		$grouped = Term_Resolver::resolve( $page_id );
		if ( empty( $grouped ) ) {
			return '<em>' . esc_html__( '— keine kuratiert —', 'depeur-food' ) . '</em>';
		}

		$parts = array();
		foreach ( $grouped as $taxonomy => $term_ids ) {
			$tax_obj   = get_taxonomy( (string) $taxonomy );
			$tax_label = $tax_obj ? $tax_obj->labels->singular_name : (string) $taxonomy;

			$names = array();
			foreach ( $term_ids as $tid ) {
				$term = get_term( (int) $tid );
				if ( $term instanceof WP_Term ) {
					$names[] = $term->name;
				}
			}

			$parts[] = '<strong>' . esc_html( (string) $tax_label ) . ':</strong> ' . esc_html( implode( ', ', $names ) );
		}

		return implode( '<br />', $parts );
	}

	/**
	 * Liest einen numerischen Meta-Wert mit Default.
	 *
	 * @since 0.3.0
	 *
	 * @param int    $page_id Seiten-ID.
	 * @param string $key     Meta-Key.
	 * @param int    $fallback Default, wenn nicht gesetzt.
	 * @return int
	 */
	private function meta_int( int $page_id, string $key, int $fallback ): int {
		$value = get_post_meta( $page_id, $key, true );

		return ( '' === $value || null === $value ) ? $fallback : (int) $value;
	}
}
