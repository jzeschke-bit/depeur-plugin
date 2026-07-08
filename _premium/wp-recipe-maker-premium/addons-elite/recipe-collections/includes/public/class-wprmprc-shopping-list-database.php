<?php
/**
 * Handle the shopping list database.
 *
 * @link       https://bootstrapped.ventures
 * @since      6.3.0
 *
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 */

/**
 * Handle the shopping list database.
 *
 * @since      6.3.0
 * @package    WP_Recipe_Maker_Premium/addons-elite/recipe-collections
 * @subpackage WP_Recipe_Maker_Premium/addons-elite/recipe-collections/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMPRC_Shopping_List_Database {
/**
	 * Current version of the rating database structure.
	 *
	 * @since    6.3.0
	 * @access   private
	 * @var      mixed $database_version Current version of the rating database structure.
	 */
	private static $database_version = '1.2';

	/**
	 * Register actions and filters.
	 *
	 * @since    6.3.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'check_database_version' ) );
	}

	/**
	 * Check if the correct database version is present.
	 *
	 * @since    6.3.0
	 */
	public static function check_database_version() {
		$current_version = get_option( 'wprm_shopping_list_db_version', '0.0' );

		if ( version_compare( $current_version, self::$database_version ) < 0 ) {
			self::update_database( $current_version );
		}
	}

	/**
	 * Create or update the shopping list database.
	 *
	 * @since    6.3.0
	 * @param    mixed $from Database version to update from.
	 */
	public static function update_database( $from ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		`created` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		`updated` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		`uid` varchar(64) NOT NULL,
		`user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		`collection_id` bigint(20) unsigned NOT NULL,
		`collection_type` varchar(20) NOT NULL,
		`collection` longtext NULL,
		`groups` longtext NULL,
		`meta` longtext NULL,
		PRIMARY KEY (id),
		KEY created (created),
		KEY updated (updated),
		KEY uid (uid),
		KEY collection_id (collection_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( 'wprm_shopping_list_db_version', self::$database_version );
	}

	/**
	 * Get the name of the rating database table.
	 *
	 * @since    6.3.0
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wprm_shopping_list';
	}

	/**
	 * Get a shopping list from the database.
	 *
	 * @since    6.3.0
	 * @param    int 	$uid UID of the shopping list to get.
	 */
	public static function get( $uid ) {
		$uid = sanitize_key( $uid );
		
		global $wpdb;
		$table_name = self::get_table_name();

		return $wpdb->get_row( 'SELECT * FROM ' . $table_name . ' WHERE uid = "' . $uid . '"', ARRAY_A );
	}

	/**
	 * Get a shopping list from the database by collection and user.
	 *
	 * @since    6.3.0
	 * @param    int $user_id			ID of the user.
	 * @param    int $collection_id		ID of the collection.
	 * @param    mixed $collection_type	Type of the collection.
	 */
	public static function get_by_collection_and_user( $user_id, $collection_id, $collection_type ) {
		$user_id = intval( $user_id );
		$collection_id = intval( $collection_id );
		$collection_type = sanitize_key( $collection_type );
		
		global $wpdb;
		$table_name = self::get_table_name();

		$query = $wpdb->prepare( 'SELECT * FROM ' . $table_name . ' WHERE user_id = %d AND collection_id = %d AND collection_type = %s ORDER BY updated DESC', array( $user_id, $collection_id, $collection_type ) );
		return $wpdb->get_row( $query, ARRAY_A );
	}

	/**
	 * Create a new shopping list in the database.
	 *
	 * @since    6.3.0
	 * @param    mixed $unsanitized_data Shopping list to create.
	 */
	public static function create( $unsanitized_data ) {
		global $wpdb;
		$table_name = self::get_table_name();

		$meta = isset( $unsanitized_data['meta'] ) ? $unsanitized_data['meta'] : array();

		$data = array(
			'created' => current_time( 'mysql' ),
			'updated' => current_time( 'mysql' ),
			'user_id' => get_current_user_id(),
			'collection_id' => isset( $unsanitized_data['collection_id'] ) ? intval( $unsanitized_data['collection_id'] ) : 0,
			'collection_type' => isset( $unsanitized_data['collection_type'] ) ? sanitize_key( $unsanitized_data['collection_type'] ) : '',
			'collection' => maybe_serialize( $unsanitized_data['collection'] ),
			'groups' => maybe_serialize( $unsanitized_data['groups'] ),
			'meta' => maybe_serialize( $meta ),
		);

		// Get UID.
		do {
			$uid = bin2hex( openssl_random_pseudo_bytes( 10 ) );
			$existing_row = $wpdb->get_row( 'SELECT * FROM ' . $table_name . ' WHERE uid = "' . $uid . '"' );
		} while ( is_object( $existing_row ) );

		$data['uid'] = $uid;

		// Insert into DB.
		$wpdb->insert( $table_name, $data );

		// Remove old shopping lists.
		self::delete_old();

		return $uid;
	}

	/**
	 * Update a shopping list in the database.
	 *
	 * @since    6.3.0
	 * @param    int 	$id ID of the shopping list to update.
	 * @param    mixed 	$unsanitized_data New shopping list.
	 */
	public static function update( $id, $unsanitized_data ) {
		$id = intval( $id );
		$data = array(
			'updated' => current_time( 'mysql' ),
		);

		if ( isset( $unsanitized_data['groups'] ) ) {
			$data['groups'] = maybe_serialize( $unsanitized_data['groups'] );
		}

		if ( isset( $unsanitized_data['meta'] ) ) {
			$data['meta'] = maybe_serialize( $unsanitized_data['meta'] );
		}

		global $wpdb;
		$table_name = self::get_table_name();

		return $wpdb->update( $table_name, $data, array( 'id' => $id ) );
	}

	/**
	 * Delete one or more shopping lists.
	 *
	 * @since    6.3.0
	 * @param    array $ids Shopping list IDs to delete.
	 */
	public static function delete( $ids ) {
		global $wpdb;
		$table_name = self::get_table_name();

		// If single int is passed along, make array.
		$ids = is_array( $ids ) ? $ids : array( $ids );
		$ids = implode( ',', array_map( 'intval', $ids ) );
		$wpdb->query( 'DELETE FROM ' . $table_name . ' WHERE id IN (' . $ids . ')' );
	}

	/**
	 * Delete old shopping lists.
	 *
	 * @since    6.3.0
	 */
	public static function delete_old() {
		global $wpdb;
		$table_name = self::get_table_name();

		$nbr_days = WPRM_Settings::get( 'recipe_collections_shopping_list_remove' );
		$nbr_days = max( 7, intval( $nbr_days ) ); // Use 7 days as a minimum.

		$query = $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE updated < NOW() - INTERVAL %d DAY', array( $nbr_days ) );
		$wpdb->query( $query );
	}
}
WPRMPRC_Shopping_List_Database::init();
