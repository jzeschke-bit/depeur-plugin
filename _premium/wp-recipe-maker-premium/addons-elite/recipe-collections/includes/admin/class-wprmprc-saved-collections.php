<?php
/**
 * Handle the saved collections page.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/admin
 */

/**
 * Handle the saved collections page.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Saved_Collections {

	/**
	 * Register actions and filters.
	 *
	 * @since    4.1.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 20 );
	}

	/**
	 * Add the edit saved collection page.
	 *
	 * @since	4.1.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'WPRM Recipe Collections', 'wp-recipe-maker' ), __( 'Recipe Collections', 'wp-recipe-maker' ), WPRM_Settings::get( 'features_manage_access' ), 'wprm_recipe_collections', array( __CLASS__, 'recipe_collections_page_template' ) );
	}

	/**
	 * Get the template for the edit saved collection page.
	 *
	 * @since	4.1.0
	 */
	public static function recipe_collections_page_template() {
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : false;
		$duplicate = isset( $_GET['action'] ) ? 'duplicate' === $_GET['action'] : false;

		if ( ! $id ) {
			$id = WPRMPRC_Manager::create_collection();
		}

		$collection = WPRMPRC_Manager::get_collection( $id );

		if ( ! $collection ) {
			wp_die( 'Something went wrong.' );
		}

		if ( $duplicate ) {
			$duplicate_id = WPRMPRC_Manager::create_collection( $collection->get_data() );
			$collection = WPRMPRC_Manager::get_collection( $duplicate_id );
		}

		wp_localize_script( 'wprmp-admin', 'wprm_public', WPRM_Assets::localize_public() );
		wp_localize_script( 'wprmp-admin', 'wprmp_public', WPRMPRC_Assets::localize_public_data( array( 'endpoints' => array() ) ) );
		wp_localize_script( 'wprmp-admin', 'wprmprc_public', WPRMPRC_Assets::localize_shortcode_data() );
		wp_localize_script( 'wprmp-admin', 'wprmprc_admin', array(
			'collection' => $collection->get_data(),
			'manage_url' => admin_url( 'admin.php?page=wprm_manage&sub=recipe_collections#/collections' ),
		) );

		// Maybe need to have modal for adding layout.
		$data = '';
		if ( 'grid' === WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) {
			if ( 'modal' === WPRM_Settings::get( 'recipe_collections_appearance_adding_layout' ) || 'modal' === WPRM_Settings::get( 'recipe_collections_appearance_structure_layout' )) {
				$modal_uid = WPRM_Popup::add(
					array(
						'type' => 'collections',
						'container' => '<div class="wprm-recipe-collections-modal"></div>',
					)
				);
				$data = ' data-modal-uid="' . esc_attr( $modal_uid ) . '"';
				WPRM_Popup::output_html_for_all_modals();
			}
		}

		// Make sure user ratings modal is added.
		WPRMP_User_Rating::get_modal_uid();

		echo '<div id="wprm-recipe-collections-manage-app" class="wrap wprm-recipe-collections-layout-' . esc_attr( WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ) . '"' . $data . '>Loading...</div>';
	}
}

WPRMPRC_Saved_Collections::init();
