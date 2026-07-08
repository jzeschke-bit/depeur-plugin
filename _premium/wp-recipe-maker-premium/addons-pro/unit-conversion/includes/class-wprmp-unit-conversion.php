<?php
/**
 * The core plugin class.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes
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
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Unit_Conversion {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_constants() {
		define( 'WPRMPUC_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMPUC_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Make sure all is set up for the plugin to load.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wprm_premium_init', array( $this, 'init' ) );
	}

	/**
	 * Set up plugin. Only loads when WP Recipe Maker is active.
	 * Only initialises if core plugin has required version number.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		$this->define_constants();
		$this->load_dependencies();
		do_action( 'wprmpuc_init' );
		add_filter( 'wprm_addon_active', array( $this, 'addon_active' ), 10, 2 );
	}

	/**
	 * Mark addon as active.
	 *
	 * @since    1.0.0
	 * @param		 boolean $bool  Whether addon is active.
	 * @param		 mixed	 $addon Addon to check.
	 */
	public function addon_active( $bool, $addon ) {
		if ( 'unit-conversion' === $addon ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Load all plugin dependencies.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		// Public.
		require_once( WPRMPUC_DIR . 'includes/public/class-wprmpuc-api.php' );
		require_once( WPRMPUC_DIR . 'includes/public/class-wprmpuc-manager.php' );
		require_once( WPRMPUC_DIR . 'includes/public/class-wprmpuc-settings.php' );
		require_once( WPRMPUC_DIR . 'includes/public/class-wprmpuc-temperature.php' );
		require_once( WPRMPUC_DIR . 'includes/public/class-wprmpuc-snapshot.php' );

		// Modal might be needed on the front-end.
		require_once( WPRMPUC_DIR . 'includes/admin/class-wprmpuc-modal.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMPUC_DIR . 'includes/admin/class-wprmpuc-conversion-api.php' );
		}
	}
}
new WPRMP_Unit_Conversion();
