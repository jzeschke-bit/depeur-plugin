<?php
/**
 * Core plugin class for the Recipe Collections add-on.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Recipe_Collections {

	/**
	 * Define any constants to be used in the plugin.
	 *
	 * @since    4.1.0
	 */
	private function define_constants() {
		define( 'WPRMPRC_POST_TYPE', 'wprm_collection' );
		define( 'WPRMPRC_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		define( 'WPRMPRC_URL', plugin_dir_url( dirname( __FILE__ ) ) );
	}

	/**
	 * Make sure all is set up for the plugin to load.
	 *
	 * @since    4.1.0
	 */
	public function __construct() {
		add_action( 'wprm_premium_init', array( $this, 'init' ) );
	}

	/**
	 * Set up plugin. Only loads when WP Recipe Maker is active.
	 * Only initialises if core plugin has required version number.
	 *
	 * @since    4.1.0
	 */
	public function init() {
		$this->define_constants();
		$this->load_dependencies();
		do_action( 'wprmprc_init' );
		add_filter( 'wprm_addon_active', array( $this, 'addon_active' ), 10, 2 );
	}

	/**
	 * Mark addon as active.
	 *
	 * @since	4.1.0
	 * @param	boolean $bool  Whether addon is active.
	 * @param	mixed   $addon Addon to check.
	 */
	public function addon_active( $bool, $addon ) {
		if ( 'recipe-collections' === $addon ) {
			return true;
		}

		return $bool;
	}

	/**
	 * Load all plugin dependencies.
	 *
	 * @since    4.1.0
	 */
	private function load_dependencies() {
		// Public.
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-api-manage-users.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-api-manage.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-api.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-assets.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-blocks.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-collection.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-ingredient-groups.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-manager.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-post-type.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-print.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-settings.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-shopping-list.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-shopping-list-database.php' );
		require_once( WPRMPRC_DIR . 'includes/public/class-wprmprc-shortcode.php' );

		// Admin.
		if ( is_admin() ) {
			require_once( WPRMPRC_DIR . 'includes/admin/class-wprmprc-saved-collections.php' );
		}
	}
}
new WPRMP_Recipe_Collections();
