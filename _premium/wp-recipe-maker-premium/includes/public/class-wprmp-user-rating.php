<?php
/**
 * Handle the user ratings.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the user ratings.
 *
 * @since      1.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_User_Rating {

	/**
	 * Current user UID.
	 *
	 * @since	8.4.0
	 * @access	private
	 * @var		mixed	$uid	UID for the current user.
	 */
	private static $uid = false;

	private static $modal_uid = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    1.6.0
	 */
	public static function init() {
		add_action( 'wp_ajax_wprm_user_rate_recipe', array( __CLASS__, 'ajax_user_rate_recipe' ) );
		add_action( 'wp_ajax_nopriv_wprm_user_rate_recipe', array( __CLASS__, 'ajax_user_rate_recipe' ) );
	}

	/**
	 * Get user ratings modal UID.
	 *
	 * @since	9.2.0
	 */
	public static function get_modal_uid() {
		// Only create modal once and reuse for all user ratings.
		if ( false === self::$modal_uid ) {
			ob_start();
			require( WPRMP_DIR . 'templates/public/user-ratings-popup.php' );
			$modal_content = ob_get_contents();
			ob_end_clean();

			self::$modal_uid = WPRM_Popup::add(
				array(
					'type' => 'user-rating',
					'reuse' => true,
					'title' => WPRM_Settings::get( 'user_ratings_modal_title' ),
					'content' => $modal_content,
				)
			);
		}

		return self::$modal_uid;
	}

	/**
	 * Get user ratings for a specific recipe.
	 *
	 * @since	2.2.0
	 * @param	int $recipe_id ID of the recipe.
	 */
	public static function get_ratings_for( $recipe_id ) {
		$recipe_id = intval( $recipe_id );

		$ratings = array();

		if ( $recipe_id ) {
			$user_ratings = WPRM_Rating_Database::get_ratings(array(
				'where' => 'recipe_id = ' . $recipe_id,
			));

			$ratings = $user_ratings['ratings'];
		}

		return $ratings;
	}

	/**
	 * Add or update rating for a specific recipe.
	 *
	 * @since	2.2.0
	 * @param	int $recipe_id ID of the recipe.
	 * @param	int $user_rating Rating to add for this recipe.
	 */
	public static function add_or_update_rating_for( $recipe_id, $user_rating ) {
		$recipe_id = intval( $recipe_id );

		if ( $recipe_id ) {
			$rating = array(
				'recipe_id' => $recipe_id,
				'user_id' => get_current_user_id(),
				'ip' => self::get_user_ip(),
				'rating' => $user_rating,
			);

			WPRM_Rating_Database::add_or_update_rating( $rating );

			// Maybe clear cache.
			if ( WPRM_Settings::get( 'user_ratings_clear_cache' ) ) {
				// Get optional parent post ID.
				$parent_post_id = false;

				$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
				if ( $recipe ) {
					$parent_post_id = $recipe->parent_post_id();
				}

				// Try to clear caching plugins.
				if ( $parent_post_id ) {
					WPRM_Cache::clear( $recipe_id, false );
					WPRM_Cache::clear( $parent_post_id );
				} else {
					WPRM_Cache::clear( $recipe_id );
				}
			}
		}
	}

	/**
	 * Get the rating the current user has given to a specific recipe.
	 *
	 * @since    1.6.0
	 * @param	 int $recipe_id The Recipe to get the rating for.
	 */
	public static function get_user_rating_for( $recipe_id ) {
		if ( isset ( $_COOKIE[ 'WPRM_User_Voted_' . $recipe_id ] ) ) {
			return intval( $_COOKIE[ 'WPRM_User_Voted_' . $recipe_id ] );
		}

		$rating = 0;

		$ip = self::get_user_ip();
		$user = get_current_user_id();

		$user_ratings = self::get_ratings_for( $recipe_id );

		foreach ( $user_ratings as $user_rating ) {
			if ( ! $user && 'unknown' !== $ip && $ip === $user_rating->ip ) {
				$rating = $user_rating->rating;
			} elseif ( $user && $user === $user_rating->user_id ) {
				$rating = $user_rating->rating;
			}
		}

		return $rating;
	}

	/**
	 * Set the user rating for a recipe through AJAX.
	 * Duplicates functionality in the api_user_rating_for_recipe function.
	 *
	 * @since    1.6.0
	 */
	public static function ajax_user_rate_recipe() {
		if ( is_user_logged_in() && ! check_ajax_referer( 'wprm', 'security', false ) ) {
			// Logged in users need to be verified.
			wp_send_json_error();
		}

		$recipe_id = isset( $_POST['recipe_id'] ) ? intval( $_POST['recipe_id'] ) : 0; // Input var okay.
		$encoded = isset( $_POST['data'] ) ? $_POST['data'] : false; // Input var okay.

		if ( $recipe_id && $encoded ) {
			$encoded = rawurldecode( $encoded );
			$encoded = str_replace( '\"', '"', $encoded );
			$data = json_decode( $encoded );

			if ( $data ) {
				$data = (array) $data;
				$rated_recipe = self::rate_recipe( $recipe_id, $data );

				if ( $rated_recipe ) {
					// Get new recipe object.
					$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		
					if ( $recipe ) {
						wp_send_json_success( $recipe->rating() );
					}
				}
			}
		}
		
		wp_send_json_error();
	}

	/**
	 * Set the user rating for a recipe.
	 *
	 * @since    9.2.0
	 */
	public static function rate_recipe( $recipe_id, $data ) {
		$recipe_id = intval( $recipe_id );
		$post_id = isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0;
		$rating = isset( $data['rating'] ) ? intval( $data['rating'] ) : 0;

		if ( $recipe_id && $rating && self::is_user_allowed_to_vote() ) {
			$comment = isset( $data['comment'] ) ? trim( $data['comment'] ) : '';
			$user_id = get_current_user_id();
			$name = '';
			$email = '';

			if ( 0 === $user_id ) {
				$name = isset( $data['name'] ) ? trim( $data['name'] ) : '';
				$email = isset( $data['email'] ) ? trim( $data['email'] ) : '';
			} else {
				$user = get_user_by( 'id', $user_id );
				$name = $user->display_name;
				$email = $user->user_email;
			}

			// Check if all required fields are filled in.
			if ( ! $comment && WPRM_Settings::get( 'user_ratings_require_comment' ) ) {
				return false;
			}
			if ( ! $user_id ) {
				if ( ! $name && WPRM_Settings::get( 'user_ratings_require_name' ) ) {
					return false;
				}
				if ( ! $email && WPRM_Settings::get( 'user_ratings_require_email' ) ) {
					return false;
				}
			}

			// Requirements met, continue with adding the rating.
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

			// To count towards this recipe, the comment needs to be on the parent post of the recipe. But fallback to wherever the modal was shown if no parent post is set.
			$post_to_add_comment_to = $recipe->parent_post_id() ? $recipe->parent_post_id() : $post_id;
			
			if ( $recipe ) {
				$comment_id = false;

				// If comment is empty, check for an existing empty comment that might have already been given.
				if ( ! $comment ) {
					$comment_id = self::get_existing_comment_id( $post_to_add_comment_to, $user_id, $name, $email );
				}

				if ( ! $comment_id ) {
					// Comment content needs to be set to display the stars in the comment.
					if ( ! $comment ) {
						$comment = ' ';
					}

					// Prevent WordPress from not allowing this comment.
					add_filter( 'allow_empty_comment', '__return_true' );

					// Don't check for duplicate if comment is empty.
					if ( ' ' === $comment ) {
						add_filter( 'duplicate_comment_id', '__return_false' );
					}

					// Prevent Akismet from marking this as spam.
					remove_filter( 'preprocess_comment', array( 'Akismet', 'auto_check_comment' ), 1 );

					// Prevent Antispam Bee from marking this as spam.
					remove_filter( 'preprocess_comment', array( 'Antispam_Bee', 'handle_incoming_request' ), 1 );

					// Prevent WPDiscuz from marking this as spam.
					if ( class_exists( 'WpdiscuzCore' ) && method_exists( 'WpdiscuzCore', 'getInstance' ) ) {
						$wpdiscuz = WpdiscuzCore::getInstance();
						remove_filter( 'preprocess_comment', array( $wpdiscuz, 'validateRecaptcha' ), 10, 2 );
					}

					// Prevent WP Comment Policy Checkbox from stopping this comment.
					remove_filter( 'preprocess_comment', 'wpcpc_verify_policy_check' );

					// Prevent hCaptcha for WP from blocking things.
					if ( function_exists( 'hcaptcha' ) ) {
						$hcaptcha = hcaptcha();

						if ( $hcaptcha instanceof HCaptcha\Main ) {
							$hcaptcha_comment = $hcaptcha->get( 'HCaptcha\WP\Comment' );

							if ( $hcaptcha_comment ) {
								remove_filter( 'preprocess_comment', array( $hcaptcha_comment, 'verify' ), - PHP_INT_MAX );
							}
						}
					}

					// Prevent Titan Anti-Spam from marking this as spam.
					$_POST['wantispam_q'] = date( 'Y' );
					$_POST['wantispam_d'] = date( 'Y' );

					// Check if comment should always be approved.
					if ( WPRM_Settings::get( 'user_ratings_automatically_approve' ) ) {
						$auto_approve = false;

						if ( 'all' === WPRM_Settings::get( 'user_ratings_automatically_approve_type' ) ) {
							$auto_approve = true;
						} else {
							if ( ' ' === $comment ) {
								$treshold = array(
									'1_star' => 1,
									'2_star' => 2,
									'3_star' => 3,
									'4_star' => 4,
									'5_star' => 5,
								);

								if ( isset( $treshold[ WPRM_Settings::get( 'user_ratings_automatically_approve_type' ) ] ) ) {
									$auto_approve = $rating >= $treshold[ WPRM_Settings::get( 'user_ratings_automatically_approve_type' ) ];
								}
							}
						}

						if ( $auto_approve ) {
							add_filter( 'pre_comment_approved', function( $approved, $commentdata ) {
								return '1';
							}, 10, 2 );
						}
					}

					// Create comment.
					$comment_id = wp_new_comment( array(
						'comment_post_ID' => $post_to_add_comment_to,
						'comment_author' => $name,
						'comment_author_email' => $email,
						'comment_author_url' => '',
						'comment_content' => $comment,
						'user_id' => $user_id,
					), true );
				}

				// Add rating to this new comment.
				if ( $comment_id && ! is_wp_error( $comment_id ) ) {
					WPRM_Comment_Rating::add_or_update_rating_for( $comment_id, $rating );

					// Store last rating of this user for this recipe.
					setcookie( 'WPRM_User_Voted_' . $recipe_id, $rating, time() + 60 * 60 * 24 * 30, '/' );
					$_COOKIE[ 'WPRM_User_Voted_' . $recipe_id ] = $rating; // Set in current request as well.

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if there is an existing comment that this voter has already given.
	 *
	 * @since	9.4.2
	 */
	public static function get_existing_comment_id( $post_to_add_comment_to, $user_id, $name, $email ) {
		// Source: wp-includes/comment.php
		global $wpdb;

		$user_ip = self::get_user_ip();

		// If we have nothing to check by, return false.
		if ( ! $user_id && ! $email && ! $user_ip ) {
			return false;
		}

		// Look for duplicate in database.
		$dupe = $wpdb->prepare(
			"SELECT c.comment_ID from $wpdb->comments c JOIN $wpdb->commentmeta m on c.comment_ID = m.comment_ID AND m.meta_key = 'wprm-comment-rating' WHERE m.meta_value > 0 AND comment_content = ' ' AND comment_post_ID = %d AND comment_approved != 'trash' ",
			wp_unslash( $post_to_add_comment_to )
		);
		if ( $user_id ) {
			$dupe .= $wpdb->prepare(
				'AND user_id = %d ',
				wp_unslash( $user_id )
			);
		} elseif ( $email ) {
			$dupe .= $wpdb->prepare(
				'AND comment_author_email = %s ',
				wp_unslash( $email )
			);
		} else {
			$dupe .= $wpdb->prepare(
				'AND comment_author_IP = %s ',
				wp_unslash( $user_ip )
			);
		}
		$dupe .= 'LIMIT 1';

		$dupe_id = $wpdb->get_var( $dupe );

		return $dupe_id ? intval( $dupe_id ) : false;
	}

	/**
	 * Check if the current user is allowed to vote.
	 *
	 * @since    1.6.0
	 */
	public static function is_user_allowed_to_vote() {
		return true; // Comments with name and email are required now, so anyone is allowed to vote.
	}

	/**
	 * Get the IP address of the current user.
	 * Source: http://stackoverflow.com/questions/6717926/function-to-get-user-ip-address
	 *
	 * @since    1.6.0
	 */
	public static function get_user_ip() {
		foreach ( array( 'REMOTE_ADDR', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED' ) as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$server_value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				foreach ( array_map( 'trim', explode( ',', $server_value ) ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return '127.0.0.1';
	}
}

WPRMP_User_Rating::init();