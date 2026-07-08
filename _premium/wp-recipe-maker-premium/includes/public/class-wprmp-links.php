<?php
/**
 * Handle links.
 *
 * @link       https://bootstrapped.ventures
 * @since      7.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 */

/**
 * Handle links.
 *
 * @since      7.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Links {
	/**
	 * Get output for a link.
	 *
	 * @since    7.1.0
	 * @param	string $url 		URL for the link.
	 * @param	string $nofollow	Nofollow option for the link.
	 * @param	string $text 		Text for the link.
	 * @param	string $type		Type of link to output.
	 */
	public static function get( $url, $nofollow, $text, $type ) {
		$url = trim( $url );
		$types = array( 'ingredient', 'equipment', 'term' );

		if ( ! $url || ! in_array( $type, $types ) ) {
			return false;
		}

		$target = WPRM_Settings::get( $type . '_links_open_in_new_tab' ) ? ' target="_blank"' : '';
		$rel_options = array();

		// Nofollow.
		switch ( $nofollow ) {
			case 'follow':
				break;
			case 'nofollow':
				$rel_options[] = 'nofollow';
				break;
			case 'sponsored':
				$rel_options[] = 'nofollow';
				$rel_options[] = 'sponsored';
				break;
			default:
				$default = WPRM_Settings::get( $type . '_links_nofollow' );

				if ( 'nofollow' === $default ) {
					$rel_options[] = 'nofollow';
				} elseif ( 'sponsored' === $default ) {
					$rel_options[] = 'nofollow';
					$rel_options[] = 'sponsored';
				}
		}

		// Noreferrer or noopener for external links.
		if ( self::is_external( $url ) ) {
			if ( WPRM_Settings::get( 'external_links_noreferrer' ) ) {
				$rel_options[] = 'noreferrer';
			}
			if ( WPRM_Settings::get( 'external_links_noopener' ) ) {
				$rel_options[] = 'noopener';
			}
		}

		// Construct rel.
		$rel = '';
		if ( 0 < count( $rel_options ) ) {
			$rel = ' rel="' . implode( ' ', $rel_options ) . '"';
		}

		return '<a href="' . $url . '" class="wprm-recipe-' . $type . '-link"' . $target . $rel . '>' . $text . '</a>';
	}

	/**
	 * Check if it's an external link.
	 * Source: https://stackoverflow.com/questions/22964579/how-to-check-whether-a-url-is-external-url-or-internal-url-with-php
	 *
	 * @since    7.1.0
	 * @param	string $url URL for the link.
	 */
	public static function is_external( $url ) {
		$components = wp_parse_url($url);
		$http_host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		return !empty($components['host']) && strcasecmp($components['host'], $http_host ); // empty host will indicate url like '/relative.php'
			
	}
}