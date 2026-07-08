<?php
/**
 * Settings for the translate addon.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/translate
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 */

/**
 * Settings for the translate addon.
 *
 * @since      7.0.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/translate
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/translate/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPT_Settings {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
	}

	/**
	 * Add translate settings.
	 *
	 * @since    7.0.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMPT_DIR . 'templates/admin/settings/translate.php' );
		$structure['translate'] = $translate;

		return $structure;
	}
}

WPRMPT_Settings::init();
