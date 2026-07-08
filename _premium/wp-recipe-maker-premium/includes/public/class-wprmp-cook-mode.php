<?php
/**
 * Handle the Cook Mode functionality.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.2.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle the Cook Mode functionality.
 *
 * @since      10.2.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Cook_Mode {

	/**
	 * Track modal UIDs for specific recipe IDs.
	 *
	 * @since    10.2.0
	 * @var      array    $recipe_modal_uids    Array mapping recipe IDs to modal UIDs.
	 */
	private static $recipe_modal_uids = array();

	/**
	 * Register actions and filters.
	 *
	 * @since    10.2.0
	 */
	public static function init() {
		add_filter( 'wprm_recipe_cook_mode_shortcode', array( __CLASS__, 'shortcode' ), 99, 3 );
		add_filter( 'wprm_settings_structure', array( __CLASS__, 'settings_structure' ) );
	}

	/**
	 * Add cook mode settings.
	 *
	 * @since    10.2.0
	 * @param    array $structure Settings structure.
	 */
	public static function settings_structure( $structure ) {
		require( WPRMP_DIR . 'templates/settings/cook-mode.php' );
		$structure['cook_mode'] = $cook_mode;

		return $structure;
	}

	/**
	 * Handle the Cook Mode shortcode output.
	 *
	 * @since	10.2.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Shortcode attributes.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe || ! $recipe->id() ) {
			return $output;
		}

		$recipe_id = $recipe->id();

		// Check if modal already exists for this recipe ID.
		if ( isset( self::$recipe_modal_uids[ $recipe_id ] ) ) {
			$modal_uid = self::$recipe_modal_uids[ $recipe_id ];
		} else {
			// Popup content - pass recipe to template.
			ob_start();
			require( WPRMP_DIR . 'templates/public/cook-mode-popup.php' );
			$modal_content = ob_get_contents();
			ob_end_clean();

			// Popup footer - pass recipe to template.
			ob_start();
			require( WPRMP_DIR . 'templates/public/cook-mode-popup-footer.php' );
			$modal_footer = ob_get_contents();
			ob_end_clean();

			// Allow shortcodes.
			$modal_content = do_shortcode( $modal_content );
			$modal_footer = do_shortcode( $modal_footer );

			// Create modal for this recipe.
			$modal_uid = WPRM_Popup::add( array(
				'type' => 'cook-mode',
				'title' => $recipe->name() ? $recipe->name() : __( 'Cook Mode', 'wp-recipe-maker-premium' ),
				'content' => $modal_content,
				'footer' => $modal_footer,
				'reuse' => false,
			) );

			// Store modal UID for this recipe ID.
			self::$recipe_modal_uids[ $recipe_id ] = $modal_uid;
		}

		// Add modal UID to the output link.
		$output = str_replace( 'data-recipe-id="' . esc_attr( $recipe_id ) . '"', 'data-recipe-id="' . esc_attr( $recipe_id ) . '" data-modal-uid="' . esc_attr( $modal_uid ) . '"', $output );

		return $output;
	}
}

WPRMP_Cook_Mode::init();
