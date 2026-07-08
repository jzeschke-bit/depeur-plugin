<?php
/**
 * Responsible for loading the premium reports.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Responsible for loading the premium reports.
 *
 * @since      9.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Reports {

	/**
	 * Register actions and filters.
	 *
	 * @since    9.5.0
	 */
	public static function init() {
		add_action( 'wprm_reports', array( __CLASS__, 'premium_reports_template' ) );
	}

	/**
	 * Load the Premium reports template.
	 *
	 * @since    9.5.0
	 */
	public static function premium_reports_template() {
		require_once( WPRMP_DIR . 'templates/admin/reports.php' );
	}
}

WPRMP_Reports::init();
