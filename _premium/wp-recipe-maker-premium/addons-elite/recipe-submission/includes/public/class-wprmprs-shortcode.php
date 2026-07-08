<?php
/**
 * Handle the Recipe Submission shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 */

/**
 * Handle the Recipe Submission shortcode.
 *
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Shortcode {
	/**
	 * Remember for entire page if there was a succesful submission during this load.
	 *
	 * @since    7.2.0
	 */
	public static $was_valid_submission = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    2.1.0
	 */
	public static function init() {
		add_shortcode( 'wprm-recipe-submission', array( __CLASS__, 'recipe_submission_shortcode' ) );
	}

	/**
	 * Output for the Recipe Submission shortcode.
	 *
	 * @since	2.1.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	public static function recipe_submission_shortcode( $atts ) {
		$shortcode = '';

		if ( ( isset( $_POST['wprmprs_submit'] ) || isset( $_POST['g-recaptcha-response'] ) ) && wp_verify_nonce( $_POST['wprmprs'], 'wprmprs' ) ) {

			// Check reCAPTCHA if enabled.
			if ( WPRM_Settings::get( 'recipe_submission_recaptcha' ) ) {
				require_once( WPRMPRS_DIR . 'vendor/recaptchalib.php');

				$recaptcha = new Recaptcha( array(
					'client-key' => WPRM_Settings::get( 'recipe_submission_recaptcha_site_key' ),
					'secret-key' => WPRM_Settings::get( 'recipe_submission_recaptcha_secret_key' ),
				) );
				$response = $recaptcha->verifyResponse( $_POST['g-recaptcha-response'] );

				if( !isset( $response['success'] ) || $response['success'] !== true) {
					$message = __( 'Submission blocked by reCAPTCHA.', 'wp-recipe-maker-premium' );

					if ( current_user_can( 'manage_options' ) ) {
						$message .= ' ' . __( 'Make sure the site and secret key are set correctly on the settings page.', 'wp-recipe-maker-premium' );
					}

					return $message;
				}
			}

			// Prevent infinite loop.
			unset( $_POST['wprmprs_submit'] );
			unset( $_POST['wprmprs'] );

			// Process form only once.
			WPRMPRS_Parser::process_recipe_submission_form( $_POST );
			WPRMPRS_Emailer::after_submission_notification( $_POST );

			// But do set valid submission to prevent form from showing again.
			self::$was_valid_submission = true;
		}
		
		if ( self::$was_valid_submission ) {
			if ( 'text' === WPRM_Settings::get( 'recipe_submission_after_action' ) ) {
				$shortcode = wpautop( WPRM_Settings::get( 'recipe_submission_after_text' ) );
			} else {
				$url = WPRM_Settings::get( 'recipe_submission_after_redirect' );
				$shortcode = '<script>window.location = "' . esc_url( $url ) . '";</script>';
			}
		} else {
			$atts = shortcode_atts( array(), $atts, 'wprm_recipe_submission' );
			$blocks = WPRMPRS_Layout::get_blocks();

			if ( is_array( $blocks ) && $blocks ) {
				ob_start();
				include( WPRMPRS_DIR . 'templates/public/recipe-submission-shortcode.php' );
				$shortcode = ob_get_contents();
				ob_end_clean();
			}
		}

		// Make sure assets are loaded.
		WPRM_Assets::load();

		return $shortcode;
	}
}

WPRMPRS_Shortcode::init();
