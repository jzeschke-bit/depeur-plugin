<?php
/**
 * Template for the Recipe Submission settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/unit-conversion/templates/admin/settings
 */

$php_max_file_size = wp_max_upload_size();
$php_max_file_size = $php_max_file_size / 1024 / 1024;
$php_max_file_size = round( $php_max_file_size, 2 );

$recipe_submission = array(
	'id' => 'recipeSubmission',
	'icon' => 'letter',
	'name' => __( 'Recipe Submission', 'wp-recipe-maker' ),
	'required' => 'elite',
	'description' => __( 'Allow your visitors to submit their own recipes to your website.', 'wp-recipe-maker' ),
	'documentation' => 'https://help.bootstrapped.ventures/article/33-recipe-submisssion',
	'subGroups' => array(
		array(
			'name' => __( 'Shortcode', 'wp-recipe-maker-premium' ),
			'description' => __( 'Add the [wprm-recipe-submission] shortcode to a regular WordPress page to display the Recipe Submission form.', 'wp-recipe-maker-premium' ),
		),
		array(
			'name' => __( 'Submission Form', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'name' => __( 'Submission Form Layout', 'wp-recipe-maker-premium' ),
					'type' => 'button',
					'button' => __( 'Edit the Recipe Submission form layout', 'wp-recipe-maker-premium' ),
					'link' => admin_url( 'admin.php?page=wprmprs_layout' ),
				),
				array(
					'id' => 'recipe_submission_max_file_size_image',
					'name' => __( 'Max File Size for Images', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optional maximum file size for uploads. Cannot be bigger than the PHP file size limit:', 'wp-recipe-maker-premium' ) . ' ' . $php_max_file_size . 'MB',
					'type' => 'number',
					'suffix' => 'MB',
					'default' => '',
				),
				array(
					'id' => 'recipe_submission_max_file_size_video',
					'name' => __( 'Max File Size for Videos', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optional maximum file size for uploads. Cannot be bigger than the PHP file size limit:', 'wp-recipe-maker-premium' ) . ' ' . $php_max_file_size . 'MB',
					'type' => 'number',
					'suffix' => 'MB',
					'default' => '',
				),
				array(
					'id' => 'recipe_submission_recaptcha',
					'name' => __( 'Use reCAPTCHA', 'wp-recipe-maker-premium' ),
					'description' => __( 'Use invisible reCAPTCHA to prevent spam submissions.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_submission_recaptcha_site_key',
					'name' => __( 'reCAPTCHA Site Key', 'wp-recipe-maker-premium' ),
					'description' => __( 'Get your free invisible reCAPTCHA keys from Google.', 'wp-recipe-maker-premium' ),
					'documentation' => 'https://www.google.com/recaptcha/admin',
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						'id' => 'recipe_submission_recaptcha',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_submission_recaptcha_secret_key',
					'name' => __( 'reCAPTCHA Secret Key', 'wp-recipe-maker-premium' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						'id' => 'recipe_submission_recaptcha',
						'value' => true,
					),
				),
			),
		),
		array(
			'name' => __( 'After Submission', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'recipe_submission_admin_email',
					'name' => __( 'Send email to', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optional email address to notify when someone has submitted a recipe.', 'wp-recipe-maker-premium' ),
					'type' => 'email',
					'default' => '',
				),
				array(
					'id' => 'recipe_submission_after_action',
					'name' => __( 'After successful submission', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'text' => __( 'Stay on page and show text', 'wp-recipe-maker-premium' ),
						'redirect' => __( 'Redirect to a different page', 'wp-recipe-maker-premium' ),
					),
					'default' => 'text',
				),
				array(
					'id' => 'recipe_submission_after_text',
					'name' => __( 'Text to show after submission', 'wp-recipe-maker-premium' ),
					'type' => 'richTextarea',
					'default' => '<p>' . __( 'Thank you for submitting a recipe!', 'wp-recipe-maker-premium' ) . '</p>',
					'dependency' => array(
						'id' => 'recipe_submission_after_action',
						'value' => 'text',
					),
				),
				array(
					'id' => 'recipe_submission_after_redirect',
					'name' => __( 'URL to redirect to after submission', 'wp-recipe-maker' ),
					'description' => __( 'Full URL to where you want people to go after a successful submission.', 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						'id' => 'recipe_submission_after_action',
						'value' => 'redirect',
					),
				),
				array(
					'id' => 'recipe_submission_admin_bar',
					'name' => __( 'Show pending submissions in admin bar', 'wp-recipe-maker' ),
					'description' => __( 'Show the number of pending recipe submissions in the admin bar.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => true,
					'dependency' => array(
						'id' => 'admin_bar_menu_item',
						'value' => true,
					),
				),
			),
		),
		array(
			'name' => __( 'Approval', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'recipe_submission_auto_approve_for',
					'name' => __( 'Automatically approve submissions for', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'nobody' => __( 'Nobody', 'wp-recipe-maker-premium' ),
						'logged_in' => __( 'Logged in Users', 'wp-recipe-maker-premium' ),
						'everyone' => __( 'Everyone', 'wp-recipe-maker-premium' ),
					),
					'default' => 'nobody',
				),
				array(
					'id' => 'recipe_submission_approve_publish_recipe',
					'name' => __( 'Automatically Publish Recipe', 'wp-recipe-maker-premium' ),
					'description' => __( 'Set recipe status to Publish upon automatic approval.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						array(
							'id' => 'post_type_structure',
							'value' => 'public',
						),
						array(
							'id' => 'recipe_submission_auto_approve_for',
							'value' => 'nobody',
							'type' => 'inverse',
						)
					),
				),
				array(
					'id' => 'recipe_submission_approve_create_post',
					'name' => __( 'Automatically add to Post', 'wp-recipe-maker-premium' ),
					'description' => __( 'Create and publish a new post with the recipe inside it upon automatic approval.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_submission_auto_approve_for',
						'value' => 'nobody',
						'type' => 'inverse',
					),
				),
				array(
					'id' => 'recipe_submission_approve_create_post_category',
					'name' => __( 'Assign Category to Post', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optionally assign a specific category to the automatically created post.', 'wp-recipe-maker-premium' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'recipe_submission_auto_approve_for',
							'value' => 'nobody',
							'type' => 'inverse',
						),
						array(
							'id' => 'recipe_submission_approve_create_post',
							'value' => true,
						),
					),
				),
			),
		),
	),
);
