<?php
/**
 * Responsible for the nutrition label layout.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.8.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Responsible for the Premium nutrition fields.
 *
 * @since      6.8.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Nutrition_Label_Layout {

	/**
	 * Register actions and filters.
	 *
	 * @since    6.8.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ), 11 );

		add_action( 'wp_ajax_wprmp_save_nutrition_layout', array( __CLASS__, 'ajax_save_layout' ) );
	}

	/**
	 * Enqueue stylesheets and scripts.
	 *
	 * @since    6.8.0
	 */
	public static function enqueue_admin() {
		$screen = get_current_screen();
		
		if ( 'admin_page_wprmp_nutrition_label_layout' === $screen->id ) {
			wp_localize_script( 'wprmp-admin', 'wprmp_nutrition_label_layout', array(
				'layout' => self::get_layout(),
				'defaults' => self::get_default_layouts(),
				'blocks' => self::get_block_options(),
				'text' => array(
					'fat_calories' => __( 'Calories from Fat', 'wp-recipe-maker-premium' ),
				)
			) );
		}
	}

	/**
	 * Add the layout page.
	 *
	 * @since	6.8.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'Nutrition Label Layout', 'wp-recipe-maker-premium' ), __( 'Nutrition Label Layout', 'wp-recipe-maker-premium' ), 'manage_options', 'wprmp_nutrition_label_layout', array( __CLASS__, 'layout_page_template' ) );
	}

	/**
	 * Get the template for the layout page.
	 *
	 * @since	6.8.0
	 */
	public static function layout_page_template() {
		echo '<div id="wprmp-nutrition-label-layout" class="wrap">Loading...</div>';
	}

	/**
	 * Save the user submission form layout through AJAX.
	 *
	 * @since    6.8.0
	 */
	public static function ajax_save_layout() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$layout = isset( $_POST['layout'] ) ? json_decode( wp_unslash( $_POST['layout'] ) ) : array(); // Input var okay.

				// Save blocks as associative array.
				$layout = json_decode( json_encode( $layout ), true );

				// Need to autoload to allow multilingual plugins to translate.
				if ( false !== WPRM_Compatibility::multilingual() ) {
					update_option( 'wprmp_nutrition_label_layout', $layout, true );
				} else {
					update_option( 'wprmp_nutrition_label_layout', $layout, false );
				}
				
				wp_send_json_success();
			}
		}

		wp_die();
	}

	/**
	 * Get nutrition label layout.
	 *
	 * @since	6.8.0
	 * @param boolean $fallback_to_defaults Fallback to default if no layout is set.
	 */
	public static function get_layout( $fallback_to_defaults = false ) {
		$layout = get_option( 'wprmp_nutrition_label_layout', false );

		if ( $layout ) {
			$layout = self::combine_with_defaults( $layout );
		} else {
			$defaults = self::get_default_layouts();
			$layout = $defaults['fda'];
		}

		return $layout;
	}

	/**
	 * Combine layout with defaults.
	 *
	 * @since	6.8.0
	 * @param boolean $layout Layout to combine with defaults.
	 */
	public static function combine_with_defaults( $layout ) {
		// Properties.
		$properties = $layout['properties'];
		$defaults = self::get_properties_defaults();

		$properties_with_defaults = array_merge(
			$defaults,
			$properties
		);

		$layout['properties'] = $properties_with_defaults;

		// Blocks.
		$blocks = $layout['blocks'];
		$defaults = self::get_block_defaults();

		$blocks_with_defaults = array();
		$block_id = 0;

		foreach ( $blocks as $block ) {
			$type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : false;

			if ( $type && array_key_exists( $type, $defaults ) ) {
				$block = array_merge(
					$defaults[ $type ],
					$block
				);
			}

			// Make sure a unique ID is set.
			$block['id'] = $block_id;
			$block_id++;

			$blocks_with_defaults[] = $block;
		}

		$layout['blocks'] = $blocks_with_defaults;

		return $layout;
	}

	/**
	 * Get the default properties for the nutrition label.
	 *
	 * @since	6.8.0
	 */
	public static function get_properties_defaults() {
		$properties = array(
			'display_values' => 'serving',
			'border_width' => 1,
			'border_style' => 'solid',
			'border_color' => '#333333',
			'max_width' => 250,
			'padding' => 5,
			'font_family' => 'Arial, Helvetica, sans-serif',
			'font_size' => 12,
			'line_height' => 16,
			'text_color' => '#000000',
			'background_color' => '#ffffff',
		);

		return $properties;
	}

	/**
	 * Get the block options for the nutrition label.
	 *
	 * @since	6.8.0
	 */
	public static function get_block_options() {
		$blocks = array(
			'text' => array(
				'type' => 'text',
				'label' => __( 'Text', 'wp-recipe-maker-premium' ),
			),
			'line' => array(
				'type' => 'line',
				'label' => __( 'Line', 'wp-recipe-maker-premium' ),
			),
			'serving' => array(
				'type' => 'serving',
				'label' => __( 'Serving Size', 'wp-recipe-maker-premium' ),
			),
			'nutrient' => array(
				'type' => 'nutrient',
				'label' => __( 'Nutrient', 'wp-recipe-maker-premium' ),
			),
			'other_nutrients' => array(
				'type' => 'other_nutrients',
				'label' => __( 'All other active Nutrients', 'wp-recipe-maker-premium' ),
			),
		);

		// Attach defaults.
		$defaults = self::get_block_defaults();

		foreach ( $blocks as $type => $options ) {
			if ( isset( $defaults[ $type ] ) ) {
				$blocks[ $type ]['properties'] = $defaults[ $type ];
			} else {
				$blocks[ $type ]['properties'] = array();
			}
		}

		return $blocks;
	}

	/**
	 * Get the default blocks for the nutrition label.
	 *
	 * @since	6.8.0
	 */
	public static function get_block_defaults() {
		$blocks = array(
			'text' => array(
				'name' => __( 'Text', 'wp-recipe-maker-premium' ),
				'text' => 'Nutrition Facts',
				'style' => 'regular',
			),
			'serving' => array(
				'name' => __( 'Servings', 'wp-recipe-maker-premium' ),
				'text' => __( 'Serving Size', 'wp-recipe-maker-premium' ),
				'style' => 'regular',
				'serving_value' => 'actual',
			),
			'nutrient' => array(
				'name' => __( 'Nutrient', 'wp-recipe-maker-premium' ),
				'nutrient' => 'calories',
				'style' => 'main',
				'calories' => 'normal'
			),
			'other_nutrients' => array(
				'name' => __( 'All other active Nutrients', 'wp-recipe-maker-premium' ),
				'style' => 'other',
			),
			'line' => array(
				'name' => __( 'Line', 'wp-recipe-maker-premium' ),
				'height' => 10,
			),
		);

		return $blocks;
	}

	/**
	 * Get the default layouts for the nutrition label.
	 *
	 * @since	6.8.0
	 */
	public static function get_default_layouts() {
		$layouts = array();

		$dirs = array(
			WPRMP_DIR . 'templates/nutrition-label',
		);

		foreach ( $dirs as $dir ) {
			if ( $handle = opendir( $dir ) ) {
				while ( false !== ( $file = readdir( $handle ) ) ) {
					preg_match( '/^(.*?).php/', $file, $match );
					if ( isset( $match[1] ) ) {
						$file = $match[0];

						$layout = false;
						require( $dir . '/' . $file );

						// Only if that file contained a $template variable.
						if ( $layout ) {
							$layouts[ $match[1] ] = self::combine_with_defaults( $layout );
						}
					}
				}
			}
		}

		return $layouts;
	}
}

WPRMP_Nutrition_Label_Layout::init();
