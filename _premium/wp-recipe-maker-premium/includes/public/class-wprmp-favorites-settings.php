<?php
/**
 * Settings for Favorites.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Settings for Favorites.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Favorites_Settings {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.6.0
	 */
	public static function init() {
		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
	}

	/**
	 * Add favorites settings.
	 *
	 * @since    10.6.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMP_DIR . 'templates/settings/favorites.php' );
		$structure['favorites'] = $favorites;

		return $structure;
	}
}

WPRMP_Favorites_Settings::init();
