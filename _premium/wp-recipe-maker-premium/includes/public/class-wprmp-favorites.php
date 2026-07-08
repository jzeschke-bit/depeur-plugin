<?php
/**
 * Handle favorite recipes for Premium users and visitors.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle favorite recipes for Premium users and visitors.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Favorites {

	/**
	 * User meta key and browser storage key for favorites.
	 *
	 * @since    10.6.0
	 */
	const KEY = 'wprm-recipe-favorites';

	/**
	 * Register actions and filters.
	 *
	 * @since    10.6.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_frontend_data', array( __CLASS__, 'frontend_recipe_data' ), 10, 2 );
	}

	/**
	 * Add favorite state to frontend recipe data for logged in users.
	 *
	 * @since    10.6.0
	 * @param    array       $data   Frontend recipe data.
	 * @param    WPRM_Recipe $recipe Recipe to add data for.
	 */
	public static function frontend_recipe_data( $data, $recipe ) {
		$data['favorite'] = self::is_recipe_favorited( $recipe->id() );

		return $data;
	}

	/**
	 * Sanitize a favorites list while preserving order.
	 *
	 * @since    10.6.0
	 * @param    mixed $favorites Favorites to sanitize.
	 */
	public static function sanitize_favorites( $favorites ) {
		if ( ! is_array( $favorites ) ) {
			$favorites = array();
		}

		$sanitized = array();

		foreach ( $favorites as $favorite ) {
			$favorite = intval( $favorite );

			if ( $favorite && ! in_array( $favorite, $sanitized, true ) ) {
				$sanitized[] = $favorite;
			}
		}

		return $sanitized;
	}

	/**
	 * Get favorites for a specific user.
	 *
	 * @since    10.6.0
	 * @param    int $user_id User to get favorites for.
	 */
	public static function get_favorites( $user_id = 0 ) {
		$user_id = intval( $user_id );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array();
		}

		return self::sanitize_favorites( get_user_meta( $user_id, self::KEY, true ) );
	}

	/**
	 * Get current favorites for the active visitor.
	 *
	 * @since    10.6.0
	 * @param    array $local_favorites Local browser favorites fallback.
	 */
	public static function get_current_favorites_ids( $local_favorites = array() ) {
		if ( get_current_user_id() ) {
			return self::get_favorites();
		}

		return self::sanitize_favorites( $local_favorites );
	}

	/**
	 * Save favorites for a specific user.
	 *
	 * @since    10.6.0
	 * @param    mixed $favorites Favorites to save.
	 * @param    int   $user_id   User to save favorites for.
	 */
	public static function save_favorites( $favorites, $user_id = 0 ) {
		$user_id = intval( $user_id );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$favorites = self::sanitize_favorites( $favorites );
		$result = update_user_meta( $user_id, self::KEY, $favorites );

		return false === $result ? false : $favorites;
	}

	/**
	 * Merge local favorites into the current user's account favorites.
	 *
	 * @since    10.6.0
	 * @param    mixed $local_favorites Favorites from the browser.
	 * @param    int   $user_id         User to merge into.
	 */
	public static function merge_local_favorites_into_account( $local_favorites, $user_id = 0 ) {
		$user_id = intval( $user_id );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$local_favorites = self::sanitize_favorites( $local_favorites );

		if ( ! $user_id ) {
			return $local_favorites;
		}

		$favorites = self::get_favorites( $user_id );

		foreach ( $local_favorites as $favorite ) {
			if ( ! in_array( $favorite, $favorites, true ) ) {
				$favorites[] = $favorite;
			}
		}

		return self::save_favorites( $favorites, $user_id );
	}

	/**
	 * Check whether a recipe is favorited.
	 *
	 * @since    10.6.0
	 * @param    int        $recipe_id  Recipe to check.
	 * @param    int        $user_id    User to check for.
	 * @param    int[]|bool $favorites  Optional favorites list to check against.
	 */
	public static function is_recipe_favorited( $recipe_id, $user_id = 0, $favorites = false ) {
		$recipe_id = intval( $recipe_id );

		if ( ! $recipe_id ) {
			return false;
		}

		if ( false === $favorites ) {
			$favorites = self::get_favorites( $user_id );
		} else {
			$favorites = self::sanitize_favorites( $favorites );
		}

		return in_array( $recipe_id, $favorites, true );
	}

	/**
	 * Set a favorite state for a recipe.
	 *
	 * @since    10.6.0
	 * @param    int   $recipe_id       Recipe to update.
	 * @param    bool  $favorite        Whether the recipe should be favorited.
	 * @param    mixed $local_favorites Local favorites for logged out visitors.
	 * @param    int   $user_id         User to update.
	 */
	public static function set_recipe_favorite_status( $recipe_id, $favorite, $local_favorites = array(), $user_id = 0 ) {
		$recipe_id = intval( $recipe_id );
		$favorite = (bool) $favorite;
		$user_id = intval( $user_id );

		if ( ! $recipe_id ) {
			return self::get_current_favorites_ids( $local_favorites );
		}

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$favorites = $user_id ? self::get_favorites( $user_id ) : self::sanitize_favorites( $local_favorites );

		if ( $favorite ) {
			if ( ! in_array( $recipe_id, $favorites, true ) ) {
				$favorites[] = $recipe_id;
			}
		} else {
			$favorites = array_values( array_diff( $favorites, array( $recipe_id ) ) );
		}

		$favorites = self::sanitize_favorites( $favorites );

		if ( $user_id ) {
			$saved_favorites = self::save_favorites( $favorites, $user_id );

			return false === $saved_favorites ? self::get_favorites( $user_id ) : $saved_favorites;
		}

		return $favorites;
	}
}

WPRMP_Favorites::init();
