<?php
/**
 * Handle the user ratings in the comments.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the user ratings in the comments.
 *
 * @since      9.5.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_User_Rating_Comments {

	/**
	 * Register actions and filters.
	 *
	 * @since    9.5.0
	 */
	public static function init() {
		add_filter( 'pre_get_comments', array( __CLASS__, 'exclude_from_comments_query' ) );
		add_filter( 'get_page_of_comment_query_args', array( __CLASS__, 'exclude_from_comments_page_query' ) );
		add_filter( 'get_comments_number', array( __CLASS__, 'exclude_from_comments_number' ), 10, 2 );
		add_filter( 'widget_comments_args', array( __CLASS__, 'exclude_from_comments_widget' ), 10, 2 );

		add_action( 'comment_form_comments_closed', array( __CLASS__, 'output_summary' ) );
		add_action( 'comment_form_before', array( __CLASS__, 'output_before_comment_form' ) );
		add_action( 'comment_form_after', array( __CLASS__, 'output_after_comment_form' ) );
	}

	/**
	 * Exclude user ratings without comment text from the comments query.
	 *
	 * @since	9.5.0
	 */
	public static function exclude_from_comments_query( $wp_comment_query ) {
		if ( WPRM_Settings::get( 'features_user_ratings' ) && 'comment' !== WPRM_Settings::get( 'user_ratings_no_comment_display' ) && ! is_admin() ) {
			// Make sure we're not looking for specific comments.
			if ( ( ! isset( $wp_comment_query->query_vars['comment__in'] ) || ! $wp_comment_query->query_vars['comment__in'] )
				&& ( ! isset( $wp_comment_query->query_vars['comment__not_in'] ) || ! $wp_comment_query->query_vars['comment__not_in'] ) ) {
				// Get post ID from the query.
				$post_id = $wp_comment_query->query_vars['post_id'];

				if ( $post_id ) {
					$comment_ids_without_text = get_post_meta( $post_id, 'wprm_rating_no_text_comments', true );

					// Make sure the comments without text have been calculated.
					if ( ! is_array( $comment_ids_without_text ) ) {
						$comment_ids_without_text = WPRM_Rating::update_comment_ratings_no_text( $post_id );
					}

					// Exclude these comment IDs from the query.
					if ( $comment_ids_without_text ) {
						$wp_comment_query->query_vars['comment__not_in'] = $comment_ids_without_text;
					}
				}
			}
		}

		return $wp_comment_query;
	}

	/**
	 * Exclude user ratings without comment text from the comments page query.
	 *
	 * @since	9.5.0
	 */
	public static function exclude_from_comments_page_query( $comment_args ) {
		if ( WPRM_Settings::get( 'features_user_ratings' ) && 'comment' !== WPRM_Settings::get( 'user_ratings_no_comment_display' ) ) {
			$post_id = isset( $comment_args['post_id'] ) ? $comment_args['post_id'] : false;

			if ( $post_id ) {
				$comment_ids_without_text = get_post_meta( $post_id, 'wprm_rating_no_text_comments', true );

				// Make sure the comments without text have been calculated.
				if ( ! is_array( $comment_ids_without_text ) ) {
					$comment_ids_without_text = WPRM_Rating::update_comment_ratings_no_text( $post_id );
				}

				// Exclude these comment IDs from the query.
				if ( $comment_ids_without_text ) {
					$comment_args['comment__not_in'] = $comment_ids_without_text;
				}
			}
		}

		return $comment_args;
	}

	/**
	 * Exclude user ratings without comment text from the comments number.
	 *
	 * @since	9.5.0
	 */
	public static function exclude_from_comments_number( $comment_number, $post_id ) {
		if ( WPRM_Settings::get( 'features_user_ratings' ) && 'comment' !== WPRM_Settings::get( 'user_ratings_no_comment_display' ) && ! is_admin() ) {
			$comment_ids_without_text = get_post_meta( $post_id, 'wprm_rating_no_text_comments', true );

			// Make sure the comments without text have been calculated.
			if ( ! is_array( $comment_ids_without_text ) ) {
				$comment_ids_without_text = WPRM_Rating::update_comment_ratings_no_text( $post_id );
			}

			$subtract_no_comments = is_array( $comment_ids_without_text ) ? count( $comment_ids_without_text ) : 0;
			$actual_number = intval( $comment_number );

			if ( is_numeric( $actual_number ) && 0 < $subtract_no_comments ) {
				$new_number = $actual_number - $subtract_no_comments;

				if ( 0 <= $new_number ) {
					$comment_number = '' . $new_number;
				}
			}
		}

		return $comment_number;
	}

	/**
	 * Exclude user ratings without comment text from the recent comments widget.
	 *
	 * @since	9.6.0
	 */
	public static function exclude_from_comments_widget( $args ) {
		if ( WPRM_Settings::get( 'features_user_ratings' ) && 'comment' !== WPRM_Settings::get( 'user_ratings_no_comment_display' ) ) {
			$args['meta_key'] = 'wprm-comment-rating-empty';
			$args['meta_compare'] = 'NOT EXISTS';
		}

		return $args;
	}

	/**
	 * Maybe output the summary before the comment form.
	 *
	 * @since	9.5.0
	 */
	public static function output_before_comment_form() {
		if ( 'above_form' === WPRM_Settings::get( 'user_ratings_summary_position' ) ) {
			self::output_summary();
		}
	}

	/**
	 * Maybe output the summary after the comment form.
	 *
	 * @since	9.5.0
	 */
	public static function output_after_comment_form() {
		if ( 'below_form' === WPRM_Settings::get( 'user_ratings_summary_position' ) ) {
			self::output_summary();
		}
	}

	/**
	 * Output the user ratings summary.
	 *
	 * @since	9.5.0
	 */
	public static function output_summary() {
		if ( WPRM_Settings::get( 'features_user_ratings' ) && 'summary' === WPRM_Settings::get( 'user_ratings_no_comment_display' ) ) {
			include( WPRMP_DIR . 'templates/public/user-ratings-summary.php' );
		}
	}

	/**
	 * Get user ratings summary popup content.
	 *
	 * @since	9.5.0
	 */
	public static function get_content_for_summary_popup( $post_id, $recipe_id ) {
		$html = '';
		$lines = self::get_ratings_for_summary_popup( $post_id, $recipe_id );

		foreach ( $lines as $line ) {
			$stars_html = '';

			$rating = $line['rating'];
			if ( $rating ) {
				$padding = 0;

				ob_start();
				$template = apply_filters( 'wprm_template_comment_rating', WPRM_DIR . 'templates/public/comment-rating.php' );
				require( $template );
				$stars_html = ob_get_contents();
				ob_end_clean();
			}

			if ( $stars_html ) {
				$date = $line['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $line['date'] ) ) : '';

				$html .= '<div class="wprm-popup-modal-user-rating-summary-rating">';
				$html .= '<div class="wprm-popup-modal-user-rating-summary-rating-stars">' . $stars_html . '</div>';
				$html .= '<div class="wprm-popup-modal-user-rating-summary-rating-name">' . esc_html( $line['name'] ) . '</div>';
				$html .= '<div class="wprm-popup-modal-user-rating-summary-rating-date">' . esc_html( $date ) . '</div>';
				$html .= '</div>';
			}
		}

		return $html;
	}

	public static function get_ratings_for_summary_popup( $post_id, $recipe_id ) {
		$ratings = array();

		// Get by post ID.
		if ( $post_id ) {
			$rating_args = array(
				'where' => 'approved = 1 AND post_id = ' . intval( $post_id ) . ' AND has_comment = 0',
			);
			$comment_ratings = WPRM_Rating_Database::get_ratings( $rating_args );
			
			$link_rating_to_comment = array();
			foreach ( $comment_ratings['ratings'] as $rating ) {
				$comment_id = intval( $rating->comment_id );

				if ( $comment_id ) {
					$link_rating_to_comment[ $comment_id ] = intval( $rating->rating );
				}
			}

			$comment_ids = array_keys( $link_rating_to_comment );
			
			if ( $comment_ids ) {
				$comments = get_comments( array(
					'comment__in' => $comment_ids,
				) );

				foreach ( $comments as $comment ) {
					$comment_id = $comment->comment_ID;

					if ( isset ( $link_rating_to_comment[ $comment_id ] ) ) {
						if ( empty( $comment->comment_author ) ) {
							$user = ! empty( $comment->user_id ) ? get_userdata( $comment->user_id ) : false;
							if ( $user ) {
								$comment_author = $user->display_name;
							} else {
								$comment_author = __( 'Anonymous' );
							}
						} else {
							$comment_author = $comment->comment_author;
						}

						$ratings[] = array(
							'date' => $comment->comment_date,
							'name' => $comment_author,
							'rating' => $link_rating_to_comment[ $comment_id ],
						);
					}
				}
			}
		}


		// Get User Ratings by recipe ID.
		if ( $recipe_id && WPRM_Settings::get( 'features_user_ratings' ) ) {
			$rating_args = array(
				'where' => 'approved = 1 AND recipe_id = ' . intval( $recipe_id ),
			);
			$user_ratings = WPRM_Rating_Database::get_ratings( $rating_args );

			$ratings_to_maybe_fix = array();

			foreach ( $user_ratings['ratings'] as $rating ) {
				$name = __( 'Anonymous' );
				$user_id = intval( $rating->user_id );

				if ( $user_id ) {
					$user = get_userdata( $user_id );
					if ( $user ) {
						$name = $user->display_name;
					}
				}
				$rating->name = $name; // Store name for later.

				// Fix Mediavine Create reviews that were imported without date.
				if ( 'mv-create-' === substr( $rating->ip, 0, 10 ) ) {
					$ratings_to_maybe_fix[ $rating->ip ] = $rating;
				} else {
					$ratings[] = array(
						'date' => $rating->date,
						'name' => $name,
						'rating' => intval( $rating->rating ),
					);
				}
			}

			if ( $ratings_to_maybe_fix ) {
				// Try to fix the date for these ratings.
				$mv_review_ids = array_map( function( $ip ) {
					return intval( substr( $ip, 10 ) );
				}, array_keys( $ratings_to_maybe_fix ) );

				// Get reviews for this MV ID, if table exists.
				global $wpdb;
				$mv_reviews_table = $wpdb->prefix . 'mv_reviews';
				if ( $mv_reviews_table === $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $mv_reviews_table ) ) ) {
					$reviews = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM `%1s`
						WHERE ID IN (" . implode( ', ', array_fill( 0, count( $mv_review_ids ), '%d' ) ) . ")",
						array_merge( array( $mv_reviews_table ), $mv_review_ids )
					) );

					$wprm_ratings_table = WPRM_Rating_Database::get_table_name();
					if ( $wprm_ratings_table === $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wprm_ratings_table ) ) ) {
						foreach ( $reviews as $review ) {
							$ratings_to_maybe_fix[ 'mv-create-' . $review->id ]->date = $review->created;

							// Update rating date in wprm_ratings_table.
							$wpdb->update(
								$wprm_ratings_table,
								array(
									'ip' => 'mediavine-create-' . $review->id,
									'date' => $review->created,
								),
								array(
									'ip' => 'mv-create-' . $review->id,
								),
								array(
									'%s',
									'%s',
								),
								array(
									'%s',
								)
							);
						}
					}
				}

				// Add maybe fixed ratings to the list.
				foreach ( $ratings_to_maybe_fix as $rating ) {
					$ratings[] = array(
						'date' => $rating->date,
						'name' => $rating->name,
						'rating' => intval( $rating->rating ),
					);
				}
			}
		}

		usort( $ratings, function ( $a, $b ) {
			return $b['date'] <=> $a['date'];
		});

		return $ratings;
	}
}

WPRMP_User_Rating_Comments::init();