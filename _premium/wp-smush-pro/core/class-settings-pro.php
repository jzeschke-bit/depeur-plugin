<?php

namespace Smush\Core;

use Smush\Core\Membership\Membership;
use WP_Smush;

class Settings_Pro extends Settings {
	public function is_module_active( $module ) {
		$pro_modules   = $this->get_placeholder_modules();
		$module_active = self::get_instance()->get( $module );
		if ( in_array( $module, $pro_modules, true ) ) {
			$module_active = $module_active && Membership::get_instance()->is_pro();
		}

		return $module_active;
	}

	public function get_highest_lossy_level() {
		if ( Membership::get_instance()->is_pro() ) {
			return self::$level_ultra_lossy;
		}
		return parent::get_highest_lossy_level();
	}

	public function can_access_pro_field( $field ) {
		if ( Membership::get_instance()->is_pro() ) {
			return true;
		}

		return parent::can_access_pro_field( $field );
	}

	public function should_enforce_bulk_limit() {
		return ! Membership::get_instance()->is_pro();
	}

	public function get_api_key() {
		$api_key = Membership::get_instance()->get_apikey();
		if ( ! empty( $api_key ) && Membership::get_instance()->is_pro() ) {
			return $api_key;
		}
		return '';
	}
}