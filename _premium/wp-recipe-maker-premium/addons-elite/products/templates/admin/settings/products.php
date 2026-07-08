<?php
/**
 * Template for the Products settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/products
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/products/templates/admin/settings
 */

$products = array(
	'id' => 'products',
	'icon' => 'dollar',
	'name' => __( 'eCommerce Products', 'wp-recipe-maker' ),
	'required' => 'elite',
	'description' => __( 'Link your ingredients and equipment to the eCommerce products on your own site to help visitors find and purchase the exact items they need for your recipes.', 'wp-recipe-maker' ) . ' ' . __( 'Currently supported integrations:', 'wp-recipe-maker' ) . ' WooCommerce',
	'documentation' => 'https://help.bootstrapped.ventures/docs/wp-recipe-maker/ecommerce-products/',
	'subGroups' => array(
		array(
			'name' => __( 'Frontend Display', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'products_show_individual',
					'name' => __( 'Show Individual Products', 'wp-recipe-maker-premium' ),
					'description' => __( 'When a product is set, show an icon next to the ingredient or equipment name in the recipe that allows visitors to view that product and add it to their cart.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => true,
				),
				array(
					'id' => 'products_show_individual_icon',
					'name' => __( 'Product Icon', 'wp-recipe-maker-premium' ),
					'description' => __( 'Choose which icon to display next to ingredients and equipment that have products linked.', 'wp-recipe-maker-premium' ),
					'type' => 'dropdown',
					'options' => array(
						'cart' => __( 'Shopping Cart', 'wp-recipe-maker-premium' ),
						'cart-alt' => __( 'Shopping Cart (alternate)', 'wp-recipe-maker-premium' ),
						'cart-simple-add' => __( 'Shopping Cart (with plus icon)', 'wp-recipe-maker-premium' ),
						'basket' => __( 'Shopping Basket', 'wp-recipe-maker-premium' ),
						'basket-simple' => __( 'Shopping Basket (simple)', 'wp-recipe-maker-premium' ),
						'dollar' => __( 'Dollar Sign', 'wp-recipe-maker-premium' ),
						'custom' => __( 'Custom Icon URL', 'wp-recipe-maker-premium' ),
					),
					'default' => 'cart-alt',
					'dependency' => array(
						'id' => 'products_show_individual',
						'value' => true,
					),
				),
				array(
					'id' => 'products_show_individual_icon_custom_url',
					'name' => __( 'Custom Icon URL', 'wp-recipe-maker-premium' ),
					'description' => __( 'Enter the URL to your custom icon image. Should be a direct link to an image file (PNG, JPG, SVG, etc.).', 'wp-recipe-maker-premium' ),
					'type' => 'text',
					'default' => '',
					'dependency' => array(
						array(
							'id' => 'products_show_individual',
							'value' => true,
						),
						array(
							'id' => 'products_show_individual_icon',
							'value' => 'custom',
						),
					),
				),
				array(
					'id' => 'products_show_individual_icon_color',
					'name' => __( 'Icon Color', 'wp-recipe-maker-premium' ),
					'type' => 'color',
					'default' => '#333333',
					'dependency' => array(
						array(
							'id' => 'products_show_individual',
							'value' => true,
						),
						array(
							'id' => 'products_show_individual_icon',
							'value' => 'custom',
							'type' => 'inverse',
						),
					),
				),
			),
		),
		array(
			'name' => __( 'Recipe Defaults', 'wp-recipe-maker-premium' ),
			'settings' => array(
				array(
					'id' => 'products_default_linked_ingredient_amount',
					'name' => __( 'Default linked ingredient amount needed to 1', 'wp-recipe-maker-premium' ),
					'description' => __( 'When enabled, linked ingredient products without a recipe-specific amount needed will default to 1. Set the amount needed to 0 on a recipe to hide that linked product for that recipe.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
				array(
					'id' => 'products_default_linked_equipment_amount',
					'name' => __( 'Default linked equipment amount needed to 1', 'wp-recipe-maker-premium' ),
					'description' => __( 'When enabled, linked equipment products without a recipe-specific amount needed will default to 1. Set the amount needed to 0 on a recipe to hide that linked product for that recipe.', 'wp-recipe-maker-premium' ),
					'type' => 'toggle',
					'default' => false,
				),
			),
		),
	),
);
