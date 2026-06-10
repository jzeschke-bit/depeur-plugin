<?php
/**
 * Einstellungsseite (Core): „Unterstützte Inhaltstypen“ als einziges Feld, manueller Save-Pfad.
 *
 * @package Depeur\Food\Core\Settings
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Core\Settings;

use Depeur\Food\Core\AdminMenu;
use Depeur\Food\Core\PostTypeRegistry;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Core-Settings-Page. Bewusst KEINE WordPress-Settings-API/options.php, sondern
// ein manueller Self-POST mit explizitem Nonce, eigenem Save-Handler und
// PRG-Redirect. Grund: das einzige Feld ist ein Multi-Checkbox (Liste von
// Post-Types), das sich nicht sauber auf ein skalares register_setting abbilden
// lässt, und wir brauchen nach dem Speichern einen klaren Ort für
// PostTypeRegistry::flush() (Memo-Invalidierung). Modul-Tabs gibt es hier noch
// nicht – die kommen, sobald Module eigene Schemata über SettingsRegistry melden.
/**
 * Klasse SettingsPage.
 *
 * @since 0.1.0
 */
final class SettingsPage {

	/**
	 * Nonce-Action des Save-Formulars.
	 *
	 * @var string
	 */
	private const NONCE_ACTION = 'depeur_food_save_settings';

	/**
	 * Nonce-Feldname im Formular.
	 *
	 * @var string
	 */
	private const NONCE_NAME = 'depeur_food_settings_nonce';

	/**
	 * Behandelt den Self-POST der Settings-Seite: nonce + cap + speichern + flush + PRG.
	 *
	 * Hängt an admin_init und reagiert nur, wenn das eigene Formular abgeschickt
	 * wurde (Nonce-Feld vorhanden). Speichert die ausgewählten Post-Types, verwirft
	 * danach das Registry-Memo und leitet per Post/Redirect/Get zurück, damit ein
	 * Reload nicht erneut speichert.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function maybe_handle_save(): void {
		// Nur auf das eigene Formular reagieren – sonst läuft admin_init normal weiter.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		// Whitelisting: nur Slugs akzeptieren, die tatsächlich als UI-Option
		// verfügbar sind (get_available()). Schützt vor manipulierten POST-Werten.
		$available = array_keys( PostTypeRegistry::get_instance()->get_available() );

		$submitted = array();
		if ( isset( $_POST[ PostTypeRegistry::OPTION ] ) ) {
			$submitted = array_map( 'sanitize_key', (array) wp_unslash( $_POST[ PostTypeRegistry::OPTION ] ) );
		}

		$clean = array_values( array_unique( array_intersect( $submitted, $available ) ) );

		update_option( PostTypeRegistry::OPTION, $clean );

		// Belt-and-Suspenders: Memo verwerfen, auch wenn gleich ein PRG-Redirect
		// folgt – garantiert konsistenten Read-After-Write im selben Request.
		PostTypeRegistry::flush();

		$redirect = add_query_arg(
			array(
				'page'    => AdminMenu::MENU_SLUG,
				'updated' => 'true',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Rendert die Settings-Seite (Submenu-Callback).
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$available = PostTypeRegistry::get_instance()->get_available();
		$current   = PostTypeRegistry::get_instance()->get_supported();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Depeur Food – Einstellungen', 'depeur-food' ); ?></h1>

			<?php
			// Erfolgs-Notice nach PRG-Redirect (reines Anzeige-Flag, kein State-Change).
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anzeige-Flag nach eigenem Redirect, keine Statusänderung.
			if ( isset( $_GET['updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['updated'] ) ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Einstellungen gespeichert.', 'depeur-food' ); ?></p>
				</div>
				<?php
			endif;
			?>

			<?php // § 6.2 Admin-UI-Doku: Intro für den Site-Owner, bewusst nicht-technisch. ?>
			<p class="description" style="max-width: 50em;">
				<?php
				esc_html_e(
					'Lege fest, für welche Inhaltstypen die Funktionen von Depeur Food gelten sollen. Standardmäßig sind das Beiträge. Auf Seiten mit eigenen Inhaltstypen (z. B. Rezepte oder Cocktails) wähle hier zusätzlich den passenden Typ aus – nur die hier aktivierten Typen werden von den Modulen verarbeitet.',
					'depeur-food'
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( add_query_arg( 'page', AdminMenu::MENU_SLUG, admin_url( 'admin.php' ) ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Unterstützte Inhaltstypen', 'depeur-food' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Unterstützte Inhaltstypen', 'depeur-food' ); ?></span>
								</legend>
								<?php
								foreach ( $available as $slug => $label ) :
									$field_id = 'depeur-food-pt-' . $slug;
									?>
									<label for="<?php echo esc_attr( $field_id ); ?>" style="display:block; margin-bottom:4px;">
										<input
											type="checkbox"
											id="<?php echo esc_attr( $field_id ); ?>"
											name="<?php echo esc_attr( PostTypeRegistry::OPTION ); ?>[]"
											value="<?php echo esc_attr( $slug ); ?>"
											<?php checked( in_array( $slug, $current, true ) ); ?>
										/>
										<?php echo esc_html( $label ); ?>
										<code><?php echo esc_html( $slug ); ?></code>
									</label>
									<?php
								endforeach;
								?>
								<p class="description">
									<?php esc_html_e( 'Mindestens ein Typ sollte aktiv sein. Wird nichts ausgewählt, fällt das Plugin automatisch auf „Beiträge“ (post) zurück.', 'depeur-food' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Einstellungen speichern', 'depeur-food' ) ); ?>
			</form>
		</div>
		<?php
	}
}
