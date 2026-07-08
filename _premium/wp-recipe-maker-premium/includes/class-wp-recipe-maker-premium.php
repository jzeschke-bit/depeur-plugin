<?php
/**
 * The core plugin class.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WP_Recipe_Maker_Premium {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_constants() {
		define( 'WPRMP_VERSION', '10.5.0' );
		define( 'WPRMP_CORE_VERSION_REQUIRED', '10.5.0' );
		define( 'WPRMP_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMP_URL', plugin_dir_url( dirname( __FILE__ ) ) );

		// Check bundle.
		$bundle = 'Premium';

		if ( file_exists( WPRMP_DIR . 'addons-elite' ) ) {
			$bundle = 'Elite';
		} elseif ( file_exists( WPRMP_DIR . 'addons-pro' ) ) {
			$bundle = 'Pro';
		}

		define( 'WPRMP_BUNDLE', $bundle );
	}

	/**
	 * Make sure all is set up for the plugin to load.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wprm_init', array( $this, 'init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice_missing_core' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice_legacy_addons' ) );
	}

	/**
	 * Set up plugin. Only loads when WP Recipe Maker is active.
	 * Only initialises if core plugin has required version number.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		$this->define_constants();

		// Always load licensing for updates.
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-license.php' );

		if ( version_compare( WPRM_VERSION, WPRMP_CORE_VERSION_REQUIRED ) >= 0 ) {
			if ( version_compare( WPRMP_VERSION, WPRM_PREMIUM_VERSION_REQUIRED ) >= 0 ) {
				$this->load_dependencies();
				$this->load_addons();
				do_action( 'wprm_premium_init' );
				add_filter( 'wprm_addon_active', array( $this, 'addon_active' ), 10, 2 );

				// Don't show upgrade menu when Elite is already active.
				if ( 'Elite' === WPRMP_BUNDLE ) {
					remove_action( 'admin_menu', array( 'WPRM_Admin_Menu_Addons', 'add_submenu_page' ), 99 );
				}
			}
		} else {
			add_action( 'admin_notices', array( $this, 'admin_notice_required_version' ) );
		}
	}

	/**
	 * Mark addon as active.
	 *
	 * @since    1.0.0
	 * @param		 boolean $bool  Whether addon is active.
	 * @param		 mixed	 $addon Addon to check.
	 */
	public function addon_active( $bool, $addon ) {
		if ( 'premium' === $addon ) {
			return true;
		}
		if ( 'pro' === $addon && ( 'Pro' === WPRMP_BUNDLE || 'Elite' === WPRMP_BUNDLE ) ) {
			return true;
		}
		if ( 'elite' === $addon && 'Elite' === WPRMP_BUNDLE ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Check if any of our legacy addons are active.
	 *
	 * @since    2.0.0
	 */
	public function admin_notice_legacy_addons() {
		if ( class_exists( 'WP_Recipe_Maker_Premium_Nutrition' ) || class_exists( 'WP_Recipe_Maker_Premium_Unit_Conversion' ) ) {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>WP Recipe Maker Premium</strong></br>';
			echo 'It looks like you are still using our old add-on system.';
			echo '<br/><a href="https://bootstrapped.ventures/wp-recipe-maker/migrating-add-ons-bundles/" target="_blank">Please follow these steps to migrate.</a>';
			echo '</p></div>';
		}
	}

	/**
	 * Admin notice to show when the core plugin is not installed.
	 *
	 * @since    1.0.0
	 */
	public function admin_notice_missing_core() {
		if ( ! defined( 'WPRM_VERSION' ) ) {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>WP Recipe Maker Premium</strong></br>';
			esc_html_e( 'The plugin has not been loaded because it requires the following plugin(s) to be activated:', 'wp-recipe-maker-premium' );
			echo '<br/><a href="' .  admin_url( 'plugin-install.php?s=wp+recipe+maker&tab=search&type=term' ) . '">WP Recipe Maker</a>';
			echo '</p></div>';
		}
	}

	/**
	 * Admin notice to show when the required version is not met.
	 *
	 * @since    1.0.0
	 */
	public function admin_notice_required_version() {
		echo '<div class="notice notice-error"><p>';
		echo '<strong>WP Recipe Maker Premium</strong></br>';
		esc_html_e( 'The plugin has not been loaded because it requires at least the following plugin versions:', 'wp-recipe-maker-premium' );
		echo '<br/><a href="' .  admin_url( 'plugin-install.php?s=wp+recipe+maker&tab=search&type=term' ) . '">WP Recipe Maker ' . esc_html( WPRMP_CORE_VERSION_REQUIRED ) . '</a>';
		echo '</p></div>';
	}

	/**
	 * Adjust action links on the plugins page.
	 *
	 * @since	2.0.0
	 * @param	array $links Current plugin action links.
	 */
	public function plugin_action_links( $links ) {
		if ( defined( 'WPRMP_BUNDLE' ) ) {
			if ( 'Premium' !== WPRMP_BUNDLE ) {
				$bundle_information = array( '<span style="color: black; font-weight: 600;">' . WPRMP_BUNDLE . ' Bundle</span>' );
			} else {
				$bundle_information = array();
			}
		} else {
			$bundle_information = array( '<span style="color: black; font-weight: 600;">Requires the free WP Recipe Maker plugin</span>' );
		}

		return array_merge( $bundle_information, $links );
	}

	/**
	 * Load all plugin dependencies.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		// General.
		require_once( WPRMP_DIR . 'includes/class-wprmp-i18n.php' );

		// API.
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-ai-assistant.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-amazon.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-custom-taxonomies.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-embed.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-equipment-affiliate.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-ingredient-links.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-license.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-modal.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-nutrients.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-pdf-download.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-favorites.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-private-notes.php' );
		require_once( WPRMP_DIR . 'includes/public/api/class-wprmp-api-user-rating.php' );

		// Public.
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-amazon-api.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-amazon-api-creators.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-amazon-api-factory.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-amazon-queue.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-amazon.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-ai-nutrition-review.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-ai-unit-conversion-review.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-assets.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-checkboxes.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-cook-mode.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-embed.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-custom-taxonomies.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-export-json.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-export-taxonomies.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-favorites-display.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-favorites-settings.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-favorites.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-glossary-terms.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-import-json.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-import-paprika.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-import-slickstream.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-import-taxonomies.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-ingredient-links.php' );
		require_once( WPRMP_DIR . 'includes/public/deprecated/class-wprmp-nutrition-label.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-links.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-list-style.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-nutrition-label-layout.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-nutrition.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-pdf-download.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-print.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-private-notes.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-recipe-sanitizer.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-proxy.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-recipe-saver.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-recipe.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-template-shortcodes.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-user-rating-comments.php' );
		require_once( WPRMP_DIR . 'includes/public/class-wprmp-user-rating.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMP_DIR . 'includes/admin/class-wprmp-import.php' );
			require_once( WPRMP_DIR . 'includes/admin/class-wprmp-ai-assistant.php' );
			require_once( WPRMP_DIR . 'includes/admin/class-wprmp-notices.php' );
			require_once( WPRMP_DIR . 'includes/admin/class-wprmp-reports.php' );

			// Reports.
			require_once( WPRMP_DIR . 'includes/admin/reports/class-wprmp-reports-recipe-collections.php' );

			// Tools.
			require_once( WPRMP_DIR . 'includes/admin/tools/class-wprmp-tools-amazon-html-links.php' );
			require_once( WPRMP_DIR . 'includes/admin/tools/class-wprmp-tools-amazon-html-products.php' );
			require_once( WPRMP_DIR . 'includes/admin/tools/class-wprmp-tools-amazon-links-products.php' );
		}
	}

	/**
	 * Load all plugin addons.
	 *
	 * @since	2.0.0
	 */
	private function load_addons() {
		// Load pro addons.
		if ( 'Pro' === WPRMP_BUNDLE || 'Elite' === WPRMP_BUNDLE ) {
			$this->load_addons_from_dir( WPRMP_DIR . 'addons-pro' );
		}

		// Load elite addons.
		if ( 'Elite' === WPRMP_BUNDLE ) {
			$this->load_addons_from_dir( WPRMP_DIR . 'addons-elite' );
		}
	}

	/**
	 * Load all plugin in a directory.
	 *
	 * @since	2.0.0
	 * @param	mixed $dir Directory to load the addons from.
	 */
	private function load_addons_from_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$contents = scandir( $dir );

		foreach ( $contents as $content ) {
			if ( '.' !== $content && '..' !== $content && 'index.php' !== $content ) {
				// Trim whitespace from directory name to prevent path issues.
				$content = trim( $content );
				
				// Skip if content is empty after trimming.
				if ( empty( $content ) ) {
					continue;
				}

				// Prevent directory traversal attacks.
				if ( false !== strpos( $content, '/' ) || false !== strpos( $content, '\\' ) || false !== strpos( $content, "\0" ) ) {
					continue;
				}

				$dir = rtrim( $dir, '/' );
				$addon_dir = $dir . '/' . $content;
				
				// Verify it's actually a directory.
				if ( ! is_dir( $addon_dir ) ) {
					continue;
				}

				$file = $addon_dir . '/includes/class-wprmp-' . $content . '.php';

				if ( is_file( $file ) ) {
					include_once( $file );
				}
			}
		}
	}
}
