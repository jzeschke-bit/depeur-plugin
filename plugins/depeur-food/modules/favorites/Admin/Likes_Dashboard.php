<?php
/**
 * Likes_Dashboard — Admin-Übersicht: welche Beiträge haben die meisten Likes?
 *
 * WOFÜR: Der Like-Zähler (_my_favorite_post_likes) ist bewusst nur intern (keine öffentliche
 * Anzeige). Dieses Dashboard macht ihn für Redaktion/Betreiber sichtbar: eine Rangliste der
 * meistgelikten Beiträge über alle unterstützten Post-Types — plus Gesamt-Kennzahlen.
 *
 * BEWUSST READ-ONLY: reine Anzeige, kein Schreibpfad → kein Nonce nötig, nur Capability-Check
 * beim Rendern. Die Zählerstände ändert ausschließlich der Favoriten-Toggle (Frontend, geclampt).
 *
 * QUELLE DER WAHRHEIT: Meta-Key + Ziel-Post-Types kommen aus Like_Counter (dieselbe Fassade, die
 * auch Toggle/Shortcodes nutzen) — kein zweiter, driftender Ort für den Key.
 *
 * @package Depeur\Food\Modules\Favorites\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Favorites\Admin;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Modules\Favorites\Meta\Like_Counter;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert und rendert die Likes-Rangliste.
 *
 * @since 0.3.0
 */
final class Likes_Dashboard {

	/**
	 * page-Parameter der Unterseite.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const PAGE_SLUG = 'depeur-food-likes';

	/**
	 * Erforderliche Capability.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const CAP = 'manage_options';

	/**
	 * Obergrenze der Rangliste (Top N). Bewusst begrenzt, damit die Seite schnell bleibt.
	 *
	 * @since 0.3.0
	 * @var int
	 */
	private const LIMIT = 100;

	/**
	 * Verdrahtet die Menü-Registrierung.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		// Prio 20: nach dem Core-Menü (AdminMenu::register), damit MENU_SLUG existiert.
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
	}

	/**
	 * Meldet die Unterseite „Likes" unter dem Depeur-Food-Menü an.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Likes', 'depeur-food' ),
			__( 'Likes', 'depeur-food' ),
			self::CAP,
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Rendert die Rangliste der meistgelikten Beiträge.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$query = $this->query_top_liked();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Likes', 'depeur-food' ); ?></h1>
			<p style="max-width: 65em;">
				<?php
				printf(
					/* translators: %s: Meta-Key des Like-Zählers. */
					esc_html__( 'Rangliste nach dem internen Like-Zähler (%s). Der Zähler ist bewusst nicht öffentlich; diese Seite macht ihn sichtbar.', 'depeur-food' ),
					'<code>' . esc_html( Like_Counter::META_KEY ) . '</code>'
				);
				?>
			</p>

			<?php if ( empty( $query->posts ) ) : ?>
				<p><?php esc_html_e( 'Noch keine Likes erfasst.', 'depeur-food' ); ?></p>
			<?php else : ?>
				<?php $this->render_summary( $query ); ?>
				<table class="widefat striped" style="max-width: 70em;">
					<thead>
						<tr>
							<th scope="col" style="width: 4em;"><?php esc_html_e( 'Rang', 'depeur-food' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Beitrag', 'depeur-food' ); ?></th>
							<th scope="col" style="width: 12em;"><?php esc_html_e( 'Typ', 'depeur-food' ); ?></th>
							<th scope="col" style="width: 8em;"><?php esc_html_e( 'Likes', 'depeur-food' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $query->posts as $index => $post ) : ?>
							<?php
							$post_id   = (int) $post->ID;
							$likes     = Like_Counter::get_likes( $post_id );
							$type_obj  = get_post_type_object( $post->post_type );
							$type_name = $type_obj ? $type_obj->labels->singular_name : $post->post_type;
							?>
							<tr>
								<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>" target="_blank" rel="noopener">
										<?php echo esc_html( get_the_title( $post_id ) ); ?>
									</a>
									<?php $edit = get_edit_post_link( $post_id ); ?>
									<?php if ( $edit ) : ?>
										<span class="description">– <a href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'bearbeiten', 'depeur-food' ); ?></a></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( (string) $type_name ); ?></td>
								<td><strong><?php echo esc_html( (string) $likes ); ?></strong></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( count( $query->posts ) >= self::LIMIT ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %d: Obergrenze der Liste. */
							esc_html__( 'Nur die Top %d werden angezeigt.', 'depeur-food' ),
							(int) self::LIMIT
						);
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Baut die Abfrage der meistgelikten Beiträge (nur solche mit Zähler > 0).
	 *
	 * @since 0.3.0
	 *
	 * @return \WP_Query
	 */
	private function query_top_liked(): \WP_Query {
		return new \WP_Query(
			array(
				'post_type'      => Like_Counter::post_types(),
				'post_status'    => 'publish',
				'posts_per_page' => self::LIMIT,
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				// Nur Beiträge mit tatsächlichem Zähler (> 0). meta_key setzt zugleich das Sortierfeld.
				'meta_key'       => Like_Counter::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin-Rangliste, bewusst nach Meta sortiert.
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- s. o.
					array(
						'key'     => Like_Counter::META_KEY,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);
	}

	/**
	 * Rendert Gesamt-Kennzahlen (Summe aller Likes + Anzahl gelikter Beiträge).
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_Query $query Ergebnis der Rangliste.
	 * @return void
	 */
	private function render_summary( \WP_Query $query ): void {
		$total = 0;
		foreach ( $query->posts as $post ) {
			$total += Like_Counter::get_likes( (int) $post->ID );
		}
		?>
		<p>
			<strong><?php echo esc_html( (string) count( $query->posts ) ); ?></strong>
			<?php esc_html_e( 'gelikte Beiträge', 'depeur-food' ); ?>
			· <strong><?php echo esc_html( (string) $total ); ?></strong>
			<?php esc_html_e( 'Likes gesamt (Top-Liste)', 'depeur-food' ); ?>
		</p>
		<?php
	}
}
