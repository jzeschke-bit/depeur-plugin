<?php
/**
 * Template for translate settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/templates/admin/settings
 */

$translate = array(
	'id' => 'translate',
	'icon' => 'text',
	'name' => __( 'Translation API', 'wp-recipe-maker' ),
	'required' => 'pro',
	'description' => __( 'Simplify the nutrition facts and unit conversion API workflow for non-English sites by automatically translating ingredients.', 'wp-recipe-maker' ),
	'documentation' => 'https://help.bootstrapped.ventures/article/284-translation-api',
	'settings' => array(
		array(
			'id' => 'translate_enabled',
			'name' => __( 'Enable Translation API', 'wp-recipe-maker-premium' ),
			'type' => 'toggle',
			'default' => false,
		),
	),
	'subGroups' => array(
		array(
			'name' => __( 'Unit Systems', 'wp-recipe-maker-premium' ),
			'dependency' => array(
				'id' => 'translate_enabled',
				'value' => true,
			),
			'settings' => array(
				array(
					'id' => 'translate_api_key',
					'name' => __( 'Google Translate API Key', 'wp-recipe-maker-premium' ),
					'description' => __( 'Google Cloud Platform API key with access to the Cloud Translation API.', 'wp-recipe-maker-premium' ),
					'documentation' => 'https://help.bootstrapped.ventures/article/284-translation-api',
					'type' => 'text',
					'default' => '',
				),
				array(
					'id' => 'translate_source_language',
					'name' => __( 'Source Language', 'wp-recipe-maker-premium' ),
					'description' => __( 'ISO-639-1 code for the source language you write in. Leave blank to let Google automatically detect the source language.', 'wp-recipe-maker-premium' ),
					'documentation' => 'https://cloud.google.com/translate/docs/languages',
					'type' => 'text',
					'default' => '',
				),
			),
		),
	),
);
