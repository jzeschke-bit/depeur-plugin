<?php
/**
 * Template for the Recipe Collections settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/templates/admin/settings
 */

$nutrition_fields = array(
	'calories' => __( 'Calories', 'wp-recipe-maker-premium' ),
	'carbohydrates' => __( 'Carbohydrates', 'wp-recipe-maker-premium' ),
	'protein' => __( 'Protein', 'wp-recipe-maker-premium' ),
	'fat' => __( 'Fat', 'wp-recipe-maker-premium' ),
);

if ( class_exists( 'WPRM_Nutrition' ) ) {
	$all_fields = WPRM_Nutrition::get_fields();
	$nutrition_fields = array_map( function( $nutrient ) { return $nutrient['label']; }, $all_fields );
	$nutrition_fields_with_servings = $nutrition_fields;

	unset( $nutrition_fields['serving_size'] );
}

$recipe_collections = array(
	'id' => 'recipeCollections',
	'icon' => 'book',
	'name' => __( 'Recipe Collections', 'wp-recipe-maker' ),
	'required' => 'elite',
	'subGroups' => array(
		array(
			'name' => __( 'Recipe Collections', 'wp-recipe-maker-premium' ),
			'description' => __( 'Add the Recipe Collections block or [wprm-recipe-collections] shortcode to a regular WordPress page to display the Recipe Collections feature.', 'wp-recipe-maker-premium' ),
			'documentation' => 'https://help.bootstrapped.ventures/article/148-recipe-collections',
			'settings' => array(
				array(
					'id' => 'recipe_collections_link',
					'name' => __( 'Link to Collections feature', 'wp-recipe-maker' ),
					'description' => __( "Full URL of the page where you've added the Recipe Collections shortcode.", 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'id' => 'recipe_collections_access',
					'name' => __( 'Access to Recipe Collections', 'wp-recipe-maker' ),
					'type' => 'dropdown',
					'options' => array(
						'everyone' => __( 'Everyone', 'wp-recipe-maker' ),
						'logged_in' => __( 'Logged In Users', 'wp-recipe-maker' ),
					),
					'default' => 'everyone',
				),
				array(
					'id' => 'recipe_collections_no_access_message_use_html',
					'name' => __( 'Use HTML for No Access Message', 'wp-recipe-maker' ),
					'description' => __( 'Enable for an advanced HTML editor for the No Access Message.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_no_access_message',
					'name' => __( 'No Access Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional text to show instead of the Recipe Collections feature for visitors with no access.', 'wp-recipe-maker' ),
					'type' => 'richTextarea',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'recipe_collections_access',
							'value' => 'logged_in',
						),
						array(
							'id' => 'recipe_collections_no_access_message_use_html',
							'value' => false,
						),
					),
				),
				array(
					'id' => 'recipe_collections_no_access_message_html',
					'name' => __( 'No Access Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional text to show instead of the Recipe Collections feature for visitors with no access', 'wp-recipe-maker' ),
					'type' => 'code',
					'code' => 'html',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'recipe_collections_access',
							'value' => 'logged_in',
						),
						array(
							'id' => 'recipe_collections_no_access_message_use_html',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_loading_message',
					'name' => __( 'Loading Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional loading message to show while the feature loads.', 'wp-recipe-maker-premium' ) . ' ' . __( 'HTML code and the following placeholders can be used:', 'wp-recipe-maker-premium' ) . ' %loader%',
					'type' => 'richTextarea',
					'default' => '',
				),
			),
		),
		array(
			'name' => __( 'Saved Collections', 'wp-recipe-maker-premium' ),
			'description' => __( 'Create your own collections to display to your visitors.', 'wp-recipe-maker-premium' ),
			'documentation' => 'https://help.bootstrapped.ventures/article/149-saved-recipe-collection',
			'settings' => array(
				array(
					'id' => 'recipe_collections_save_button',
					'name' => __( 'Allow save to own collections', 'wp-recipe-maker' ),
					'description' => __( 'Allow users to save recipes to their own collections, where they can edit the recipes and add notes.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
				'id' => 'saved_recipe_collection_loading_message',
					'name' => __( 'Loading Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional loading message to show while the feature loads.', 'wp-recipe-maker-premium' ) . ' ' . __( 'HTML code and the following placeholders can be used:', 'wp-recipe-maker-premium' ) . ' %loader%, %saved_collection_name%',
					'type' => 'richTextarea',
					'default' => '',
				),
			),
		),
		array(
			'name' => __( 'Quick Access Shopping List', 'wp-recipe-maker-premium' ),
			'description' => __( 'Allow visitors to generate a shopping list without having go through the full collections feature.', 'wp-recipe-maker-premium' ),
			'documentation' => 'https://help.bootstrapped.ventures/article/322-quick-access-shopping-list',
			'settings' => array(
				array(
					'id' => 'quick_access_shopping_list_link',
					'name' => __( 'Link to Quick Access Shopping List feature', 'wp-recipe-maker' ),
					'description' => __( "Full URL of the page where you've added the Quick Access Shopping List shortcode.", 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => '',
				),
				array(
					'id' => 'quick_access_shopping_list_access',
					'name' => __( 'Access to this Shopping List', 'wp-recipe-maker' ),
					'type' => 'dropdown',
					'options' => array(
						'everyone' => __( 'Everyone', 'wp-recipe-maker' ),
						'logged_in' => __( 'Logged In Users', 'wp-recipe-maker' ),
					),
					'default' => 'everyone',
				),
				array(
					'id' => 'quick_access_shopping_list_loading_message',
					'name' => __( 'Loading Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional loading message to show while the feature loads.', 'wp-recipe-maker-premium' ) . ' ' . __( 'HTML code and the following placeholders can be used:', 'wp-recipe-maker-premium' ) . ' %loader%',
					'type' => 'richTextarea',
					'default' => '',
				),
			),
		),
	),
);

$recipe_collections_appearance = array(
	'id' => 'recipeCollectionsAppearance',
	'icon' => 'palette',
	'name' => __( 'Collection Appearance', 'wp-recipe-maker' ),
	'required' => 'elite',
	'settings' => array(
		array(
			'id' => 'recipe_collections_appearance_layout',
			'name' => __( 'Layout Style', 'wp-recipe-maker-premium' ),
			'description' => __( '"Grid" is the default collection layout as of version 8.0.0', 'wp-recipe-maker-premium' ),
			'type' => 'dropdown',
			'options' => array(
				'classic' => __( 'Classic - The original collections layout using columns', 'wp-recipe-maker-premium' ),
				'grid' => __( 'Grid - Large images, presented in a grid', 'wp-recipe-maker-premium' ),
			),
			'default' => 'grid',
		),
		array(
			'id' => 'recipe_collections_appearance_adding_layout',
			'name' => __( 'Adding Items Layout', 'wp-recipe-maker-premium' ),
			'description' => __( 'Layout to use when adding items to a collection', 'wp-recipe-maker-premium' ),
			'type' => 'dropdown',
			'options' => array(
				'column' => __( 'Add directly in column (backwards compatibility)', 'wp-recipe-maker-premium' ),
				'modal' => __( 'Open modal to select the item to add', 'wp-recipe-maker-premium' ),
			),
			'default' => 'modal',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_appearance_structure_layout',
			'name' => __( 'Change Recipe Structure Layout', 'wp-recipe-maker-premium' ),
			'description' => __( 'Layout to use for changing the recipe structure', 'wp-recipe-maker-premium' ),
			'type' => 'dropdown',
			'options' => array(
				'icons' => __( 'Inline icons for changing the structure (backwards compatibility)', 'wp-recipe-maker-premium' ),
				'modal' => __( 'Change the structure in a separate modal', 'wp-recipe-maker-premium' ),
			),
			'default' => 'modal',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_appearance_font_size_grid',
			'name' => __( 'Base Font Size', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'px',
			'default' => '16',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_appearance_column_size_grid',
			'name' => __( 'Minimum Column Width', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'px',
			'default' => '250',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_appearance_font_size',
			'name' => __( 'Base Font Size', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'px',
			'default' => '12',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_appearance_column_size',
			'name' => __( 'Minimum Column Width', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'px',
			'default' => '200',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_recipe_style',
			'name' => __( 'Recipe style', 'wp-recipe-maker' ),
			'type' => 'dropdown',
			'options' => array(
				'compact' => __( 'Compact', 'wp-recipe-maker' ),
				'large' => __( 'Large Image', 'wp-recipe-maker' ),
				'overlay' => __( 'Overlay', 'wp-recipe-maker' ),
			),
			'default' => 'compact',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_header_color',
			'name' => __( 'Header Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_header_text_color',
			'name' => __( 'Header Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#ffffff',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_button_color',
			'name' => __( 'Button Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#d3d3d3',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_button_text_color',
			'name' => __( 'Button Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#666666',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'classic',
			),
		),
		array(
			'id' => 'recipe_collections_icon_color',
			'name' => __( 'Icon Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#111111',
		),
		array(
			'id' => 'recipe_collections_grid_base_color',
			'name' => __( 'Base Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_header_color',
			'name' => __( 'Header Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_header_border',
			'name' => __( 'Header Border Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#cccccc',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_item_background',
			'name' => __( 'Item Background Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#ffffff',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_item_color',
			'name' => __( 'Item Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_item_border',
			'name' => __( 'Item Border Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#cccccc',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_item_image_position',
			'name' => __( 'Item Image Position', 'wp-recipe-maker-premium' ),
			'type' => 'dropdown',
			'options' => array(
				'side' => __( 'On the side', 'wp-recipe-maker-premium' ),
				'top' => __( 'On top', 'wp-recipe-maker-premium' ),
			),
			'default' => 'side',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_item_image_size',
			'name' => __( 'Item Image Size', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'px',
			'default' => '75',
			'dependency' => array(
				array(
					'id' => 'recipe_collections_appearance_layout',
					'value' => 'grid',
				),
				array(
					'id' => 'recipe_collections_grid_item_image_position',
					'value' => 'side',
				),
			),
		),
		array(
			'id' => 'recipe_collections_grid_servings_background',
			'name' => __( 'Servings Background Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#eeeeee',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_servings_color',
			'name' => __( 'Servings Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_nutrition_background',
			'name' => __( 'Nutrition Background Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#fafafa',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_nutrition_color',
			'name' => __( 'Nutrition Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_nutrition_border',
			'name' => __( 'Nutrition Border Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#cccccc',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
		array(
			'id' => 'recipe_collections_grid_action_color',
			'name' => __( 'Action Text Color', 'wp-recipe-maker' ),
			'type' => 'color',
			'default' => '#000000',
			'dependency' => array(
				'id' => 'recipe_collections_appearance_layout',
				'value' => 'grid',
			),
		),
	),
);

$recipe_collections_functionality = array(
	'id' => 'recipeCollectionsFunctionality',
	'icon' => 'touch',
	'name' => __( 'Collection Functionality', 'wp-recipe-maker' ),
	'required' => 'elite',
	'subGroups' => array(
		array(
			'name' => __( 'Add to Collection Button', 'wp-recipe-maker-premium' ),
			'description' => __( 'The button shown in the recipe template to allow users to easily add recipes to collections.', 'wp-recipe-maker-premium' ),
			'documentation' => 'https://help.bootstrapped.ventures/docs/wp-recipe-maker/add-to-recipe-collection-button/',
			'settings' => array(
				array(
					'id' => 'recipe_collections_inbox_name',
					'name' => __( 'Default Inbox Name', 'wp-recipe-maker' ),
					'description' => __( 'Name of the inbox collection that exists for everyone. Will not affect existing collections.', 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => __( 'Inbox', 'wp-recipe-maker-premium' ),
				),
				array(
					'id' => 'recipe_collections_add_button_behaviour',
					'name' => __( 'Add Button Behaviour', 'wp-recipe-maker-premium' ),
					'description' => __( 'What happens when clicking the "Add to Collection" button.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'inbox' => __( 'Recipe gets added to the default "Inbox" collection', 'wp-recipe-maker-premium' ),
						'choose' => __( 'User gets to choose a collection to add the recipe to', 'wp-recipe-maker-premium' ),
					),
					'default' => 'inbox',
				),
				array(
					'id' => 'recipe_collections_add_button_choice',
					'name' => __( 'Where to add the Recipe', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'first' => __( 'Do not offer a choice, put in the first column and first group', 'wp-recipe-maker-premium' ),
						'choose_column' => __( 'Let user choose a column only, put in the first group of that column', 'wp-recipe-maker-premium' ),
						'choose_column_group' => __( 'User picks both the column and group to add the recipe to', 'wp-recipe-maker-premium' ),
					),
					'default' => 'choose_column_group',
					'dependency' => array(
						'id' => 'recipe_collections_add_button_behaviour',
						'value' => 'choose',
					),
				),
				array(
					'id' => 'recipe_collections_add_button_placement',
					'name' => __( 'Recipe Placement', 'wp-recipe-maker-premium' ),
					'description' => __( 'Where the recipe gets added after clicking the button.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'top' => __( 'At the top of the collection', 'wp-recipe-maker-premium' ),
						'bottom' => __( 'At the bottom of the collection', 'wp-recipe-maker-premium' ),
					),
					'default' => 'bottom',
				),
				array(
					'id' => 'recipe_collections_add_button_not_logged_in',
					'name' => __( 'When not logged in', 'wp-recipe-maker-premium' ),
					'description' => __( 'What to do with the "Add to Collection" button when the visitor is not logged in.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'hide' => __( 'Hide the button', 'wp-recipe-maker-premium' ),
						'disabled' => __( 'The button does not do anything', 'wp-recipe-maker-premium' ),
						'redirect' => __( 'Take the user to a specific page on click', 'wp-recipe-maker-premium' ),
					),
					'default' => 'hide',
					'dependency' => array(
						'id' => 'recipe_collections_access',
						'value' => 'logged_in',
					),
				),
				array(
					'id' => 'recipe_collections_add_button_not_logged_in_redirect',
					'name' => __( 'Redirect URL', 'wp-recipe-maker' ),
					'description' => __( 'URL to take a non-logged in user to when clicking on the button.', 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'recipe_collections_access',
							'value' => 'logged_in',
						),
						array(
							'id' => 'recipe_collections_add_button_not_logged_in',
							'value' => 'redirect',
						),
					)
				),
				array(
					'id' => 'recipe_collections_add_button_not_logged_in_tooltip',
					'name' => __( 'Tooltip Message', 'wp-recipe-maker' ),
					'description' => __( 'Optional tooltip message to show to non-logged in users when hovering over the button.', 'wp-recipe-maker' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'recipe_collections_access',
							'value' => 'logged_in',
						),
						array(
							'id' => 'recipe_collections_add_button_not_logged_in',
							'value' => 'hide',
							'type' => 'inverse',
						),
					)
				),
			),
		),
		array(
			'name' => __( 'Features', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'recipe_collections_recipe_click',
					'name' => __( 'Click on recipe', 'wp-recipe-maker' ),
					'description' => __( 'What happens when clicking on a recipe in the collection.', 'wp-recipe-maker' ),
					'type' => 'dropdown',
					'options' => array(
						'disabled' => __( 'Does nothing', 'wp-recipe-maker' ),
						'recipe' => __( 'Shows the recipe box', 'wp-recipe-maker' ),
						'parent' => __( 'Opens the parent post', 'wp-recipe-maker' ),
					),
					'default' => 'recipe',
				),
				array(
					'id' => 'recipe_collections_template_modern',
					'name' => __( 'Recipe template to show', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_recipe_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
					),
				),
				array(
					'id' => 'recipe_collections_howto_template_modern',
					'name' => __( 'How-To template to show', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_recipe_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
						array(
							'id' => 'recipe_template_show_types',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_other_template_modern',
					'name' => __( 'Other template to show', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_recipe_template' => __( 'Use same as Default Recipe Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_recipe_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
						array(
							'id' => 'recipe_template_show_types',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_template_legacy',
					'name' => __( 'Recipe template to show', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateLegacy',
					'default' => 'simple',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'legacy',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
					),
				),
				array(
					'id' => 'recipe_collections_recipe_click_new_tab',
					'name' => __( 'Force recipe click to open in new tab', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_collections_recipe_click',
						'value' => 'parent',
					),
				),
				array(
					'id' => 'recipe_collections_adjustable_servings',
					'name' => __( 'Allow adjustable servings', 'wp-recipe-maker' ),
					'description' => __( 'Allow visitors to adjust the servings in their collections', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'recipe_collections_share_collection',
					'name' => __( 'Allow Collection Sharing', 'wp-recipe-maker' ),
					'description' => __( 'Allow logged in users to share any collection that they have created. Will create a link that allows others to view that collection.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_print',
					'name' => __( 'Show "Print Collection" button', 'wp-recipe-maker' ),
					'description' => __( 'This prints the collection over with columns and rows', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_print_qr_codes',
					'name' => __( 'Show QR Codes for Recipe Items', 'wp-recipe-maker' ),
					'description' => __( 'When printing a collection, show QR codes linking back to the parent post for all recipe items.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_collections_print',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_print_recipes',
					'name' => __( 'Show "Print Recipes" button', 'wp-recipe-maker' ),
					'description' => __( 'This prints the recipes used in the collection.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_print_recipes_multiple_times',
					'name' => __( 'Print recipes multiple times', 'wp-recipe-maker' ),
					'description' => __( 'When printing a collection of recipes, print the same recipe multiple times', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_collections_print_recipes',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_print_recipes_template_modern',
					'name' => __( 'Recipe template to print', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_print_template' => __( 'Use same as Default Print Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_print_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_print_recipes',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_print_howto_recipes_template_modern',
					'name' => __( 'How-To template to print', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_print_template' => __( 'Use same as Default Print Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_print_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
						array(
							'id' => 'recipe_template_show_types',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_print_other_recipes_template_modern',
					'name' => __( 'Other template to print', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateModern',
					'options' => array(
						'default_print_template' => __( 'Use same as Default Print Template', 'wp-recipe-maker' ),
					),
					'default' => 'default_print_template',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'modern',
						),
						array(
							'id' => 'recipe_collections_recipe_click',
							'value' => 'recipe',
						),
						array(
							'id' => 'recipe_template_show_types',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_print_recipes_template',
					'name' => __( 'Recipe template print', 'wp-recipe-maker' ),
					'type' => 'dropdownTemplateLegacy',
					'default' => 'clean',
					'dependency' => array(
						array(
							'id' => 'recipe_template_mode',
							'value' => 'legacy',
						),
						array(
							'id' => 'recipe_collections_print_recipes',
							'value' => true,
						),
					),
				),
				array(
					'id' => 'recipe_collections_scroll_to_top',
					'name' => __( 'Scroll to Top', 'wp-recipe-maker' ),
					'description' => __( 'Automatically stroll to top while navigating collections.', 'wp-recipe-maker' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'recipe_collections_scroll_to_top_offset',
					'name' => __( 'Scroll to Top Offset', 'wp-recipe-maker' ),
					'description' => __( 'Offset to use when scrolling to top (increase to get below sticky headers, for example).', 'wp-recipe-maker' ),
					'type' => 'number',
					'suffix' => 'px',
					'default' => '30',
					'dependency' => array(
						'id' => 'recipe_collections_scroll_to_top',
						'value' => true,
					),
				),
			),
		),
		array(
			'name' => __( 'Collection Items', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'recipe_collections_history',
					'name' => __( 'Undo/Redo History', 'wp-recipe-maker-premium' ),
					'description' => __( 'Enable undo/redo and history timeline for collection changes.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'recipe_collections_default_add',
					'name' => __( 'Default Add Item Selection', 'wp-recipe-maker-premium' ),
					'description' => __( 'Default selection when adding items to a collection (inbox excluded).', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'collection' => __( 'Add from Collection', 'wp-recipe-maker-premium' ),
						'search' => __( 'Search Recipes', 'wp-recipe-maker-premium' ),
						'ingredient' => __( 'Search Ingredients', 'wp-recipe-maker-premium' ),
						'custom' => __( 'Add Custom Recipe', 'wp-recipe-maker-premium' ),
						'note' => __( 'Add Note', 'wp-recipe-maker-premium' ),
					),
					'default' => 'collection',
				),
				array(
					'id' => 'recipe_collections_items_allow_recipe_search',
					'name' => __( 'Allow Search Recipes', 'wp-recipe-maker-premium' ),
					'description' => __( 'Allow recipes to be added to a collection by searching for them.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'recipe_collections_items_allow_recipe_search_by_types',
					'name' => __( 'Limit Search Recipes by Type', 'wp-recipe-maker-premium' ),
					'description' => __( 'Limit recipes shown in search results by type. Does not affect searches by editors.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'all' => __( 'Show all published recipes from all types', 'wp-recipe-maker-premium' ),
						'limit' => __( 'Only show published recipes from selected types', 'wp-recipe-maker-premium' ),
					),
					'default' => 'all',
					'dependency' => array(
						'id' => 'recipe_collections_items_allow_recipe_search',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_items_allow_recipe_search_types',
					'name' => __( 'Recipe Types', 'wp-recipe-maker-premium' ),
					'description' => __( 'Types to show in the search results. Select at least one.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownMultiselect',
					'options' => array(
						'food' => __( 'Food Recipe', 'wp-recipe-maker' ),
						'howto' => __( 'How-to Instructions', 'wp-recipe-maker' ),
						'other' => __( 'Other (no metadata)', 'wp-recipe-maker' ),
					),
					'default' => array(
						'food',
						'howto',
						'other',
					),
					'dependency' => array(
						array(
							'id' => 'recipe_collections_items_allow_recipe_search',
							'value' => true,
						),
						array(
							'id' => 'recipe_collections_items_allow_recipe_search_by_types',
							'value' => 'limit',
						),
					),
				),
				array(
					'id' => 'recipe_collections_items_recipe_servings',
					'name' => __( 'Search Recipes Servings', 'wp-recipe-maker-premium' ),
					'description' => __( 'Servings to use when searching for recipes in the collections feature.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'original' => __( 'Use servings as set for the recipe', 'wp-recipe-maker-premium' ),
						'one' => __( 'Default to 1 serving for the collection', 'wp-recipe-maker-premium' ),
						'two' => __( 'Default to 2 servings for the collection', 'wp-recipe-maker-premium' ),
						'three' => __( 'Default to 3 servings for the collection', 'wp-recipe-maker-premium' ),
						'four' => __( 'Default to 4 servings for the collection', 'wp-recipe-maker-premium' ),
					),
					'default' => 'original',
					'dependency' => array(
						'id' => 'recipe_collections_items_allow_recipe_search',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_search_recipes_wpessid',
					'name' => __( 'WP Extended Search ID', 'wp-recipe-maker-premium' ),
					'description' => __( 'Optionally alter the recipe search in combination with the WP Extended Search plugin.', 'wp-recipe-maker-premium' ),
					'documentation' => 'https://help.bootstrapped.ventures/article/346-altering-the-recipe-collections-search-with-wp-extended-search',
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						'id' => 'recipe_collections_items_allow_recipe_search',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_items_leftovers',
					'name' => __( 'Allow Marking as Leftovers', 'wp-recipe-maker-premium' ),
					'description' => __( 'Leftovers will count towards the nutrition facts, but the ingredients for this recipe will not be added to the shopping list.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_items_allow_ingredient',
					'name' => __( 'Allow Ingredients', 'wp-recipe-maker-premium' ),
					'description' => __( 'Allow nutrition ingredients to be added to a collection. These can be added on the WP Recipe Maker > Manage > Your Custom Fields > Custom Nutrition page.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_items_allow_custom_recipe',
					'name' => __( 'Allow Custom Recipes', 'wp-recipe-maker-premium' ),
					'description' => __( 'Allow custom recipes to be added to a collection.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'recipe_collections_items_allow_note',
					'name' => __( 'Allow Notes', 'wp-recipe-maker-premium' ),
					'description' => __( 'Allow notes to be added to a collection.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
			),
		),
		array(
			'name' => __( 'Nutrition Facts', 'wp-recipe-maker-premium' ),
			'description' => __( 'In each column, show the added totals for the nutrition facts.', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'recipe_collections_nutrition_facts',
					'name' => __( 'Enable button to show nutrition facts', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_hidden_default',
					'name' => __( 'Button is enabled by default', 'wp-recipe-maker-premium' ),
					'description' => __( 'When disabled an extra click is required to show the nutrition facts.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_collections_nutrition_facts',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_count',
					'name' => __( 'Nutrition Facts Totals', 'wp-recipe-maker' ),
					'type' => 'dropdown',
					'options' => array(
						'serving' => __( 'Show totals per serving', 'wp-recipe-maker' ),
						'total' => __( 'Show totals including all servings', 'wp-recipe-maker' ),
					),
					'default' => 'serving',
					'dependency' => array(
						'id' => 'recipe_collections_nutrition_facts',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_fields',
					'name' => __( 'Nutrition fields to show', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownMultiselect',
					'options' => $nutrition_fields,
					'default' => array(
						'calories',
						'carbohydrates',
						'protein',
						'fat',
					),
					'dependency' => array(
						'id' => 'recipe_collections_nutrition_facts',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_round_to_decimals',
					'name' => __( 'Round quantity to', 'wp-recipe-maker' ),
					'description' => __( 'Number of decimals to round a quantity to when adding up nutrition facts.', 'wp-recipe-maker' ),
					'type' => 'number',
					'suffix' => 'decimals',
					'default' => '1',
					'dependency' => array(
						'id' => 'recipe_collections_nutrition_facts',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_highlight',
					'name' => __( 'Highlight nutrition facts per item', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
					'dependency' => array(
						'id' => 'recipe_collections_nutrition_facts',
						'value' => true,
					),
				),
				array(
					'id' => 'recipe_collections_nutrition_facts_highlight_fields',
					'name' => __( 'Nutrition fields to highlight', 'wp-recipe-maker-premium' ),
					'type' => 'dropdownMultiselect',
					'options' => $nutrition_fields_with_servings,
					'default' => array(
						'calories',
					),
					'dependency' => array(
						array(
							'id' => 'recipe_collections_nutrition_facts',
							'value' => true,
						),
						array(
							'id' => 'recipe_collections_nutrition_facts_highlight',
							'value' => true,
						),
					),
				),
			),
		),
	),
);

$recipe_collections_shopping_list = array(
	'id' => 'recipeCollectionsShoppingList',
	'icon' => 'shopping-cart',
	'name' => __( 'Collection Shopping List', 'wp-recipe-maker' ),
	'required' => 'elite',
	'settings' => array(
		array(
			'id' => 'recipe_collections_shopping_list',
			'name' => __( 'Allow shopping list generation', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
		),
		array(
			'id' => 'recipe_collections_shopping_list_shortcut',
			'name' => __( 'Show shortcut in collections overview', 'wp-recipe-maker' ),
			'description' => __( 'Show icon to immediately go to the shopping list from the recipe collections overview.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_options',
			'name' => __( 'Show shopping list generation options', 'wp-recipe-maker' ),
			'description' => __( 'Allows user to choose how the shopping list gets generated.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_options_notes',
			'name' => __( 'Default for Include Ingredient Notes', 'wp-recipe-maker' ),
			'description' => __( 'The initial value for the "Include Ingredient Notes" checkbox when generating the shopping list.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => false,
			'dependency' => array(
				array(
					'id' => 'recipe_collections_shopping_list',
					'value' => true,
				),
				array(
					'id' => 'recipe_collections_shopping_list_options',
					'value' => true,
				),
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_links',
			'name' => __( 'Ingredient Links', 'wp-recipe-maker' ),
			'description' => __( 'Use ingredient links in the shopping list.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_round_to_decimals',
			'name' => __( 'Round quantity to', 'wp-recipe-maker' ),
			'description' => __( 'Number of decimals to round a quantity to in the shopping list.', 'wp-recipe-maker' ),
			'type' => 'number',
			'suffix' => 'decimals',
			'default' => '2',
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_smart_combine',
			'name' => __( 'Smart Combine Units', 'wp-recipe-maker' ),
			'description' => __( 'Try to combine different weight and volume units to one value.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => false,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_share',
			'name' => __( 'Show "Share Edit Link" option', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_print',
			'name' => __( 'Show "Print Shopping List" button', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => true,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_print_recipes',
			'name' => __( 'Show "Print Recipes" button', 'wp-recipe-maker' ),
			'description' => __( 'This prints the full recipes used in the shopping list.', 'wp-recipe-maker' ),
			'type' => 'toggle',
			'default' => false,
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
		array(
			'id' => 'recipe_collections_shopping_list_remove',
			'name' => __( "Remove shopping lists that haven't been updated in", 'wp-recipe-maker' ),
			'description' => __( 'Automatically remove inactive shopping lists to save database storage. Mininum of 7 days.', 'wp-recipe-maker' ),
			'type' => 'number',
			'default' => 31,
			'suffix' => __( 'days', 'wp-recipe-maker' ),
			'dependency' => array(
				'id' => 'recipe_collections_shopping_list',
				'value' => true,
			),
		),
	),
);