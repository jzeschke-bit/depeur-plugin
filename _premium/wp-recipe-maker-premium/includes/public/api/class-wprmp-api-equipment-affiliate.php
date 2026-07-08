<?php
/**
 * Handle the equipment affiliate API.
 *
 * @link       https://bootstrapped.ventures
 * @since      8.0.0
 *
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 */

/**
 * Handle the equipment affiliate API.
 *
 * @since      8.0.0
 * @package    WP_Recipe_Maker_Premium
 * @subpackage WP_Recipe_Maker_Premium/includes/public/api
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPRMP_Api_Equipment_Affiliate {

	/**
	 * Register actions and filters.
	 *
	 * @since    8.0.0
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'api_register_data' ) );
	}

	/**
	 * Register data for the REST API.
	 *
	 * @since    8.0.0
	 */
	public static function api_register_data() {
		if ( function_exists( 'register_rest_field' ) ) { // Prevent issue with Jetpack.
			register_rest_route( 'wp-recipe-maker/v1', '/equipment-affiliate', array(
				'callback' => array( __CLASS__, 'api_get_equipment_affiliate' ),
				'methods' => 'POST',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
			register_rest_route( 'wp-recipe-maker/v1', '/equipment-affiliate', array(
				'callback' => array( __CLASS__, 'api_save_equipment_affiliate' ),
				'methods' => 'PUT',
				'permission_callback' => array( __CLASS__, 'api_required_permissions' ),
			));
		}
	}

	/**
	 * Required permissions for the API.
	 *
	 * @since 8.0.0
	 */
	public static function api_required_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle get equipment affiliate call to the REST API.
	 *
	 * @since 8.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_get_equipment_affiliate( $request ) {
		// Parameters.
		$params = $request->get_params();

		$equipment = isset( $params['equipment'] ) ? $params['equipment'] : array();
		$affiliate = array();

		foreach ( $equipment as $index => $name ) {
			$equipment_affiliate = false;
			$equipment_id = WPRM_Recipe_Sanitizer::get_equipment_id( $name );

			if ( $equipment_id ) {

				// Check if linked to an Easy Affiliate Links link.
				$eafl = false;
				if ( class_exists( 'EAFL_Link_Manager' ) ) {
					$eafl_id = get_term_meta( $equipment_id, 'wprmp_equipment_eafl', true );

					if ( $eafl_id ) {
						$eafl_link = EAFL_Link_Manager::get_link( intval( $eafl_id ) );

						if ( $eafl_link ) {
							$eafl = $eafl_link->get_data();
						}
					}
				}

				// Image.
				$image_id = get_term_meta( $equipment_id, 'wprmp_equipment_image_id', true );
				$image_url = '';

				if ( $image_id ) {
					$image_id = intval( $image_id );
					$thumb = wp_get_attachment_image_src( $image_id, array( 150, 999 ) );

					if ( $thumb && isset( $thumb[0] ) ) {
						$image_url = $thumb[0];
					}
				}

				// Count.
				$term_object = get_term( $equipment_id, 'wprm_equipment' );

				$count = false;
				if ( ! is_wp_error( $term_object ) ) {
					$count = $term_object->count;
				}

				// Simple fields.
				$link = get_term_meta( $equipment_id, 'wprmp_equipment_link', true );
				$nofollow = get_term_meta( $equipment_id, 'wprmp_equipment_link_nofollow', true );
				$html = get_term_meta( $equipment_id, 'wprmp_equipment_affiliate_html', true );
				$amazon_asin = get_term_meta( $equipment_id, 'wprmp_amazon_asin', true );
				$amazon_name = get_term_meta( $equipment_id, 'wprmp_amazon_name', true );
				$amazon_image = get_term_meta( $equipment_id, 'wprmp_amazon_image', true );
				$amazon_image_width = get_term_meta( $equipment_id, 'wprmp_amazon_image_width', true );
				$amazon_image_height = get_term_meta( $equipment_id, 'wprmp_amazon_image_height', true );
				$amazon_updated = get_term_meta( $equipment_id, 'wprmp_amazon_updated', true );

				// Values to return.
				$equipment_affiliate = array(
					'eafl' => $eafl,
					'link' => $link,
					'nofollow' => $nofollow,
					'html' => $html,
					'amazon_asin' => $amazon_asin,
					'amazon_name' => $amazon_name,
					'amazon_image' => $amazon_image,
					'amazon_image_width' => $amazon_image_width,
					'amazon_image_height' => $amazon_image_height,
					'amazon_updated' => $amazon_updated,
					'image_id' => $image_id,
					'image_url' => $image_url,
					'count' => $count,
				);
			}

			$affiliate[ $index ] = $equipment_affiliate;
		}

		return rest_ensure_response( array(
			'affiliate' => $affiliate,
		) );
	}

	/**
	 * Handle save equipment affiliate call to the REST API.
	 *
	 * @since 8.0.0
	 * @param WP_REST_Request $request Current request.
	 */
	public static function api_save_equipment_affiliate( $request ) {
		// Parameters.
		$params = $request->get_params();

		$equipment = isset( $params['equipment'] ) ? $params['equipment'] : array();

		foreach ( $equipment as $item ) {
			$equipment_id = WPRM_Recipe_Sanitizer::get_equipment_id( $item['name'] );

			if ( $equipment_id ) {
				$affiliate = isset( $item['affiliate'] ) && $item['affiliate'] ? $item['affiliate'] : array();

				// Equipment meta.
				if ( isset( $affiliate['link'] ) ) 		{ update_term_meta( $equipment_id, 'wprmp_equipment_link', $affiliate['link'] ); }
				if ( isset( $affiliate['nofollow'] ) ) 	{ update_term_meta( $equipment_id, 'wprmp_equipment_link_nofollow', $affiliate['nofollow'] ); }
				if ( isset( $affiliate['html'] ) ) 		{ update_term_meta( $equipment_id, 'wprmp_equipment_affiliate_html', $affiliate['html'] ); }
				if ( isset( $affiliate['amazon_asin'] ) ) { update_term_meta( $equipment_id, 'wprmp_amazon_asin', $affiliate['amazon_asin'] ); }
				if ( isset( $affiliate['amazon_name'] ) ) { update_term_meta( $equipment_id, 'wprmp_amazon_name', $affiliate['amazon_name'] ); }
				if ( isset( $affiliate['amazon_image'] ) ) { update_term_meta( $equipment_id, 'wprmp_amazon_image', $affiliate['amazon_image'] ); }
				if ( isset( $affiliate['amazon_image_width'] ) ) {
					$width = intval( $affiliate['amazon_image_width'] );
					if ( $width > 0 ) {
						update_term_meta( $equipment_id, 'wprmp_amazon_image_width', $width );
					} else {
						delete_term_meta( $equipment_id, 'wprmp_amazon_image_width' );
					}
				}
				if ( isset( $affiliate['amazon_image_height'] ) ) {
					$height = intval( $affiliate['amazon_image_height'] );
					if ( $height > 0 ) {
						update_term_meta( $equipment_id, 'wprmp_amazon_image_height', $height );
					} else {
						delete_term_meta( $equipment_id, 'wprmp_amazon_image_height' );
					}
				}
				if ( isset( $affiliate['amazon_updated'] ) ) { update_term_meta( $equipment_id, 'wprmp_amazon_updated', $affiliate['amazon_updated'] ); }
				if ( isset( $affiliate['image_id'] ) ) 	{ update_term_meta( $equipment_id, 'wprmp_equipment_image_id', $affiliate['image_id'] ); }

				// Update term meta for Easy Affiliate Links integration.
				if ( isset( $affiliate['eafl'] ) ) {
					$eafl = $affiliate['eafl'] && isset( $affiliate['eafl']['id'] ) ? intval( $affiliate['eafl']['id'] ) : false;
					if ( ! $eafl ) {
						delete_term_meta( $equipment_id, 'wprmp_equipment_eafl' );
					} else {
						update_term_meta( $equipment_id, 'wprmp_equipment_eafl', $eafl );
					}
				}
			}
		}

		return rest_ensure_response( true );
	}
}

WPRMP_Api_Equipment_Affiliate::init();
