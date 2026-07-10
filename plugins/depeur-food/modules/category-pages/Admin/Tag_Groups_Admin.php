<?php
/**
 * Tag_Groups_Admin — Verwaltung der Schlagwort-Gruppen (Term-Meta `tag_group`) im Backend.
 *
 * WOFÜR: Der „Was koche ich heute"-Filter (Recipe_Filter) gruppiert die Schlagwörter nach der
 * Term-Meta `tag_group` (Support\Tag_Groups). Damit Redakteure diese Zuordnung pflegen können,
 * ergänzt diese Klasse — analog zum Alt-Theme:
 *   - register_term_meta( post_tag, 'tag_group' ) mit Sanitize auf gültige Gruppen-Keys,
 *   - ein Auswahlfeld „Filter-Gruppe" auf der Schlagwort-Bearbeiten- UND Anlegen-Seite,
 *   - eine sortierbare Admin-Spalte „Filter-Gruppe" in der Schlagwort-Liste.
 *
 * Bewusst NATIV (kein ACF nötig): Gruppen-Keys/-Labels kommen aus Support\Tag_Groups (Single
 * Source), das Speichern läuft über die von WordPress bereits nonce-geprüften Term-Form-Hooks
 * (edited_/created_post_tag) plus expliziten Capability-Check.
 *
 * @package Depeur\Food\Modules\CategoryPages\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\CategoryPages\Admin;

use Depeur\Food\Modules\CategoryPages\Support\Tag_Groups;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert Term-Meta, Edit-Feld und sortierbare Spalte für Schlagwort-Gruppen.
 *
 * @since 0.3.0
 */
final class Tag_Groups_Admin {

	/**
	 * Betroffene Taxonomie.
	 *
	 * @since 0.3.0
	 * @var string
	 */
	private const TAXONOMY = 'post_tag';

	/**
	 * Verdrahtet Meta-Registrierung, Edit-Feld, Speichern und Spalte.
	 *
	 * @since 0.3.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );

		// Auswahlfeld auf der Bearbeiten- und der Anlegen-Seite eines Schlagworts.
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'render_edit_field' ), 10, 1 );
		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'render_add_field' ), 10, 1 );

		// Speichern (die Term-Formulare prüfen ihre Nonce bereits im Core → hier Cap + Sanitize).
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save' ), 10, 1 );
		add_action( 'created_' . self::TAXONOMY, array( $this, 'save' ), 10, 1 );

		// Sortierbare Spalte in der Schlagwort-Liste.
		add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_' . self::TAXONOMY . '_custom_column', array( $this, 'render_column' ), 10, 3 );
		add_filter( 'manage_edit-' . self::TAXONOMY . '_sortable_columns', array( $this, 'sortable_column' ) );
		add_action( 'pre_get_terms', array( $this, 'sort_by_group' ) );
	}

	/**
	 * Registriert die Term-Meta `tag_group` (REST-sichtbar, auf gültige Gruppen-Keys begrenzt).
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function register_meta(): void {
		register_term_meta(
			self::TAXONOMY,
			Tag_Groups::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_group' ),
			)
		);
	}

	/**
	 * Sanitisiert einen Gruppen-Wert: nur ein bekannter Gruppen-Key, sonst leer.
	 *
	 * @since 0.3.0
	 *
	 * @param mixed $value Roher Wert.
	 * @return string Gültiger Gruppen-Key oder ''.
	 */
	public function sanitize_group( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';

		return array_key_exists( $value, Tag_Groups::label_map() ) ? $value : '';
	}

	/**
	 * Rendert das Auswahlfeld auf der Schlagwort-BEARBEITEN-Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_Term $term Das bearbeitete Schlagwort.
	 * @return void
	 */
	public function render_edit_field( $term ): void {
		$current = is_object( $term ) ? (string) get_term_meta( $term->term_id, Tag_Groups::META_KEY, true ) : '';
		?>
		<tr class="form-field">
			<th scope="row"><label for="df_tag_group"><?php esc_html_e( 'Filter-Gruppe', 'depeur-food' ); ?></label></th>
			<td>
				<?php $this->select_html( $current ); ?>
				<p class="description"><?php esc_html_e( 'Ordnet dieses Schlagwort einer Gruppe im „Was koche ich heute"-Filter zu.', 'depeur-food' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Rendert das Auswahlfeld auf der Schlagwort-ANLEGEN-Seite.
	 *
	 * @since 0.3.0
	 *
	 * @param string $taxonomy Taxonomie-Slug (ungenutzt, von WordPress hereingereicht).
	 * @return void
	 */
	public function render_add_field( $taxonomy ): void {
		unset( $taxonomy );
		?>
		<div class="form-field">
			<label for="df_tag_group"><?php esc_html_e( 'Filter-Gruppe', 'depeur-food' ); ?></label>
			<?php $this->select_html( '' ); ?>
			<p><?php esc_html_e( 'Ordnet dieses Schlagwort einer Gruppe im „Was koche ich heute"-Filter zu.', 'depeur-food' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Gibt das <select> mit allen bekannten Gruppen aus (+ „keine").
	 *
	 * @since 0.3.0
	 *
	 * @param string $current Aktuell gewählter Gruppen-Key.
	 * @return void
	 */
	private function select_html( string $current ): void {
		?>
		<select name="df_tag_group" id="df_tag_group">
			<option value=""><?php esc_html_e( '— keine —', 'depeur-food' ); ?></option>
			<?php foreach ( Tag_Groups::label_map() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( (string) $key ); ?>" <?php selected( $current, (string) $key ); ?>>
					<?php echo esc_html( (string) $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Speichert die gewählte Gruppe (Cap-Check; Nonce prüft der Term-Form-Core bereits).
	 *
	 * @since 0.3.0
	 *
	 * @param int $term_id Term-ID.
	 * @return void
	 */
	public function save( $term_id ): void {
		$term_id = (int) $term_id;
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- edit-tags.php/add-tag prüfen die Nonce vor edited_/created_{taxonomy}.
		if ( ! isset( $_POST['df_tag_group'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- s. o.
		$group = $this->sanitize_group( wp_unslash( $_POST['df_tag_group'] ) );

		if ( '' === $group ) {
			delete_term_meta( $term_id, Tag_Groups::META_KEY );
			return;
		}
		update_term_meta( $term_id, Tag_Groups::META_KEY, $group );
	}

	/**
	 * Fügt die Spalte „Filter-Gruppe" in der Schlagwort-Liste hinzu.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, string> $columns Bestehende Spalten.
	 * @return array<string, string>
	 */
	public function add_column( $columns ): array {
		$columns = (array) $columns;
		$columns[ Tag_Groups::META_KEY ] = __( 'Filter-Gruppe', 'depeur-food' );

		return $columns;
	}

	/**
	 * Rendert den Zellinhalt der Gruppen-Spalte (Label statt Key).
	 *
	 * @since 0.3.0
	 *
	 * @param string $content     Bisheriger Zellinhalt.
	 * @param string $column_name Spalten-Key.
	 * @param int    $term_id     Term-ID.
	 * @return string
	 */
	public function render_column( $content, $column_name, $term_id ): string {
		if ( Tag_Groups::META_KEY !== $column_name ) {
			return (string) $content;
		}

		$key = (string) get_term_meta( (int) $term_id, Tag_Groups::META_KEY, true );
		if ( '' === $key ) {
			return '<span class="description">—</span>';
		}

		$labels = Tag_Groups::label_map();

		return esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : $key );
	}

	/**
	 * Macht die Gruppen-Spalte sortierbar.
	 *
	 * @since 0.3.0
	 *
	 * @param array<string, string> $columns Sortierbare Spalten.
	 * @return array<string, string>
	 */
	public function sortable_column( $columns ): array {
		$columns                         = (array) $columns;
		$columns[ Tag_Groups::META_KEY ] = Tag_Groups::META_KEY;

		return $columns;
	}

	/**
	 * Sortiert die Schlagwort-Liste nach der Gruppen-Meta, wenn danach sortiert wird.
	 *
	 * @since 0.3.0
	 *
	 * @param \WP_Term_Query $query Die Term-Abfrage.
	 * @return void
	 */
	public function sort_by_group( $query ): void {
		if ( ! is_admin() ) {
			return;
		}
		$orderby = $query->query_vars['orderby'] ?? '';
		if ( Tag_Groups::META_KEY !== $orderby ) {
			return;
		}

		$query->query_vars['meta_key'] = Tag_Groups::META_KEY; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin-Sortierung.
		$query->query_vars['orderby']  = 'meta_value';
	}
}
