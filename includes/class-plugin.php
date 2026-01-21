<?php
/**
 * The core plugin class.
 *
 * @package    G470_Security
 * @subpackage G470_Security/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin orchestrator class.
 *
 * Loads dependencies, initializes modules, and coordinates
 * the interaction between different parts of the plugin.
 */
class G470_Security_Plugin {

	/**
	 * Settings manager instance.
	 *
	 * @var G470_Security_Settings
	 */
	protected $settings;

	/**
	 * Admin manager instance.
	 *
	 * @var G470_Security_Admin
	 */
	protected $admin;

	/**
	 * REST security manager instance.
	 *
	 * @var G470_Security_REST_Security
	 */
	protected $rest_security;

	/**
	 * Initialize the plugin.
	 *
	 * Load dependencies and set up hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->instantiate_modules();
	}

	/**
	 * Load required dependencies.
	 *
	 * Includes all necessary class files.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// Security classes.
		require_once G470_SECURITY_PATH . 'security/class-capability-manager.php';
		require_once G470_SECURITY_PATH . 'security/class-rest-security.php';

		// Admin classes.
		require_once G470_SECURITY_PATH . 'admin/class-settings.php';
		require_once G470_SECURITY_PATH . 'admin/class-admin.php';
	}

	/**
	 * Instantiate plugin modules.
	 *
	 * Creates instances of core plugin classes and establishes
	 * dependencies between them.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function instantiate_modules() {
		// Settings must be instantiated first (other modules depend on it).
		$this->settings = new G470_Security_Settings();

		// Admin UI.
		if ( is_admin() ) {
			$this->admin = new G470_Security_Admin( $this->settings );
		}

		// REST API security (always active).
		$this->rest_security = new G470_Security_REST_Security( $this->settings );
	}

	/**
	 * Run the plugin.
	 *
	 * Executes the plugin's main functionality.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		// All hooks are registered in constructors.
		// This method can be used for additional runtime logic if needed.
	}

	/**
	 * Get the settings instance.
	 *
	 * @since  1.0.0
	 * @return G470_Security_Settings Settings instance.
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get the admin instance.
	 *
	 * @since  1.0.0
	 * @return G470_Security_Admin|null Admin instance or null if not in admin context.
	 */
	public function get_admin() {
		return $this->admin;
	}

	/**
	 * Get the REST security instance.
	 *
	 * @since  1.0.0
	 * @return G470_Security_REST_Security REST security instance.
	 */
	public function get_rest_security() {
		return $this->rest_security;
	}
}
