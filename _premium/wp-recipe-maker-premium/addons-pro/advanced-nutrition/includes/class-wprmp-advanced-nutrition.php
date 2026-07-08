<?php
/**
 * The core addon class.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes
 */

/**
 * The core addon class.
 *
 * @since      1.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Advanced_Nutrition {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_constants() {
		define( 'WPRMPN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMPN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
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
		do_action( 'wprmpn_init' );
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
		if ( 'nutrition' === $addon ) {
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
		require_once( WPRMPN_DIR . 'includes/public/class-wprmpn-api.php' );
		require_once( WPRMPN_DIR . 'includes/public/class-wprmpn-calculated-nutrition.php' );
		require_once( WPRMPN_DIR . 'includes/public/class-wprmpn-ingredient-manager.php' );
		require_once( WPRMPN_DIR . 'includes/public/class-wprmpn-ingredient-nutrition-label.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMPN_DIR . 'includes/admin/class-wprmpn-nutrition-api.php' );
		}
	}
}
new WPRMP_Advanced_Nutrition();
