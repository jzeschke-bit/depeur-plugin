<?php
/**
 * Favorites settings structure.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/settings
 */

$favorites = array(
	'id' => 'favorites',
	'icon' => 'heart',
	'name' => __( 'Favorites', 'wp-recipe-maker-premium' ),
	'required' => 'premium',
	'documentation' => 'https://help.bootstrapped.ventures/docs/wp-recipe-maker/favorite-recipes/',
	'description' => __( 'Allow visitors to keep track of their favorite recipes with a simple toggle button and dedicated favorites view.', 'wp-recipe-maker' ),
	'subGroups' => array(
		array(
			'name' => __( 'Favorite Recipes Shortcode & Block', 'wp-recipe-maker-premium' ),
			'description' => __( 'Add the Favorite Recipes block or [wprm-favorite-recipes] shortcode to a regular WordPress page to display this feature.', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'favorite_recipes_link',
					'name' => __( 'Link to Favorite Recipes feature', 'wp-recipe-maker' ),
					'description' => __( "Full URL of the page where you've added the Favorite Recipes block or shortcode.", 'wp-recipe-maker-premium' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'id' => 'favorite_recipes_loading_message',
					'name' => __( 'Loading Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional loading message to show while favorite recipes are loading.', 'wp-recipe-maker-premium' ) . ' ' . __( 'HTML code and the following placeholders can be used:', 'wp-recipe-maker-premium' ) . ' %loader%',
					'type' => 'richTextarea',
					'default' => '',
				),
				array(
					'id' => 'favorite_recipes_empty_message',
					'name' => __( 'Empty Message', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optional message to show when the visitor has not favorited any recipes yet.', 'wp-recipe-maker-premium' ),
					'type' => 'richTextarea',
					'default' => '<p>' . __( 'You have no favorite recipes yet.', 'wp-recipe-maker-premium' ) . '</p>',
				),
			),
		),
		array(
			'name' => __( 'Favorite Recipes Button', 'wp-recipe-maker-premium' ),
			'description' => __( 'Add the "Favorite" button to your recipe card using the Template Editor.', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'favorite_recipes_link_text',
					'name' => __( 'Favorite Recipes Link Text', 'wp-recipe-maker-premium' ),
					'description' => __( 'Text to use for the linked Favorite Recipes page inside the tooltip.', 'wp-recipe-maker-premium' ),
					'type' => 'text',
					'default' => __( 'Your Favorites', 'wp-recipe-maker-premium' ),
				),
				array(
					'id' => 'favorite_recipes_tooltip_inactive',
					'name' => __( 'Inactive Tooltip', 'wp-recipe-maker-premium' ),
					'description' => __( 'Tooltip to show when a recipe has not been favorited yet. Use %favorites_link% to insert the linked Favorite Recipes page.', 'wp-recipe-maker-premium' ),
					'type' => 'richTextarea',
					'default' => __( 'Add to %favorites_link%', 'wp-recipe-maker-premium' ),
				),
				array(
					'id' => 'favorite_recipes_tooltip_active',
					'name' => __( 'Active Tooltip', 'wp-recipe-maker-premium' ),
					'description' => __( 'Tooltip to show when a recipe is already favorited. Use %favorites_link% to insert the linked Favorite Recipes page.', 'wp-recipe-maker-premium' ),
					'type' => 'richTextarea',
					'default' => __( 'Remove from %favorites_link%', 'wp-recipe-maker-premium' ),
				),
			),
		),
		array(
			'name' => __( 'Favorite Recipe Templates', 'wp-recipe-maker-premium' ),
			'description' => __( 'Choose which recipe template to use when displaying favorite recipes.', 'wp-recipe-maker-premium' ),
			'dependency' => array(
				'id' => 'recipe_template_mode',
				'value' => 'modern',
			),
			'settings' => array(
				array(
					'id' => 'favorite_recipes_template_modern',
					'name' => __( 'Food Template', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownTemplateModern',
					'priority' => 'favorites',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'favorites-summary',
				),
				array(
					'id' => 'favorite_recipes_howto_template_modern',
					'name' => __( 'How-to Template', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownTemplateModern',
					'priority' => 'favorites',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'favorites-summary',
					'dependency' => array(
						'id' => 'recipe_template_show_types',
						'value' => true,
					),
				),
				array(
					'id' => 'favorite_recipes_other_template_modern',
					'name' => __( 'Other Template', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownTemplateModern',
					'priority' => 'favorites',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'favorites-summary',
					'dependency' => array(
						'id' => 'recipe_template_show_types',
						'value' => true,
					),
				),
			),
		),
	),
);
