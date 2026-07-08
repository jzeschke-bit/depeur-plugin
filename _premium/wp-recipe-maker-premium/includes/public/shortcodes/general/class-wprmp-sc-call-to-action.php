<?php
/**
 * Handle the Premium call to action shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/general
 */

/**
 * Handle the Premium call to action shortcode.
 *
 * @since      5.6.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/shortcodes/general
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_SC_Call_to_Action {
	public static function init() {
		add_filter( 'wprm_call_to_action_shortcode', array( __CLASS__, 'shortcode' ), 10, 2 );
	}

	/**
	 * Add the call to action.
	 *
	 * @since	5.6.0	 
	 * @param	mixed $output Current output.
	 * @param	array $atts   Options passed along with the shortcode.
	 */
	public static function shortcode( $output, $atts ) {
		$output = '';

		// Get optional icon.
		$icon = '';
		if ( $atts['icon'] ) {
			$icon = WPRM_Icon::get( $atts['icon'], $atts['icon_color'] );

			if ( $icon ) {
				$icon = '<span class="wprm-recipe-icon wprm-call-to-action-icon">' . $icon . '</span> ';
			}
		}

		// Custom container style.
		$style = '';
		$style .= 'color: ' . $atts['text_color'] . ';';
		$style .= $atts['background_color'] ? 'background-color: ' . $atts['background_color'] . ';' : '';
		$style .= 'margin: ' . $atts['margin'] . ';';
		$style .= 'padding-top: ' . $atts['padding'] . ';';
		$style .= 'padding-bottom: ' . $atts['padding'] . ';';

		if ( $atts['border_radius'] && $atts['border_radius'] !== '0px' && $atts['border_radius'] !== '0em' ) {
			$style .= 'border-radius: ' . $atts['border_radius'] . ';';
		}

		if ( 'top' === $atts['icon_position'] || 'bottom' === $atts['icon_position'] ) {
			$style .= 'flex-direction: column;';
		}

		if ( '20px' !== $atts['icon_gap'] ) {
			$style .= 'gap: ' . $atts['icon_gap'] . ';';
		}

		// Output.
		$classes = array(
			'wprm-call-to-action',
			'wprm-call-to-action-' . $atts['style'],
		);

		// Add custom class if set.
		if ( $atts['class'] ) { $classes[] = esc_attr( $atts['class'] ); }

		// Check if we need to wrap the entire block in a link
		$wrap_entire_block = false;
		$block_link_attributes = '';
		if ( 'entire_block' === $atts['link_behavior'] && ( 'custom' === $atts['action'] || 'rating' === $atts['action'] ) ) {
			$wrap_entire_block = true;
			
			if ( 'custom' === $atts['action'] ) {
				$url = $atts['custom_link_url'] ? esc_url_raw( $atts['custom_link_url'] ) : '#';
				$nofollow = 'nofollow' === $atts['custom_link_nofollow'] ? ' rel="nofollow"' : '';
				$block_link_attributes = 'href="' . esc_attr( $url ) . '" target="' . esc_attr( $atts['custom_link_target'] ) . '" style="color: ' . esc_attr( $atts['text_color'] ) . '; text-decoration: none;"' . $nofollow;
			} elseif ( 'rating' === $atts['action'] ) {
				$modal_uid = WPRMP_User_Rating::get_modal_uid();
				$block_link_attributes = 'href="#" role="button" class="wprm-cta-rating-modal" data-modal-uid="' . esc_attr( $modal_uid ) . '" data-recipe="%recipe_id%" style="color: ' . esc_attr( $atts['text_color'] ) . '; text-decoration: none;"';
			}
		}

		// Start the link wrapper if needed
		if ( $wrap_entire_block ) {
			// Add the block link class to the existing attributes
			if ( strpos( $block_link_attributes, 'class="' ) !== false ) {
				// If class already exists, prepend our class
				$block_link_attributes = str_replace( 'class="', 'class="wprm-call-to-action-block-link ', $block_link_attributes );
			} else {
				// If no class exists, add it
				$block_link_attributes .= ' class="wprm-call-to-action-block-link"';
			}
			$output .= '<a ' . $block_link_attributes . '>';
		}

		$output .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '">';

		if ( 'left' === $atts['icon_position'] || 'top' === $atts['icon_position'] ) {
			$output .= $icon;
		}
		$output .= '<span class="wprm-call-to-action-text-container">';

		// Optional Header.
		if ( $atts['header'] ) {
			$style = 'color: ' . $atts['header_color'] . ';';
			$tag = WPRM_Shortcode_Helper::sanitize_html_element( $atts['header_tag'] );
			$output .= '<' . $tag . ' class="wprm-call-to-action-header" style="' . esc_attr( $style ) . '">' . WPRM_Shortcode_Helper::sanitize_html( __( $atts['header'], 'wp-recipe-maker' ) ) . '</' . $tag . '>';
		}

		// Social URLs
		$social_urls = array(
			'instagram' => array(
				'handle' => 'https://www.instagram.com/',
				'tag' => 'https://www.instagram.com/explore/tags/',
			),
			'twitter' => array(
				'handle' => 'https://twitter.com/',
				'tag' => 'https://twitter.com/hashtag/',
			),
			'facebook' => array(
				'handle' => 'https://www.facebook.com/',
				'tag' => 'https://www.facebook.com/hashtag/',
			),
			'pinterest' => array(
				'handle' => 'https://www.pinterest.com/',
				'tag' => 'https://www.pinterest.com/search/pins/?rs=hashtag_closeup&q=%23',
			),
		);

		// Main CTA text.
		$output .= '<span class="wprm-call-to-action-text">';
		switch ( $atts['action'] ) {
			case 'instagram':
			case 'twitter':
			case 'facebook':
			case 'pinterest':
				$handle = $atts['social_handle'] ? '<a href="' . $social_urls[ $atts['action'] ]['handle'] . urlencode( $atts['social_handle'] ) . '" target="_blank" rel="noreferrer noopener" style="color: ' . esc_attr( $atts['link_color'] ) . '">@' . $atts['social_handle'] . '</a>' : '';
				$tag = $atts['social_tag'] ? '<a href="' . $social_urls[ $atts['action'] ]['tag'] . urlencode( $atts['social_tag'] ) . '" target="_blank" rel="noreferrer noopener" style="color: ' . esc_attr( $atts['link_color'] ) . '">#' . $atts['social_tag'] . '</a>' : '';

				$text = __( $atts['social_text'], 'wp-recipe-maker' );
				$text = str_ireplace( '%handle%', $handle, $text );
				$text = str_ireplace( '%tag%', $tag, $text );

				$output .= $text;
				break;
			case 'custom':
				$output .= self::get_custom_action_output( $atts );
				break;
			case 'rating':
				$output .= self::get_rating_action_output( $atts );
				break;
		}
		$output .= '</span>';

		$output .= '</span>';
		if ( 'right' === $atts['icon_position'] || 'bottom' === $atts['icon_position'] ) {
			$output .= $icon;
		}
		$output .= '</div>';
		
		// Close the link wrapper if needed
		if ( $wrap_entire_block ) {
			$output .= '</a>';
		}

		// If inside of a recipe card, replace placeholders.
		$recipe = WPRM_Template_Shortcodes::get_recipe( 0 );

		if ( $recipe ) {
			$output = $recipe->replace_placeholders( $output );
		}

		return $output;
	}

	/**
	 * Get output for custom action with link behavior handling.
	 *
	 * @since	5.6.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	private static function get_custom_action_output( $atts ) {
		$url = $atts['custom_link_url'] ? esc_url_raw( $atts['custom_link_url'] ) : '#';
		$nofollow = 'nofollow' === $atts['custom_link_nofollow'] ? ' rel="nofollow"' : '';
		$text = __( $atts['custom_text'], 'wp-recipe-maker' );
		
		return self::handle_link_behavior( $atts, $text, $url, $atts['custom_link_target'], $nofollow, $atts['custom_link_text'] );
	}

	/**
	 * Get output for rating action with link behavior handling.
	 *
	 * @since	5.6.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	private static function get_rating_action_output( $atts ) {
		$modal_uid = WPRMP_User_Rating::get_modal_uid();
		$text = __( $atts['rating_text'], 'wp-recipe-maker' );
		
		// For rating, we use a special URL and attributes
		$url = '#';
		$target = '';
		$nofollow = '';
		$link_attributes = 'role="button" class="wprm-cta-rating-modal" data-modal-uid="' . esc_attr( $modal_uid ) . '" data-recipe="%recipe_id%"';
		
		return self::handle_link_behavior( $atts, $text, $url, $target, $nofollow, $atts['rating_link_text'], $link_attributes );
	}

	/**
	 * Handle link behavior for both custom and rating actions.
	 *
	 * @since	5.6.0
	 * @param	array  $atts            Options passed along with the shortcode.
	 * @param	string $text            The text content.
	 * @param	string $url             The URL for the link.
	 * @param	string $target         The target attribute for the link.
	 * @param	string $nofollow        The nofollow attribute for the link.
	 * @param	string $link_text       The text to replace %link% with.
	 * @param	string $link_attributes Additional attributes for the link (for rating).
	 */
	private static function handle_link_behavior( $atts, $text, $url, $target, $nofollow, $link_text, $link_attributes = '' ) {
		$link_text_plain = $link_text ? __( $link_text, 'wp-recipe-maker' ) : '';
		
		if ( 'entire_block' === $atts['link_behavior'] ) {
			// For entire block, replace %link% with plain text (link will wrap the entire block)
			return str_ireplace( '%link%', $link_text_plain, $text );
		} elseif ( 'entire_text' === $atts['link_behavior'] ) {
			// For entire text, replace %link% with plain text and wrap the whole text in a link
			$text_with_link = str_ireplace( '%link%', $link_text_plain, $text );
			$target_attr = $target ? ' target="' . esc_attr( $target ) . '"' : '';
			$style = 'color: ' . esc_attr( $atts['text_color'] ) . ';';
			return '<a href="' . esc_attr( $url ) . '"' . $target_attr . ' style="' . esc_attr( $style ) . '"' . $nofollow . ' ' . $link_attributes . '>' . $text_with_link . '</a>';
		} else {
			// Default behavior: only part of the text links - replace %link% with linked text
			$link = $link_text ? '<a href="' . esc_attr( $url ) . '"' . ( $target ? ' target="' . esc_attr( $target ) . '"' : '' ) . ' style="color: ' . esc_attr( $atts['link_color'] ) . '"' . $nofollow . ' ' . $link_attributes . '>' . __( $link_text, 'wp-recipe-maker' ) . '</a>' : '';
			$text_with_link = str_ireplace( '%link%', $link, $text );
			return $text_with_link;
		}
	}
}

WPRMP_SC_Call_to_Action::init();