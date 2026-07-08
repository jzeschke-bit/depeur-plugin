<?php
/**
 * Layout for the Recipe Submission form.
 *
 * @link       https://bootstrapped.ventures
 * @since      2.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 */

/**
 * Layout for the Recipe Submission form.
 *
 * @since      2.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-submission
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-submission/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRS_Layout {

	/**
	 * Register actions and filters.
	 *
	 * @since    2.1.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ), 11 );

		add_action( 'wp_ajax_wprmprs_save_layout', array( __CLASS__, 'ajax_save_layout' ) );
	}

	/**
	 * Enqueue stylesheets and scripts.
	 *
	 * @since    2.1.0
	 */
	public static function enqueue_admin() {
		$screen = get_current_screen();
		
		if ( 'admin_page_wprmprs_layout' === $screen->id ) {
			wp_localize_script( 'wprmp-admin', 'wprmprs_layout', array(
				'blocks' => self::get_blocks(),
				'blocks_default' => self::get_blocks( true ),
				'defaults' => self::get_block_defaults(),
			) );
		}
	}

	/**
	 * Add the layout page.
	 *
	 * @since	2.1.0
	 */
	public static function add_submenu_page() {
		add_submenu_page( '', __( 'Recipe Submission Layout', 'wp-recipe-maker-premium' ), __( 'Recipe Submission Layout', 'wp-recipe-maker-premium' ), 'manage_options', 'wprmprs_layout', array( __CLASS__, 'layout_page_template' ) );
	}

	/**
	 * Get the template for the layout page.
	 *
	 * @since	2.1.0
	 */
	public static function layout_page_template() {
		echo '<div id="wprmprs-layout" class="wrap">Loading...</div>';
	}

	/**
	 * Save the user submission form layout through AJAX.
	 *
	 * @since    2.1.0
	 */
	public static function ajax_save_layout() {
		if ( check_ajax_referer( 'wprm', 'security', false ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$blocks = isset( $_POST['blocks'] ) ? json_decode( wp_unslash( $_POST['blocks'] ) ) : array(); // Input var okay.

				// Save blocks as associative array.
				$blocks = json_decode( json_encode( $blocks ), true );

				// Reset block keys.
				$blocks_to_save = array();
				foreach ( $blocks as $index => $block ) {
					$block['key'] = $index;
					$blocks_to_save[] = $block;
				}

				update_option( 'wprmprs_layout', $blocks_to_save, false );
				wp_send_json_success();
			}
		}

		wp_die();
	}

	/**
	 * Get layout for the blocks.
	 *
	 * @since	3.0.0
	 * @param boolean $default Whether or not to get the default layout.
	 */
	public static function get_layout( $default = false ) {
		$layout = self::get_default_layout();

		if ( ! $default ) {
			$saved_layout = get_option( 'wprmprs_layout', false );

			// Backwords compatibility.
			if ( ! $saved_layout ) {
				$saved_layout = WPRM_Settings::get( 'recipe_submission_blocks' );
			}

			if ( $saved_layout ) {
				$layout = $saved_layout;
			}
		}

		return $layout;
	}

	/**
	 * Save current layout as default.
	 *
	 * @since	2.1.0
	 */
	public static function get_default_layout() {
		// Use JSON.stringify(wprmprs_layout.blocks) in JS console to get code for new default layout (first save and reload page).
		$default_layout = '[{"name":"Header","text":"Your Details","tag":"h3","key":0,"type":"header"},{"name":"User Name","label":"Name","help":"","placeholder":"","required":true,"key":1,"type":"user_name"},{"name":"User Email","label":"Email","help":"","placeholder":"","required":true,"key":2,"type":"user_email"},{"name":"Header","text":"Recipe Essentials","tag":"h3","key":3,"type":"header"},{"name":"Recipe Image","label":"Image","help":"","placeholder":"Drop an image","required":false,"key":4,"type":"recipe_image"},{"name":"Recipe Name","label":"Name","help":"","placeholder":"","required":true,"key":5,"type":"recipe_name"},{"name":"Recipe Summary","label":"Summary","help":"","placeholder":"","required":false,"key":6,"type":"recipe_summary"},{"name":"Recipe Ingredients","label":"Ingredients","help":"One ingredient per line","placeholder":"","required":true,"key":7,"type":"recipe_ingredients"},{"name":"Recipe Instructions","label":"Instructions","help":"One instruction per line","placeholder":"","required":true,"key":8,"type":"recipe_instructions"},{"name":"Header","text":"Recipe Details","tag":"h3","key":9,"type":"header"},{"name":"Recipe Servings","label":"Servings","help":"","placeholder":"4 people","required":false,"key":10,"type":"recipe_servings"},{"name":"Recipe Prep Time","label":"Prep Time","help":"","placeholder":"20 minutes","required":false,"key":11,"type":"recipe_prep_time"},{"name":"Recipe Cook Time","label":"Cook Time","help":"","placeholder":"10 minutes","required":false,"key":12,"type":"recipe_cook_time"},{"name":"Recipe Total Time","label":"Total Time","help":"","placeholder":"30 minutes","required":false,"key":13,"type":"recipe_total_time"},{"name":"Header","text":"Recipe Tags","tag":"h3","key":14,"type":"header"},{"name":"Paragraph","text":"Separate multiple tags with a comma.\nFor example: Italian, American","key":15,"type":"paragraph"},{"field":"course","name":"Recipe Custom Taxonomy","label":"Courses","help":"","placeholder":"","required":false,"key":16,"type":"recipe_custom_taxonomy"},{"field":"cuisine","name":"Recipe Custom Taxonomy","label":"Cuisines","help":"","placeholder":"","required":false,"key":17,"type":"recipe_custom_taxonomy"},{"name":"Submit","text":"Submit Recipe","key":18,"type":"submit"}]';
		$blocks = json_decode( $default_layout, true );

		return $blocks;
	}

	/**
	 * Get the blocks for the User Submissions form.
	 *
	 * @since	2.1.0
	 * @param boolean $default Whether or not to get the default layout.
	 */
	public static function get_blocks( $default = false ) {
		$blocks = self::get_layout( $default );
		$defaults = WPRMPRS_Layout::get_block_defaults();

		$blocks_with_defaults = array();

		foreach ( $blocks as $block ) {
			$type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : false;

			if ( $type && array_key_exists( $type, $defaults ) ) {
				$block = array_merge(
					$defaults[ $type ],
					$block
				);

				$blocks_with_defaults[] = $block;
			}
		}

		return $blocks_with_defaults;
	}

	/**
	 * Get the default blocks for the User Submissions form.
	 *
	 * @since	2.1.0
	 */
	public static function get_block_defaults() {
		$blocks = array(
			'header' => array(
				'name' => __( 'Header', 'wp-recipe-maker-premium' ),
				'text' => __( 'My Header', 'wp-recipe-maker-premium' ),
				'tag' => 'h2',
			),
			'paragraph' => array(
				'name' => __( 'Paragraph', 'wp-recipe-maker-premium' ),
				'text' => __( 'My paragraph...', 'wp-recipe-maker-premium' ),
			),
			'html' => array(
				'name' => __( 'HTML Code', 'wp-recipe-maker-premium' ),
				'text' => '<p>Use <em>any</em> HTML code to style your form as needed. Take note that <strong>you</strong> are responsible for having the correct HTML structure.</p>',
			),
			'agree_to_terms' => array(
				'name' => __( 'Agree to Terms', 'wp-recipe-maker-premium' ),
				'text' => 'I agree to <a href="#">the terms</a>',
			),
			'submit' => array(
				'name' => __( 'Submit', 'wp-recipe-maker-premium' ),
				'text' => __( 'Submit Recipe', 'wp-recipe-maker-premium' ),
			),
			'recipe_name' => array(
				'name' => __( 'Recipe Name', 'wp-recipe-maker-premium' ),
				'label' => __( 'Name', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => true,
			),
			'recipe_summary' => array(
				'name' => __( 'Recipe Summary', 'wp-recipe-maker-premium' ),
				'label' => __( 'Summary', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_image' => array(
				'name' => __( 'Recipe Image', 'wp-recipe-maker-premium' ),
				'label' => __( 'Image', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => 'Drop an image',
			),
			'recipe_video_upload' => array(
				'name' => __( 'Recipe Video Upload', 'wp-recipe-maker-premium' ),
				'label' => __( 'Video', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
			),
			'recipe_video_embed' => array(
				'name' => __( 'Recipe Video Embed', 'wp-recipe-maker-premium' ),
				'label' => __( 'Video', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => 'https://www.youtube.com/watch?v=lbt01DL03DU',
			),
			'recipe_servings' => array(
				'name' => __( 'Recipe Servings', 'wp-recipe-maker-premium' ),
				'label' => __( 'Recipe Servings', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '4 people',
				'required' => false,
			),
			'recipe_prep_time' => array(
				'name' => __( 'Recipe Prep Time', 'wp-recipe-maker-premium' ),
				'label' => __( 'Prep Time', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '20 minutes',
				'required' => false,
			),
			'recipe_cook_time' => array(
				'name' => __( 'Recipe Cook Time', 'wp-recipe-maker-premium' ),
				'label' => __( 'Cook Time', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '10 minutes',
				'required' => false,
			),
			'recipe_total_time' => array(
				'name' => __( 'Recipe Total Time', 'wp-recipe-maker-premium' ),
				'label' => __( 'Total Time', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '30 minutes',
				'required' => false,
			),
			'recipe_cost' => array(
				'name' => __( 'Recipe Cost', 'wp-recipe-maker-premium' ),
				'label' => __( 'Estimated Cost', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '$5',
				'required' => false,
			),
			'recipe_courses' => array(
				'name' => __( 'Recipe Courses', 'wp-recipe-maker-premium' ),
				'label' => __( 'Courses', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_cuisines' => array(
				'name' => __( 'Recipe Cuisines', 'wp-recipe-maker-premium' ),
				'label' => __( 'Cuisines', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_equipment' => array(
				'name' => __( 'Recipe Equipment', 'wp-recipe-maker-premium' ),
				'label' => __( 'Equipment', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_ingredients' => array(
				'name' => __( 'Recipe Ingredients', 'wp-recipe-maker-premium' ),
				'label' => __( 'Ingredients', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => true,
			),
			'recipe_instructions' => array(
				'name' => __( 'Recipe Instructions', 'wp-recipe-maker-premium' ),
				'label' => __( 'Instructions', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => true,
			),
			'recipe_notes' => array(
				'name' => __( 'Recipe Notes', 'wp-recipe-maker-premium' ),
				'label' => __( 'Notes', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_custom_taxonomy' => array(
				'field' => '',
				'name' => __( 'Recipe Custom Taxonomy', 'wp-recipe-maker-premium' ),
				'label' => __( 'Custom Taxonomy', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'recipe_custom_field' => array(
				'field' => '',
				'name' => __( 'Recipe Custom Field', 'wp-recipe-maker-premium' ),
				'label' => __( 'Custom Field', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => false,
			),
			'user_name' => array(
				'name' => __( 'User Name', 'wp-recipe-maker-premium' ),
				'label' => __( 'Name', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => true,
			),
			'user_email' => array(
				'name' => __( 'User Email', 'wp-recipe-maker-premium' ),
				'label' => __( 'Email', 'wp-recipe-maker-premium' ),
				'help' => '',
				'placeholder' => '',
				'required' => true,
			),
		);

		return $blocks;
	}
}

WPRMPRS_Layout::init();
