<?php
/**
 * Layout for the nutrition label.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/nutrition-label
 */

$layout = array(
    'name' => 'Classic',
    'properties' => array(),
    'blocks' => array(
        array(
            'type' => 'text',
            'text' => __( 'Nutrition Facts', 'wp-recipe-maker-premium' ),
            'style' => 'title',
        ),
        array(
            'type' => 'text',
            'text' => '%recipe_name%',
            'style' => 'subtitle',
        ),
        array(
            'type' => 'serving',
            'style' => 'subtitle',
        ),
        array(
            'type' => 'line',
            'height' => 10,
        ),
        array(
            'type' => 'text',
            'text' => __( 'Amount per Serving', 'wp-recipe-maker-premium' ),
            'style' => 'bold',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'calories',
            'calories' => 'fat',
        ),
        array(
            'type' => 'line',
            'height' => 5,
        ),
        array(
            'type' => 'text',
            'text' => __( '% Daily Value*', 'wp-recipe-maker-premium' ),
            'style' => 'daily',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'fat',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'saturated_fat',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'trans_fat',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'polyunsaturated_fat',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'monounsaturated_fat',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'cholesterol',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'sodium',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'potassium',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'carbohydrates',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'fiber',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'sugar',
            'style' => 'child',
        ),
        array(
            'type' => 'nutrient',
            'nutrient' => 'protein',
        ),
        array(
            'type' => 'line',
            'height' => 10,
        ),
        array(
            'type' => 'other_nutrients',
        ),
        array(
            'type' => 'line',
            'height' => 1,
        ),
        array(
            'type' => 'text',
            'text' => __( '* Percent Daily Values are based on a 2000 calorie diet.', 'wp-recipe-maker-premium' ),
            'style' => 'disclaimer',
        ),
    ),
);