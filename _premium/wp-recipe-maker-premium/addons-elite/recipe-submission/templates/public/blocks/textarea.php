<div class="wprmprs-layout-block-textarea wprmprs-layout-block-<?php echo esc_attr( $block['type'] ); ?>">
	<?php if ( $block['label'] ) : ?>
	<label class="wprmprs-form-label"><?php echo do_shortcode( $block['label'] ); ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo '<span class="wprmprs-layout-block-required">*</span>'; } ?></label>
	<?php endif; ?>
	<?php if ( $block['help'] ) : ?>
	<div class="wprmprs-form-help"><?php echo do_shortcode( $block['help'] ); ?></div>
	<?php endif; ?>
	<?php if ( 'recipe_custom_field' === $block['type'] ) : ?>
		<textarea class="wprmprs-form-input" name="recipe_custom_field_<?php echo esc_attr( $block['field'] ); ?>" placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?>></textarea>
	<?php else : ?>
		<textarea class="wprmprs-form-input" name="<?php echo esc_attr( $block['type'] ); ?>" placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?>></textarea>
	<?php endif; ?>
</div>