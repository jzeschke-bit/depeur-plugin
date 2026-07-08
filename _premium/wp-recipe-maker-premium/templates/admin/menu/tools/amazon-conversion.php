<?php
/**
 * Template for Amazon converting HTML page.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.1.0
 *
 * @package    WP_Recipe_Maker
 * @subpackage WP_Recipe_Maker/templates/admin/menu/tools
 */

?>

<div class="wrap wprm-tools">
	<h2><?php esc_html_e( 'Amazon Conversion', 'wp-recipe-maker' ); ?></h2>
	<?php printf( esc_html( _n( 'Searching %d equipment', 'Searching %d equipment', count( $posts ), 'wp-recipe-maker-premium' ) ), count( $posts ) ); ?>.
	<?php
	$progress_bar_type = 'tools';
	include WPRM_DIR . 'templates/admin/progress-bar.php';
	?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprm_manage#equipment' ) ); ?>" id="wprm-tools-finished"><?php esc_html_e( 'Finished succesfully. Click here to continue.', 'wp-recipe-maker' ); ?></a>
</div>
