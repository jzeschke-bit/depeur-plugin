<?php
/**
 * Admin/Reset_Overrides — „Alle individuellen Überschreibungen eines Inhaltstyps zurücksetzen".
 *
 * WOFÜR (§ 6.2): Der Typ-Standard („Standard je Inhaltstyp") greift nur für Beiträge OHNE
 * eigene An/Aus-Wahl. Wer den Standard eines Typs verbindlich auf ALLE Beiträge anwenden will
 * (auch die manuell überschriebenen), nutzt pro Typ diesen Reset: er löscht die Per-Post-Meta
 * `show_newsletter_form` + `show_app_promo` für alle Beiträge dieses Typs → sie fallen auf
 * „Standard" (= aktuelle Typ-Vorgabe) zurück.
 *
 * Sicherheits-Pfad (wie handle_module_save im Core): Capability → Nonce → Slug-Sanitize →
 * Whitelist → Aktion → PRG-Redirect. Schließt bewusst nur die beiden Sichtbarkeits-Keys ein;
 * `newsletter_position` (individuelle Position) bleibt unangetastet.
 *
 * @package Depeur\Food\Modules\Newsletter\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\Newsletter\Admin;

use Depeur\Food\Core\AdminMenu;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verarbeitet die Reset-Aktion (admin-post) und zeigt die Erfolgs-Notice.
 *
 * @since 0.3.0
 */
final class Reset_Overrides {

	/**
	 * Admin-post-Action-Name (zugleich der Nonce-Action-Präfix).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	public const ACTION = 'depeur_food_newsletter_reset';

	/**
	 * Modul-Slug (für den Settings-Tab im Redirect-Ziel).
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private string $slug;

	/**
	 * Verdrahtet den admin-post-Handler und die Erfolgs-Notice.
	 *
	 * @since 0.3.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_notices', array( $this, 'maybe_notice' ) );
	}

	/**
	 * Baut die nonce-gesicherte Reset-URL für einen Post-Type (für den Button in den Settings).
	 *
	 * @since 0.3.0
	 *
	 * @param string $post_type Post-Type-Slug.
	 * @return string
	 */
	public static function url( string $post_type ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'    => self::ACTION,
					'post_type' => rawurlencode( $post_type ),
				),
				admin_url( 'admin-post.php' )
			),
			self::ACTION . '_' . $post_type
		);
	}

	/**
	 * Führt den Reset aus: löscht die Sichtbarkeits-Meta aller Beiträge des Typs.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Keine Berechtigung.', 'depeur-food' ) );
		}

		// Slug ZUERST lesen + sanitisieren; die Nonce-Action ist an ihn gebunden.
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

		check_admin_referer( self::ACTION . '_' . $post_type );

		// Whitelist: nur ein aktuell vom Inserter unterstützter Typ ist erlaubt.
		if ( '' === $post_type || ! in_array( $post_type, $this->allowed_post_types(), true ) ) {
			$this->redirect( $post_type, -1 );
		}

		// Zwei-Stufen-Bestätigung: wp_kses_post entfernt jedes inline-confirm() im Button, daher
		// eine echte Interstitial-Seite (native WP-Bestätigung) vor der destruktiven Aktion.
		$confirmed = isset( $_GET['confirm'] ) && '1' === sanitize_key( wp_unslash( $_GET['confirm'] ) );
		if ( ! $confirmed ) {
			$this->render_confirm( $post_type );
			return; // render_confirm() beendet via wp_die; return nur zur Klarheit.
		}

		$deleted = $this->delete_overrides( $post_type );

		$this->redirect( $post_type, $deleted );
	}

	/**
	 * Rendert die native Bestätigungs-Seite (Ja/Abbrechen) vor dem Zurücksetzen.
	 *
	 * @since 0.3.0
	 *
	 * @param string $post_type Post-Type-Slug (bereits whitelist-geprüft).
	 * @return void
	 */
	private function render_confirm( string $post_type ): void {
		$object = get_post_type_object( $post_type );
		$label  = ( $object && ! empty( $object->labels->singular_name ) ) ? (string) $object->labels->singular_name : $post_type;

		$confirm_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'    => self::ACTION,
					'post_type' => rawurlencode( $post_type ),
					'confirm'   => '1',
				),
				admin_url( 'admin-post.php' )
			),
			self::ACTION . '_' . $post_type
		);

		$cancel_url = add_query_arg(
			array(
				'page' => AdminMenu::MENU_SLUG,
				'tab'  => $this->slug,
			),
			admin_url( 'admin.php' )
		);

		$message  = '<h1>' . esc_html__( 'Überschreibungen zurücksetzen?', 'depeur-food' ) . '</h1>';
		$message .= '<p>' . esc_html(
			sprintf(
				/* translators: %s: Name des Inhaltstyps. */
				__( 'Dadurch werden ALLE individuellen An/Aus-Wahlen (Newsletter und App-Promotion) in allen Beiträgen des Typs „%s" gelöscht. Danach folgt jeder dieser Beiträge wieder dem Typ-Standard aus den Einstellungen. Die eingestellte Position bleibt erhalten. Diese Aktion lässt sich nicht rückgängig machen.', 'depeur-food' ),
				$label
			)
		) . '</p>';
		$message .= '<p><a href="' . esc_url( $confirm_url ) . '" class="button button-primary">' . esc_html__( 'Ja, alle zurücksetzen', 'depeur-food' ) . '</a> ';
		$message .= '<a href="' . esc_url( $cancel_url ) . '" class="button">' . esc_html__( 'Abbrechen', 'depeur-food' ) . '</a></p>';

		wp_die(
			wp_kses_post( $message ),
			esc_html__( 'Überschreibungen zurücksetzen?', 'depeur-food' ),
			array(
				'response'  => 200,
				'back_link' => false,
			)
		);
	}

	/**
	 * Zeigt nach dem Redirect eine Erfolgs-/Fehler-Notice auf der Settings-Seite.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function maybe_notice(): void {
		// Reine Anzeige eines Post-Redirect-Flags (kein zustandsändernder Request) → keine Nonce.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( AdminMenu::MENU_SLUG !== $page || ! isset( $_GET['df_nl_reset'] ) ) {
			return;
		}

		$count = (int) $_GET['df_nl_reset'];
		$type  = isset( $_GET['df_nl_reset_type'] ) ? sanitize_key( wp_unslash( $_GET['df_nl_reset_type'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $count < 0 ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'Zurücksetzen fehlgeschlagen: unbekannter Inhaltstyp.', 'depeur-food' )
			);
			return;
		}

		$object = $type ? get_post_type_object( $type ) : null;
		$label  = ( $object && ! empty( $object->labels->singular_name ) ) ? (string) $object->labels->singular_name : $type;

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: Anzahl bereinigter Beiträge, 2: Name des Inhaltstyps. */
					_n(
						'%1$d Überschreibung für „%2$s" zurückgesetzt — dieser Beitrag folgt wieder dem Typ-Standard.',
						'%1$d Überschreibungen für „%2$s" zurückgesetzt — diese Beiträge folgen wieder dem Typ-Standard.',
						$count,
						'depeur-food'
					),
					$count,
					$label
				)
			)
		);
	}

	/**
	 * Aktuell vom Inserter unterstützte Post-Types (identische Quelle wie Fields/Overrides).
	 *
	 * @since 0.3.0
	 *
	 * @return string[]
	 */
	private function allowed_post_types(): array {
		/** This filter is documented in Fields/Overrides.php */
		$types = apply_filters( 'depeur_food/newsletter/post_types', depeur_food()->get_supported_post_types() );

		return array_values( array_unique( array_filter( array_map( 'strval', (array) $types ) ) ) );
	}

	/**
	 * Löscht `show_newsletter_form` + `show_app_promo` für alle Beiträge eines Typs.
	 *
	 * @since 0.3.0
	 *
	 * @param string $post_type Post-Type-Slug (bereits whitelist-geprüft).
	 * @return int Anzahl der Beiträge, bei denen mindestens ein Wert entfernt wurde.
	 */
	private function delete_overrides( string $post_type ): int {
		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// Nur Beiträge, die überhaupt eine der beiden Überschreibungen tragen.
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'     => 'show_newsletter_form',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'show_app_promo',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$count = 0;
		foreach ( $ids as $id ) {
			$removed = (bool) delete_post_meta( (int) $id, 'show_newsletter_form' );
			$removed = (bool) delete_post_meta( (int) $id, 'show_app_promo' ) || $removed;
			$count  += $removed ? 1 : 0;
		}

		return $count;
	}

	/**
	 * PRG-Redirect zurück auf den Newsletter-Settings-Tab mit Ergebnis-Flag.
	 *
	 * @since 0.3.0
	 *
	 * @param string $post_type Post-Type-Slug (für die Notice).
	 * @param int    $count     Anzahl bereinigter Beiträge, oder -1 bei Fehler.
	 * @return void
	 */
	private function redirect( string $post_type, int $count ): void {
		$url = add_query_arg(
			array(
				'page'             => AdminMenu::MENU_SLUG,
				'tab'              => $this->slug,
				'df_nl_reset'      => $count,
				'df_nl_reset_type' => $post_type,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}
}
