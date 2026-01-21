<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    G470_Security
 * @subpackage G470_Security/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class G470_Security_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Removes plugin options from wp_options.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		delete_option( 'g470_security_options' );
	}
}
