<?php
/**
 * Template for the license settings sub page.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin/settings
 */

$license_key_settings = array();

$products = WPRMP_License::get_products();
foreach ( $products as $id => $product ) {
	// Use option directly and NOT settings API. Otherwise activation problems.
	$license_key_status = get_option( 'wprm_license_' . $id . '_status', false );

	$product_setting = array(
		'id' => 'license_' . $id,
		'name' => str_replace( 'WP Recipe Maker Premium - ', '', $product['name'] ),
		'description' => '',
		'type' => 'text',
	);

	if ( in_array( $license_key_status, array( 'inactive', 'invalid' ) ) ) {
		$product_setting['description'] = __( 'Warning: the license is currently inactive.', 'wp-recipe-maker-premium' );
		$product_setting['documentation'] = 'https://help.bootstrapped.ventures/article/93-activating-your-license-key';
	} elseif ( 'expired' === $license_key_status ) {
		$product_setting['description'] = __( 'Your license key has expired. Renew to keep getting updates.', 'wp-recipe-maker-premium' );
	} elseif ( 'invalid_item_id' === $license_key_status ) {
		$product_setting['description'] = __( 'The license key you have activated is for a different WP Recipe Maker Bundle. Make sure the correct plugin file is installed.', 'wp-recipe-maker-premium' );
		$product_setting['documentation'] = 'https://help.bootstrapped.ventures/article/63-installing-wp-recipe-maker';
	} elseif ( in_array( $license_key_status, array( 'active', 'valid' ) ) ) {
		$product_setting['description'] = __( 'Your license key is currently active. Fill in a blank key to deactivate.', 'wp-recipe-maker-premium' );
	}

	$license_key_settings[] = $product_setting;
}

// Add tracking preference setting.
$license_key_settings[] = array(
	'id' => 'license_allow_tracking',
	'name' => __( 'Allow Usage Data Tracking', 'wp-recipe-maker-premium' ),
	'description' => __( 'Allow the licensing API to collect usage data about your site, such as the PHP and WordPress versions.', 'wp-recipe-maker-premium' ),
	'type' => 'toggle',
	'default' => false,
);

$license_key = array(
	'id' => 'licenseKey',
	'icon' => 'key',
	'name' => __( 'License Key', 'wp-recipe-maker-premium' ),
	'description' => __( 'You can find your license key by logging into your account on our website.', 'wp-recipe-maker-premium' ),
	'documentation' => 'https://bootstrapped.ventures/account/',
	'settings' => $license_key_settings,
);
