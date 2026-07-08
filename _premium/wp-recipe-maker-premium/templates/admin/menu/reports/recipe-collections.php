<?php
/**
 * Template for recipe collections report.
 *
 * @link       https://bootstrapped.ventures
 * @since      9.5.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/admin/menu/reports
 */
?>

<div class="wrap wprm-reports">
	<h2><?php esc_html_e( 'Recipe Collections Report', 'wp-recipe-maker-premium' ); ?></h2>
	<?php
		if ( ! $report_finished ) :
	?>
	<?php printf( esc_html( _n( 'Searching %d user', 'Searching %d users', count( $users ), 'wp-recipe-maker-premium' ) ), count( $users ) ); ?>.
	<?php
	$progress_bar_type = 'reports';
	include WPRM_DIR . 'templates/admin/progress-bar.php';
	?>
	<?php
		else :
	?>
	<div id="wprm-reports-recipe-collections">Loading...</div>
	<?php endif; ?>
</div>
