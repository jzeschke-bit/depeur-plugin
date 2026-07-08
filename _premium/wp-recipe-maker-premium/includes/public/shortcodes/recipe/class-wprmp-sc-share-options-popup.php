<?php
/**
 * Handle the Premium share options popup shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 */

/**
 * Handle the Premium share options popup shortcode.
 *
 * @since      10.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/recipe
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Share_Options_Popup {
	public static function init() {
		add_filter( 'wprm_recipe_share_options_popup_shortcode', array( __CLASS__, 'shortcode' ), 10, 3 );
	}

	/**
	 * Private notes shortcode.
	 *
	 * @since	10.1.0
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 * @param	mixed $recipe Recipe the shortcode is getting output for.
	 */
	public static function shortcode( $output, $atts, $recipe ) {
		if ( ! $recipe ) {
			return $output;
		}

		// Check share options we need to output.
		$valid_options = array( 'pinterest', 'facebook', 'twitter', 'bluesky', 'mastodon', 'tumblr', 'messenger', 'whatsapp', 'text', 'email', 'line' );

		$share_options = $atts['share_options'];
		$share_options = str_replace( ' ', '', $share_options );
		$share_options = str_replace( ';', ',', $share_options );
		$share_options = explode( ',', $share_options );

		// Check if "others" placeholder is used.
		$has_others_placeholder = in_array( 'others', $share_options, true );
		
		if ( $has_others_placeholder ) {
			// Get manually specified options (excluding the placeholder).
			$manually_specified = array_filter( $share_options, function( $option ) {
				return 'others' !== $option;
			} );
			
			// Get all other options not manually specified (excluding 'line' as it's a separator).
			$share_only_options = array_diff( $valid_options, array( 'line' ) );
			$other_options = array_diff( $share_only_options, $manually_specified );
			
			// Replace the placeholder with all other options.
			$share_options = array_merge( $manually_specified, $other_options );
		}

		$share_options = array_filter( $share_options, function( $option ) use ( $valid_options ) {
			return in_array( $option, $valid_options, true );
		} );

		// If no share options, return.
		if ( empty( $share_options ) ) {
			return $output;
		}

		$output = '';

		// Start constructing the output for the popup.
		$popup_output = '';

		// Output share options.
		foreach ( $share_options as $option ) {
			// Handle line separator.
			if ( 'line' === $option ) {
				$popup_output .= '<div class="wprm-share-popup-line"></div>';
				continue;
			}

			// Construct own icon and text.
			$icon = '';
			if ( $atts[ 'icon_' . $option ] ) {
				$icon = WPRM_Icon::get( $atts[ 'icon_' . $option ], 'currentColor' );

				if ( $icon ) {
					$icon = '<span class="wprm-recipe-icon wprm-share-popup-icon">' . $icon . '</span> ';
				}
			}

			$text = WPRM_i18n::maybe_translate( $atts[ 'text_' . $option ] );
			$text = '<span class="wprm-share-popup-text">' . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</span>';

			// Get output of regular shortcode.
			$shortcode = '';

			switch ( $option ) {
				case 'pinterest':
					$shortcode = '[wprm-recipe-pin use_in_popup="1" action="' . esc_attr( $atts['pinterest_action'] ) . '"]';
					break;
				case 'facebook':
					$shortcode = '[wprm-recipe-facebook-share use_in_popup="1"]';
					break;
				case 'twitter':
					$shortcode = '[wprm-recipe-twitter-share use_in_popup="1" twitter_message_intro="' . esc_attr( $atts['twitter_message_intro'] ) . '"]';
					break;
				case 'bluesky':
					$shortcode = '[wprm-recipe-bluesky-share use_in_popup="1" bluesky_message_intro="' . esc_attr( $atts['bluesky_message_intro'] ) . '"]';
					break;
				case 'mastodon':
					$shortcode = '[wprm-recipe-mastodon-share use_in_popup="1" mastodon_message_intro="' . esc_attr( $atts['mastodon_message_intro'] ) . '"]';
					break;
				case 'tumblr':
					$shortcode = '[wprm-recipe-tumblr-share use_in_popup="1"]';
					break;
				case 'messenger':
					$shortcode = '[wprm-recipe-messenger-share use_in_popup="1"]';
					break;
				case 'whatsapp':
					$shortcode = '[wprm-recipe-whatsapp-share use_in_popup="1"]';
					break;
				case 'text':
					$shortcode = '[wprm-recipe-text-share use_in_popup="1" text_message_intro="' . esc_attr( $atts['text_message_intro'] ) . '" text_message_ingredients="' . esc_attr( $atts['text_message_ingredients'] ) . '"]';
					break;
				case 'email':
					$shortcode = '[wprm-recipe-email-share use_in_popup="1" email_message_subject="' . esc_attr( $atts['email_message_subject'] ) . '" email_message_intro="' . esc_attr( $atts['email_message_intro'] ) . '" email_message_ingredients="' . esc_attr( $atts['email_message_ingredients'] ) . '"]';
					break;
			}

			// Get shortcode output and replace placeholder.
			$shortcode_output = do_shortcode( $shortcode );

			// Continue if no output or %wprm_share_placeholder% not found.
			if ( ! $shortcode_output || false === strpos( $shortcode_output, '%wprm_share_placeholder%' ) ) {
				continue;
			}

			// Replace placholder with icon and text.
			$popup_output .= str_replace( '%wprm_share_placeholder%', $icon . $text, $shortcode_output );
		}

		// Popup styling.
		$css_variables = '';
		if ( '#ffffff' !== $atts['popup_icon_color'] ) {
			$css_variables .= '--wprm-share-popup-icon-color: ' . $atts['popup_icon_color'] . ';';
		}
		if ( '#ffffff' !== $atts['popup_icon_hover_color'] ) {
			$css_variables .= '--wprm-share-popup-icon-hover-color: ' . $atts['popup_icon_hover_color'] . ';';
		}
		if ( '#ffffff' !== $atts['popup_text_color'] ) {
			$css_variables .= '--wprm-share-popup-text-color: ' . $atts['popup_text_color'] . ';';
		}
		if ( '#ffffff' !== $atts['popup_text_hover_color'] ) {
			$css_variables .= '--wprm-share-popup-text-hover-color: ' . $atts['popup_text_hover_color'] . ';';
		}
		if ( false !== (bool) $atts['underline'] ) {
			$css_variables .= '--wprm-share-popup-text-decoration: underline;';
		}
		if ( true !== (bool) $atts['underline_on_hover'] ) {
			$css_variables .= '--wprm-share-popup-text-hover-decoration: none;';
		}
		if ( 'flex-start' !== $atts['popup_align'] ) {
			$css_variables .= '--wprm-share-popup-align: ' . $atts['popup_align'] . ';';
		}
		$style = WPRM_Shortcode_Helper::get_inline_style( $css_variables );

		// Maybe set different background color.
		$background_color = '';
		if ( '#333333' !== $atts['popup_background'] ) {
			$background_color = ' data-color="' . esc_attr( $atts['popup_background'] ) . '"';
		}

		// Output conrtent for the popup.
		$output .= '<div class="wprm-recipe-share-options-popup-container"' . $background_color . '>';
		$output .= '<div class="wprm-recipe-share-options-popup-options"' . $style . '>';
		$output .= $popup_output;
		$output .= '</div>';
		$output .= '</div>';

		// Outputting the actual share button.
		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-recipe-share-options-popup-icon">' . $icon . '</span> ';
			}
		}

		// Output.
		$classes = array(
			'wprm-recipe-share-options-popup',
			'wprm-recipe-link',
			'wprm-block-text-' . $atts['text_style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		$style = 'color: ' . $atts['text_color'] . ';';
		if ( 'text' !== $atts['style'] ) {
			$classes[] = 'wprm-recipe-share-options-popup-' . $atts['style'];
			$classes[] = 'wprm-recipe-link-' . $atts['style'];
			$classes[] = 'wprm-color-accent';

			$style .= 'background-color: ' . $atts['button_color'] . ';';
			$style .= 'border-color: ' . $atts['border_color'] . ';';
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
			$style .= 'padding: ' . $atts['vertical_padding'] . ' ' . $atts['horizontal_padding'] . ';';
		}

		// Text and optional aria-label.
		$text = WPRM_i18n::maybe_translate( $atts['text'] );

		$aria_label = '';
		if ( ! $text ) {
			$aria_label = ' aria-label="' . __( 'Share Recipe', 'wp-recipe-maker' ) . '"';
		}

		$output .= '<a href="#" data-recipe="' . esc_attr( $recipe->id() ) . '" style="' . esc_attr( $style ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $aria_label . '>' . $icon . WPRM_Shortcode_Helper::sanitize_html( $text ) . '</a>';

		return $output;
	}
}

WPRMP_SC_Share_Options_Popup::init();