<?php
/**
 * Handle the Recipe Collections printing.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.3.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections printing.
 *
 * @since      6.3.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Print {

	/**
	 * Register actions and filters.
	 *
	 * @since    6.3.0
	 */
	public static function init() {
		add_filter( 'wprm_print_output', array( __CLASS__, 'output' ), 10, 2 );
	}

	/**
	 * Get output for the collections print page.
	 *
	 * @since    6.3.0
	 * @param	array $output 	Current output for the print page.
	 * @param	array $args	 	Arguments for the print page.
	 */
	public static function output( $output, $args ) {
		// Printing a shopping list.
		if ( 'collection' === $args[0] ) {
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprm_public = ' . json_encode( WPRM_Assets::localize_public() ) . ';</script>',
			);
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprmp_public = ' . json_encode( WPRMPRC_Assets::localize_public_data( array( 'endpoints' => array() ) ) ) . ';</script>',
			);
			$output['assets'][] = array(
				'type' => 'custom',
				'html' => '<script>wprmprc_public = ' . json_encode( WPRMPRC_Assets::localize_shortcode_data() ) . ';</script>',
			);

			// Custom CSS.
			WPRMPRC_Assets::$load_assets = true;
			ob_start();
			WPRMPRC_Assets::custom_css();
			$custom_css = ob_get_contents();
			ob_end_clean();

			$output['assets'][] = array(
				'type' => 'custom',
				'html' => $custom_css,
			);

			$output['assets'][] = array(
				'type' => 'css',
				'url' => WPRMP_URL . 'dist/public-recipe-collections.css',
			);
			$output['assets'][] = array(
				'type' => 'js',
				'url' => WPRM_URL . 'dist/public-modern.js',
			);
			$output['assets'][] = array(
				'type' => 'js',
				'url' => WPRMP_URL . 'dist/public-elite.js',
			);
			$output['assets'][] = array(
				'type' => 'js',
				'url' => WPRMP_URL . 'dist/public-recipe-collections.js',
			);

			$output['header'] = self::collection_header();
			$output['type'] = 'collection';
			$output['title'] = __( 'Collection', 'wp-recipe-maker-premium' ) . ' - ' . get_bloginfo( 'name' );
			$output['html'] = '<div id="wprm-recipe-collections-print-app"></div>';
			$output['no-email'] = true;
		}

		// Printing a shopping list.
		if ( 'shopping-list' === $args[0] ) {
			$uid = $args[1];
			$shopping_list = $uid ? WPRMPRC_Shopping_List::get( $uid ) : false;

			if ( $shopping_list ) {
				$output['assets'][] = array(
					'type' => 'css',
					'url' => WPRMP_URL . 'dist/public-recipe-collections.css',
				);

				$html = '';
				$html .= self::shopping_list_collection_html( $shopping_list['collection'] );
				$html .= self::shopping_list_html( $shopping_list );

				$output['header'] = self::shopping_list_header();
				$output['type'] = 'shopping-list';
				$output['shopping_list'] = $shopping_list;
				$output['title'] = __( 'Shopping List', 'wp-recipe-maker-premium' ) . ' - ' . get_bloginfo( 'name' );
				$output['html'] = $html;
			}
		}

		return $output;
	}

	/**
	 * Get collection print header toggles.
	 *
	 * @since    8.0.0
	 */
	public static function collection_header() {
		$header = '';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-collection-name" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-name">' . __( 'Name', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-collection-description" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-description">' . __( 'Description', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-collection-images" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-images">' . __( 'Recipe Images', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-collection-servings" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-servings">' . __( 'Servings', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		if ( WPRM_Settings::get( 'recipe_collections_nutrition_facts' ) ) {
			$header .= '<div class="wprm-print-toggle-container">';
			$header .= '<input type="checkbox" id="wprm-print-toggle-collection-nutrition" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-nutrition">' . __( 'Nutrition Facts', 'wp-recipe-maker-premium' ) . '</label>';
			$header .= '</div>';
		}

		if ( WPRM_Settings::get( 'recipe_collections_print_qr_codes' ) ) {
			$header .= '<div class="wprm-print-toggle-container">';
			$header .= '<input type="checkbox" id="wprm-print-toggle-collection-qr" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-collection-qr">' . __( 'QR Codes', 'wp-recipe-maker-premium' ) . '</label>';
			$header .= '</div>';
		}

		return $header;
	}

	/**
	 * Get HTML for the shopping list collection print.
	 *
	 * @since	8.0.0
	 * @param	array 	$collection Collection get the HTML for.
	 */
	public static function shopping_list_collection_html( $collection ) {
		ob_start();
		require( WPRMPRC_DIR . 'templates/print/shopping-list-collection.php' );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get HTML for the shopping list print.
	 *
	 * @since    6.3.0
	 * @param	array $shopping_list Shopping list to get the HTML for.
	 */
	public static function shopping_list_html( $shopping_list ) {
		ob_start();
		require( WPRMPRC_DIR . 'templates/print/shopping-list.php' );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get shopping list print header toggles.
	 *
	 * @since    6.3.0
	 */
	public static function shopping_list_header() {
		$header = '';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-shopping-list-collection" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-shopping-list-collection">' . __( 'Collection', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-shopping-list" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-shopping-list">' . __( 'Shopping List', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		$header .= '<div class="wprm-print-toggle-container">';
		$header .= '<input type="checkbox" id="wprm-print-toggle-checked-items" class="wprm-print-toggle" value="1" checked="checked"/><label for="wprm-print-toggle-checked-items">' . __( 'Show Checked Items', 'wp-recipe-maker-premium' ) . '</label>';
		$header .= '</div>';

		return $header;
	}
}

WPRMPRC_Print::init();
