<?php
/**
 * Represents a recipe collection.
 *
 * @link       https://bootstrapped.ventures
 * @since      4.1.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Represents a recipe collection.
 *
 * @since      4.1.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Collection {
/**
	 * WP_Post object associated with this collection post type.
	 *
	 * @since	4.1.0
	 * @access	private
	 * @var		object	$post WP_Post object of this collection post type.
	 */
	private $post;

	/**
	 * Metadata associated with this collection post type.
	 *
	 * @since	4.1.0
	 * @access	private
	 * @var		array $meta Collection metadata.
	 */
	private $meta = false;

	/**
	 * Get new collection object from associated post.
	 *
	 * @since	4.1.0
	 * @param	object $post WP_Post object for this collection post type.
	 */
	public function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * Get collection data.
	 *
	 * @since	4.1.0
	 * @param	mixed $force_servings Optionally force to a specific number of servings.
	 */
	public function get_data( $force_servings = false ) {
		$collection = array();

		// Technical Fields.
		$collection['id'] = $this->id();

		// Collection Fields.
		$collection['name'] = $this->name();
		$collection['category'] = $this->category();
		$collection['description'] = $this->description();
		$collection['default'] = $this->is_default();
		$collection['push'] = $this->is_push();
		$collection['fixed'] = $this->is_fixed();
		$collection['template'] = $this->is_template();
		$collection['quick_add'] = $this->is_quick_add();
		$collection['nbrItems'] = $this->nbr_items();
		$collection['columns'] = $this->columns();
		$collection['groups'] = $this->groups();
		$collection['items'] = $this->items( $force_servings );
		$collection['saveLink'] = $this->save_link();
		$collection['order'] = $this->order();

		// Get shopping list for currently logged in user.
		$current_user = get_current_user_id();
		
		if ( $current_user ) {
			$collection['shoppingList'] = WPRMPRC_Shopping_List::get_uid_by_collection_and_user( $current_user, $this->id(), 'saved' );
		} else {
			$collection['shoppingList'] = false;
		}

		return $collection;
	}

	/**
	 * Get collection data for the manage page.
	 *
	 * @since	5.0.0
	 */
	public function get_data_manage() {
		$collection = $this->get_data();

		$collection['date'] = $this->date();

		return $collection;
	}

	/**
	 * Get metadata value.
	 *
	 * @since	4.1.0
	 * @param	mixed $field Metadata field to retrieve.
	 * @param	mixed $default Default to return if metadata is not set.
	 */
	public function meta( $field, $default ) {
		if ( ! $this->meta ) {
			$this->meta = get_post_custom( $this->id() );
		}

		if ( isset( $this->meta[ $field ] ) ) {
			return $this->meta[ $field ][0];
		}

		return $default;
	}

	/**
	 * Try to unserialize as best as possible.
	 *
	 * @since	4.1.0
	 * @param	mixed $maybe_serialized Potentially serialized data.
	 */
	public function unserialize( $maybe_serialized ) {
		$unserialized = @maybe_unserialize( $maybe_serialized );

		if ( false === $unserialized ) {
			$maybe_serialized = preg_replace('/\s+/', ' ', $maybe_serialized );
			$unserialized = unserialize( preg_replace_callback( '!s:(\d+):"(.*?)";!', array( $this, 'regex_replace_serialize' ), $maybe_serialized ) );
		}

		return $unserialized;
	}

	/**
	 * Callback for regex to fix serialize issues.
	 *
	 * @since	4.1.0
	 * @param	mixed $match Regex match.
	 */
	public function regex_replace_serialize( $match ) {
		return ( $match[1] == strlen( $match[2] ) ) ? $match[0] : 's:' . strlen( $match[2] ) . ':"' . $match[2] . '";';
	}

	/**
	 * Get the collection ID.
	 *
	 * @since	4.1.0
	 */
	public function id() {
		return $this->post->ID;
	}

	/**
	 * Get the collection date.
	 *
	 * @since	5.0.0
	 */
	public function date() {
		return $this->post->post_date;
	}

	/**
	 * Get the collection name.
	 *
	 * @since	4.1.0
	 */
	public function name() {
		return $this->post->post_title;
	}

	/**
	 * Get the collection description.
	 *
	 * @since	9.6.0
	 */
	public function category() {
		return $this->meta( 'wprm_category', '' );
	}

	/**
	 * Get the collection description.
	 *
	 * @since	8.1.0
	 */
	public function description() {
		return $this->meta( 'wprm_description', '' );
	}

	/**
	 * Get the collection default.
	 *
	 * @since	4.1.0
	 */
	public function is_default() {
		return (bool) $this->meta( 'wprm_default', false );
	}

	/**
	 * Get the collection push.
	 *
	 * @since	8.1.0
	 */
	public function is_push() {
		return (bool) $this->meta( 'wprm_push', false );
	}

	/**
	 * Get the collection fixed.
	 *
	 * @since	8.1.0
	 */
	public function is_fixed() {
		return (bool) $this->meta( 'wprm_fixed', false );
	}

	/**
	 * Get the collection template.
	 *
	 * @since	8.1.0
	 */
	public function is_template() {
		return (bool) $this->meta( 'wprm_template', false );
	}

	/**
	 * Get the collection quick add.
	 *
	 * @since	8.1.0
	 */
	public function is_quick_add() {
		return (bool) $this->meta( 'wprm_quick_add', false );
	}

	/**
	 * Get the collection number of items.
	 *
	 * @since	4.1.0
	 */
	public function nbr_items() {
		return $this->meta( 'wprm_nbr_items', 0 );
	}

	/**
	 * Get the save link for this collection.
	 *
	 * @since	6.6.0
	 */
	public function save_link() {
		$collections_url = str_replace( '/#/', '', WPRM_Settings::get( 'recipe_collections_link' ) );

		if ( $collections_url ) {
			
			require_once( WPRM_DIR . 'vendor/hashids/lib/Hashids/HashGenerator.php' );
			require_once( WPRM_DIR . 'vendor/hashids/lib/Hashids/Hashids.php' );
			$hashids = new Hashids\Hashids('wp-recipe-maker');

			$hash = $hashids->encode( $this->id() );

			if ( false !== strpos( $collections_url, '?' ) ) {
				return $collections_url . '&save=' . $hash;
			} else {
				return $collections_url . '?save=' . $hash;
			}
		}

		return false;
	}

	/**
	 * Get the collection order.
	 *
	 * @since	8.3.0
	 */
	public function order() {
		return $this->meta( 'wprm_order', 0 );
	}

	/**
	 * Get the collection columns.
	 *
	 * @since	4.1.0
	 */
	public function columns() {
		return self::unserialize(  $this->meta( 'wprm_columns', array(
			array(
				'id' => 0,
				'name' => __( 'Recipes', 'wp-recipe-maker-premium' ),
			),
		) ) );
	}

	/**
	 * Get the collection groups.
	 *
	 * @since	4.1.0
	 */
	public function groups() {
		return self::unserialize(  $this->meta( 'wprm_groups', array(
			array(
				'id' => 0,
				'name' => '',
			),
		) ) );
	}

	/**
	 * Get the collection items.
	 *
	 * @since	4.1.0
	 * @param	mixed $force_servings Optionally force to a specific number of servings.
	 */
	public function items( $force_servings = false ) {
		$items = self::unserialize( $this->meta( 'wprm_items', array(
			'0-0' => array(),
		) ) );

		foreach ( $items as $column_group => $column_group_items ) {
			foreach ( $column_group_items as $index => $item ) {
				$items[ $column_group ][ $index ] = WPRMPRC_Manager::maybe_backfill_recipe_item_servings_data( $item );

				if ( false !== $force_servings && 0 <= $force_servings ) {
					$items[ $column_group ][ $index ]['servings'] = $force_servings;
				}
			}
		}

		return $items;
	}

	/**
	 * Get the collection shopping lists.
	 *
	 * @since	6.3.0
	 */
	public function shopping_lists() {
		return self::unserialize( $this->meta( 'wprm_shopping_lists', array(
			'0-0' => array(),
		) ) );
	}

	/**
	 * Reload collection items.
	 *
	 * @since	8.1.0
	 */
	public function reload() {
		$items = $this->items();

		foreach ( $items as $column_group => $column_group_items ) {
			foreach ( $column_group_items as $index => $item ) {
				if ( isset( $item['type'] ) ) {
					switch ( $item['type'] ) {
						case 'recipe':
							$recipe_id = intval( $item['recipeId'] );
							$recipe = WPRM_Recipe_Manager::get_recipe( $recipe_id );
		
							if ( $recipe ) {
								$data = WPRMPRC_Manager::get_collections_data_for_recipe( $recipe );
								unset( $data['servings'] );
								$items[ $column_group ][ $index ] = array_merge( $items[ $column_group ][ $index ], $data );
							}
							break;
						case 'nutrition-ingredient':
							$ingredient_id = intval( $item['ingredientId'] );
							$ingredient = get_term( $ingredient_id, 'wprm_nutrition_ingredient' );

							if ( $ingredient && ! is_wp_error( $ingredient ) ) {
								$data = WPRMPRC_Manager::get_collections_data_for_ingredient( $ingredient );
								unset( $data['servings'] );
								$items[ $column_group ][ $index ] = array_merge( $items[ $column_group ][ $index ], $data );
							}
							break;
					}
				}
			}
		}

		// Update and invalidate.
		update_post_meta( $this->id(), 'wprm_items', $items );
		WPRMPRC_Manager::invalidate_collection( $this->id() );
	}
}
