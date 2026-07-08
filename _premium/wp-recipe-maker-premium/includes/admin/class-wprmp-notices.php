<?php
/**
 * Responsible for showing admin notices related to the Premium plugin.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.3.1
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Responsible for showing admin notices related to the Premium plugin.
 *
 * @since      9.3.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Notices {

	/**
	 * Register actions and filters.
	 *
	 * @since    9.3.1
	 */
	public static function init() {
		add_filter( 'wprm_admin_notices', array( __CLASS__, 'amazon_creators_api_notice' ) );
	}

	/**
	 * Show the Amazon Creators API notice.
	 *
	 * @since	10.3.0
	 * @param	array $notices Existing notices.
	 */
	public static function amazon_creators_api_notice( $notices ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		// Only load on manage page.
		if ( $screen && 'wp-recipe-maker_page_wprm_manage' === $screen->id ) {
			// Only show if PA-API is currently being used.
			if ( self::is_using_paapi() ) {
				$settings_url = admin_url( 'admin.php?page=wprm_settings#wprm-settings-group-amazon' );
				$notices[] = array(
					'id' => 'amazon_creators_api',
					'title' => __( 'New Amazon Creators API Available', 'wp-recipe-maker' ),
					'text' => '<p>' . __( 'We\'ve added support for the new Amazon Creators API! This is Amazon\'s recommended API going forward and provides better reliability.', 'wp-recipe-maker' ) . '</p><p>' . __( 'To ensure your Amazon Products feature continues working smoothly, we recommend filling in both API settings (PA-API and Creators API). The default "Automatically Switch" setting will then use Creators API when available, with PA-API as a fallback.', 'wp-recipe-maker' ) . '</p><p><a href="' . esc_url( $settings_url ) . '" class="button button-primary button-compact">' . __( 'Configure Amazon Products Settings', 'wp-recipe-maker' ) . '</a></p>',
				);
			}
		}

		return $notices;
	}

	/**
	 * Check if PA-API is currently being used.
	 *
	 * @since	10.3.0
	 * @return	bool True if PA-API is currently being used.
	 */
	private static function is_using_paapi() {
		$api_type = WPRM_Settings::get( 'amazon_api_type' );

		// Explicitly set to PA-API.
		if ( 'paapi' === $api_type ) {
			return true;
		}

		// Auto mode or empty (defaults to auto).
		if ( 'auto' === $api_type || ! $api_type ) {
			// Check if PA-API credentials are available.
			$access_key = trim( WPRM_Settings::get( 'amazon_access_key' ) );
			$secret_key = trim( WPRM_Settings::get( 'amazon_secret_key' ) );
			$partner_tag = trim( WPRM_Settings::get( 'amazon_partner_tag' ) );
			$has_paapi = ! empty( $access_key ) && ! empty( $secret_key ) && ! empty( $partner_tag );

			// Check if Creators API credentials are available.
			$credential_id = trim( WPRM_Settings::get( 'amazon_credential_id' ) );
			$credential_secret = trim( WPRM_Settings::get( 'amazon_credential_secret' ) );
			$credential_version = trim( WPRM_Settings::get( 'amazon_credential_version' ) );
			$has_creators = ! empty( $credential_id ) && ! empty( $credential_secret ) && ! empty( $credential_version ) && ! empty( $partner_tag );

			// In auto mode, PA-API is used only if:
			// - PA-API credentials exist AND Creators API credentials don't exist
			// (If both exist, Creators API is preferred, so PA-API is not being used)
			return $has_paapi && ! $has_creators;
		}

		return false;
	}
}

WPRMP_Notices::init();
