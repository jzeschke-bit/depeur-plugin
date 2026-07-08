<?php
/**
 * Template for the Cook Mode Popup Modal Footer.
 *
 * @link   https://bootstrapped.ventures
 * @since  10.2.0
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

// Count total steps.
$total_steps = 0;
foreach ( $recipe->instructions() as $instruction_group ) {
	foreach ( $instruction_group['instructions'] as $instruction ) {
		$instruction_type = isset( $instruction['type'] ) ? $instruction['type'] : 'instruction';
		if ( 'tip' !== $instruction_type ) {
			$total_steps++;
		}
	}
}
?>

<!-- Footer Content -->
<div class="wprm-cook-mode-footer">
	<!-- Start Button (shown initially) -->
	<button class="wprm-cook-mode-start-button" type="button" <?php echo 0 === $total_steps ? 'disabled' : ''; ?>>
		<?php esc_html_e( 'Start Cooking', 'wp-recipe-maker-premium' ); ?>
	</button>

	<!-- Navigation (shown when cooking) -->
	<div class="wprm-cook-mode-cooking-footer" style="display: none;">
		<div class="wprm-cook-mode-timers"></div>
		<div class="wprm-cook-mode-navigation">
			<button class="wprm-cook-mode-nav-button wprm-cook-mode-nav-prev" type="button" aria-label="<?php esc_attr_e( 'Previous step', 'wp-recipe-maker-premium' ); ?>">
				← <?php esc_html_e( 'Previous', 'wp-recipe-maker-premium' ); ?>
			</button>
			<div class="wprm-cook-mode-progress">
				<div class="wprm-cook-mode-progress-text">
					<?php esc_html_e( 'step', 'wp-recipe-maker-premium' ); ?> <span class="wprm-cook-mode-progress-step-current">1</span> <?php esc_html_e( 'of', 'wp-recipe-maker-premium' ); ?> <span class="wprm-cook-mode-progress-step-total"><?php echo esc_html( $total_steps ); ?></span>
				</div>
				<div class="wprm-cook-mode-progress-bar">
					<div class="wprm-cook-mode-progress-bar-fill" style="width: 0%;"></div>
				</div>
			</div>
			<button class="wprm-cook-mode-nav-button wprm-cook-mode-nav-next" type="button" aria-label="<?php esc_attr_e( 'Next step', 'wp-recipe-maker-premium' ); ?>">
				<?php esc_html_e( 'Next', 'wp-recipe-maker-premium' ); ?> →
			</button>
			<button class="wprm-cook-mode-nav-button wprm-cook-mode-nav-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'wp-recipe-maker-premium' ); ?>" style="display: none;">
				<?php esc_html_e( 'Close', 'wp-recipe-maker-premium' ); ?>
			</button>
		</div>
	</div>
</div>
