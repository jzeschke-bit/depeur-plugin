<?php
/**
 * Template for the Cook Mode settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/templates/settings
 * @subpackage WP_Recipe_Maker_Premium/templates/settings
 */

$cook_mode = array(
	'id' => 'cook_mode',
	'icon' => 'whisk',
	'name' => __( 'Cook Mode', 'wp-recipe-maker' ),
	'required' => 'premium',
	'description' => __( 'Allow visitors to start cooking the recipe directly from the recipe page, with step-by-step instructions and timers.', 'wp-recipe-maker' ),
	'documentation' => 'https://help.bootstrapped.ventures/docs/wp-recipe-maker/cook-mode-popup/',
	'subGroups' => array(
		array(
			'name' => __( 'Appearance', 'wp-recipe-maker-premium' ),
			'description' => __( 'Change the appearance of the Cook Mode modal on the WP Recipe Maker > Settings > Custom Style > Popup Modal page.', 'wp-recipe-maker-premium' ),
			'settings' => array(),
		),
		array(
			'name' => __( 'Ingredients', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'cook_mode_ingredient_notes_show',
					'name' => __( 'Show Ingredient Notes', 'wp-recipe-maker-premium' ),
					'description' => __( 'Show ingredient notes in the cook mode overview and associated ingredients.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'cook_mode_ingredient_notes_separator',
					'name' => __( 'Ingredient Notes Separator', 'wp-recipe-maker-premium' ),
					'description' => __( 'How notes should be separated from the ingredient name in cook mode.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'none' => __( 'None', 'wp-recipe-maker-premium' ),
						'comma' => __( 'Comma', 'wp-recipe-maker-premium' ),
						'dash' => __( 'Dash', 'wp-recipe-maker-premium' ),
						'parentheses' => __( 'Parentheses', 'wp-recipe-maker-premium' ),
					),
					'default' => 'parentheses',
					'dependency' => array(
						'id' => 'cook_mode_ingredient_notes_show',
						'value' => true,
					),
				),
			),
		),
		array(
			'name' => __( 'Closing Screen', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'cook_mode_closing_screen',
					'name' => __( 'Closing Screen', 'wp-recipe-maker-premium' ),
					'description' => __( 'What to display on the closing screen, after the recipe has been cooked.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'message' => __( 'Show a message', 'wp-recipe-maker-premium' ),
						'message_html' => __( 'Show a message (set custom HTML)', 'wp-recipe-maker-premium' ),
					),
					'default' => 'message',
				),
				array(
					'id' => 'cook_mode_closing_screen_message',
					'name' => __( 'Closing Screen Message', 'wp-recipe-maker-premium' ),
					'type' => 'richTextarea',
					'default' => '<p>' . __( 'Hope you enjoyed cooking this recipe!', 'wp-recipe-maker-premium' ) . '</p>' . '<p>' . __( 'Please rate this recipe to help others find it.', 'wp-recipe-maker-premium' ) . '</p>',
					'dependency' => array(
						'id' => 'cook_mode_closing_screen',
						'value' => 'message',
					),
				),
				array(
					'id' => 'cook_mode_closing_screen_html',
					'name' => __( 'Closing Screen HTML', 'wp-recipe-maker-premium' ),
					'type' => 'code',
					'code' => 'html',
					'default' => '',
					'dependency' => array(
						'id' => 'cook_mode_closing_screen',
						'value' => 'message_html',
					),
				),
				array(
					'id' => 'cook_mode_closing_screen_show_stars',
					'name' => __( 'Show User Rating Stars', 'wp-recipe-maker-premium' ),
					'description' => __( 'Show voteable stars on the closing screen, which will open the User Rating Modal when clicked.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
					'dependency' => array(
						'id' => 'features_user_ratings',
						'value' => true,
					),
				),
			),
		),
	),
);
