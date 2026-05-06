<?php
/**
 * Ausgabe der Admin-Seite Autoload Inspector (Tabs, Tabelle, Filter, Formulare).
 *
 * @package Depeur\WPSuite\Modules\AutoloadCleanup\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\WPSuite\Modules\AutoloadCleanup\Admin;

use Depeur\WPSuite\Modules\AutoloadCleanup\Services\SizeCalculator;
use Depeur\WPSuite\Modules\AutoloadCleanup\Services\SuspicionScorer;

// Kein Zugriff außerhalb von WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rendert die komplette UI (escaped).
 */
class Views {

	/**
	 * Rendert die Seite mit Tabs und Inhalt.
	 *
	 * @param array $data Daten vom ScreenController.
	 */
	public static function render( array $data ) {
		$tab    = isset( $data['tab'] ) ? $data['tab'] : 'autoload';
		$base   = isset( $data['base_url'] ) ? $data['base_url'] : '';
		$msg    = isset( $data['message'] ) ? $data['message'] : '';
		$msg_t  = isset( $data['msg_text'] ) ? $data['msg_text'] : '';
		self::render_notice( $msg, $msg_t );
		?>
		<div class="wrap depeur-suite-wrap">
			<h1><?php esc_html_e( 'Autoload Inspector', 'depeur-wp-suite' ); ?></h1>
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'autoload', $base ) ); ?>" class="nav-tab <?php echo $tab === 'autoload' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Autoload', 'depeur-wp-suite' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'all', $base ) ); ?>" class="nav-tab <?php echo $tab === 'all' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Alle Optionen', 'depeur-wp-suite' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'suspicious', $base ) ); ?>" class="nav-tab <?php echo $tab === 'suspicious' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Verdächtig', 'depeur-wp-suite' ); ?></a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'rules', $base ) ); ?>" class="nav-tab <?php echo $tab === 'rules' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Regeln & Ignorieren', 'depeur-wp-suite' ); ?></a>
			</nav>
			<?php
			if ( $tab === 'rules' ) {
				self::render_rules_tab( $data );
			} elseif ( $tab === 'suspicious' ) {
				self::render_suspicious_tab( $data );
			} else {
				self::render_options_table( $data );
			}
			?>
		</div>
		<?php
		if ( $tab !== 'rules' ) :
			?>
		<script>
		(function(){
			var el = document.getElementById('autoload-select-all');
			if (!el) return;
			el.addEventListener('change', function(){
				document.querySelectorAll('.autoload-row-cb').forEach(function(cb){ cb.checked = el.checked; });
			});
		})();
		</script>
		<?php
		endif;
	}

	/**
	 * Admin-Notice aus Message-Code und optionalem Text.
	 *
	 * @param string $code Message-Code.
	 * @param string $text Optionaler Text.
	 */
	private static function render_notice( $code, $text = '' ) {
		if ( $code === '' ) {
			return;
		}
		$cls = 'notice-success';
		if ( in_array( $code, array( 'nonce_error', 'delete_error', 'prefix_error', 'prefix_confirm_required', 'rules_error' ), true ) ) {
			$cls = 'notice-error';
		} elseif ( $code === 'prefix_ok' || $code === 'bulk_ok' || $code === 'rules_ok' ) {
			$cls = 'notice-success';
		}
		$message = $text;
		if ( $message === '' ) {
			switch ( $code ) {
				case 'nonce_error':
					$message = __( 'Sicherheitsprüfung fehlgeschlagen. Bitte erneut versuchen.', 'depeur-wp-suite' );
					break;
				case 'prefix_confirm_required':
					$message = __( 'Bitte bestätigen Sie das Löschen per Checkbox.', 'depeur-wp-suite' );
					break;
				default:
					$message = $code;
			}
		}
		?>
		<div class="notice <?php echo esc_attr( $cls ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	/**
	 * Filterleiste und Tabelle Optionen.
	 *
	 * @param array $data Daten.
	 */
	private static function render_options_table( array $data ) {
		$base = $data['base_url'];
		$tab  = $data['tab'];
		$search = $data['search'];
		$min_bytes = $data['min_bytes'];
		$filter_autoload = $data['filter_autoload'];
		$filter_prefix = $data['filter_prefix'];
		$orderby = $data['orderby'];
		$order = $data['order'];
		$paged = $data['paged'];
		$per_page = $data['per_page'];
		$total = $data['total'];
		$items = $data['items'];
		$nonce_delete = $data['nonce_delete'];
		$min_suspicion = $tab === 'suspicious' ? 'medium' : 'low';

		$query_args = array( 'tab' => $tab );
		if ( $search !== '' ) {
			$query_args['s'] = $search;
		}
		if ( $min_bytes > 0 ) {
			$query_args['min_bytes'] = $min_bytes;
		}
		if ( $filter_prefix !== '' ) {
			$query_args['prefix'] = $filter_prefix;
		}
		if ( $filter_autoload !== 'all' ) {
			$query_args['autoload'] = $filter_autoload;
		}
		$query_args['orderby'] = $orderby;
		$query_args['order']   = $order;
		?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin: 1em 0;">
			<input type="hidden" name="page" value="<?php echo esc_attr( ScreenController::PAGE_SLUG ); ?>" />
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Option-Name suchen…', 'depeur-wp-suite' ); ?>" />
			<input type="number" name="min_bytes" value="<?php echo esc_attr( (string) $min_bytes ); ?>" min="0" placeholder="<?php esc_attr_e( 'Min. Bytes', 'depeur-wp-suite' ); ?>" style="width: 100px;" />
			<input type="text" name="prefix" value="<?php echo esc_attr( $filter_prefix ); ?>" placeholder="<?php esc_attr_e( 'Prefix (optional)', 'depeur-wp-suite' ); ?>" />
			<?php if ( $tab === 'all' ) : ?>
				<select name="autoload">
					<option value="all" <?php selected( $filter_autoload, 'all' ); ?>><?php esc_html_e( 'Alle', 'depeur-wp-suite' ); ?></option>
					<option value="yes" <?php selected( $filter_autoload, 'yes' ); ?>><?php esc_html_e( 'Nur autoload=yes', 'depeur-wp-suite' ); ?></option>
					<option value="no" <?php selected( $filter_autoload, 'no' ); ?>><?php esc_html_e( 'Nur autoload=no', 'depeur-wp-suite' ); ?></option>
				</select>
			<?php endif; ?>
			<button type="submit" class="button"><?php esc_html_e( 'Filter anwenden', 'depeur-wp-suite' ); ?></button>
		</form>

		<form method="post" action="<?php echo esc_url( $base ); ?>" id="autoload-bulk-form">
			<input type="hidden" name="action" value="bulk_delete" />
			<?php wp_nonce_field( self::get_nonce_delete(), '_wpnonce' ); ?>
			<?php
			$order_opp = $order === 'DESC' ? 'ASC' : 'DESC';
			$url_size = add_query_arg( array_merge( $query_args, array( 'orderby' => 'size', 'order' => ( $orderby === 'size' ? $order_opp : 'DESC' ) ) ), $base );
			$url_name = add_query_arg( array_merge( $query_args, array( 'orderby' => 'name', 'order' => ( $orderby === 'name' ? $order_opp : 'ASC' ) ) ), $base );
			?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="check-column"><input type="checkbox" id="autoload-select-all" /></td>
						<th scope="col"><?php esc_html_e( 'Option-Name', 'depeur-wp-suite' ); ?></th>
						<th scope="col"><a href="<?php echo esc_url( $url_size ); ?>"><?php esc_html_e( 'Größe', 'depeur-wp-suite' ); ?></a></th>
						<th scope="col"><?php esc_html_e( 'autoload', 'depeur-wp-suite' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Zuordnung', 'depeur-wp-suite' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Warnstufe', 'depeur-wp-suite' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Aktionen', 'depeur-wp-suite' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $items as $row ) {
						$level = isset( $row['suspicion']['level'] ) ? $row['suspicion']['level'] : 'low';
						if ( $tab === 'suspicious' && $level === 'low' ) {
							continue;
						}
						$reason = isset( $row['suspicion']['reason'] ) ? $row['suspicion']['reason'] : '';
						$plugin = isset( $row['plugin_slug'] ) ? $row['plugin_slug'] : '';
						?>
						<tr>
							<th scope="row" class="check-column"><input type="checkbox" name="option_names[]" value="<?php echo esc_attr( $row['option_name'] ); ?>" class="autoload-row-cb" /></th>
							<td><code><?php echo esc_html( $row['option_name'] ); ?></code></td>
							<td><?php echo esc_html( SizeCalculator::format_bytes( $row['size_bytes'] ) ); ?> <span class="description">(<?php echo esc_html( (string) $row['size_bytes'] ); ?> B)</span></td>
							<td><?php echo esc_html( $row['autoload'] ); ?></td>
							<td><?php echo $plugin !== '' ? esc_html( $plugin ) : '—'; ?></td>
							<td><span class="autoload-badge autoload-badge-<?php echo esc_attr( $level ); ?>"><?php echo esc_html( $level ); ?></span> <?php echo esc_html( $reason ); ?></td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'delete_option' => $row['option_name'], '_wpnonce' => $nonce_delete ), $base ) ); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Option wirklich löschen?', 'depeur-wp-suite' ) ); ?>');"><?php esc_html_e( 'Löschen', 'depeur-wp-suite' ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'Ausgewählte Optionen wirklich löschen?', 'depeur-wp-suite' ) ); ?>');"><?php esc_html_e( 'Ausgewählte löschen', 'depeur-wp-suite' ); ?></button>
			</p>
		</form>

		<?php
		self::render_pagination( $total, $per_page, $paged, $base, $query_args );
	}

	/**
	 * Verdächtig-Tab: nur Optionen mit Stufe >= MEDIUM anzeigen.
	 *
	 * @param array $data Daten.
	 */
	private static function render_suspicious_tab( array $data ) {
		$data['tab'] = 'suspicious';
		$data['filter_autoload'] = 'all';
		self::render_options_table( $data );
	}

	/**
	 * Pagination-Links.
	 */
	private static function render_pagination( $total, $per_page, $paged, $base, array $query_args ) {
		if ( $total <= $per_page ) {
			return;
		}
		$total_pages = (int) ceil( $total / $per_page );
		?>
		<div class="tablenav bottom">
			<p class="pagination-links">
				<?php
				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$url = add_query_arg( array_merge( $query_args, array( 'paged' => $i ) ), $base );
					if ( $i === $paged ) {
						echo '<span class="current">' . (int) $i . '</span> ';
					} else {
						echo '<a href="' . esc_url( $url ) . '">' . (int) $i . '</a> ';
					}
				}
				?>
			</p>
			<p class="description"><?php echo esc_html( sprintf( __( 'Zeige %1$d–%2$d von %3$d Optionen.', 'depeur-wp-suite' ), ( $paged - 1 ) * $per_page + 1, min( $paged * $per_page, $total ), $total ) ); ?></p>
		</div>
		<?php
	}

	/**
	 * Regeln & Ignorieren Tab: Prefix-Map, Ignore-Listen, Scan, Prefix-Bulk-Preview.
	 *
	 * @param array $data Daten.
	 */
	private static function render_rules_tab( array $data ) {
		$rules = $data['rules'];
		$suggestions = $data['suggestions'];
		$prefix_preview = $data['prefix_preview'];
		$base = $data['base_url'];
		$nonce_rules = $data['nonce_rules'];
		?>
		<form method="post" action="<?php echo esc_url( add_query_arg( 'tab', 'rules', $base ) ); ?>">
			<input type="hidden" name="action" value="save_rules" />
			<?php wp_nonce_field( ScreenController::NONCE_RULES, '_wpnonce' ); ?>
		<div class="card" style="max-width: 900px; margin-top: 1em;">
			<h2 class="title"><?php esc_html_e( 'Prefix-Mapping (Plugin-Zuordnung)', 'depeur-wp-suite' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Diese Zuordnungen werden für die Warnstufe „Verdächtig“ genutzt. Wenn ein Prefix einem Plugin zugeordnet ist und das Plugin nicht installiert oder deaktiviert ist, erscheint die Option als verdächtig.', 'depeur-wp-suite' ); ?></p>
			<table class="form-table" id="prefix-map-table">
				<?php
				$map = $rules['prefix_map'];
				foreach ( $map as $prefix => $slug ) :
					?>
					<tr>
						<td><input type="text" name="prefix_map_key[]" value="<?php echo esc_attr( $prefix ); ?>" placeholder="<?php esc_attr_e( 'Prefix (z. B. rank_math_)', 'depeur-wp-suite' ); ?>" class="regular-text" /></td>
						<td><input type="text" name="prefix_map_val[]" value="<?php echo esc_attr( $slug ); ?>" placeholder="<?php esc_attr_e( 'Plugin-Slug', 'depeur-wp-suite' ); ?>" class="regular-text" /></td>
					</tr>
				<?php endforeach; ?>
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<tr class="prefix-map-empty-row">
					<td><input type="text" name="prefix_map_key[]" value="" placeholder="<?php esc_attr_e( 'Prefix (z. B. rank_math_)', 'depeur-wp-suite' ); ?>" class="regular-text" /></td>
					<td><input type="text" name="prefix_map_val[]" value="" placeholder="<?php esc_attr_e( 'Plugin-Slug', 'depeur-wp-suite' ); ?>" class="regular-text" /></td>
				</tr>
				<?php endfor; ?>
			</table>
			<p>
				<button type="button" class="button" id="prefix-map-add-row"><?php esc_html_e( 'Zeile hinzufügen', 'depeur-wp-suite' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Leere Zeilen werden beim Speichern ignoriert.', 'depeur-wp-suite' ); ?></p>
		</div>

		<div class="card" style="max-width: 900px;">
			<h2 class="title"><?php esc_html_e( 'Plugin-Scan (Vorschläge übernehmen)', 'depeur-wp-suite' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Installierte Plugins scannen und plausible Prefix-Zuordnungen vorschlagen. Vorschläge sind nicht perfekt – bitte prüfen. Nach neuen Plugin-Installationen „Erneut scannen“ ausführen.', 'depeur-wp-suite' ); ?></p>
			<p>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'rules', $base ) ); ?>" class="button"><?php esc_html_e( 'Erneut scannen', 'depeur-wp-suite' ); ?></a>
			</p>
			<?php if ( ! empty( $suggestions ) ) : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Prefix (Vorschlag)', 'depeur-wp-suite' ); ?></th><th><?php esc_html_e( 'Plugin', 'depeur-wp-suite' ); ?></th><th><?php esc_html_e( 'Übernehmen', 'depeur-wp-suite' ); ?></th></tr></thead>
					<tbody>
						<?php foreach ( $suggestions as $s ) : ?>
							<tr>
								<td><code><?php echo esc_html( $s['prefix'] ); ?></code></td>
								<td><?php echo esc_html( $s['plugin_name'] ); ?> <span class="description">(<?php echo esc_html( $s['plugin_slug'] ); ?>)</span></td>
								<td><input type="checkbox" name="suggest_accept[<?php echo esc_attr( $s['prefix'] ); ?>]" value="<?php echo esc_attr( $s['plugin_slug'] ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'Keine neuen Vorschläge (alle bereits in der Map oder keine Plugins).', 'depeur-wp-suite' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width: 900px;">
			<h2 class="title"><?php esc_html_e( 'Ignorierte Prefixe', 'depeur-wp-suite' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Optionen mit diesen Prefixen werden in der Verdächtig-Ansicht als „ignoriert“ geführt und nicht zum Löschen vorgeschlagen.', 'depeur-wp-suite' ); ?></p>
			<textarea name="ignored_prefixes" rows="3" class="large-text"><?php echo esc_textarea( implode( "\n", $rules['ignored_prefixes'] ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Ein Prefix pro Zeile (z. B. _transient_, woocommerce_).', 'depeur-wp-suite' ); ?></p>
		</div>

		<div class="card" style="max-width: 900px;">
			<h2 class="title"><?php esc_html_e( 'Ignorierte Optionen (einzeln)', 'depeur-wp-suite' ); ?></h2>
			<textarea name="ignored_options" rows="3" class="large-text"><?php echo esc_textarea( implode( "\n", $rules['ignored_options'] ) ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Ein Option-Name pro Zeile.', 'depeur-wp-suite' ); ?></p>
		</div>

		<div class="card" style="max-width: 900px;">
			<h2 class="title"><?php esc_html_e( 'UI-Einstellungen', 'depeur-wp-suite' ); ?></h2>
			<table class="form-table">
				<tr>
					<th><label for="per_page"><?php esc_html_e( 'Optionen pro Seite', 'depeur-wp-suite' ); ?></label></th>
					<td><input type="number" id="per_page" name="per_page" value="<?php echo esc_attr( (string) $rules['ui']['per_page'] ); ?>" min="1" max="500" /></td>
				</tr>
				<tr>
					<th><label for="min_size_bytes"><?php esc_html_e( 'Mindestgröße (Bytes)', 'depeur-wp-suite' ); ?></label></th>
					<td><input type="number" id="min_size_bytes" name="min_size_bytes" value="<?php echo esc_attr( (string) $rules['ui']['min_size_bytes'] ); ?>" min="0" /></td>
				</tr>
			</table>
		</div>
			<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Regeln speichern', 'depeur-wp-suite' ); ?></button></p>
		</form>

		<div class="card" style="max-width: 900px;">
			<h2 class="title"><?php esc_html_e( 'Bulk-Löschen nach Prefix (mit Preview)', 'depeur-wp-suite' ); ?></h2>
			<form method="get" action="<?php echo esc_url( $base ); ?>">
				<input type="hidden" name="page" value="<?php echo esc_attr( ScreenController::PAGE_SLUG ); ?>" />
				<input type="hidden" name="tab" value="rules" />
				<input type="text" name="preview_prefix" value="<?php echo esc_attr( $prefix_preview ? $prefix_preview['prefix'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Prefix eingeben', 'depeur-wp-suite' ); ?>" />
				<button type="submit" class="button"><?php esc_html_e( 'Preview anzeigen', 'depeur-wp-suite' ); ?></button>
			</form>
			<?php if ( $prefix_preview !== null ) : ?>
				<p><strong><?php echo esc_html( sprintf( __( 'Anzahl Treffer: %d', 'depeur-wp-suite' ), $prefix_preview['count'] ) ); ?></strong></p>
				<?php if ( $prefix_preview['count'] > 0 ) : ?>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Option-Name', 'depeur-wp-suite' ); ?></th><th><?php esc_html_e( 'Größe', 'depeur-wp-suite' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $prefix_preview['items'] as $item ) : ?>
								<tr><td><code><?php echo esc_html( $item['option_name'] ); ?></code></td><td><?php echo esc_html( SizeCalculator::format_bytes( $item['size_bytes'] ) ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<form method="post" action="<?php echo esc_url( $base ); ?>" style="margin-top: 1em;">
						<?php wp_nonce_field( ScreenController::NONCE_BULK_PREFIX, '_wpnonce' ); ?>
						<input type="hidden" name="action" value="bulk_delete_prefix" />
						<input type="hidden" name="prefix" value="<?php echo esc_attr( $prefix_preview['prefix'] ); ?>" />
						<label><input type="checkbox" name="confirm_prefix" value="1" /> <?php esc_html_e( 'Ich bestätige, dass ich diese Optionen löschen möchte.', 'depeur-wp-suite' ); ?></label>
						<p><button type="submit" class="button button-primary" onclick="return confirm('<?php echo esc_js( __( 'Wirklich alle Optionen mit diesem Prefix löschen?', 'depeur-wp-suite' ) ); ?>');"><?php esc_html_e( 'Jetzt löschen', 'depeur-wp-suite' ); ?></button></p>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<script>
		(function(){
			var btn = document.getElementById('prefix-map-add-row');
			var table = document.getElementById('prefix-map-table');
			if (!btn || !table) return;
			btn.addEventListener('click', function(){
				var rows = table.getElementsByTagName('tr');
				var last = rows[rows.length - 1];
				if (!last) return;
				var newRow = last.cloneNode(true);
				newRow.querySelectorAll('input').forEach(function(inp){ inp.value = ''; });
				table.appendChild(newRow);
			});
		})();
		</script>
		<?php
	}

	/**
	 * Nonce-Name für Delete (für Views-Kompatibilität).
	 *
	 * @return string
	 */
	private static function get_nonce_delete() {
		return ScreenController::NONCE_DELETE;
	}
}
