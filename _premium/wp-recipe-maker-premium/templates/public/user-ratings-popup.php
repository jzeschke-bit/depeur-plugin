<?php
/**
 * Template for the User Ratings Popup Modal.
 *
 * @link   https://bootstrapped.ventures
 * @since  9.9.2
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

// Code similar to /wp-recipe-maker/templates/public/comment-rating-form.php
$size = intval( WPRM_Settings::get( 'user_ratings_modal_star_size' ) );
$size = 0 < $size ? $size : 16;

$padding = intval( WPRM_Settings::get( 'user_ratings_modal_star_padding' ) );
$padding = 0 < $padding ? $padding : 0;

$stars_width = 5 * $size + 10 * $padding;
$stars_height = $size + 2 * $padding;
$input_size = $size + 2 * $padding;

if ( is_rtl() ) {
	$first_input_style = ' style="margin-right: -' . ( $size + $padding ) . 'px !important; width: ' . $input_size . 'px !important; height: ' . $input_size . 'px !important;"';
} else {
	$first_input_style = ' style="margin-left: -' . ( $size + $padding ) . 'px !important; width: ' . $input_size . 'px !important; height: ' . $input_size . 'px !important;"';
}

$input_style = ' style="width: ' . $input_size . 'px !important; height: ' . $input_size . 'px !important;"';
$span_style = ' style="width: ' . $stars_width . 'px !important; height: ' . $stars_height . 'px !important;"';

$onclick = ' onclick="WPRecipeMaker.rating.onClick(this)"';

// Currently selected rating.
$selected = 0;
?>
<form id="wprm-user-ratings-modal-stars-form" onsubmit="window.WPRecipeMaker.userRatingModal.submit( this ); return false;">
	<div class="wprm-user-ratings-modal-recipe-name"></div>
	<div class="wprm-user-ratings-modal-stars-container">
		<fieldset class="wprm-user-ratings-modal-stars">
			<legend><?php _e( 'Your vote:', 'wp-recipe-maker' ); ?></legend>
			<?php
			$labels = array(
				0 => __( "Don't rate this recipe", 'wp-recipe-maker' ),
				1 => __( 'Rate this recipe 1 out of 5 stars', 'wp-recipe-maker' ),
				2 => __( 'Rate this recipe 2 out of 5 stars', 'wp-recipe-maker' ),
				3 => __( 'Rate this recipe 3 out of 5 stars', 'wp-recipe-maker' ),
				4 => __( 'Rate this recipe 4 out of 5 stars', 'wp-recipe-maker' ),
				5 => __( 'Rate this recipe 5 out of 5 stars', 'wp-recipe-maker' ),
			);

			$rating_icons = array();

			$stars_style = WPRM_Settings::get( 'rating_stars_style' );
			$star_icon = 'pointy' === $stars_style ? 'stars-' : 'stars-alt-';

			for ( $i = 0; $i <= 5; $i++ ) {
				// Reverse order for RTL.
				$star = is_rtl() ? 5 - $i : $i;

				ob_start();
				include( WPRM_DIR . 'assets/icons/rating/' . $star_icon . $star . '.svg' );
				$svg = ob_get_contents();
				ob_end_clean();

				// Add padding.
				if ( $padding ) {
					$ratio = 120 / ( $size * 5 );
					$viewbox_padding = $padding * $ratio;

					if ( is_numeric( $viewbox_padding ) ) {
						$viewbox_width = 120 + (5 * 2 * $viewbox_padding);
						$viewbox_height = 24 + (2 * $viewbox_padding);

						$svg = str_replace( 'viewBox="0 0 120 24"', 'viewBox="0 0 ' . $viewbox_width . ' ' . $viewbox_height . '"', $svg );
						$svg = str_replace( 'width="80px"', 'width="' . ( $viewbox_width / 6 * 4 ) . 'px"', $svg );

						$svg = str_replace( 'x="96"', 'x="' . ( 9 * $viewbox_padding + 4 * 24 ) . '"', $svg );
						$svg = str_replace( 'x="72"', 'x="' . ( 7 * $viewbox_padding + 3 * 24 ) . '"', $svg );
						$svg = str_replace( 'x="48"', 'x="' . ( 5 * $viewbox_padding + 2 * 24 ) . '"', $svg );
						$svg = str_replace( 'x="24"', 'x="' . ( 3 * $viewbox_padding + 1 * 24 ) . '"', $svg );
						$svg = str_replace( 'x="0"', 'x="' . ( 1 * $viewbox_padding + 0 * 24 ) . '"', $svg );

						$svg = str_replace( 'y="0"', 'y="' . $viewbox_padding . '"', $svg );
					}
				}

				// Replace color and make ID unique.
				$svg = str_replace( 'wprm-star', 'wprm-modal-star', $svg );
				$svg = str_replace( '#343434', WPRM_Settings::get( 'user_ratings_modal_star_color' ), $svg );

				// Output HTML.
				echo '<input aria-label="' . esc_attr( $labels[ $star ] ) . '" name="wprm-user-rating-stars" value="' . $star . '" type="radio"' . $onclick;
				echo 0 === $star ? $first_input_style : $input_style;
				echo $selected === $star ? ' checked="checked"' : '';
				echo '>';
				echo '<span aria-hidden="true"' . $span_style . '>' . apply_filters( 'wprm_rating_stars_svg', $svg, $star ) . '</span>';

				if ( ( is_rtl() && 0 !== $star ) || ( ! is_rtl() && 5 !== $star ) ) {
					echo '<br>';
				}
			}
			?>
		</fieldset>
	</div>
	<?php
	$text_above_comment = trim( WPRM_Settings::get( 'user_ratings_text_above_comment' ) );

	if ( $text_above_comment ) :
	?>
	<div class="wprm-user-rating-modal-above-comment-text">
		<?php echo $text_above_comment; ?>
	</div>
	<?php endif; ?>
	<?php
	if ( 'never' !== WPRM_Settings::get( 'user_ratings_comment_suggestions_enabled' ) ) :
		$comment_suggestions = array();

		for ( $i = 1; $i <= 6; $i++ ) {
			$suggestion_setting = WPRM_Settings::get( 'user_ratings_comment_suggestion_' . $i );
			$suggestion_setting = $suggestion_setting ? trim( $suggestion_setting ) : false;

			if ( $suggestion_setting ) {
				$comment_suggestions[] = $suggestion_setting;
			}
		}

		if ( ! empty( $comment_suggestions ) ) :
			$before_suggestions_text = WPRM_Settings::get( 'user_ratings_comment_suggestion_text_before' );
			$after_suggestions_text = WPRM_Settings::get( 'user_ratings_comment_suggestion_text_after' );
	?>
	<div class="wprm-user-rating-modal-comment-suggestions-container">
		<?php if ( $before_suggestions_text ) : ?>
		<p class="wprm-user-rating-modal-comment-suggestions-before"><?php echo $before_suggestions_text; ?></p>
		<?php endif; ?>
		<div class="wprm-user-rating-modal-comment-suggestions">
			<?php foreach ( $comment_suggestions as $suggestion ) : ?>
				<div class="wprm-user-rating-modal-comment-suggestion" role="button"><?php echo $suggestion; ?></div>
			<?php endforeach; ?>
		</div>
		<?php if ( $after_suggestions_text ) : ?>
		<p class="wprm-user-rating-modal-comment-suggestions-after"><?php echo $after_suggestions_text; ?></p>
		<?php endif; ?>
	</div>
	<?php
		endif;
	endif;
	?>
	<textarea name="wprm-user-rating-comment" class="wprm-user-rating-modal-comment" placeholder="<?php echo esc_attr( WPRM_Settings::get( 'user_ratings_modal_comment_placeholder' ) ); ?>" oninput="window.WPRecipeMaker.userRatingModal.checkFields();" aria-label="<?php _e( 'Comment' ); ?>"></textarea>
	<input type="hidden" name="wprm-user-rating-recipe-id" value="" />
	<div class="wprm-user-rating-modal-comment-meta">
		<?php
		// Get current commenter as stored in cookies by WordPress.
		$current_commenter = wp_get_current_commenter();
		?>
		<div class="wprm-user-rating-modal-field">
			<label for="wprm-user-rating-name"><?php _e( 'Name' ); ?><?php if ( WPRM_Settings::get( 'user_ratings_require_name' ) ) { echo ' *'; } ?></label>
			<input type="text" id="wprm-user-rating-name" name="wprm-user-rating-name" value="<?php echo esc_attr( isset( $current_commenter['comment_author'] ) ? $current_commenter['comment_author'] : '' ); ?>" placeholder="<?php echo esc_attr( WPRM_Settings::get( 'user_ratings_modal_name_placeholder' ) ); ?>" />		</div>
		<div class="wprm-user-rating-modal-field">
			<label for="wprm-user-rating-email"><?php _e( 'Email' ); ?><?php if ( WPRM_Settings::get( 'user_ratings_require_email' ) ) { echo ' *'; } ?></label>
			<input type="email" id="wprm-user-rating-email" name="wprm-user-rating-email" value="<?php echo esc_attr( isset( $current_commenter['comment_author'] ) ? $current_commenter['comment_author_email'] : '' ); ?>" placeholder="<?php echo esc_attr( WPRM_Settings::get( 'user_ratings_modal_email_placeholder' ) ); ?>" />
		</div>	</div>
	<footer class="wprm-popup-modal__footer">
		<?php if ( ! WPRM_Settings::get( 'user_ratings_require_comment' ) ) : ?>
		<button type="submit" class="wprm-popup-modal__btn wprm-user-rating-modal-submit-no-comment"><?php echo WPRM_Settings::get( 'user_ratings_modal_submit_no_comment_button' ); ?></button>
		<?php endif; ?>
		<button type="submit" class="wprm-popup-modal__btn wprm-user-rating-modal-submit-comment"><?php echo WPRM_Settings::get( 'user_ratings_modal_submit_comment_button' ); ?></button>
		<div id="wprm-user-rating-modal-errors">
			<div id="wprm-user-rating-modal-error-rating"><?php _e( 'A rating is required', 'wp-recipe-maker' ); ?></div>
			<div id="wprm-user-rating-modal-error-name"><?php _e( 'A name is required', 'wp-recipe-maker' ); ?></div>
			<div id="wprm-user-rating-modal-error-email"><?php _e( 'An email is required', 'wp-recipe-maker' ); ?></div>
		</div>
		<div id="wprm-user-rating-modal-waiting">
			<div class="wprm-loader"></div>
		</div>
	</footer>
</form>
<div id="wprm-user-ratings-modal-message"></div>