<?php
/**
 * The core plugin class.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes
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
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Products {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    10.2.0
	 */
	private function define_constants() {
		define( 'WPRMPP_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMPP_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Make sure all is set up for the plugin to load.
	 *
	 * @since    10.2.0
	 */
	public function __construct() {
		add_action( 'wprm_premium_init', array( $this, 'init' ) );
	}

	/**
	 * Set up plugin. Only loads when WP Recipe Maker is active.
	 * Only initialises if core plugin has required version number.
	 *
	 * @since    10.2.0
	 */
	public function init() {
		$this->define_constants();
		$this->load_dependencies();
		do_action( 'wprmpp_init' );
		add_filter( 'wprm_addon_active', array( $this, 'addon_active' ), 10, 2 );
	}

	/**
	 * Mark addon as active.
	 *
	 * @since	10.2.0
	 * @param	boolean $bool  Whether addon is active.
	 * @param	mixed   $addon Addon to check.
	 */
	public function addon_active( $bool, $addon ) {
		if ( 'products' === $addon ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Load all plugin dependencies.
	 *
	 * @since    10.2.0
	 */
	private function load_dependencies() {
		// Public.
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-api.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-cart.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-icon.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-integrations.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-meta.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-modal.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-product-manager.php' );
		require_once( WPRMPP_DIR . 'includes/public/class-wprmpp-settings.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMPP_DIR . 'includes/admin/class-wprmpp-manage.php' );
		}
	}
}
new WPRMP_Products();
