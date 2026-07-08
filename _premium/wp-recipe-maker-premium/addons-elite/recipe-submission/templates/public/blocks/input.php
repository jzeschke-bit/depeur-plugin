<?php
$value = '';

if ( 'user_' === substr( $block['type'], 0, 5 ) ) {

	$user = wp_get_current_user();
	if ( $user->ID ) {
		switch ( $block['type'] ) {
			case 'user_name':
			$value = $user->display_name;
				break;
			case 'user_email':
				$value = $user->user_email;
				break;
		}
	}
}
?>
<div class="wprmprs-layout-block-input wprmprs-layout-block-<?php echo esc_attr( $block['type'] ); ?>">
	<?php if ( $block['label'] ) : ?>
	<label class="wprmprs-form-label"><?php echo do_shortcode( $block['label'] ); ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo '<span class="wprmprs-layout-block-required">*</span>'; } ?></label>
	<?php endif; ?>
	<?php if ( $block['help'] ) : ?>
	<div class="wprmprs-form-help"><?php echo do_shortcode( $block['help'] ); ?></div>
	<?php endif; ?>
	<?php if ( 'recipe_custom_field' === $block['type'] ) : ?>
		<input type="<?php echo 'link' === $custom_field['type'] ? 'url' : $custom_field['type']; ?>" name="recipe_custom_field_<?php echo esc_attr( $block['field'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="wprmprs-form-input" placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?> />
	<?php elseif ( 'recipe_custom_taxonomy' === $block['type'] ) : ?>
		<input type="text" name="recipe_custom_taxonomy_<?php echo esc_attr( $block['field'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="wprmprs-form-input" placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?> />
	<?php else : ?>
		<input type="<?php echo 'user_email' === $block['type'] ? 'email' : 'text'; ?>" name="<?php echo esc_attr( $block['type'] ); ?>" value="<?php echo esc_attr( $value ); ?>" class="wprmprs-form-input" placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>" <?php if ( isset( $block['required'] ) && $block['required'] ) { echo 'required'; } ?> />
	<?php endif; ?>
</div>