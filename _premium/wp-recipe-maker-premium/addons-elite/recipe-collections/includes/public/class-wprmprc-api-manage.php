<?php
/**
 * Handle the Recipe Collections manage API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.0.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the Recipe Collections manage API.
 *
 * @since      5.0.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Api_Manage {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.0.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/manage/saved-collections', array(
				'callback' => array( __CLASS__, 'api_manage_saved_collections' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );

			register_rest_route( 'wp-recipe-maker/v1', '/manage/collection/bulk', array(
				'callback' => array( __CLASS__, 'api_manage_collection_bulk_edit' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since    5.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle manage saved collections call to the REST API.
	 *
	 * @since    5.0.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_manage_saved_collections( $request ) {
		// Parameters.
		$params = $request->get_params();

		$page = isset( $params['page'] ) ? intval( $params['page'] ) : 0;
		$page_size = isset( $params['pageSize'] ) ? intval( $params['pageSize'] ) : 25;
		$sorted = isset( $params['sorted'] ) ? $params['sorted'] : array( array( 'id' => 'id', 'desc' => true ) );
		$filtered = isset( $params['filtered'] ) ? $params['filtered'] : array();

		// Starting query args.
		$args = array(
			'post_type' => WPRMPRC_POST_TYPE,
			'post_status' => 'any',
			'posts_per_page' => $page_size,
			'offset' => $page * $page_size,
			'meta_query' => array(
				'relation' => 'AND',
			),
			'tax_query' => array(),
		);

		// Order.
		$args['order'] = $sorted[0]['desc'] ? 'DESC' : 'ASC';
		switch( $sorted[0]['id'] ) {
			case 'date':
				$args['orderby'] = 'date';
				break;
			case 'name':
				$args['orderby'] = 'title';
				break;
			case 'description':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = 'wprm_description';
				break;
			case 'category':
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = 'wprm_category';
				break;
			case 'order':
				// Make sure all values are included.
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key' => 'wprm_order',
						'compare' => 'EXISTS'
					),
					array(
						'key' => 'wprm_order',
						'compare' => 'NOT EXISTS'
					)
				);
				$args['orderby'] = 'meta_value_num title';
				break;
			case 'nbrItems':
				$args['orderby'] = 'meta_value_num';
				$args['meta_key'] = 'wprm_nbr_items';
				break;
			default:
			 	$args['orderby'] = 'ID';
		}

		// Filter.
		if ( $filtered ) {
			foreach ( $filtered as $filter ) {
				$value = $filter['value'];
				switch( $filter['id'] ) {
					case 'id':
						$args['wprm_search_id'] = $value;
						break;
					case 'date':
						$args['wprm_search_date'] = $value;
						break;
					case 'name':
						$args['wprm_search_title'] = $value;
						break;
					case 'description':
						if ( '' !== $value ) {
							$args['meta_query'][] = array(
								'key' => 'wprm_description',
								'compare' => 'LIKE',
								'value' => $value,
							);
						}
						break;
					case 'category':
						if ( '' !== $value ) {
							$args['meta_query'][] = array(
								'key' => 'wprm_category',
								'compare' => 'LIKE',
								'value' => $value,
							);
						}
						break;
					case 'nbrItems':
						if ( '' !== $value ) {
							$args['meta_query'][] = array(
								'key' => 'wprm_nbr_items',
								'compare' => 'LIKE',
								'value' => $value,
							);
						}
						break;
				}
			}
		}

		add_filter( 'posts_where', array( __CLASS__, 'api_manage_saved_collections_query_where' ), 10, 2 );
		$query = new WP_Query( $args );
		remove_filter( 'posts_where', array( __CLASS__, 'api_manage_saved_collections_query_where' ), 10, 2 );

		$collections = array();
		$posts = $query->posts;
		foreach ( $posts as $post ) {
			$collection = WPRMPRC_Manager::get_collection( $post );

			if ( ! $collection ) {
				continue;
			}

			$collections[] = $collection->get_data_manage();
		}

		// Got total number of collections.
		$total = (array) wp_count_posts( WPRMPRC_POST_TYPE );
		unset( $total['trash'] );

		$data = array(
			'rows' => array_values( $collections ),
			'total' => array_sum( $total ),
			'filtered' => intval( $query->found_posts ),
			'pages' => ceil( $query->found_posts / $page_size ),
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Filter the where saved collections query.
	 *
	 * @since    5.0.0
	 */
	public static function api_manage_saved_collections_query_where( $where, $wp_query ) {
		global $wpdb;

		$id_search = $wp_query->get( 'wprm_search_id' );
		if ( $id_search ) {
			$where .= ' AND ' . $wpdb->posts . '.ID LIKE \'%' . esc_sql( $wpdb->esc_like( $id_search ) ) . '%\'';
		}

		$date_search = $wp_query->get( 'wprm_search_date' );
		if ( $date_search ) {
			$where .= ' AND DATE_FORMAT(' . $wpdb->posts . '.post_date, \'%Y-%m-%d %T\') LIKE \'%' . esc_sql( $wpdb->esc_like( $date_search ) ) . '%\'';
		}

		$title_search = $wp_query->get( 'wprm_search_title' );
		if ( $title_search ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $title_search ) ) . '%\'';
		}

		return $where;
	}

	/**
	 * Handle collection bulk edit call to the REST API.
	 *
	 * @since    8.1.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_manage_collection_bulk_edit( $request ) {
		// Parameters.
		$params = $request->get_params();

		$ids = isset( $params['ids'] ) ? array_map( 'intval', $params['ids'] ) : array();
		$action = isset( $params['action'] ) ? $params['action'] : false;

		if ( $ids && $action && $action['type'] ) {
			foreach ( $ids as $id ) {
				switch ( $action['type'] ) {
					case 'reload':
						$collection = WPRMPRC_Manager::get_collection( $id );

						if ( $collection ) {
							$collection->reload();
						}
						break;
					case 'delete':
						$post = get_post( $id );

						if ( WPRMPRC_POST_TYPE === $post->post_type && current_user_can( 'delete_post', $id ) ) {
							wp_delete_post( $id );
						}
						break;
				}
			}

			return rest_ensure_response( true );
		}

		return rest_ensure_response( false );
	}
}

WPRMPRC_Api_Manage::init();
