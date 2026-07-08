<?php
/**
 * Template for the Cook Mode Popup Modal.
 *
 * @link   https://bootstrapped.ventures
 * @since  10.2.0
 *
 * @package WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/templates/public
 */

// Build cook mode steps from non-tip instructions and attach tips to closest previous step.
$cook_mode_steps = array();
$leading_tips = array();
$tip_icon_ids = array_keys( WPRM_Icon::get_all() );

foreach ( $recipe->instructions() as $instruction_group ) {
	foreach ( $instruction_group['instructions'] as $instruction ) {
		$instruction_type = isset( $instruction['type'] ) ? $instruction['type'] : 'instruction';

		if ( 'tip' === $instruction_type ) {
			$tip_data = array(
				'text' => isset( $instruction['text'] ) ? $instruction['text'] : '',
				'tip_icon' => isset( $instruction['tip_icon'] ) ? $instruction['tip_icon'] : '',
				'tip_accent' => isset( $instruction['tip_accent'] ) ? $instruction['tip_accent'] : '',
			);

			if ( count( $cook_mode_steps ) > 0 ) {
				$last_index = count( $cook_mode_steps ) - 1;
				$cook_mode_steps[ $last_index ]['tips'][] = $tip_data;
			} else {
				$leading_tips[] = $tip_data;
			}

			continue;
		}

		$step_data = array(
			'instruction' => $instruction,
			'tips' => array(),
		);

		// If the recipe starts with tips, attach them to the first step.
		if ( $leading_tips ) {
			$step_data['tips'] = $leading_tips;
			$leading_tips = array();
		}

		$cook_mode_steps[] = $step_data;
	}
}

$total_steps = count( $cook_mode_steps );

// Check if unit conversion is available.
$unit_systems = array(
	1 => true, // Default unit system.
);

if ( WPRM_Addons::is_active( 'unit-conversion' ) && WPRM_Settings::get( 'unit_conversion_enabled' ) ) {
	$ingredients = $recipe->ingredients_without_groups();
	if ( $ingredients ) {
		foreach ( $ingredients as $index => $ingredient ) {
			if ( isset( $ingredient['converted'] ) ) {
				foreach ( $ingredient['converted'] as $system => $values ) {
					if ( $values['amount'] || $values['unit'] ) {
						$unit_systems[ $system ] = true;
					}
				}
			}
		}
	}
}

// Ingredient notes settings.
$cook_mode_ingredient_notes_show = (bool) WPRM_Settings::get( 'cook_mode_ingredient_notes_show' );
$cook_mode_ingredient_notes_separator = WPRM_Settings::get( 'cook_mode_ingredient_notes_separator' );
$allowed_notes_separators = array( 'none', 'comma', 'dash', 'parentheses' );

if ( ! in_array( $cook_mode_ingredient_notes_separator, $allowed_notes_separators, true ) ) {
	$cook_mode_ingredient_notes_separator = 'parentheses';
}

$cook_mode_ingredient_notes_before = ' ';
$cook_mode_ingredient_notes_after = '';

switch ( $cook_mode_ingredient_notes_separator ) {
	case 'comma':
		$cook_mode_ingredient_notes_before = ', ';
		break;
	case 'dash':
		$cook_mode_ingredient_notes_before = ' - ';
		break;
	case 'parentheses':
		$cook_mode_ingredient_notes_before = ' (';
		$cook_mode_ingredient_notes_after = ')';
		break;
	default:
		$cook_mode_ingredient_notes_before = ' ';
}
?>

<div class="wprm-cook-mode wprm-cook-mode-<?php echo esc_attr( $recipe->id() ); ?>" data-recipe-id="<?php echo esc_attr( $recipe->id() ); ?>">
	<!-- Initial Screen: Recipe Overview -->
	<div class="wprm-cook-mode-screen wprm-cook-mode-screen-overview" data-screen="overview">
		<!-- Recipe Image -->
		<?php if ( $recipe->image_url( array( 500, 9999 ) ) ) : ?>
		<div class="wprm-cook-mode-image-container">
			<img class="wprm-cook-mode-image" src="<?php echo esc_url( $recipe->image_url( 'full' ) ); ?>" alt="<?php echo esc_attr( $recipe->name() ); ?>" />
		</div>
		<?php endif; ?>

		<!-- Controls: Servings and Unit System -->
		<div class="wprm-cook-mode-controls">
			<!-- Servings Control -->
			<?php if ( $recipe->servings() && floatval( $recipe->servings() ) > 0 ) : ?>
			<div class="wprm-cook-mode-control wprm-cook-mode-servings">
				<label class="wprm-cook-mode-control-label"><?php esc_html_e( 'Servings', 'wp-recipe-maker-premium' ); ?></label>
				<div class="wprm-cook-mode-servings-input-container">
					<button class="wprm-cook-mode-servings-decrease" type="button" aria-label="<?php esc_attr_e( 'Decrease servings', 'wp-recipe-maker-premium' ); ?>">−</button>
					<input type="number" class="wprm-cook-mode-servings-input" min="0" step="1" value="<?php echo esc_attr( $recipe->servings() ); ?>" data-original-servings="<?php echo esc_attr( $recipe->servings() ); ?>" />
					<button class="wprm-cook-mode-servings-increase" type="button" aria-label="<?php esc_attr_e( 'Increase servings', 'wp-recipe-maker-premium' ); ?>">+</button>
				</div>
			</div>
			<?php endif; ?>

			<!-- Unit System Control -->
			<?php if ( count( $unit_systems ) > 1 ) : ?>
			<div class="wprm-cook-mode-control wprm-cook-mode-units">
				<label class="wprm-cook-mode-control-label"><?php esc_html_e( 'Unit System', 'wp-recipe-maker-premium' ); ?></label>
				<div class="wprm-cook-mode-units-buttons">
					<?php
					$recipe_unit_system = intval( $recipe->unit_system() );

					foreach ( $unit_systems as $system => $value ) {
						$unit_system_label = 2 === $recipe_unit_system ? WPRM_Settings::get( 'unit_conversion_system_' . ( 3 - $system ) ) : WPRM_Settings::get( 'unit_conversion_system_' . $system );
						$active = $system === $recipe_unit_system ? 'active' : '';

						echo '<button class="wprm-cook-mode-unit-button ' . esc_attr( $active ) . '" data-system="' . esc_attr( $system ) . '" type="button">' . esc_html( $unit_system_label ) . '</button>';
					}
					?>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<!-- Ingredients List -->
		<?php
		// Check if any ingredient has an image.
		$has_any_ingredient_image = false;
		if ( count( $recipe->ingredients() ) > 0 ) {
			foreach ( $recipe->ingredients() as $ingredient_group ) {
				if ( isset( $ingredient_group['ingredients'] ) && is_array( $ingredient_group['ingredients'] ) ) {
					foreach ( $ingredient_group['ingredients'] as $ingredient ) {
						if ( isset( $ingredient['id'] ) && $ingredient['id'] ) {
							$image_id = intval( get_term_meta( $ingredient['id'], 'wprmp_ingredient_image_id', true ) );
							if ( $image_id ) {
								$has_any_ingredient_image = true;
								break 2; // Break out of both loops.
							}
						}
					}
				}
			}
		}
		?>
		<div class="wprm-cook-mode-ingredients">
			<h3 class="wprm-cook-mode-section-title"><?php esc_html_e( 'Ingredients', 'wp-recipe-maker-premium' ); ?></h3>
			<div class="wprm-cook-mode-ingredients-list">
				<?php if ( count( $recipe->ingredients() ) > 0 ) : ?>
					<?php foreach ( $recipe->ingredients() as $ingredient_group ) : ?>
						<div class="wprm-cook-mode-ingredient-group">
							<?php if ( $ingredient_group['name'] ) : ?>
								<div class="wprm-cook-mode-ingredient-group-name"><?php echo wp_kses_post( $ingredient_group['name'] ); ?></div>
							<?php endif; ?>
							<?php if ( count( $ingredient_group['ingredients'] ) > 0 ) : ?>
								<ul class="wprm-cook-mode-ingredients-group-list">
									<?php foreach ( $ingredient_group['ingredients'] as $ingredient ) : ?>
										<?php
										// Maybe replace fractions in amount.
										if ( WPRM_Settings::get( 'automatic_amount_fraction_symbols' ) ) {
											$ingredient['amount'] = WPRM_Recipe_Parser::replace_any_fractions_with_symbol( $ingredient['amount'] );
										}

										// Get ingredient image if available.
										$ingredient_image = '';
										if ( $has_any_ingredient_image && isset( $ingredient['id'] ) && $ingredient['id'] ) {
											$image_id = intval( get_term_meta( $ingredient['id'], 'wprmp_ingredient_image_id', true ) );
											if ( $image_id ) {
												$ingredient_image = wp_get_attachment_image( $image_id, array( 60, 60 ) );
												// Disable ingredient image pinning.
												$ingredient_image = str_ireplace( '<img ', '<img data-pin-nopin="true" ', $ingredient_image );
											}
										}
										?>
										<li class="wprm-cook-mode-ingredient">
											<?php if ( $has_any_ingredient_image ) : ?>
												<?php if ( $ingredient_image ) : ?>
													<span class="wprm-cook-mode-ingredient-image"><?php echo $ingredient_image; ?></span>
												<?php else : ?>
													<span class="wprm-cook-mode-ingredient-image wprm-cook-mode-ingredient-image-placeholder"></span>
												<?php endif; ?>
											<?php endif; ?>
											<span class="wprm-cook-mode-ingredient-content">
												<?php if ( $ingredient['amount'] ) : ?>
													<span class="wprm-cook-mode-ingredient-amount"><?php echo wp_kses_post( $ingredient['amount'] ); ?></span>
												<?php endif; ?>
												<?php if ( $ingredient['unit'] ) : ?>
													<span class="wprm-cook-mode-ingredient-unit"><?php echo wp_kses_post( $ingredient['unit'] ); ?></span>
												<?php endif; ?>
												<?php if ( $ingredient['name'] ) : ?>
													<span class="wprm-cook-mode-ingredient-name"><?php echo wp_kses_post( $ingredient['name'] ); ?></span>
												<?php endif; ?>
												<?php if ( $cook_mode_ingredient_notes_show && $ingredient['notes'] ) : ?>
													<span class="wprm-cook-mode-ingredient-notes wprm-cook-mode-ingredient-notes-separator-<?php echo esc_attr( $cook_mode_ingredient_notes_separator ); ?>"><?php echo esc_html( $cook_mode_ingredient_notes_before ); ?><?php echo wp_kses_post( $ingredient['notes'] ); ?><?php echo esc_html( $cook_mode_ingredient_notes_after ); ?></span>
												<?php endif; ?>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No ingredients found.', 'wp-recipe-maker-premium' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Equipment List -->
		<?php if ( count( $recipe->equipment() ) > 0 ) : ?>
		<div class="wprm-cook-mode-equipment">
			<h3 class="wprm-cook-mode-section-title"><?php esc_html_e( 'Equipment', 'wp-recipe-maker-premium' ); ?></h3>
			<div class="wprm-cook-mode-equipment-list">
				<ul class="wprm-cook-mode-equipment-items">
					<?php foreach ( $recipe->equipment() as $equipment ) : ?>
						<li class="wprm-cook-mode-equipment-item">
							<?php if ( isset( $equipment['amount'] ) && $equipment['amount'] ) : ?>
								<span class="wprm-cook-mode-equipment-amount"><?php echo wp_kses_post( $equipment['amount'] ); ?></span>
							<?php endif; ?>
							<?php if ( $equipment['name'] ) : ?>
								<span class="wprm-cook-mode-equipment-name"><?php echo wp_kses_post( $equipment['name'] ); ?></span>
							<?php endif; ?>
							<?php if ( isset( $equipment['notes'] ) && $equipment['notes'] ) : ?>
								<span class="wprm-cook-mode-equipment-notes"><?php echo wp_kses_post( $equipment['notes'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- Cooking Screen: Instructions -->
	<div class="wprm-cook-mode-screen wprm-cook-mode-screen-cooking" data-screen="cooking" style="display: none;">
		<div class="wprm-cook-mode-instructions-container">
			<?php if ( count( $cook_mode_steps ) > 0 ) : ?>
				<?php
				$ingredients_flat = $recipe->ingredients_flat();
				foreach ( $cook_mode_steps as $step_index => $step_data ) :
					$instruction = $step_data['instruction'];
					$step_tips = isset( $step_data['tips'] ) ? $step_data['tips'] : array();
				?>
					<div class="wprm-cook-mode-instruction-step" data-step-index="<?php echo esc_attr( $step_index ); ?>" style="display: none;">
						<!-- Media (Image/Video) at top -->
						<?php
						$media_output = '';
						
						// Handle image
						if ( isset( $instruction['image'] ) && $instruction['image'] ) {
							// Use similar logic to instruction_image() - use 'large' size for cook mode
							$img = wp_get_attachment_image( $instruction['image'], 'large' );
							
							// Disable instruction image pinning if setting is enabled
							if ( WPRM_Settings::get( 'pinterest_nopin_instruction_image' ) ) {
								$img = str_ireplace( '<img ', '<img data-pin-nopin="true" ', $img );
							}
							
							// Clickable images (but not in Gutenberg Preview)
							if ( WPRM_Settings::get( 'instruction_image_clickable' ) && ! WPRM_Context::is_gutenberg_preview() ) {
								$settings_size = WPRM_Settings::get( 'clickable_image_size' );
								
								preg_match( '/^(\d+)x(\d+)$/i', $settings_size, $match );
								if ( ! empty( $match ) ) {
									$size = array( intval( $match[1] ), intval( $match[2] ) );
								} else {
									$size = $settings_size;
								}
								
								$clickable_image = wp_get_attachment_image_src( $instruction['image'], $size );
								$clickable_image_url = $clickable_image && isset( $clickable_image[0] ) ? $clickable_image[0] : '';
								if ( $clickable_image_url ) {
									$img = '<a href="' . esc_url( $clickable_image_url ) . '" aria-label="' . esc_attr__( 'Open larger version of the instruction image', 'wp-recipe-maker' ) . '">' . $img . '</a>';
								}
							}
							
							$media_output = $img;
						}
						// Handle video
						elseif ( isset( $instruction['video'] ) && isset( $instruction['video']['type'] ) && in_array( $instruction['video']['type'], array( 'upload', 'embed' ) ) ) {
							// Use similar logic to instruction_video()
							if ( 'upload' === $instruction['video']['type'] ) {
								$video_id = isset( $instruction['video']['id'] ) ? $instruction['video']['id'] : 0;
								
								if ( $video_id ) {
									$video_data = wp_get_attachment_metadata( $video_id );
									$video_url = wp_get_attachment_url( $video_id );
									
									if ( $video_url && $video_data ) {
										// Construct video shortcode
										$video_shortcode = '[video';
										$video_shortcode .= ' width="' . ( isset( $video_data['width'] ) ? $video_data['width'] : 640 ) . '"';
										$video_shortcode .= ' height="' . ( isset( $video_data['height'] ) ? $video_data['height'] : 360 ) . '"';
										
										if ( in_array( WPRM_Settings::get( 'video_autoplay' ), array( 'instruction', 'all' ) ) ) {
											$video_shortcode .= ' autoplay="true"';
										}
										if ( in_array( WPRM_Settings::get( 'video_loop' ), array( 'instruction', 'all' ) ) ) {
											$video_shortcode .= ' loop="true"';
										}
										
										$format = isset( $video_data['fileformat'] ) && $video_data['fileformat'] ? $video_data['fileformat'] : 'mp4';
										$video_shortcode .= ' ' . $format . '="' . esc_url( $video_url ) . '"';
										
										$thumb_size = array( isset( $video_data['width'] ) ? $video_data['width'] : 640, isset( $video_data['height'] ) ? $video_data['height'] : 360 );
										
										// Get thumb URL
										$image_id = get_post_thumbnail_id( $video_id );
										if ( $image_id ) {
											$thumb = wp_get_attachment_image_src( $image_id, $thumb_size );
											$thumb_url = $thumb && isset( $thumb[0] ) ? $thumb[0] : '';
											
											if ( $thumb_url ) {
												$video_shortcode .= ' poster="' . esc_url( $thumb_url ) . '"';
											}
										}
										
										$video_shortcode .= '][/video]';
										$media_output = do_shortcode( $video_shortcode );
									}
								}
							} elseif ( 'embed' === $instruction['video']['type'] ) {
								$video_embed = isset( $instruction['video']['embed'] ) ? $instruction['video']['embed'] : '';
								
								if ( $video_embed ) {
									// Check if it's a regular URL
									$url = filter_var( $video_embed, FILTER_SANITIZE_URL );
									
									if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
										global $wp_embed;
										
										if ( isset( $wp_embed ) ) {
											$media_output = $wp_embed->run_shortcode( '[embed]' . $url . '[/embed]' );
										}
									} else {
										$media_output = $video_embed;
									}
									
									$media_output = do_shortcode( $media_output );
								}
							}
						}
						
						if ( $media_output ) :
						?>
							<div class="wprm-cook-mode-step-media">
								<?php echo $media_output; ?>
							</div>
						<?php endif; ?>

						<!-- Associated Ingredients -->
						<?php if ( isset( $instruction['ingredients'] ) && is_array( $instruction['ingredients'] ) && count( $instruction['ingredients'] ) > 0 ) : ?>
							<?php
							$step_ingredients = array();
							foreach ( $instruction['ingredients'] as $ingredient_value ) {
								$ingredient_str = (string) $ingredient_value;
								$is_split = false;
								$parent_uid = null;
								$split_id = null;
								
								// Check if this is a split (format: "uid:splitId")
								if ( strpos( $ingredient_str, ':' ) !== false ) {
									$is_split = true;
									$parts = explode( ':', $ingredient_str, 2 );
									if ( count( $parts ) === 2 && is_numeric( $parts[0] ) && is_numeric( $parts[1] ) ) {
										$parent_uid = intval( $parts[0] );
										$split_id = intval( $parts[1] );
									} else {
										// Invalid split format, skip
										continue;
									}
								} else {
									// Regular ingredient UID
									$parent_uid = intval( $ingredient_value );
								}
								
								$index = array_search( $parent_uid, array_column( $ingredients_flat, 'uid' ) );
								if ( false !== $index && isset( $ingredients_flat[ $index ] ) && 'ingredient' === $ingredients_flat[ $index ]['type'] ) {
									$found_ingredient = $ingredients_flat[ $index ];
									
									// If it's a split, calculate amount from percentage
									if ( $is_split && isset( $found_ingredient['splits'] ) && is_array( $found_ingredient['splits'] ) ) {
										$found_split = null;
										foreach ( $found_ingredient['splits'] as $split ) {
											if ( isset( $split['id'] ) && intval( $split['id'] ) === $split_id ) {
												$found_split = $split;
												break;
											}
										}
										
										if ( $found_split && isset( $found_split['percentage'] ) ) {
											// Calculate split amount from parent amount and percentage
											$parent_amount = isset( $found_ingredient['amount'] ) ? $found_ingredient['amount'] : '';
											$parent_amount_parsed = WPRM_Recipe_Parser::parse_quantity( $parent_amount );
											
											$split_amount = '';
											if ( $parent_amount_parsed > 0 ) {
												$percentage = floatval( $found_split['percentage'] );
												$split_amount_parsed = ( $parent_amount_parsed * $percentage ) / 100;

												// Match the regular instructions output for split amounts, including fractions.
												$split_amount = WPRM_Recipe_Parser::format_quantity(
													$split_amount_parsed,
													WPRM_Settings::get( 'adjustable_servings_round_to_decimals' ),
													WPRM_Settings::get( 'fractions_enabled' ),
													true
												);
											}
											
											// Build split ingredient array - use calculated amount, parent's unit
											$split_ingredient = array(
												'uid' => $found_ingredient['uid'],
												'amount' => $split_amount,
												'unit' => isset( $found_ingredient['unit'] ) ? $found_ingredient['unit'] : '',
												'name' => isset( $found_ingredient['name'] ) ? $found_ingredient['name'] : '',
												'notes' => isset( $found_ingredient['notes'] ) ? $found_ingredient['notes'] : '',
												'id' => isset( $found_ingredient['id'] ) ? $found_ingredient['id'] : 0,
												'type' => 'ingredient',
												'_is_split' => true,
												'_split_id' => $split_id,
												'_split_percentage' => $percentage, // Store percentage for adjustable servings
											);
											$step_ingredients[] = $split_ingredient;
										} else {
											// Split not found, fall back to parent ingredient
											$step_ingredients[] = $found_ingredient;
										}
									} else {
										// Regular ingredient
										$step_ingredients[] = $found_ingredient;
									}
								}
							}
							?>
							<?php if ( count( $step_ingredients ) > 0 ) : ?>
								<div class="wprm-cook-mode-step-ingredients">
									<ul class="wprm-cook-mode-step-ingredients-list">
										<?php foreach ( $step_ingredients as $ingredient ) : ?>
											<?php
											// Maybe replace fractions in amount.
											if ( WPRM_Settings::get( 'automatic_amount_fraction_symbols' ) ) {
												$ingredient['amount'] = WPRM_Recipe_Parser::replace_any_fractions_with_symbol( $ingredient['amount'] );
											}
											?>
											<?php
											// Check if this is a split and store percentage
											$split_data_attr = '';
											if ( isset( $ingredient['_is_split'] ) && $ingredient['_is_split'] && isset( $ingredient['_split_percentage'] ) ) {
												$split_data_attr = ' data-split-percentage="' . esc_attr( $ingredient['_split_percentage'] ) . '" data-split-id="' . esc_attr( $ingredient['_split_id'] ) . '"';
											}

											$notes_data_attr = '';
											if ( $cook_mode_ingredient_notes_show ) {
												$notes_data_attr = ' data-notes-separator="' . esc_attr( $cook_mode_ingredient_notes_separator ) . '"';
											}
											?>
											<li class="wprm-cook-mode-step-ingredient wprm-recipe-instruction-ingredient-<?php echo esc_attr( $recipe->id() ); ?>-<?php echo esc_attr( $ingredient['uid'] ); ?>"<?php echo $split_data_attr . $notes_data_attr; ?>>
												<?php if ( $ingredient['amount'] ) : ?>
													<span class="wprm-cook-mode-step-ingredient-amount"><?php echo wp_kses_post( $ingredient['amount'] ); ?></span>
												<?php endif; ?>
												<?php if ( $ingredient['unit'] ) : ?>
													<span class="wprm-cook-mode-step-ingredient-unit"><?php echo wp_kses_post( $ingredient['unit'] ); ?></span>
												<?php endif; ?>
												<?php if ( $ingredient['name'] ) : ?>
													<span class="wprm-cook-mode-step-ingredient-name"><?php echo wp_kses_post( $ingredient['name'] ); ?></span>
												<?php endif; ?>
												<?php if ( $cook_mode_ingredient_notes_show && $ingredient['notes'] ) : ?>
													<span class="wprm-cook-mode-step-ingredient-notes wprm-cook-mode-step-ingredient-notes-separator-<?php echo esc_attr( $cook_mode_ingredient_notes_separator ); ?>"><?php echo esc_html( $cook_mode_ingredient_notes_before ); ?><?php echo wp_kses_post( $ingredient['notes'] ); ?><?php echo esc_html( $cook_mode_ingredient_notes_after ); ?></span>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<!-- Instruction Text -->
						<?php if ( isset( $instruction['text'] ) && $instruction['text'] ) : ?>
							<?php
							$text = $instruction['text'];
							// Clean paragraphs similar to shortcode
							$text = WPRM_Template_Shortcode::clean_paragraphs( $text );
							?>
							<div class="wprm-cook-mode-step-text">
								<div class="wprm-cook-mode-step-number"><?php echo esc_html( $step_index + 1 ); ?></div>
								<div class="wprm-cook-mode-step-text-content">
									<?php echo wp_kses_post( $text ); ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( count( $step_tips ) > 0 ) : ?>
							<div class="wprm-cook-mode-step-tips">
								<?php foreach ( $step_tips as $tip ) : ?>
									<?php
									$raw_tip_icon = isset( $tip['tip_icon'] ) && trim( $tip['tip_icon'] ) ? trim( $tip['tip_icon'] ) : '';
									$tip_icon = false;

									if ( '__none__' !== strtolower( $raw_tip_icon ) ) {
										$tip_icon_valid = false;
										$tip_icon_candidate = $raw_tip_icon;

										if ( $tip_icon_candidate && filter_var( $tip_icon_candidate, FILTER_VALIDATE_URL ) ) {
											$tip_icon_valid = true;
										} elseif ( $tip_icon_candidate ) {
											$tip_icon_candidate = sanitize_key( $tip_icon_candidate );
											$tip_icon_valid = in_array( $tip_icon_candidate, $tip_icon_ids, true );
										}

										if ( ! $tip_icon_valid ) {
											$tip_icon_candidate = 'lightbulb';
										}

										$tip_icon = $tip_icon_candidate;
									}

									$tip_accent = isset( $tip['tip_accent'] ) ? sanitize_hex_color( $tip['tip_accent'] ) : false;
									$tip_accent = $tip_accent ? $tip_accent : 'var(--wprm-cook-mode-primary)';
									$tip_icon_html = $tip_icon ? WPRM_Icon::get( $tip_icon, $tip_accent ) : '';

									$tip_text = isset( $tip['text'] ) ? $tip['text'] : '';
									$tip_text = WPRM_Template_Shortcode::clean_paragraphs( $tip_text );
									?>
									<div class="wprm-cook-mode-step-tip" style="--wprm-cook-mode-tip-accent: <?php echo esc_attr( $tip_accent ); ?>;">
										<?php if ( $tip_icon_html ) : ?>
											<span class="wprm-recipe-icon wprm-cook-mode-step-tip-icon" aria-hidden="true"><?php echo $tip_icon_html; ?></span>
										<?php endif; ?>
										<div class="wprm-cook-mode-step-tip-text"><?php echo wp_kses_post( $tip_text ); ?></div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No instructions found.', 'wp-recipe-maker-premium' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Thank You Screen -->
	<div class="wprm-cook-mode-screen wprm-cook-mode-screen-thank-you" data-screen="thank-you" style="display: none;">
		<div class="wprm-cook-mode-thank-you-content">
			<?php
			$closing_screen_type = WPRM_Settings::get( 'cook_mode_closing_screen' );
			if ( 'message_html' === $closing_screen_type ) {
				$closing_screen_content = WPRM_Settings::get( 'cook_mode_closing_screen_html' );
				echo $closing_screen_content;
			} else {
				// Default to 'message' type - rich textarea content may already contain HTML
				$closing_screen_content = WPRM_Settings::get( 'cook_mode_closing_screen_message' );
				// Rich textarea content - allow HTML formatting (may already include paragraph tags)
				if ( $closing_screen_content ) {
					echo wp_kses_post( $closing_screen_content );
				}
			}

			if ( WPRM_Settings::get( 'cook_mode_closing_screen_show_stars' ) ) {
				$star_color = WPRM_Settings::get( 'user_ratings_modal_star_color' );

				echo '<div class="wprm-cook-mode-thank-you-stars">';
				echo do_shortcode( '[wprm-recipe-rating icon_size="1.75em" icon_padding="0.1em" icon_color="' . esc_attr( $star_color ) . '" id="' . esc_attr( $recipe->id() ) . '"]' );
				echo '</div>';
			}
			?>
		</div>
	</div>
</div>
