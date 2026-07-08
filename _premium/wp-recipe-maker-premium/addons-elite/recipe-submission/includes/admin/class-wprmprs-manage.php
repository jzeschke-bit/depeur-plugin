<?php
/**
 * Handle the recipe submission manage page.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.5.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/admin
 */

/**
 * Handle the recipe submission manage page.
 *
 * @since      5.5.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Manage {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.5.0
	 */
	public static function init() {
		add_filter( 'wprm_admin_manage_localize', array( __CLASS__, 'localize' ) );
	}

	/**
	 * Localize data for the manage page.
	 *
	 * @since   5.5.0
	 * @param	array $data Localized data.
	 */
	public static function localize( $data ) {
		if ( isset( $data['post_statuses'] ) ) {
			unset( $data['post_statuses']['pending'] );
		}

		return $data;
	}
}

WPRMPRS_Manage::init();
