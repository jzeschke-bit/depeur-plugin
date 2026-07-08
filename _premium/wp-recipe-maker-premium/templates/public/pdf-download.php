<?php
/**
 * Template to be used for the dedicated PDF download page.
 *
 * @link       https://bootstrapped.ventures
 * @since      10.6.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */
?>
<!DOCTYPE html>
<html <?php echo wp_kses_post( get_language_attributes() ); ?>>
	<head>
		<title><?php echo esc_html( isset( $output['title'] ) && $output['title'] ? $output['title'] : get_bloginfo( 'name' ) ); ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta name="robots" content="noindex">
		<?php if ( WPRM_Settings::get( 'metadata_pinterest_disable_print_page' ) ) : ?>
			<meta name="pinterest" content="nopin" />
		<?php endif; ?>
		<?php wp_site_icon(); ?>
		<?php
		if ( isset( $output['assets'] ) ) {
			$serialized = array_map( 'serialize', $output['assets'] );
			$unique = array_unique( $serialized );
			$assets = array_intersect_key( $output['assets'], $unique );

			foreach ( $output['assets'] as $asset ) {
				$asset_version = isset( $asset['version'] ) ? $asset['version'] : WPRM_VERSION;

				switch ( $asset['type'] ) {
					case 'css':
						echo '<link rel="stylesheet" type="text/css" href="' . esc_attr( $asset['url'] . '?ver=' . $asset_version ) . '"/>';
						break;
					case 'js':
						echo '<script src="' . esc_attr( $asset['url'] . '?ver=' . $asset_version ) . '"></script>';
						break;
					case 'custom':
						echo $asset['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						break;
				}
			}
		}
		?>
		<?php do_action( 'wprm_print_head' ); ?>
	</head>
	<body class="wprm-print<?php echo esc_attr( is_rtl() ? ' rtl' : '' ); ?> wprm-print-<?php echo esc_attr( $output['type'] ); ?>">
		<?php do_action( 'wprm_print_body_open' ); ?>
		<div id="wprm-pdf-generation-indicator" role="status" aria-live="polite" aria-atomic="true">
			<span class="wprm-pdf-generation-indicator-spinner" aria-hidden="true"></span>
			<div class="wprm-pdf-generation-indicator-text">
				<strong><?php esc_html_e( 'Generating PDF...', 'wp-recipe-maker' ); ?></strong>
				<span><?php esc_html_e( 'Your download should start automatically.', 'wp-recipe-maker' ); ?></span>
			</div>
		</div>
		<?php
		$classes = isset( $output['classes'] ) ? $output['classes'] : array();

		echo '<div id="wprm-print-content" class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		$html = do_shortcode( $output['html'] );
		echo apply_filters( 'wprm_print_output_html', $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';

		// Recipes we need the data for.
		WPRM_Recipe_Manager::recipe_data_in_footer( $output['recipe_ids'] );
		?>
		<div id="print-pdf"></div>
		<?php do_action( 'wprm_print_footer' ); ?>
	</body>
</html>
