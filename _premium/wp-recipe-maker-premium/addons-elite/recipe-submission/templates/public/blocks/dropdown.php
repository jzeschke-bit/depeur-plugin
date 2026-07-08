<div class="wprmprs-layout-block-dropdown wprmprs-layout-block-<?php echo esc_attr( $block['type'] ); ?>">
	<?php if ( $block['label'] ) : ?>
	<label class="wprmprs-form-label"><?php echo do_shortcode( $block['label'] ); ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo '<span class="wprmprs-layout-block-required">*</span>'; } ?></label>
	<?php endif; ?>
	<?php if ( $block['help'] ) : ?>
	<div class="wprmprs-form-help"><?php echo do_shortcode( $block['help'] ); ?></div>
	<?php endif; ?>
	<select name="recipe_custom_taxonomy_id_<?php echo esc_attr( $block['field'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="wprmprs-form-input" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?>>
		<?php if ( $block['placeholder'] ) : ?>
		<option value=""><?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?></div>
		<?php endif; ?>
		<?php foreach ( $terms as $id => $term ) : ?>
		<option value="<?php echo $id; ?>"><?php echo $term; ?></option>
		<?php endforeach; ?>
	</select>
</div>