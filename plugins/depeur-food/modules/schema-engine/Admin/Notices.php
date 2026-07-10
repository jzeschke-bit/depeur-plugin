<?php
/**
 * Admin/Notices — Hinweis, wenn die Hard-Dependency Rank Math fehlt.
 *
 * Die Schema-Engine ruht ohne Rank Math (E1); ihre Feld-Provisionierung läuft weiter, aber
 * die eigentliche Schema-Anreicherung tut nichts. Damit das nicht still passiert, zeigt diese
 * Klasse dem Administrator einen Hinweis. Rein Admin, nur für Nutzer mit Plugin-Rechten.
 *
 * @package Depeur\Food\Modules\SchemaEngine\Admin
 * @license GPL-2.0-or-later
 */

namespace Depeur\Food\Modules\SchemaEngine\Admin;

use Depeur\Food\Modules\SchemaEngine\Support\Dependencies;

// Kein direkter Aufruf.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriert die admin_notices-Warnung bei fehlendem Rank Math.
 *
 * @since 0.2.0
 */
final class Notices {

	/**
	 * Hängt den Hinweis ein – nur wenn Rank Math tatsächlich fehlt.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		if ( Dependencies::rank_math_active() ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'render_missing_rank_math' ) );
	}

	/**
	 * Gibt die Warnung aus (Rank Math inaktiv → Schema-Anreicherung ruht).
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function render_missing_rank_math(): void {
		// Nur für Nutzer, die Plugins verwalten dürfen – sonst irrelevant.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p>
				<?php
				esc_html_e(
					'Depeur Food – Schema-Engine: Rank Math SEO ist nicht aktiv. Die Autor- und CollectionPage-Anreicherung ruht, bis Rank Math aktiviert ist. Die Autor-/Review-Felder werden weiterhin bereitgestellt.',
					'depeur-food'
				);
				?>
			</p>
		</div>
		<?php
	}
}
