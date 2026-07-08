<?php
/**
 * Admin/Settings — read-only Diagnose-Tab für language-selector.
 *
 * Zeigt, für welche Post-Types/Taxonomien link_de/link_en angelegt sind und ob sie in der
 * REST-API sichtbar sind. Keine speicherbaren Felder (SettingsPage unterdrückt den Submit).
 *
 * @package Depeur\Food\Modules\LanguageSelector\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\LanguageSelector\Admin;

use Depeur\Food\Core\Settings\SettingsRegistry;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meldet den Diagnose-Tab an.
 *
 * @since 0.2.0
 */
final class Settings {

	/**
	 * Modul-Slug.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * @since 0.2.0
	 *
	 * @param string $slug Modul-Slug (= Ordnername).
	 */
	public function __construct( string $slug ) {
		$this->slug = $slug;

		SettingsRegistry::register(
			$this->slug,
			__( 'Sprachumschalter', 'depeur-food' ),
			array(
				array(
					'id'    => 'diagnose',
					'type'  => 'html',
					'label' => '',
					'html'  => $this->build_diagnostics(),
				),
			),
			__( 'Legt die Felder link_de/link_en automatisch für Post + Term an und gibt hreflang-Tags im <head> aus. Sprachumschalter im Theme via Shortcode [df_language_switcher]. Aktiviere die Ziel-Inhaltstypen im Core-Tab → Unterstützte Post-Types (post-type-agnostisch). Dieser Tab ist eine read-only Diagnose.', 'depeur-food' )
		);
	}

	/**
	 * Baut die Diagnose-Tabelle (registrierte Ziele + REST-Status).
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	private function build_diagnostics(): string {
		$post_types = (array) apply_filters( 'depeur_food/language_selector/post_types', depeur_food()->get_supported_post_types() );
		$taxonomies = (array) apply_filters( 'depeur_food/language_selector/taxonomies', array( 'category' ) );

		ob_start();
		?>
		<p class="description" style="max-width: 60em;">
			<?php esc_html_e( 'Für diese Objekte werden link_de/link_en angelegt (REST-sichtbar). Der Sprachumschalter erscheint per Shortcode [df_language_switcher].', 'depeur-food' ); ?>
		</p>
		<table class="widefat striped" style="max-width: 60em;">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Meta-Key', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Post-Types', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Taxonomien', 'depeur-food' ); ?></th>
					<th scope="col"><?php esc_html_e( 'REST', 'depeur-food' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array( 'link_de', 'link_en' ) as $key ) : ?>
					<?php
					$subtype    = isset( $post_types[0] ) ? (string) $post_types[0] : '';
					$registered = registered_meta_key_exists( 'post', $key, $subtype );
					$rest       = false;
					if ( $registered ) {
						$keys = get_registered_meta_keys( 'post', $subtype );
						$rest = ! empty( $keys[ $key ]['show_in_rest'] );
					}
					?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( implode( ', ', $post_types ) ); ?></td>
						<td><?php echo esc_html( implode( ', ', $taxonomies ) ); ?></td>
						<td><?php echo $rest ? '✓' : '✗'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return (string) ob_get_clean();
	}
}
