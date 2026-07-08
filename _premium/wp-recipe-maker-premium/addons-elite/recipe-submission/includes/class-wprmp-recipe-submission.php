<?php
/**
 * The core plugin class.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes
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
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Recipe_Submission {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    2.1.0
	 */
	private function define_constants() {
		define( 'WPRMPRS_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMPRS_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Make sure all is set up for the plugin to load.
	 *
	 * @since    2.1.0
	 */
	public function __construct() {
		add_action( 'wprm_premium_init', array( $this, 'init' ) );
	}

	/**
	 * Set up plugin. Only loads when WP Recipe Maker is active.
	 * Only initialises if core plugin has required version number.
	 *
	 * @since    2.1.0
	 */
	public function init() {
		$this->define_constants();
		$this->load_dependencies();
		do_action( 'wprmprs_init' );
		add_filter( 'wprm_addon_active', array( $this, 'addon_active' ), 10, 2 );
	}

	/**
	 * Mark addon as active.
	 *
	 * @since	2.1.0
	 * @param	boolean $bool  Whether addon is active.
	 * @param	mixed   $addon Addon to check.
	 */
	public function addon_active( $bool, $addon ) {
		if ( 'recipe-submission' === $addon ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Load all plugin dependencies.
	 *
	 * @since    2.1.0
	 */
	private function load_dependencies() {
		// Public.
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-api-manage.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-api.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-emailer.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-layout.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-parser.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-saver.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-settings.php' );
		require_once( WPRMPRS_DIR . 'includes/public/class-wprmprs-shortcode.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMPRS_DIR . 'includes/admin/class-wprmprs-manage.php' );
		}
	}
}
new WPRMP_Recipe_Submission();
