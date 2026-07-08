<?php
/**
 * Template for the Recipe Submission form.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/templates/public
 */

?>
<form class="wprm-recipe-submission" method="post" action="" enctype="multipart/form-data" onsubmit="return window.WPRecipeMaker.submission.onSubmit(this);">
	<?php echo wp_nonce_field( 'wprmprs', 'wprmprs' ); ?>
	<?php
	$dir = WPRMPRS_DIR . 'templates/public/blocks/';

	foreach ( $blocks as $block ) {
		$type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : false;

		switch ( $type ) {
			case 'agree_to_terms':
				include( $dir . 'agree.php' );
				break;
			case 'html':
				include( $dir . 'html.php' );
				break;
			case 'submit':
				if ( WPRM_Settings::get( 'recipe_submission_recaptcha' ) ) {
					include( $dir . 'submit-captcha.php' );
				} else {
					include( $dir . 'submit.php' );
				}
				break;
			case 'header':
			case 'paragraph':
			case 'recipe_image':
			case 'recipe_video_upload':
				include( $dir . $type . '.php' );
				break;
			case 'recipe_video_embed':
			case 'recipe_name':
			case 'recipe_servings':
			case 'recipe_prep_time':
			case 'recipe_cook_time':
			case 'recipe_total_time':
			case 'recipe_cost':
			case 'recipe_courses':
			case 'recipe_cuisines':
			case 'user_name':
			case 'user_email':
				include( $dir . 'input.php' );
				break;
			case 'recipe_summary':
			case 'recipe_equipment':
			case 'recipe_ingredients':
			case 'recipe_instructions':
			case 'recipe_notes':
				include( $dir . 'textarea.php' );
				break;
			case 'recipe_custom_taxonomy':
				$taxonomy_input_type = isset( $block['input_type'] ) ? sanitize_key( $block['input_type'] ) : 'text';

				if ( 'text' !== $taxonomy_input_type ) {
					$terms = get_terms( array(
						'taxonomy' => 'wprm_' . $block['field'],
						'hide_empty' => false,
						'fields' => 'id=>name',
					) );

					// Special Diet term label.
					if ( 'suitablefordiet' === $block['field'] ) {
						foreach ( $terms as $id => $label ) {
							$diet_label = get_term_meta( $id, 'wprm_term_label', true );
			
							if ( $diet_label ) {
								$terms[ $id ] = $diet_label;
							}
						}
					}

					$terms = apply_filters( 'wprm_recipe_submission_terms', $terms, 'wprm_' . $block['field'] );

					if ( 'single-select' === $taxonomy_input_type ) {
						include( $dir . 'dropdown.php' );
						break;
					} elseif ( 'single-isotope' === $taxonomy_input_type || 'multiple-isotope' === $taxonomy_input_type ) {
						include( $dir . 'isotope.php' );
						break;
					}
				}

				include( $dir . 'input.php' );
				break;
			case 'recipe_custom_field':
				$field = isset( $block['field'] ) ? $block['field'] : false;

				if ( $field ) {
					$custom_field = WPRMPCF_Manager::get_custom_field( $field );

					if ( $custom_field ) {
						switch ( $custom_field['type'] ) {
							case 'text':
							case 'link':
							case 'email':
								include( $dir . 'input.php' );
								break;
							case 'textarea':
								include( $dir . 'textarea.php' );
								break;
							case 'image':
								include( $dir . 'recipe_image.php' );
								break;
						}
					}
				}
				break;
		}
	}
	?>
</form>
