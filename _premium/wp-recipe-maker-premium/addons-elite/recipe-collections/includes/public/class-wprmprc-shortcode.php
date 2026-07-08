<?php
/**
 * Handle the Recipe Collections shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections shortcode.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Shortcode {

	/**
	 * Register actions and filters.
	 *
	 * @since    4.1.0
	 */
	public static function init() {
		add_shortcode( 'wprm-recipe-collections', array( __CLASS__, 'recipe_collections_shortcode' ) );
		add_shortcode( 'wprm-saved-collection', array( __CLASS__, 'saved_collection_shortcode' ) );
		add_shortcode( 'wprm-shopping-list', array( __CLASS__, 'shopping_list_shortcode' ) );
	}

	/**
	 * Output for the Recipe Collections shortcode.
	 *
	 * @since	4.1.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	public static function recipe_collections_shortcode( $atts ) {
		$atts = shortcode_atts( array(), $atts, 'wprm_recipe_collections' );

		// Display no access message if user is not logged in.
		if ( 'logged_in' === WPRM_Settings::get( 'recipe_collections_access' ) && ! is_user_logged_in() ) {
			$message = false;

			if ( WPRM_Settings::get( 'recipe_collections_no_access_message_use_html' ) ) {
				$message = WPRM_Settings::get( 'recipe_collections_no_access_message_html' );
			} else {
				$message = WPRM_Settings::get( 'recipe_collections_no_access_message' );
			}

			if ( $message ) {
				$message = '<div class="wprm-recipe-collections-no-access">' . $message . '</div>';
			}

			return $message;
		}

		// Check if editing specific user.
		$specific_user = false;
		if ( isset( $_GET['wprmprc_user'] ) && current_user_can( 'edit_others_posts' ) ) {
			$specific_user = intval( $_GET['wprmprc_user'] );
		}

		WPRMPRC_Assets::load();
		WPRMPRC_Assets::localize_collections( $specific_user );

		// Check if we should add a saved collection.
		if ( isset( $_GET['save'] ) ) {
			require_once( WPRM_DIR . 'vendor/hashids/lib/Hashids/HashGenerator.php' );
			require_once( WPRM_DIR . 'vendor/hashids/lib/Hashids/Hashids.php' );
			$hashids = new Hashids\Hashids('wp-recipe-maker');

			$decoded = $hashids->decode( $_GET['save'] );

			if ( $decoded && $decoded[0] ) {
				$collection_id = intval( $decoded[0] );
				$collection = WPRMPRC_Manager::get_collection( $collection_id );

				if ( $collection ) {
					WPRM_Assets::add_js_data( 'wprmprc_public_collection_save', $collection->get_data() );
				}
			}
		}

		// Check if we should add a recipe to a collection.
		if ( isset( $_GET['add'] ) ) {
			$recipe_id = intval( $_GET['add'] );
			$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
			
			if ( $recipe ) {
				WPRM_Assets::add_js_data( 'wprmprc_public_collection_save_recipe', array(
					'data' => WPRMPRC_Manager::get_collections_data_for_recipe( $recipe ),
				) );
			}
		}

		$data = '';
		if ( 'grid' === WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) {
			if ( 'modal' === WPRM_Settings::get( 'recipe_collections_appearance_adding_layout' ) || 'modal' === WPRM_Settings::get( 'recipe_collections_appearance_structure_layout' ) ) {
				$modal_uid = WPRM_Popup::add(
					array(
						'type' => 'collections',
						'container' => '<div class="wprm-recipe-collections-modal"></div>',
					)
				);
				$data = ' data-modal-uid="' . esc_attr( $modal_uid ) . '"';
			}
		}

		// Make sure user ratings modal is added.
		WPRMP_User_Rating::get_modal_uid();

		$loading_message = self::get_loading_message( 'recipe_collections_loading_message' );

		return '<div id="wprm-recipe-collections-app" class="wprm-recipe-collections-layout-' . esc_attr( WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) . '"' . $data . '>' . $loading_message . '</div>';
	}

	/**
	 * Output for the saved collection shortcode.
	 *
	 * @since	4.1.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	public static function saved_collection_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'wprm_saved_collection' );

		$id = intval( $atts['id'] );

		if ( $id ) {
			$collection = WPRMPRC_Manager::get_collection( $id );

			if ( $collection ) {
				WPRMPRC_Assets::load();
				WPRMPRC_Assets::localize_saved_collection( $collection );

				// Make sure user ratings modal is added.
				WPRMP_User_Rating::get_modal_uid();

				$loading_message = self::get_loading_message( 'saved_recipe_collection_loading_message', $collection );

				return '<div class="wprm-recipe-saved-collections-app wprm-recipe-collections-layout-' . esc_attr( WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) . '" data-id="' . esc_attr( $id ) . '">' . $loading_message . '</div>';
			}
		}

		return '';
	}

	/**
	 * Output for the shopping list shortcode.
	 *
	 * @since	8.3.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	public static function shopping_list_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'wprm_shopping_list' );

		$id = intval( $atts['id'] );
		$collection = false;

		WPRMPRC_Assets::load();

		if ( $id ) {
			$collection = WPRMPRC_Manager::get_collection( $id );

			if ( $collection ) {
				WPRMPRC_Assets::localize_saved_collection( $collection );
			} else {
				return '';
			}
		} else {
			$id = 'temp';

			WPRMPRC_Assets::localize_collections( false );
		}

		$loading_message = self::get_loading_message( 'quick_access_shopping_list_loading_message' );

		return '<div class="wprm-shopping-list-app wprm-recipe-collections-layout-' . esc_attr( WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) . '" data-id="' . esc_attr( $id ) . '">' . $loading_message . '</div>';
	}

	public static function get_loading_message( $setting, $collection = false ) {
		$message = WPRM_Settings::get( $setting );
 
		if ( $message ) {
			$collection_name = $collection ? $collection->name() : '';

			$message = str_replace( '%saved_collection_name%', $collection_name, $message );
			$message = str_replace( '%loader%', '<span class="wprm-loader"></span>', $message );

			$message = do_shortcode( $message );
		}

		return $message;
	}
}

WPRMPRC_Shortcode::init();
