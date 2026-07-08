<?php
/**
 * Template for the shopping list print page.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.3.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/templates/print
 */

?>
<div class="wrap wprm-recipe-collections-layout-<?php echo esc_attr( WPRM_Settings::get( 'recipe_collections_appearance_layout' ) ); ?>">
	<div class="wprmprc-shopping-list">
		<div class="wprmprc-shopping-list-list">
			<div class="wprmprc-shopping-list-list-header">
				<div class="wprmprc-shopping-list-list-name">
					<?php _e( 'Shopping List', 'wp-recipe-maker-premium' ); ?>
				</div>
			</div>
			<div class="wprmprc-shopping-list-list-ingredients">
				<?php foreach( $shopping_list['groups'] as $group ) : ?>
				<div class="wprmprc-shopping-list-list-ingredient-group-container">
					<?php if ( $group['name'] ) : ?>
					<div class="wprmprc-shopping-list-list-ingredient-group"><?php echo $group['name']; ?></div>
					<?php endif; ?>
					<div class="wprmprc-shopping-list-list-ingredient-group-ingredients">
						<?php foreach( $group['ingredients'] as $ingredient ) : ?>
						<div id="wprmprc-checkbox-container-<?php echo $ingredient['id']; ?>" class="wprmprc-shopping-list-list-ingredient<?php if ( $ingredient['checked'] ) { echo ' wprmprc-shopping-list-list-ingredient-checked'; } ?>">
							<div class="wprmprc-shopping-list-list-ingredient-name-container">
								<input
									id="wprmprc-shopping-list-list-ingredient-<?php echo $ingredient['id']; ?>"
									class="wprmprc-checkbox"
									type="checkbox"
									onChange="(function (e) {
										document.getElementById('wprmprc-checkbox-container-<?php echo $ingredient['id']; ?>').classList.toggle('wprmprc-shopping-list-list-ingredient-checked');
									})()";
									<?php echo $ingredient['checked'] ? 'checked="checked"' : ''; ?>
								/>
								<label for="wprmprc-shopping-list-list-ingredient-<?php echo $ingredient['id']; ?>"><?php echo $ingredient['name']; ?></label>
							</div>
							<div class="wprmprc-shopping-list-list-ingredient-variations">
								<?php foreach( $ingredient['variations'] as $variation ) : 
									$variation_amount = isset( $variation['amount'] ) ? $variation['amount'] : '';
									$variation_unit = isset( $variation['unit'] ) ? $variation['unit'] : '';

									$display = isset( $variation['display'] ) ? $variation['display'] : $variation_amount . ' ' . $variation_unit;
									$display = trim( $display );

									if ( $display ) :
								?>
								<div class="wprmprc-shopping-list-list-ingredient-variation">
									<?php echo $display; ?>
								</div>
								<?php endif;
								endforeach ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>