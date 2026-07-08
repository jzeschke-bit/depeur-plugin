<?php
/**
 * Send out emails on recipe submission.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 */

/**
 * Send out emails on recipe submission.
 *
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Emailer {
	/**
	 * Send the after recipe submission email notification.
	 *
	 * @since	2.1.0
	 * @param	array $data Data passed in through the recipe submission form.
	 */
	public static function after_submission_notification( $data = array() ) {
		$to = WPRM_Settings::get( 'recipe_submission_admin_email' );

		if ( $to ) {
			$manage_link = admin_url( 'admin.php?page=wprm_manage#/recipe-submission' );

			$subject = __( 'New Recipe Submission', 'wp-recipe-maker-premium' );
			$message = __( 'There is a new Recipe Submission on your website!', 'wp-recipe-maker-premium' );
			$message .= "\n";
			$message .= __( 'Manage now', 'wp-recipe-maker-premium' );
			$message .= ': ' . $manage_link;
			$message .= "\n";

			// Recipe details.
			$message .= "\n";
			$name = isset( $data['recipe_name'] ) ? $data['recipe_name'] : '';
			if ( $name ) {
				$message .= __( 'Recipe', 'wp-recipe-maker' ) . ': ' . $name;
			}

			// User details.
			$message .= "\n";
			$user_id = get_current_user_id();

			if ( $user_id ) {
				$message .= __( 'User', 'wp-recipe-maker' ) . ': ' . $user_id;

				$user = get_userdata( $user_id );
				if ( $user ) {
					$message .= ' - ' . $user->display_name;
				}
			} else {
				$name = isset( $data['user_name'] ) ? $data['user_name'] : '';
				$email = isset( $data['user_email'] ) ? $data['user_email'] : '';

				if ( $name || $email ) {
					$message .= __( 'Guest', 'wp-recipe-maker' ) . ':';

					if ( $name ) { $message .= ' ' . $name; }
					if ( $email ) { $message .= ' <' . $email . '>'; }
				}
			}

			$subject = apply_filters( 'wprm_recipe_submission_mail_subject', $subject, $data );
			$message = apply_filters( 'wprm_recipe_submission_mail_message', $message, $data );

			wp_mail( $to, $subject, $message );
		}
	}
}