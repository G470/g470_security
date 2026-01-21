<?php
/**
 * Module/patch management system.
 *
 * @package    G470_Security
 * @subpackage G470_Security/security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages security modules/patches registration and activation.
 *
 * Provides a registry for security modules that can be enabled/disabled
 * via the admin interface.
 */
class G470_Security_Module_Manager {

	/**
	 * Registered modules.
	 *
	 * @var array
	 */
	private static $modules = array();

	/**
	 * Settings instance.
	 *
	 * @var G470_Security_Settings
	 */
	private $settings;

	/**
	 * Initialize the module manager.
	 *
	 * @since 1.0.0
	 * @param G470_Security_Settings $settings Settings instance.
	 */
	public function __construct( G470_Security_Settings $settings ) {
		$this->settings = $settings;
		$this->register_default_modules();
	}

	/**
	 * Register default security modules.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function register_default_modules() {
		// REST Users Endpoint Protection (always available, built-in)
		$this->register_module(
			'rest_users_protection',
			array(
				'name'        => __( 'REST Users Protection', 'g470-gatonet-plugins' ),
				'description' => __( 'Restrict access to /wp/v2/users REST endpoint based on capabilities.', 'g470-gatonet-plugins' ),
				'enabled'     => true,
				'locked'      => false, // Can be disabled by admin
				'has_settings' => true,
				'settings_callback' => null, // Uses dedicated module settings page
				'class'       => 'G470_Security_REST_Security',
			)
		);
	}

	/**
	 * Register a security module.
	 *
	 * @since 1.0.0
	 * @param string $id   Unique module identifier.
	 * @param array  $args Module configuration.
	 * @return bool True on success, false on failure.
	 */
	public function register_module( $id, $args ) {
		$defaults = array(
			'name'              => '',
			'description'       => '',
			'enabled'           => false,
			'locked'            => false,
			'has_settings'      => false,
			'settings_callback' => null,
			'class'             => '',
			'priority'          => 10,
		);

		$module = wp_parse_args( $args, $defaults );

		if ( empty( $module['name'] ) ) {
			return false;
		}

		self::$modules[ $id ] = $module;
		return true;
	}

	/**
	 * Get all registered modules.
	 *
	 * @since  1.0.0
	 * @return array Registered modules.
	 */
	public function get_modules() {
		// Sort by priority
		uasort(
			self::$modules,
			function( $a, $b ) {
				return $a['priority'] - $b['priority'];
			}
		);

		return self::$modules;
	}

	/**
	 * Get a specific module.
	 *
	 * @since  1.0.0
	 * @param  string $id Module identifier.
	 * @return array|null Module data or null if not found.
	 */
	public function get_module( $id ) {
		return isset( self::$modules[ $id ] ) ? self::$modules[ $id ] : null;
	}

	/**
	 * Check if a module is enabled.
	 *
	 * For the REST Users Protection module, we check the dedicated
	 * 'g470_security_enabled' setting. For other modules, we check
	 * the module-specific setting.
	 *
	 * @since  1.0.0
	 * @param  string $id Module identifier.
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_module_enabled( $id ) {
		$options = $this->settings->get_options();
		$module  = $this->get_module( $id );

		// Locked modules are always enabled
		if ( $module && ! empty( $module['locked'] ) ) {
			return true;
		}

		// Special handling for REST Users Protection module
		if ( 'rest_users_protection' === $id ) {
			return ! empty( $options['g470_security_enabled'] );
		}

		$option_key = 'g470_security_module_' . $id;
		return ! empty( $options[ $option_key ] );
	}

	/**
	 * Enable a module.
	 *
	 * For the REST Users Protection module, we enable the dedicated
	 * 'g470_security_enabled' setting. For other modules, we use
	 * the module-specific setting.
	 *
	 * @since 1.0.0
	 * @param string $id Module identifier.
	 * @return bool True on success, false on failure.
	 */
	public function enable_module( $id ) {
		$module = $this->get_module( $id );

		if ( ! $module || ! empty( $module['locked'] ) ) {
			return false;
		}

		$options = $this->settings->get_options();

		// Special handling for REST Users Protection module
		if ( 'rest_users_protection' === $id ) {
			$options['g470_security_enabled'] = true;
		} else {
			$options[ 'g470_security_module_' . $id ] = true;
		}

		update_option( 'g470_security_options', $options );

		return true;
	}

	/**
	 * Disable a module.
	 *
	 * For the REST Users Protection module, we disable the dedicated
	 * 'g470_security_enabled' setting. For other modules, we use
	 * the module-specific setting.
	 *
	 * @since 1.0.0
	 * @param string $id Module identifier.
	 * @return bool True on success, false on failure.
	 */
	public function disable_module( $id ) {
		$module = $this->get_module( $id );

		if ( ! $module || ! empty( $module['locked'] ) ) {
			return false;
		}

		$options = $this->settings->get_options();

		// Special handling for REST Users Protection module
		if ( 'rest_users_protection' === $id ) {
			$options['g470_security_enabled'] = false;
		} else {
			unset( $options[ 'g470_security_module_' . $id ] );
		}

		update_option( 'g470_security_options', $options );

		return true;
	}

	/**
	 * Get enabled modules.
	 *
	 * @since  1.0.0
	 * @return array Enabled module IDs.
	 */
	public function get_enabled_modules() {
		$enabled = array();

		foreach ( self::$modules as $id => $module ) {
			if ( $this->is_module_enabled( $id ) ) {
				$enabled[] = $id;
			}
		}

		return $enabled;
	}
}
