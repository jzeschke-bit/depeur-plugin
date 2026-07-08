<div class="wprmprs-layout-block-isotope wprmprs-layout-block-<?php echo esc_attr( $block['type'] ); ?>">
	<?php if ( $block['label'] ) : ?>
	<label class="wprmprs-form-label"><?php echo do_shortcode( $block['label'] ); ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo '<span class="wprmprs-layout-block-required">*</span>'; } ?></label>
	<?php endif; ?>
	<?php if ( $block['help'] ) : ?>
	<div class="wprmprs-form-help"><?php echo do_shortcode( $block['help'] ); ?></div>
	<?php endif; ?>
	<div class="wprmprs-layout-block-isotope-options">
		<?php foreach ( $terms as $id => $term ) : ?>
		<div class="wprmprs-layout-block-isotope-option">
		<input type="<?php echo 'multiple-isotope' === $taxonomy_input_type ? 'checkbox' : 'radio'; ?>" id="recipe_custom_taxonomy_id_<?php echo esc_attr( $block['field'] ); ?>-<?php echo $id; ?>" name="recipe_custom_taxonomy_id_<?php echo esc_attr( $block['field'] ); ?>[]" value="<?php echo $id; ?>">
		<label for="recipe_custom_taxonomy_id_<?php echo esc_attr( $block['field'] ); ?>-<?php echo $id; ?>"><?php echo $term; ?></label>
		</div>
		<?php endforeach; ?>
	</div>
</div>