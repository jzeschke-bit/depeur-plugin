<?php
/**
 * Handles saved nutrition ingredients.
 *
 * @link       https://bootstrapped.ventures
 * @since      1.1.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 */

/**
 * Handles saved nutrition ingredients.
 *
 * @since      1.1.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/addons-pro/advanced-nutrition/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPN_Ingredient_Manager {

	/**
	 * Register actions and filters.
	 *
	 * @since    1.1.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'ingredient_taxonomy' ), 2 );
		add_action( 'edited_wprm_nutrition_ingredient', array( __CLASS__, 'track_ingredient_modification' ), 10, 2 );
		add_action( 'created_wprm_nutrition_ingredient', array( __CLASS__, 'track_ingredient_modification' ), 10, 2 );
	}

	/**
	 * Register the ingredient taxonomy.
	 *
	 * @since    1.1.0
	 */
	public static function ingredient_taxonomy() {
		$args = apply_filters( 'wprm_nutrition_ingredient_taxonomy', array(
			'labels'            => array(
				'name'               => _x( 'Nutrition Ingredients', 'taxonomy general name', 'wp-recipe-maker' ),
				'singular_name'      => _x( 'Nutrition Ingredient', 'taxonomy singular name', 'wp-recipe-maker' ),
			),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui' 			=> false,
			'query_var'         => false,
			'rewrite'           => false,
			'show_in_rest'      => true,
		) );

		$args = apply_filters( 'wprm_register_taxonomy_args', $args, 'wprm_nutrition_ingredient' );

		register_taxonomy( 'wprm_nutrition_ingredient', WPRM_POST_TYPE, $args );
		register_taxonomy_for_object_type( 'wprm_nutrition_ingredient', WPRM_POST_TYPE );
	}

	/**
	 * Save ingredient.
	 *
	 * @since	5.0.0
	 */
	public static function save_ingredient( $id, $amount, $unit, $name, $nutrients ) {
		$id = intval( $id );
		$name = sanitize_text_field( $name );
		$unique_name = $name;
		$i = 2;

		if ( ! $id ) {
			do {
				$term = wp_insert_term( $unique_name, 'wprm_nutrition_ingredient' );
				$unique_name = $name . ' (' . $i . ')';
				$i++;
			} while ( is_wp_error( $term ) );

			$id = $term['term_id'];
		} else {
			$existing_id = term_exists( $unique_name, 'wprm_nutrition_ingredient' );

			while ( $existing_id && $id !== intval( $existing_id['term_id'] ) ) {
				$unique_name = $name . ' (' . $i . ')';
				$i++;
				$existing_id = term_exists( $unique_name, 'wprm_nutrition_ingredient' );
			}

			wp_update_term( $id, 'wprm_nutrition_ingredient', array(
				'name' => $unique_name,
			) );
		}

		$nutrition = array(
			'amount' => sanitize_text_field( $amount ),
			'unit' => sanitize_text_field( $unit ),
			'nutrients' => $nutrients,
		);

		update_term_meta( $id, 'wprpn_nutrition', $nutrition );
		
		// Track modification time for recipe collections feature.
		update_term_meta( $id, 'wprm_modified_at', time() );

		return $id;
	}

	/**
	 * Search for saved ingredients by keyword.
	 *
	 * @since	5.0.0
	 */
	public static function search_saved_ingredients( $search ) {
		$ingredients = array();

		$args = array(
			'taxonomy' => 'wprm_nutrition_ingredient',
			'hide_empty' => false,
			'fields' => 'id=>name',
			'name__like' => $search,
		);

		$terms = get_terms( $args );

		foreach ( $terms as $id => $name ) {
			$ingredients[] = array(
				'id' => $id,
				'text' => $name,
				'nutrition' => self::get_nutrition( $id ),
			);
		}

		return $ingredients;
	}

	/**
	 * Track ingredient modification time for recipe collections feature.
	 *
	 * @since	8.1.0
	 * @param	int    $term_id  Term ID.
	 * @param	int    $tt_id    Term taxonomy ID.
	 */
	public static function track_ingredient_modification( $term_id, $tt_id ) {
		// Update modification time for recipe collections feature.
		update_term_meta( $term_id, 'wprm_modified_at', time() );
	}

	/**
	 * Get nutrition.
	 *
	 * @since	5.0.0
	 */
	public static function get_nutrition( $id ) {
		$nutrition = get_term_meta( $id, 'wprpn_nutrition', true );

		if ( $nutrition ) {
			// Fix dash to underscore. E.g. "saturated-fat" to "saturated_fat".
			$fixed_nutrients = array();

			foreach ( $nutrition['nutrients'] as $nutrient => $value ) {
				$fixed_nutrient = str_replace( '-', '_', $nutrient );
				$fixed_nutrients[ $fixed_nutrient ] = $value;
			}
	
			$nutrition['nutrients'] = apply_filters( 'wprm_nutrition_ingredient_nutrition', $fixed_nutrients );
		}

		return $nutrition;
	}

	/**
	 * Get saved ingredient by ID.
	 *
	 * @since	5.0.0
	 */
	public static function get_ingredient( $id ) {
		$term = get_term( $id, 'wprm_nutrition_ingredient' );

		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}
		
		return array(
			'id' => $id,
			'name' => $term->name,
			'nutrition' => self::get_nutrition( $id ),
		);
	}
}

WPRMPN_Ingredient_Manager::init();
