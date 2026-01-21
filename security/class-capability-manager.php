<?php
/**
 * Capability management utilities.
 *
 * @package    G470_Security
 * @subpackage G470_Security/security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages WordPress capability aggregation and utilities.
 *
 * Provides methods to retrieve available capabilities from roles
 * and common WordPress capabilities for use in settings.
 */
class G470_Security_Capability_Manager {

	/**
	 * Cached list of available capabilities.
	 *
	 * @var array|null
	 */
	private static $capabilities = null;

	/**
	 * Get all available capabilities from roles plus common caps.
	 *
	 * Returns a sorted array of capability strings aggregated from
	 * all registered WordPress roles plus a predefined list of
	 * common core capabilities.
	 *
	 * @since  1.0.0
	 * @return array Array of capability strings.
	 */
	public static function get_available_capabilities() {
		if ( null !== self::$capabilities ) {
			return self::$capabilities;
		}

		$caps_set = array();

		// Common core capabilities to ensure useful defaults.
		$common = array(
			'read',
			'list_users',
			'manage_options',
			'edit_posts',
			'edit_pages',
			'publish_posts',
			'delete_posts',
			'moderate_comments',
			'install_plugins',
			'activate_plugins',
			'edit_theme_options',
			'manage_categories',
		);

		foreach ( $common as $cap ) {
			$caps_set[ $cap ] = true;
		}

		// Pull capabilities from all registered roles.
		$wp_roles = wp_roles();
		if ( $wp_roles instanceof WP_Roles ) {
			foreach ( $wp_roles->roles as $role ) {
				if ( isset( $role['capabilities'] ) && is_array( $role['capabilities'] ) ) {
					foreach ( $role['capabilities'] as $cap => $grant ) {
						$caps_set[ $cap ] = true;
					}
				}
			}
		}

		self::$capabilities = array_keys( $caps_set );
		sort( self::$capabilities, SORT_STRING | SORT_FLAG_CASE );

		return self::$capabilities;
	}

	/**
	 * Check if a capability exists in the available capabilities list.
	 *
	 * @since  1.0.0
	 * @param  string $capability The capability to check.
	 * @return bool True if capability exists, false otherwise.
	 */
	public static function capability_exists( $capability ) {
		$available = self::get_available_capabilities();
		return in_array( $capability, $available, true );
	}
}
