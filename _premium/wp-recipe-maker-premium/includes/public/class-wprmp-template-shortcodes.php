<?php
/**
 * Handle the Premium recipe shortcodes.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the Premium recipe shortcodes.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Template_Shortcodes {

	/**
	 * Register actions and filters.
	 *
	 * @since	5.6.0
	 */
	public static function init() {
		self::load_shortcodes();
	}

	/**
	 * Load all available shortcodes from the /includes/public/recipe-shortcodes directory.
	 *
	 * @since	5.6.0
	 */
	private static function load_shortcodes() {
		$dirs = array(
			WPRMP_DIR . 'includes/public/shortcodes/general',
			WPRMP_DIR . 'includes/public/shortcodes/recipe',
		);

		foreach ( $dirs as $dir ) {
			if ( $handle = opendir( $dir ) ) {
				while ( false !== ( $file = readdir( $handle ) ) ) {
					preg_match( '/^class-wprmp-sc-(.*?).php/', $file, $match );
					if ( isset( $match[1] ) ) {
						require_once( $dir . '/' . $match[0] );
					}
				}
			}
		}
	}
}

WPRMP_Template_Shortcodes::init();
