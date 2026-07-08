<?php
/**
 * Template for the Modern Nutrition Label.
 *
 * @link   https://bootstrapped.ventures
 * @since  6.8.0
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

$properties = isset( $layout['properties'] ) ? $layout['properties'] : array();
$blocks = isset( $layout['blocks'] ) ? $layout['blocks'] : array();

// Inline style for properties.
$inline_style = '';

if ( 0 < $properties['border_width'] ) {
	$inline_style .= 'border: ' . $properties['border_width'] . 'px ' . $properties['border_style'] . ' ' . $properties['border_color'] . ';';
}
$inline_style .= 'max-width: ' . $properties['max_width'] . 'px;';
$inline_style .= 'padding: ' . $properties['padding'] . 'px;';
$inline_style .= 'font-family: ' . $properties['font_family'] . ';';
$inline_style .= 'font-size: ' . $properties['font_size'] . 'px;';
$inline_style .= 'line-height: ' . $properties['line_height'] . 'px;';
$inline_style .= 'color: ' . $properties['text_color'] . ';';
$inline_style .= 'background-color: ' . $properties['background_color'] . ';';

// Global styles.
$global_style = '';

$global_style .= ' .wprm-nutrition-label-layout .wprmp-nutrition-label-block-line {';
$global_style .= 'background-color: ' . $properties['border_color'] . ';';
$global_style .= '}';

$global_style .= ' .wprm-nutrition-label-layout .wprmp-nutrition-label-block-nutrient {';
$global_style .= 'border-top-color: ' . $properties['border_color'] . ';';
$global_style .= '}';
?>
<div class="wprm-nutrition-label wprm-nutrition-label-layout" style="<?php echo esc_attr( $inline_style ); ?>">
	<style type="text/css"><?php echo $global_style; ?></style>
	<?php
	foreach ( $blocks as $block ) {
		$nutrients_to_output = array();
		
		switch ( $block['type'] ) {
			case 'text':
				$text = str_ireplace( '%recipe_name%', $name, $block['text'] );

				// Different default text for 100g.
				if ( '100g' === $type && __( 'Amount per Serving', 'wp-recipe-maker-premium' ) === $text ) {
					$text = __( 'Amount per 100g', 'wp-recipe-maker-premium' );
				}

				echo '<div class="wprmp-nutrition-label-block-text wprmp-nutrition-label-block-text-' . esc_attr( $block['style'] ) . '">' . esc_html( $text ) . '</div>';
				break;
			case 'line':
				echo '<div class="wprmp-nutrition-label-block-line" style="height:' . intval( $block['height'] ) . 'px;"></div>';
				break;
			case 'serving':
				if ( ! isset( $output['serving_size'] ) ) {
					break;
				}

				if ( '100' === $block['serving_value'] ) {
					$value = '100 ' . $serving_unit;
				} else {
					$value = $output['serving_size']['value'] . ' ' . $serving_unit;
				}

				echo '<div class="wprmp-nutrition-label-block-serving wprmp-nutrition-label-block-serving-' . esc_attr( $block['style'] ) . '">';
				echo '<div class="wprmp-nutrition-label-block-serving-text">' . esc_html( $block['text'] ) . '</div>';
				echo '<div class="wprmp-nutrition-label-block-serving-spacer">&nbsp;</div>';
				echo '<div class="wprmp-nutrition-label-block-serving-value">' . esc_html( $value ) .'</div>';
				echo '</div>';
				break;
			case 'nutrient':
				if ( isset( $output[ $block['nutrient'] ] ) ) {
					$nutrients_to_output[] = $output[ $block['nutrient'] ];
				}
				break;
			case 'other_nutrients':
				// Get all nutrients that are already getting displayed.
				$nutrients_displayed = array( 'serving_size' );
				foreach ( $blocks as $other_block ) {
					if ( 'nutrient' === $other_block['type'] ) {
						$nutrients_displayed[] = $other_block['nutrient'];
					}
				}

				// Loop through all nutrients that should get output.
				foreach ( $output as $key => $nutrient ) {
					if ( ! in_array( $key, $nutrients_displayed ) ) {
						$nutrients_to_output[] = $nutrient;
					}
				}
				break;
		}

		foreach ( $nutrients_to_output as $nutrient ) {
			// Default values.
			$name = $nutrient['label'];
			$value = $nutrient['value'];
			$unit = $nutrient['unit'];
			$percentage = $nutrient['percentage'];
			$extra = false;

			// Special case: calories block.
			if ( 'nutrient' === $block['type'] && 'calories' === $block['nutrient'] ) {
				if ( 'fat' === $block['calories'] ) {
					$unit = '';
					$percentage = false;
					$extra = __( 'Calories from Fat', 'wp-recipe-maker-premium' ) . ' ' . $fat_calories;
				} elseif ( 'normal' === $block['calories'] ) {
					$extra = $value;
					$value = false;

					$percentage = false;
				}
			}

			echo '<div class="wprmp-nutrition-label-block-nutrient wprmp-nutrition-label-block-nutrient-' . esc_attr( $block['style'] ) . '">';

			echo '<div class="wprmp-nutrition-label-block-nutrient-name-value-unit-container">';
			echo '<div class="wprmp-nutrition-label-block-nutrient-name">' . esc_html( $name ) . '</div>';
			if ( false !== $value ) {
				echo '<div class="wprmp-nutrition-label-block-nutrient-spacer">&nbsp;</div>';
				echo '<div class="wprmp-nutrition-label-block-nutrient-value-unit-container">';
				echo '<div class="wprmp-nutrition-label-block-nutrient-value">' . esc_html( $value ) . '</div>';
				echo '<div class="wprmp-nutrition-label-block-nutrient-unit">' . esc_html( $unit ) . '</div>';
				echo '</div>';
			}
			echo '</div>';

			if ( false !== $percentage ) {
				echo '<div class="wprmp-nutrition-label-block-nutrient-daily-container">';
				echo '<div class="wprmp-nutrition-label-block-nutrient-daily">' . esc_html( $percentage ) . '</div>';
				echo '<div class="wprmp-nutrition-label-block-nutrient-percentage">%</div>';
				echo '</div>';
			}

			if ( false !== $extra ) {
				echo '<div class="wprmp-nutrition-label-block-nutrient-extra-container">' . esc_html( $extra ) . '</div>';
			}

			echo '</div>';
		}
	}
	?>
</div>