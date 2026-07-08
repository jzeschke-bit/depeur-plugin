<?php
/**
 * Handle the Premium download PDF shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium download PDF shortcode.
 *
 * @since      10.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Download_PDF {
	public static function init() {
		add_filter( 'wprm_recipe_download_pdf_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Download PDF shortcode.
	 *
	 * @since	10.6.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! WPRM_Settings::get( 'pdf_download_enabled' ) ) {
			if ( $atts['is_template_editor_preview'] ) {
				return '<div class="wprm-template-editor-premium-only">Make sure to enable the PDF Download feature in the settings first.</div>';
			}
			return '';
		}

		if ( ! $recipe || ! $recipe->id() ) {
			return '';
		}

		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-download-pdf-icon">' . $icon . '</span> ';
			}
		}

		// Output.
		$classes = array(
			'wprm-recipe-download-pdf',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-download-pdf-' . $atts['style'];
			$classes[] = 'wprm-recipe-link-' . $atts['style'];
			$classes[] = 'wprm-color-accent';

			$style .= 'background-color: ' . $atts['button_color'] . ';';
			$style .= 'border-color: ' . $atts['border_color'] . ';';
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
			$style .= 'padding: ' . $atts['vertical_padding'] . ' ' . $atts['horizontal_padding'] . ';';
		}

		// Backwards compatibility.
		if ( 'legacy' === WPRM_Settings::get( 'recipe_template_mode' ) ) {
			$style = '';
		}

		// Optional PDF template.
		$template = sanitize_key( $atts['template'] );
		if ( ! $template ) {
			$pdf_template = WPRM_Template_Manager::get_template_by_type( 'pdf', $recipe->type() );

			if ( ! $pdf_template ) {
				$pdf_template = WPRM_Template_Manager::get_template_by_type( 'print', $recipe->type() );
			}

			if ( $pdf_template && isset( $pdf_template['slug'] ) ) {
				$template = sanitize_key( $pdf_template['slug'] );
			}
		}

		// Open links in new tab?
		$target = '';
		if ( WPRM_Settings::get( 'print_new_tab' ) ) {
			$target = ' target="_blank"';
		}

		// Text and optional aria-label.
		$text = WPRM_i18n::maybe_translate( $atts['text'] );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . __( 'Download PDF', 'wp-recipe-maker' ) . '"';
		}

		return '<a href="' . esc_attr( $recipe->print_url( $template ) ) . '" style="' . esc_attr( $style ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-recipe-id="' . esc_attr( $recipe->id() ) . '" data-template="' . esc_attr( $template ) . '" data-print-action="pdf"' . $target . ' rel="nofollow"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';
	}
}

WPRMP_SC_Download_PDF::init();
