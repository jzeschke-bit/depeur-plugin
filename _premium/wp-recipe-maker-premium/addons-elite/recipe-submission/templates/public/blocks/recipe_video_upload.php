<?php
$max_file_size_data = '';
$max_file_size = floatval( WPRM_Settings::get( 'recipe_submission_max_file_size_video' ) ) * 1024 * 1024;

if ( 0 < $max_file_size && $max_file_size < wp_max_upload_size() ) {
	$max_file_size_data = ' data-max-size="' . esc_attr( $max_file_size ) . '"';
}
?>
<div class="wprmprs-layout-block-recipe_video_upload">
	<?php if ( $block['label'] ) : ?>
	<label class="wprmprs-form-label"><?php echo do_shortcode( $block['label'] ); ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo '<span class="wprmprs-layout-block-required">*</span>'; } ?></label>
	<?php endif; ?>
	<?php if ( $block['help'] ) : ?>
	<div class="wprmprs-form-help"><?php echo do_shortcode( $block['help'] ); ?></div>
	<?php endif; ?>
	<input type="file" name="recipe_video_upload" accept="video/*" data-placeholder="<?php echo esc_attr( do_shortcode( $block['placeholder'] ) ); ?>"<?php echo $max_file_size_data; ?><?php if ( isset( $block['required'] ) && $block['required'] ) { echo ' required'; } ?>/>
</div>