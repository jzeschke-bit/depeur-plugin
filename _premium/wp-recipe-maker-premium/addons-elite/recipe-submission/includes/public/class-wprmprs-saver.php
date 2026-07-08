<?php
/**
 * Save recipe from the Recipe Submission form data.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 */

/**
 * Save recipe from the Recipe Submission form data.
 *
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Saver {
	/**
	 * Save recipe submission.
	 *
	 * @since	2.1.0
	 * @param	array $user User that submitted the form.
	 * @param	array $recipe Recipe that was submitted through the form.
	 */
	public static function save_recipe( $user, $recipe ) {
		$recipe = WPRM_Recipe_Sanitizer::sanitize( $recipe );

		// Create recipe as pending.
		$post = array(
			'post_type' => WPRM_POST_TYPE,
			'post_status' => 'pending',
		);

		$recipe_id = wp_insert_post( $post );

		// Save recipe data.
		WPRM_Recipe_Saver::update_recipe( $recipe_id, $recipe );

		// Save user data.
		update_post_meta( $recipe_id, 'wprm_submission_user', $user );

		// Automatically approve recipe?
		if ( 'everyone' === WPRM_Settings::get( 'recipe_submission_auto_approve_for' )
			|| ( 'logged_in' === WPRM_Settings::get( 'recipe_submission_auto_approve_for' ) && is_user_logged_in() ) ) {
				self::approve_recipe( $recipe_id, WPRM_Settings::get( 'recipe_submission_approve_create_post' ), true );
		}
	}

	/**
	 * Approve recipe submission.
	 *
	 * @since	5.5.0
	 * @param	array $recipe_id 	Recipe to approve.
	 * @param	array $create_post 	Whether we should create a post with that recipe.
	 * @param	array $publish_post Whether we should publish that optional post.
	 */
	public static function approve_recipe( $recipe_id, $create_post, $publish_post ) {
		if ( $recipe_id && WPRM_POST_TYPE === get_post_type( $recipe_id ) && 'pending' === get_post_status( $recipe_id ) ) {
			if ( ! $create_post ) {
				// Approve recipe by updating status.
				$recipe = array(
					'ID'          	=> $recipe_id,
					'post_status' 	=> WPRM_Settings::get( 'recipe_submission_approve_publish_recipe' ) ? 'publish' : 'draft',
				);
				wp_update_post( $recipe );

				return true;
			} else {
				// Create new post to add recipe to.
				$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );

				$post = array(
					'post_type' => 'post',
					'post_status' => $publish_post ? 'publish' : 'draft',
					'post_title' => $recipe->name(),
					'post_content' => '[wprm-recipe id="' . $recipe_id . '"]',
				);

				$post = apply_filters( 'wprm_recipe_submission_approve_add_post', $post, $recipe_id );
				$post_id = wp_insert_post( $post );

				// Optionally set a specific category for this new post.
				$post_category = trim( WPRM_Settings::get( 'recipe_submission_approve_create_post_category' ) ); 

				if ( $post_category ) {
					$post_category_id = WPRM_Recipe_Sanitizer::get_term_id_by_name( 'category', $post_category );

					if ( $post_category_id ) {
						wp_set_object_terms( $post_id, array( $post_category_id ), 'category', false );
					}
				}

				// Force parent post update if we're already publishing the post.
				if ( $publish_post ) {
					WPRM_Recipe_Saver::update_recipes_check();
				}

				// Set recipe image as post thumbnail.
				$image_id = $recipe->image_id();
				if ( $image_id ) {
					set_post_thumbnail( $post_id, $image_id );
				}

				return array(
					'edit_link' => get_edit_post_link( $post_id, '' ),
				);
			}
		}

		return false;
	}
}
