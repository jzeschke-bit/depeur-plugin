<?php
/**
 * Template for the Legacy Nutrition Label.
 *
 * @link   https://bootstrapped.ventures
 * @since  6.8.0
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

$header_output = array();
$main_output = array();
$sub_output = array();

foreach ( $output as $nutrient => $options ) {
	if ( in_array( $nutrient, array( 'serving_size', 'calories' ), true ) ) {
		$header_output[ $nutrient ] = $options;
	} elseif ( in_array( $nutrient, array( 'fat', 'saturated_fat', 'trans_fat', 'polyunsaturated_fat', 'monounsaturated_fat', 'cholesterol', 'sodium', 'potassium', 'carbohydrates', 'fiber', 'sugar', 'protein' ), true ) ) {
		$main_output[ $nutrient ] = $options;
	} else {
		$sub_output[] = $options;
	}
}

?>
<div class="wprm-nutrition-label">
	<div class="nutrition-title"><?php esc_html_e( 'Nutrition Facts', 'wp-recipe-maker-premium' ); ?></div>
	<div class="nutrition-recipe"><?php echo esc_html( $name ); ?></div>
	<div class="nutrition-line nutrition-line-big"></div>
	<div class="nutrition-serving">
		<?php
			if ( '100g' === $type ) {
				esc_html_e( 'Amount Per 100g', 'wp-recipe-maker-premium' );
			} else {
		?>
			<?php esc_html_e( 'Amount Per Serving', 'wp-recipe-maker-premium' ); ?>
			<?php
			if ( isset( $header_output['serving_size'] ) ) {
				$unit = isset( $nutrition['serving_unit'] ) && $nutrition['serving_unit'] ? $nutrition['serving_unit'] : WPRM_Settings::get( 'nutrition_default_serving_unit' );
				echo ' (' . esc_html( $header_output['serving_size']['value'] ) . ' ' . esc_html( $unit ) . ')';
			}
			?>
		<?php } ?>
	</div>
	<div class="nutrition-item">
		<span class="nutrition-main"><strong><?php esc_html_e( 'Calories', 'wp-recipe-maker-premium' ); ?></strong> <?php echo esc_html( $header_output['calories']['value'] ); ?></span>
		<?php if ( $fat_calories ) : ?>
		<span class="nutrition-percentage"><?php esc_html_e( 'Calories from Fat', 'wp-recipe-maker-premium' ); ?> <?php echo esc_html( $fat_calories ); ?></span>
		<?php endif; // Fat calories. ?>
	</div>
	<?php if ( ! empty( $main_output ) ) : ?>
	<div class="nutrition-line"></div>
	<div class="nutrition-item">
		<span class="nutrition-percentage"><strong><?php esc_html_e( '% Daily Value', 'wp-recipe-maker-premium' ); ?>*</strong></span>
	</div>
	<?php
	$main_info_order = array(
		'fat' => true,
		'saturated_fat' => false,
		'trans_fat' => false,
		'polyunsaturated_fat' => false,
		'monounsaturated_fat' => false,
		'cholesterol' => true,
		'sodium' => true,
		'potassium' => true,
		'carbohydrates' => true,
		'fiber' => false,
		'sugar' => false,
		'protein' => true,
	);

	foreach ( $main_info_order as $nutrient => $main ) {
		if ( isset( $main_output[ $nutrient ] ) ) {
			$class = $main ? 'nutrition-item' : 'nutrition-sub-item';
			$subclass = $main ? 'nutrition-main' : 'nutrition-sub';

			$class .= ' nutrition-item-' . $nutrient;

			echo '<div class="' . esc_attr( $class ) . '">';
			echo '<span class="' . esc_attr( $subclass ) . '">';
			echo $main ? '<strong>' . $main_output[ $nutrient ]['label'] . '</strong>' : $main_output[ $nutrient ]['label'];
			echo ' ' . $main_output[ $nutrient ]['value'] . $main_output[ $nutrient ]['unit'];
			echo '</span>';

			if ( false !== $main_output[ $nutrient ]['percentage'] ) {
				echo '<span class="nutrition-percentage"><strong>' . $main_output[ $nutrient ]['percentage'] . '%</strong></span>';
			}

			echo '</div>';
		}
	}

	?>
	<?php endif; // Main info. ?>
	<?php if ( ! empty( $sub_output ) ) : ?>
	<div class="nutrition-line nutrition-line-big"></div>
	<?php
	foreach ( $sub_output as $output ) {
		echo '<div class="nutrition-item nutrition-item-' . esc_attr( $output['key'] ) . '">';
		echo '<span class="nutrition-main">';
		echo '<strong>' . $output['label'] . '</strong>';
		echo ' ' . $output['value'] . $output['unit'];
		echo '</span>';

		if ( false !== $output['percentage'] ) {
			echo '<span class="nutrition-percentage"><strong>' . $output['percentage'] . '%</strong></span>';
		}

		echo '</div>';
	}
	?>
	<?php endif; // Sub info. ?>
	<div class="nutrition-warning">* <?php echo esc_html( WPRM_Settings::get( 'nutrition_label_custom_daily_values_disclaimer' ) ); ?></div>
</div>
