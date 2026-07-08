<?php
/**
 * Auto-generate snapshots for legacy unit conversion data.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 */

/**
 * Auto-generate snapshots for legacy unit conversion data.
 *
 * @since    10.2.0
 * @package    WP_Recipe_Maker_Premium/addons-pro/unit-conversion
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/unit-conversion/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPUC_Snapshot {

	/**
	 * Register hooks.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_field', array( __CLASS__, 'auto_generate_snapshots' ), 10, 3 );
	}

	/**
	 * Auto-generate snapshots for ingredients that have conversions but no snapshot (legacy data).
	 * This prevents false warnings for ingredients created before the snapshot feature.
	 *
	 * @since    10.2.0
	 * @param    mixed $value  Field value.
	 * @param    mixed $field  Field name.
	 * @param    mixed $recipe Recipe object.
	 */
	public static function auto_generate_snapshots( $value, $field, $recipe ) {
		// Only process ingredients field.
		if ( 'ingredients' !== $field ) {
			return $value;
		}

		// Only run if unit conversion is enabled.
		if ( ! WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
			return $value;
		}

		// Make sure we have an array.
		if ( ! is_array( $value ) ) {
			return $value;
		}

		// Auto-generate snapshots for ingredients that have conversions but no snapshot.
		$needs_snapshot_update = false;
		foreach ( $value as $group_index => $group ) {
			if ( ! isset( $group['ingredients'] ) || ! is_array( $group['ingredients'] ) ) {
				continue;
			}

			foreach ( $group['ingredients'] as $ingredient_index => $ingredient ) {
				// Only process actual ingredients with a name.
				if ( ! isset( $ingredient['name'] ) || ! $ingredient['name'] ) {
					continue;
				}
				
				if ( ! isset( $ingredient['conversion_item_snapshot'] ) ) {
					$needs_snapshot_update = true;
					$value[ $group_index ]['ingredients'][ $ingredient_index ]['conversion_item_snapshot'] = array(
						'amount' => isset( $ingredient['amount'] ) ? $ingredient['amount'] : '',
						'unit' => isset( $ingredient['unit'] ) ? $ingredient['unit'] : '',
						'timestamp' => time() * 1000, // Match JavaScript Date.now() format (milliseconds).
					);
				} else {
                    // At least one existing snapshot, so assume all other ingredients have a snapshot too.
                    break 2;
                }
			}
		}

		return $value;
	}
}

WPRMPUC_Snapshot::init();

