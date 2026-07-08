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
		<div class="wprmprc-shopping-list-collection">
			<div class="wprmprc-shopping-list-collection-header">
				<div class="wprmprc-shopping-list-collection-name">
					<?php
					if ( isset( $collection['name'] ) && $collection['name'] ) {
						echo $collection['name'];
					} else {
						_e( 'Collection', 'wp-recipe-maker-premium' );
					}
					?>
				</div>
			</div>
			<?php foreach ( $collection['columns'] as $column ) :
				if ( isset( $column['inShoppingList'] ) ) {
					$in_shopping_list = $column['inShoppingList'];
				} else {
					$in_shopping_list = 1 === count( $collection['columns'] );
				}

				$total_column_items = 0;
				foreach ( $collection['groups'] as $group ) {
					$group_items = isset( $collection['items'][ $column['id'] . '-' . $group['id'] ] ) ? $collection['items'][ $column['id'] . '-' . $group['id'] ] : array();
					$total_column_items += count( $group_items );
				}

				if ( $in_shopping_list && 0 < $total_column_items ) :
			?>
			<div class="wprmprc-shopping-list-column">
				<?php if ( $column['name'] ) : ?>
				<div class="wprmprc-shopping-list-column-header">
					<div class="wprmprc-shopping-list-column-name"><?php echo $column['name']; ?></div>
				</div>
				<?php endif; ?>
				<div class="wprmprc-shopping-list-column-items">
					<?php foreach ( $collection['groups'] as $group ) :
						$group_items = isset( $collection['items'][ $column['id'] . '-' . $group['id'] ] ) ? $collection['items'][ $column['id'] . '-' . $group['id'] ] : array();

						foreach ( $group_items as $item ) :
							// Classes.
							$classes = array(
								'wprmprc-shopping-list-item',
								'wprmprc-shopping-list-item-' . $item['type'],
							);

							if ( 0 >= $item['servings'] ) { 
								$classes[] = 'wprmprc-shopping-list-item-unused';
							}

							if ( isset( $item['color'] ) ) {
								$classes[] = 'wprmprc-shopping-list-item-color-' . $item['color'];
							}

							// Text.
							$text = $item['name'];

							if ( ! $text && 'ingredient' === $item['type'] ) {
								$text = '';
								foreach ( $item['ingredients'] as $index => $ingredient ) {
									if ( 0 < $index ) { $text .= '<br/>'; }
									if ( $ingredient['amount'] ) { $text .= $ingredient['amount'] . ' '; }
									if ( $ingredient['unit'] ) { $text .= $ingredient['unit'] . ' '; }
									if ( $ingredient['name'] ) { $text .= $ingredient['name']; }

									$text = trim( $text );
								}
							} elseif ( 'nutrition-ingredient' === $item['type'] ) {
								$prefix = trim( $item['amount'] . ' ' . $item['unit'] );
								$text = $prefix . ' ' . $text;
							}
						?>
						<div class="<?php echo implode( ' ', $classes ); ?>">
							<?php if ( 'note' !== $item['type'] ) : ?>
								<div class="wprmprc-shopping-list-item-servings-adjust">
									<div class="wprmprc-shopping-list-item-servings-adjust-servings-container">
										<div class="wprmprc-shopping-list-item-servings-adjust-servings"><?php echo $item['servings']; ?></div>
										<?php if ( isset( $item['servingsUnit'] ) && $item['servingsUnit'] ) : ?>
										<div class="wprmprc-shopping-list-item-servings-adjust-servings-unit"><?php echo $item['servingsUnit']; ?></div>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
							<div class="wprmprc-shopping-list-item-details">
								<div class="wprmprc-shopping-list-item-name"><?php echo $text; ?></div>
								<?php if ( isset( $item['image'] ) && $item['image'] ) : ?>
									<div class="wprmprc-shopping-list-item-image">
										<img class="wprmprc-shopping-list-item-image" width="50" src="<?php echo $item['image']; ?>" />
									</div>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif;
			endforeach; ?>
		</div>
	</div>
</div>