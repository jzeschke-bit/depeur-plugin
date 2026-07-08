<?php
/**
 * Handle the Recipe Collections assets.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections assets.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Assets {

	public static $localized_with_user_data = false;

	/**
	 * Whether or not the assets should get loaded on this page.
	 *
	 * @since    6.3.0
	 * @access   private
	 * @var      boolean $load_assets Whether or not the assets should get loaded on this page.
	 */
	public static $load_assets = false;

	/**
	 * Register actions and filters.
	 *
	 * @since	4.1.0
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'custom_css' ), 99 );
		add_action( 'admin_head', array( __CLASS__, 'custom_css_admin' ) );

		add_filter( 'wprmp_localize_public', array( __CLASS__, 'localize_public_data' )  );
	}

	/**
	 * Enqueue the public assets.
	 *
	 * @since	4.2.0
	 */
	public static function enqueue() {
		wp_register_style( 'wprmprc-public', WPRMP_URL . 'dist/public-recipe-collections.css', array(), WPRMP_VERSION, 'all' );
		wp_register_script( 'wprmprc-public', WPRMP_URL . 'dist/public-recipe-collections.js', array( 'wprmp-public' ), WPRMP_VERSION, true );
	}

	/**
	 * Actually load the public assets.
	 *
	 * @since	5.5.0
	 */
	public static function load() {
		// Make sure regular recipe assets are loaded as well.
		WPRM_Assets::load();

		self::$load_assets = true;

		wp_enqueue_style( 'wprmprc-public' );
		wp_enqueue_script( 'wprmprc-public' );
	}

	/**
	 * Localize the public JS file.
	 *
	 * @since	4.1.0
	 */
	public static function localize_public_data( $data ) {
		$data['endpoints']['collections'] = rtrim( get_rest_url( null, 'wp/v2/' . WPRMPRC_POST_TYPE ), '/' );
		$data['endpoints']['collections_helper'] = rtrim( get_rest_url( null, 'wp-recipe-maker/v1/recipe-collections' ), '/' );
		$data['endpoints']['nutrition'] = rtrim( get_rest_url( null, 'wp-recipe-maker/v1/nutrition' ), '/' );
		$data['collections'] = array(
			'default' => WPRMPRC_Manager::get_default_collections(),
		);
		$data['user'] = get_current_user_id();

		// Scroll to Top.
		$data['settings']['recipe_collections_scroll_to_top'] = WPRM_Settings::get( 'recipe_collections_scroll_to_top' );
		$data['settings']['recipe_collections_scroll_to_top_offset'] = WPRM_Settings::get( 'recipe_collections_scroll_to_top_offset' );

		// Add to Collection behaviour.
		$data['add_to_collection'] = array(
			'access' => WPRM_Settings::get( 'recipe_collections_access' ),
			'behaviour' => WPRM_Settings::get( 'recipe_collections_add_button_behaviour' ),
			'choice' => WPRM_Settings::get( 'recipe_collections_add_button_choice' ),
			'placement' => WPRM_Settings::get( 'recipe_collections_add_button_placement' ),
			'not_logged_in' => WPRM_Settings::get( 'recipe_collections_add_button_not_logged_in' ),
			'not_logged_in_redirect' => WPRM_Settings::get( 'recipe_collections_add_button_not_logged_in_redirect' ),
			'not_logged_in_tooltip' => WPRM_Settings::get( 'recipe_collections_add_button_not_logged_in_tooltip' ),
			'collections' => array(
				'inbox' => WPRM_Settings::get('recipe_collections_inbox_name'),
				'user' => array(),
			),
		);

		if ( 'choose' === WPRM_Settings::get( 'recipe_collections_add_button_behaviour' ) ) {
			$collections = WPRMPRC_Manager::get_user_collections( false, true );

			// Use actual name of inbox for current user.
			if ( $collections['inbox']['name'] ) {
				$data['add_to_collection']['collections']['inbox'] = $collections['inbox']['name'];
			}

			foreach ( $collections['user'] as $collection ) {
				// Add as option.
				$data['add_to_collection']['collections']['user'][] = array(
					'id' => $collection['id'],
					'name' => $collection['name'],
					'nbrItems' => $collection['nbrItems'],
					'groups' => $collection['groups'],
					'columns' => $collection['columns'],
				);
			}
		}

		// Quick Access Shopping List behaviour.
		$data['quick_access_shopping_list'] = array(
			'access' => WPRM_Settings::get( 'quick_access_shopping_list_access' ),
		);

		return $data;
	}

	/**
	 * Data for localizing the shortcode.
	 *
	 * @since	4.1.0
	 */
	public static function localize_shortcode_data( $include_user_collections = false, $specific_user = false ) {
		$current_user = get_current_user_id();

		// Get user display name.
		$collections_user_name = '';
		$collections_user_data = $specific_user ? get_userdata( $specific_user ) : get_userdata( $current_user );
		if ( $collections_user_data ) {
			$collections_user_name =  $collections_user_data->data->display_name;
		}

		$data = array(
			'user' => $current_user,
			'collections_user' => $specific_user ? $specific_user : $current_user,
			'collections_user_name' => $collections_user_name,
			'starter_templates' => array(),
			'quick_add_collections' => array(),
			'settings' => array(
				'recipe_collections_link' => WPRM_Settings::get( 'recipe_collections_link' ),
				'recipe_collections_adjustable_servings' => WPRM_Settings::get( 'recipe_collections_adjustable_servings' ),
				'recipe_collections_share_collection' => WPRM_Settings::get( 'recipe_collections_share_collection' ),
				'recipe_collections_print' => WPRM_Settings::get( 'recipe_collections_print' ),
				'recipe_collections_print_qr_codes' => WPRM_Settings::get( 'recipe_collections_print_qr_codes' ),
				'recipe_collections_print_recipes' => WPRM_Settings::get( 'recipe_collections_print_recipes' ),
				'recipe_collections_appearance_layout' => WPRM_Settings::get( 'recipe_collections_appearance_layout' ),
				'recipe_collections_appearance_adding_layout' => WPRM_Settings::get( 'recipe_collections_appearance_adding_layout' ),
				'recipe_collections_appearance_structure_layout' => WPRM_Settings::get( 'recipe_collections_appearance_structure_layout' ),
				'recipe_collections_icons' => apply_filters( 'wprmrc_icons', array() ),
				'recipe_collections_icon_color' => WPRM_Settings::get( 'recipe_collections_icon_color' ),
				'recipe_collections_grid_item_image_position' => WPRM_Settings::get( 'recipe_collections_grid_item_image_position' ),
				'recipe_collections_recipe_style' => WPRM_Settings::get( 'recipe_collections_recipe_style' ),
				'recipe_collections_recipe_click' => WPRM_Settings::get( 'recipe_collections_recipe_click' ),
				'recipe_collections_recipe_click_new_tab' => WPRM_Settings::get( 'recipe_collections_recipe_click_new_tab' ),
				'recipe_collections_history' => WPRM_Settings::get( 'recipe_collections_history' ),
				'recipe_collections_default_add' => WPRM_Settings::get( 'recipe_collections_default_add' ),
				'recipe_collections_items_allow_recipe_search' => WPRM_Settings::get( 'recipe_collections_items_allow_recipe_search' ),
				'recipe_collections_search_recipes_wpessid' => WPRM_Settings::get( 'recipe_collections_search_recipes_wpessid' ),
				'recipe_collections_items_leftovers' => WPRM_Settings::get( 'recipe_collections_items_leftovers' ),
				'recipe_collections_items_allow_ingredient' => WPRM_Settings::get( 'recipe_collections_items_allow_ingredient' ),
				'recipe_collections_items_allow_custom_recipe' => WPRM_Settings::get( 'recipe_collections_items_allow_custom_recipe' ),
				'recipe_collections_items_allow_note' => WPRM_Settings::get( 'recipe_collections_items_allow_note' ),
				'recipe_collections_nutrition_facts' => WPRM_Settings::get( 'recipe_collections_nutrition_facts' ),
				'recipe_collections_nutrition_facts_hidden_default' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_hidden_default' ),
				'recipe_collections_nutrition_facts_count' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_count' ),
				'recipe_collections_nutrition_facts_fields' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_fields' ),
				'recipe_collections_nutrition_facts_round_to_decimals' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_round_to_decimals' ),
				'recipe_collections_nutrition_facts_highlight' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_highlight' ),
				'recipe_collections_nutrition_facts_highlight_fields' => WPRM_Settings::get( 'recipe_collections_nutrition_facts_highlight_fields' ),
				'recipe_collections_shopping_list' => WPRM_Settings::get( 'recipe_collections_shopping_list' ),
				'integration_instacart_shopping_list' => WPRM_Settings::get( 'integration_instacart_shopping_list' ),
				'recipe_collections_shopping_list_share' => WPRM_Settings::get( 'recipe_collections_shopping_list_share' ),
				'recipe_collections_shopping_list_print' => WPRM_Settings::get( 'recipe_collections_shopping_list_print' ),
				'recipe_collections_shopping_list_print_recipes' => WPRM_Settings::get( 'recipe_collections_shopping_list_print_recipes' ),
				'recipe_collections_save_button' => WPRM_Settings::get( 'recipe_collections_save_button' ),
				'recipe_collections_shopping_list_shortcut' => WPRM_Settings::get( 'recipe_collections_shopping_list_shortcut' ),
				'recipe_collections_shopping_list_options' => WPRM_Settings::get( 'recipe_collections_shopping_list_options' ),
				'recipe_collections_shopping_list_options_notes' => WPRM_Settings::get( 'recipe_collections_shopping_list_options_notes' ),
				'unit_conversion_enabled' => WPRM_Settings::get( 'unit_conversion_enabled' ),
				'unit_conversion_system_1' => WPRM_Settings::get( 'unit_conversion_system_1' ),
				'unit_conversion_system_2' => WPRM_Settings::get( 'unit_conversion_system_2' ),
			),
			'labels' => array(
				'nutrition_fields' => WPRM_Nutrition::get_fields(),
			),
		);

		// If we included user data before, we should include it again.
		$should_include_user_data = $include_user_collections || self::$localized_with_user_data;

		if ( $should_include_user_data ) {
			self::$localized_with_user_data = true;

			$data['collections'] = WPRMPRC_Manager::get_user_collections( $specific_user, false );
			$data['collections_default'] = WPRMPRC_Manager::get_default_collections();
			$data['starter_templates'] = WPRMPRC_Manager::get_collections_by_field( 'template' );
			$data['quick_add_collections'] = WPRMPRC_Manager::get_collections_by_field( 'quick_add' );
			$data['push_collections'] = WPRMPRC_Manager::get_collections_by_field( 'push' );
			$data['fixed_collections'] = WPRMPRC_Manager::get_collections_by_field( 'fixed' );

			// Get all saved collections.
			if ( isset( $_GET['wprmprc_user'] ) ) {
				$collections = array();

				$args = array(
					'post_type' => WPRMPRC_POST_TYPE,
					'post_status' => 'any',
					'posts_per_page' => -1,
					'orderby' => 'date',
					'order' => 'DESC',
				);

				$query = new WP_Query( $args );
				$posts = $query->posts;
				foreach ( $posts as $post ) {
					$collection = WPRMPRC_Manager::get_collection( $post );

					if ( ! $collection ) {
						continue;
					}

					$collections[] = $collection->get_data();
				}

				// First order, then add as options.
				$collections = WPRMPRC_Manager::order_collections( $collections );

				$saved_collections = array();
				foreach ( $collections as $collection ) {
					$saved_collections[] = array(
						'value' => $collection['id'],
						'label' => $collection['id'] . ' - ' . $collection['name'],
						'data' => $collection,
					);
				}

				$data['saved_collections'] = $saved_collections;
			}
		}

		return $data;
	}

	/**
	 * Localize the collections feature.
	 *
	 * @since	5.9.0
	 */
	public static function localize_collections( $specific_user = false ) {
		WPRM_Assets::add_js_data( 'wprmprc_public', self::localize_shortcode_data( true, $specific_user ) );
	}

	/**
	 * Localize the saved collections feature.
	 *
	 * @since	5.9.0
	 */
	public static function localize_saved_collection( $collection ) {
		if ( false !== $collection ) {
			// Optionally force servings, if GET parameter is set.
			$force_servings = false;
			if ( WPRM_Settings::get( 'adjustable_servings_url' ) ) {
				$param = trim( WPRM_Settings::get( 'adjustable_servings_url_param' ) );
				$param = $param ? $param : 'servings';
	
				if ( isset( $_GET[ $param ] ) ) {
					$param_servings = floatval( $_GET[ $param ] );
					
					if ( $param_servings ) {
						$force_servings = $param_servings;
					}
				}
			}

			// Localize required JS data.
			WPRM_Assets::add_js_data( 'wprmprc_public', self::localize_shortcode_data( false ) );
			WPRM_Assets::add_js_data( 'wprmprc_public_collection_' . $collection->id(), $collection->get_data( $force_servings ) );
		}
	}

	/**
	 * Custom CSS from settings.
	 *
	 * @since	4.1.0
	 */
	public static function custom_css() {
		if ( self::$load_assets ) {
			$css = '';

			// Classic layout.
			if ( 'classic' === WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) {
				// Font and column size.
				$css .= '.wprm-recipe-collections-layout-classic { font-size: ' . WPRM_Settings::get( 'recipe_collections_appearance_font_size' ) . 'px; }';
				$css .= '.wprm-recipe-collections-layout-classic .wprmprc-collection-column-width { flex: 1; flex-basis: ' . WPRM_Settings::get( 'recipe_collections_appearance_column_size' ) . 'px; }';

				$header_color = WPRM_Settings::get( 'recipe_collections_header_color' );
				if ( $header_color !== WPRM_Settings::get_default( 'recipe_collections_header_color' ) ) {
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-overview, .wprm-recipe-collections-layout-classic .wprmprc-collection .wprmprc-collection-column { border-color: ' . $header_color  . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-overview-header, .wprm-recipe-collections-layout-classic .wprmprc-collection .wprmprc-collection-column-header { background-color: ' . $header_color  . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-collection, .wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-list { border-color: ' . $header_color  . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-collection-header, .wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-list-header { background-color: ' . $header_color  . '; }';
				}

				$header_text_color = WPRM_Settings::get( 'recipe_collections_header_text_color' );
				if ( $header_text_color !== WPRM_Settings::get_default( 'recipe_collections_header_text_color' ) ) {
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-overview-header, .wprm-recipe-collections-layout-classic .wprmprc-collection .wprmprc-collection-column-header { color: ' . $header_text_color  . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-collection-header, .wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-list-header { color: ' . $header_text_color  . '; }';
				}

				$button_color = WPRM_Settings::get( 'recipe_collections_button_color' );
				if ( $button_color !== WPRM_Settings::get_default( 'recipe_collections_button_color' ) ) {
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-collection-action { background-color: ' . $button_color  . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-actions .wprmprc-shopping-list-action { background-color: ' . $button_color  . '; }';
				}

				$button_text_color = WPRM_Settings::get( 'recipe_collections_button_text_color' );
				if ( $button_text_color !== WPRM_Settings::get_default( 'recipe_collections_button_text_color' ) ) {
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-collection-action { color: ' . $button_text_color  . '; border-color: ' . $button_text_color . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-collection-action:hover { color: ' . $button_color  . '; background-color: ' . $button_text_color . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-actions .wprmprc-shopping-list-action { color: ' . $button_text_color  . '; border-color: ' . $button_text_color . '; }';
					$css .= '.wprm-recipe-collections-layout-classic .wprmprc-shopping-list .wprmprc-shopping-list-actions .wprmprc-shopping-list-action:not(.wprmprc-shopping-list-action-disabled):hover { color: ' . $button_color  . '; background-color: ' . $button_text_color . '; }';
				}	
			}

			// Grid layout.
			// Font and column size.
			if ( 'grid' === WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) {
				$css .= ' body { --wprm-collections-font-size: ' . intval( WPRM_Settings::get( 'recipe_collections_appearance_font_size_grid' ) ) . 'px; }';
				$css .= ' body { --wprm-collections-column-size: ' . intval( WPRM_Settings::get( 'recipe_collections_appearance_column_size_grid' ) ) . 'px; }';
				$css .= ' body { --wprm-collections-base-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_base_color' ) ) . '; }';
				$css .= ' body { --wprm-collections-header-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_header_color' ) ) . '; }';
				$css .= ' body { --wprm-collections-header-border: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_header_border' ) ) . '; }';
				$css .= ' body { --wprm-collections-item-background: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_item_background' ) ) . '; }';
				$css .= ' body { --wprm-collections-item-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_item_color' ) ) . '; }';
				$css .= ' body { --wprm-collections-item-border: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_item_border' ) ) . '; }';
				$css .= ' body { --wprm-collections-item-image-size: ' . intval( WPRM_Settings::get( 'recipe_collections_grid_item_image_size' ) ) . 'px; }';
				$css .= ' body { --wprm-collections-servings-background: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_servings_background' ) ) . '; }';
				$css .= ' body { --wprm-collections-servings-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_servings_color' ) ) . '; }';
				$css .= ' body { --wprm-collections-nutrition-background: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_nutrition_background' ) ) . '; }';
				$css .= ' body { --wprm-collections-nutrition-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_nutrition_color' ) ) . '; }';
				$css .= ' body { --wprm-collections-nutrition-border: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_nutrition_border' ) ) . '; }';
				$css .= ' body { --wprm-collections-action-color: ' . esc_attr( WPRM_Settings::get( 'recipe_collections_grid_action_color' ) ) . '; }';
			}

			if ( $css ) {
				echo '<style type="text/css">' . $css . '</style>';
			}
		}
	}

	/**
	 * Custom CSS from settings on admin page.
	 *
	 * @since	4.1.0
	 */
	public static function custom_css_admin() {
		$screen = get_current_screen();
		
		if ( 'admin_page_wprm_recipe_collections' === $screen->id ) {
			self::$load_assets = true;
			self::custom_css();
		}
	}
}

WPRMPRC_Assets::init();
