<?php
/**
 * Handle the User Collections manage API.
 *
 * @link       https://bootstrapped.ventures
 * @since      5.9.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the User Collections manage API.
 *
 * @since      5.9.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Api_Manage_Users {

	/**
	 * Register actions and filters.
	 *
	 * @since    5.9.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    5.9.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/manage/user-collections', array(
				'callback' => array( __CLASS__, 'api_manage_user_collections' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			) );
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since    5.9.0
	 */
	public static function api_required_permissions() {
		return current_user_can( WPRM_Settings::get( 'features_manage_access' ) );
	}

	/**
	 * Handle manage saved collections call to the REST API.
	 *
	 * @since    5.9.0
	 * @param    WP_REST_Request $request Current request.
	 */
	public static function api_manage_user_collections( $request ) {
		// Parameters.
		$params = $request->get_params();

		$page = isset( $params['page'] ) ? intval( $params['page'] ) : 0;
		$page_size = isset( $params['pageSize'] ) ? intval( $params['pageSize'] ) : 25;
		$sorted = isset( $params['sorted'] ) ? $params['sorted'] : array( array( 'id' => 'id', 'desc' => true ) );
		$filtered = isset( $params['filtered'] ) ? $params['filtered'] : array();

		// Starting query args.
		$args = array (
			'number' => $page_size,
			'offset' => $page * $page_size,
			'meta_query' => array(),
		);

		// Order.
		$args['order'] = $sorted[0]['desc'] ? 'DESC' : 'ASC';
		switch( $sorted[0]['id'] ) {
			case 'display_name':
				$args['orderby'] = 'display_name';
				break;
			case 'user_email':
				$args['orderby'] = 'user_email';
				break;
			default:
			 	$args['orderby'] = 'ID';
		}

		// Filter.
		if ( $filtered ) {
			foreach ( $filtered as $filter ) {
				$value = trim( $filter['value'] );
				switch( $filter['id'] ) {
					case 'id':
						$args['search_columns'] = array( 'ID' );
						$args['search'] = $value;
						break;
					case 'display_name':
						$args['search_columns'] = array( 'display_name' );
						$args['search'] = '*' . $value . '*';
						break;
					case 'user_email':
						$args['search_columns'] = array( 'user_email' );
						$args['search'] = '*' . $value . '*';
						break;
					case 'collections':
						if ( 'all' !== $value ) {
							$compare = 'yes' === $value ? 'EXISTS' : 'NOT EXISTS';

							$args['meta_query'][] = array(
								'key' => 'wprm-recipe-collections',
								'compare' => $compare,
							);
						}
						break;
				}
			}
		}

		$query = new WP_User_Query($args);

		$users = $query->get_results();
		foreach ( $users as $user ) {
			$collections = get_user_meta( $user->ID, 'wprm-recipe-collections', true );

			if ( $collections ) {
				$user->collections = wp_list_pluck( $collections['user'], 'name' );
				$user->inbox = $collections['inbox']['nbrItems'];
				$user->items = array_sum( wp_list_pluck( $collections['user'], 'nbrItems' ) );
			} else {
				$user->collections = array();
				$user->inbox = 0;
				$user->items = 0;
			}
		}

		// Get total users.
		$users_count = count_users();

		$data = array(
			'rows' => array_values( $users ),
			'total' => $users_count['total_users'],
			'filtered' => $query->total_users,
			'pages' => ceil( $query->total_users / $page_size ),
		);

		return rest_ensure_response( $data );
	}
}

WPRMPRC_Api_Manage_Users::init();
