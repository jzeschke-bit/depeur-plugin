<?php
/**
 * Template for the User Ratings Summary in the comment section.
 *
 * @link   https://bootstrapped.ventures
 * @since  9.5.0
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

$recipe = WPRM_Template_Shortcodes::get_recipe( 0 );

if ( ! $recipe ) {
	return;
}
$recipe_id = $recipe->id();
$post_id = get_the_ID();

$rating = $recipe->rating();

if ( ! isset( $rating['type'] ) ) {
	$rating = WPRM_Rating::update_recipe_rating( $recipe->id() );
}

$nbr_votes = $rating['count'];
$nbr_votes_without_comment = $rating['count'] - $rating['type']['comment'];

// No votes given yet? Don't show summary.
if ( ! $nbr_votes ) {
	return;
}

// Set up modal.
ob_start();
require( WPRMP_DIR . 'templates/public/user-ratings-summary-popup.php' );
$modal_content = ob_get_contents();
ob_end_clean();

$modal_uid = WPRM_Popup::add(
	array(
		'type' => 'user-rating-summary',
		'title' => WPRM_Settings::get( 'user_ratings_summary_modal_title' ),
		'content' => $modal_content,
	)
);

$atts = array(
	'display' => 'stars',
	'style' => 'inline',
	'icon_color' => WPRM_Settings::get( 'user_ratings_summary_star_color' ),
	'icon_size' => WPRM_Settings::get( 'user_ratings_summary_star_size' ) . 'px',
	'icon_padding' => WPRM_Settings::get( 'user_ratings_summary_star_padding' ) . 'px',
	'average_decimals' => 2,
);

$stars_style = WPRM_Settings::get( 'rating_stars_style' );
$star_icon = 'pointy' === $stars_style ? 'star-' : 'star-alt-';

$stars = WPRMP_SC_Rating::stars( '', 'summary', $rating, false, $star_icon . 'empty', $atts['icon_color'], $atts );
$stars .= '</div>';

 // Get details to output.
 $formatted_average = WPRM_Recipe_Parser::format_quantity( $rating['average'], $atts['average_decimals'] );
 ?>
 <div class="wprm-user-rating-summary">
	<div class="wprm-user-rating-summary-stars"><?php echo $stars; ?></div>
	<div class="wprm-user-rating-summary-details">
	<?php
		if ( 1 === $nbr_votes ) {
			printf( __( '%s from 1 vote', 'wp-recipe-maker-premium' ), $formatted_average );
		} else {
			printf( __( '%s from %s votes', 'wp-recipe-maker-premium' ), $formatted_average, $nbr_votes );
		}
		if ( 0 < $nbr_votes_without_comment ) :
	?> (<a href="#" role="button" class="wprm-user-rating-summary-details-no-comments" data-modal-uid="<?php echo esc_attr( $modal_uid ); ?>" data-recipe-id="<?php echo esc_attr( $recipe_id ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>"><?php printf( _n( '%s rating without comment', '%s ratings without comment', $nbr_votes_without_comment, 'wp-recipe-maker-premium' ), number_format_i18n( $nbr_votes_without_comment ) ); ?></a>)
	<?php endif; ?>
	</div>
 </div>
