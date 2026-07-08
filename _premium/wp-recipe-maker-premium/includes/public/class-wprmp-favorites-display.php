<?php
/**
 * Display favorites shortcodes and blocks.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Display favorites shortcodes and blocks.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Favorites_Display {

	/**
	 * Register actions and filters.
	 *
	 * @since    10.6.0
	 */
	public static function init() {
		add_shortcode( 'wprm-favorite-recipes', array( __CLASS__, 'favorite_recipes_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register blocks.
	 *
	 * @since    10.6.0
	 */
	public static function register_blocks() {
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type(
				'wp-recipe-maker/favorite-recipes',
				array(
					'attributes' => array(),
					'render_callback' => array( __CLASS__, 'render_favorite_recipes_block' ),
				)
			);
		}
	}

	/**
	 * Render the favorite recipes block.
	 *
	 * @since    10.6.0
	 * @param    mixed $atts Block attributes.
	 */
	public static function render_favorite_recipes_block( $atts ) {
		if ( WPRM_Context::is_gutenberg_preview() ) {
			$output = '<h4 style="margin: 0;">' . esc_html__( 'Favorite Recipes', 'wp-recipe-maker-premium' ) . '</h4>';
			$output .= '<p style="margin: 10px 0;">' . esc_html__( 'Your visitors will see their favorite recipes here on the actual site. This is just a placeholder.', 'wp-recipe-maker-premium' ) . '</p>';

			return $output;
		}

		return self::favorite_recipes_shortcode( $atts );
	}

	/**
	 * Output for the favorite recipes shortcode.
	 *
	 * @since    10.6.0
	 * @param    array $atts Options passed along with the shortcode.
	 */
	public static function favorite_recipes_shortcode( $atts ) {
		WPRM_Assets::load();

		$loading_message = self::get_message( 'favorite_recipes_loading_message', '%loader%' );

		return '<div class="wprm-favorite-recipes-container">' . $loading_message . '</div>';
	}

	/**
	 * Render favorite recipe templates for a list of recipe IDs.
	 *
	 * @since    10.6.0
	 * @param    array $favorites Recipe IDs to render.
	 */
	public static function get_favorite_recipes_html( $favorites ) {
		$favorites = WPRMP_Favorites::sanitize_favorites( $favorites );

		if ( ! count( $favorites ) ) {
			return self::get_message( 'favorite_recipes_empty_message' );
		}

		$styles = array();
		$recipes_html = '';

		foreach ( $favorites as $favorite_id ) {
			$recipe = WPRM_Recipe_Manager::get_recipe( $favorite_id );

			if ( ! $recipe ) {
				continue;
			}

			$template = WPRM_Template_Manager::get_template_by_type( 'favorites-list', $recipe->type() );
			if ( $template && ! isset( $styles[ $template['slug'] ] ) ) {
				$styles[ $template['slug'] ] = WPRM_Template_Manager::get_template_css( $template );
			}

			$recipes_html .= '<div id="wprm-recipe-container-' . esc_attr( $recipe->id() ) . '" class="wprm-recipe-container wprm-favorite-recipes-item" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-servings="' . esc_attr( $recipe->servings() ) . '">';
			$recipes_html .= WPRM_Template_Manager::get_template( $recipe, 'favorites-list' );
			$recipes_html .= '</div>';
		}

		if ( ! $recipes_html ) {
			return self::get_message( 'favorite_recipes_empty_message' );
		}

		$output = '';

		foreach ( $styles as $style ) {
			if ( $style ) {
				$output .= '<style type="text/css">' . $style . '</style>';
			}
		}

		$output .= '<div class="wprm-favorite-recipes-list">' . $recipes_html . '</div>';

		return $output;
	}

	/**
	 * Get configurable loading or empty state message.
	 *
	 * @since    10.6.0
	 * @param    string $setting     Setting to load.
	 * @param    string $placeholder Placeholder replacements.
	 */
	private static function get_message( $setting, $placeholder = '' ) {
		$message = WPRM_Settings::get( $setting );

		if ( ! $message ) {
			if ( 'favorite_recipes_empty_message' === $setting ) {
				$message = __( 'You have no favorite recipes yet.', 'wp-recipe-maker-premium' );
			} else {
				$message = '%loader%';
			}
		}

		if ( false !== strpos( $message, '%loader%' ) ) {
			$message = str_replace( '%loader%', '<span class="wprm-loader"></span>', $message );
		} elseif ( $placeholder && 'favorite_recipes_loading_message' === $setting ) {
			$message .= str_replace( '%loader%', '<span class="wprm-loader"></span>', $placeholder );
		}

		return do_shortcode( $message );
	}
}

WPRMP_Favorites_Display::init();
