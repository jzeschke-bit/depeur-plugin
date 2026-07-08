<?php
/**
 * Handle private recipe notes.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.7.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle private recipe notes.
 *
 * @since      7.7.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Private_Notes {

	/**
	 * Get the private notes for a specific recipe and user.
	 *
	 * @since    7.7.0
	 * @param	int	$recipe_id	Recipe ID to get the private notes for.
	 * @param	int $user_id	User ID to get the private notes for.
	 */
	public static function get( $recipe_id, $user_id = 0 ) {
		$notes = '';

		// Sanitize.
		$recipe_id = intval( $recipe_id );
		$user_id = intval( $user_id );

		// Use current user by default.
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// If both user and recipe are set, get notes.
		if ( $user_id && $recipe_id ) {
			$notes = get_user_meta( $user_id, 'wprm-recipe-private-notes-' . $recipe_id, true );
		}

		return $notes;
	}

	/**
	 * Save the private notes for a specific recipe and user.
	 *
	 * @since    7.7.0
	 * @param	int		$recipe_id	Recipe ID to save the private notes for.
	 * @param	string 	$notes		Private notes to save.
	 * @param	int 	$user_id	User ID to save the private notes for.
	 */
	public static function save( $recipe_id, $notes, $user_id = 0 ) {
		// Sanitize.
		$recipe_id = intval( $recipe_id );
		$notes = trim( $notes );
		$user_id = intval( $user_id );

		// Use current user by default.
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// If both user and recipe are set, save notes.
		if ( $user_id && $recipe_id ) {
			if ( $notes ) {
				$result = update_user_meta( $user_id, 'wprm-recipe-private-notes-' . $recipe_id, $notes );
				return false === $result ? false : true;
			} else {
				return delete_user_meta( $user_id, 'wprm-recipe-private-notes-' . $recipe_id );
			}
		}

		return false;
	}
}
