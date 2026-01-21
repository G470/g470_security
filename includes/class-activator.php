<?php
/**
 * Fired during plugin activation.
 *
 * @package    G470_Security
 * @subpackage G470_Security/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class G470_Security_Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates default options in wp_options if they don't exist.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		$option_name = 'g470_security_options';
		
		if ( ! get_option( $option_name ) ) {
			$defaults = array(
				'g470_security_enabled'    => true,
				'g470_security_capability' => 'list_users',
			);
			add_option( $option_name, $defaults );
		}
	}
}
