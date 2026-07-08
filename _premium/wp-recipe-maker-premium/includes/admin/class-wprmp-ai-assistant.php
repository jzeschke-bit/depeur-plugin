<?php
/**
 * Premium integrations for the AI Assistant admin page.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.4.1
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 */

/**
 * Premium integrations for the AI Assistant admin page.
 *
 * @since      10.4.1
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_AI_Assistant {
	/**
	 * Register actions and filters.
	 *
	 * @since    10.4.1
	 */
	public static function init() {
		add_filter( 'wprm_ai_assistant_tools', array( __CLASS__, 'filter_tools' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_tool_assets' ) );
	}

	/**
	 * Enqueue assets for AI tool sub-pages.
	 *
	 * @since    10.5.0
	 */
	public static function enqueue_tool_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( ! $screen || 'wp-recipe-maker_page_wprm_ai_assistant' !== $screen->id ) {
			return;
		}

		if ( 'Elite' !== WPRMP_BUNDLE ) {
			return;
		}

		$tool = WPRM_AI_Assistant::get_current_tool();
		if ( ! $tool ) {
			return;
		}

		wp_enqueue_style( 'wprm-admin-ai-assistant', WPRMP_URL . 'dist/admin-ai-assistant.css', array(), WPRMP_VERSION, 'all' );
		wp_enqueue_script( 'wprm-admin-ai-assistant', WPRMP_URL . 'dist/admin-ai-assistant.js', array( 'wprm-shared', 'wprm-admin-modal' ), WPRMP_VERSION, true );
	}

	/**
	 * Update AI Assistant tools for Premium bundles.
	 *
	 * @since    10.4.1
	 *
	 * @param    array $tools Existing AI Assistant tools.
	 *
	 * @return   array
	 */
	public static function filter_tools( $tools ) {
		if ( 'Elite' !== WPRMP_BUNDLE ) {
			return $tools;
		}

		if ( isset( $tools['suggest_tags'] ) ) {
				$tools['suggest_tags']['button'] = array(
					'label' => __( 'Suggest Tags', 'wp-recipe-maker' ),
					'url' => admin_url( 'admin.php?page=wprm_manage' ),
					'tooltip' => __( 'Suggesting tags is available when editing a recipe on the Manage page.', 'wp-recipe-maker' ),
					'class' => 'button button-primary button-compact wprm-button-ai wprm-ai-assistant-tool-button',
					'new_tab' => false,
				);
			}

		if ( isset( $tools['import_with_ai'] ) ) {
			$tools['import_with_ai']['button'] = array(
				'label' => __( 'Import with AI', 'wp-recipe-maker' ),
				'url' => admin_url( 'admin.php?page=wprm_ai_assistant&tool=import_with_ai' ),
				'class' => 'button button-primary button-compact wprm-button-ai wprm-ai-assistant-tool-button',
				'new_tab' => false,
			);
		}

		if ( isset( $tools['generate_ideas'] ) ) {
			$tools['generate_ideas']['button'] = array(
				'label' => __( 'Generate Ideas', 'wp-recipe-maker' ),
				'url' => admin_url( 'admin.php?page=wprm_ai_assistant&tool=generate_ideas' ),
				'class' => 'button button-primary button-compact wprm-button-ai wprm-ai-assistant-tool-button',
				'new_tab' => false,
			);
		}

		if ( isset( $tools['nutrition_review'] ) ) {
			$tools['nutrition_review']['button'] = array(
				'label' => __( 'Nutrition Review', 'wp-recipe-maker' ),
				'url' => admin_url( 'admin.php?page=wprm_ai_assistant&tool=nutrition_review' ),
				'class' => 'button button-primary button-compact wprm-button-ai wprm-ai-assistant-tool-button',
				'new_tab' => false,
			);
		}

		if ( isset( $tools['unit_conversion_review'] ) ) {
			$tools['unit_conversion_review']['button'] = array(
				'label' => __( 'Unit Conversion Review', 'wp-recipe-maker' ),
				'url' => admin_url( 'admin.php?page=wprm_ai_assistant&tool=unit_conversion_review' ),
				'class' => 'button button-primary button-compact wprm-button-ai wprm-ai-assistant-tool-button',
				'new_tab' => false,
			);
		}

		return $tools;
	}
}

WPRMP_AI_Assistant::init();
