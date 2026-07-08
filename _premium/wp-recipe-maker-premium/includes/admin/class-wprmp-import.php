<?php
/**
 * Responsible for loading the premium recipe importers.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Responsible for loading the premium recipe importers.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Import {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::load_importers();
	}

	/**
	 * Load all available importers from the /includes/admin/import directory.
	 *
	 * @since    1.0.0
	 */
	private static function load_importers() {
		$dir = WPRMP_DIR . 'includes/admin/import';

		if ( $handle = opendir( $dir ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				preg_match( '/^class-wprmp-import-(.*?).php/', $file, $match );
				if ( isset( $match[1] ) ) {
					require_once( $dir . '/' . $match[0] );
				}
			}
		}
	}
}

WPRMP_Import::init();
