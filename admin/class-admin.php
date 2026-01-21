<?php
/**
 * Admin area functionality.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines admin menu, pages, and admin-facing hooks.
 */
class G470_Security_Admin {

	/**
	 * Settings instance.
	 *
	 * @var G470_Security_Settings
	 */
	private $settings;

	/**
	 * Module manager instance.
	 *
	 * @var G470_Security_Module_Manager
	 */
	private $module_manager;

	/**
	 * Initialize the admin functionality.
	 *
	 * @since 1.0.0
	 * @param G470_Security_Settings       $settings       Settings instance.
	 * @param G470_Security_Module_Manager $module_manager Module manager instance.
	 */
	public function __construct( G470_Security_Settings $settings, G470_Security_Module_Manager $module_manager ) {
		$this->settings       = $settings;
		$this->module_manager = $module_manager;
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_ajax_g470_toggle_module', array( $this, 'ajax_toggle_module' ) );
		add_action( 'wp_ajax_g470_test_rest_users_protection', array( $this, 'ajax_test_rest_users_protection' ) );
	}

	/**
	 * Register the administration menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'G470 Security', 'g470-gatonet-plugins' ),
			__( 'G470 Security', 'g470-gatonet-plugins' ),
			'manage_options',
			'g470-security-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( 'settings_page_g470-security-settings' !== $hook_suffix ) {
			return;
		}

		// Inline CSS for tabs (avoids external file dependency)
		wp_add_inline_style(
			'common',
			'
			.g470-nav-tab-wrapper { margin-bottom: 20px; }
			.g470-tab-content { display: none; }
			.g470-tab-content.active { display: block; }
			.tab-patches  { margin-top: 20px; }
			.g470-module-item { padding: 15px; background: #fff; border: 1px solid #ccd0d4; margin-bottom: 10px; border-radius: 4px; }
			.g470-module-header { display: flex; align-items: center; justify-content: space-between; }
			.g470-module-info h3 { margin: 0 0 5px; }
			.g470-module-info p { margin: 0; color: #646970; }
			.g470-module-toggle { display: flex; align-items: center; gap: 10px; }
			.g470-module-toggle .components-form-toggle { margin: 0; }
			.g470-module-settings-link { margin-top: 10px; padding-top: 10px; border-top: 1px solid #dcdcde; }
			.g470-module-locked { opacity: 0.6; }
			.switch.g470-style-toggleswitch { position: relative; display: inline-block; width: 60px; height: 34px; }
			.switch.g470-style-toggleswitch input { opacity: 0; width: 0; height: 0; }
			.switch.g470-style-toggleswitch span { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; overflow: hidden; text-indent: -9999px; }
			.switch.g470-style-toggleswitch span:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
			.switch.g470-style-toggleswitch input:checked + span { background-color: #2196F3; }
			.switch.g470-style-toggleswitch input:checked + span:before { transform: translateX(26px); }
			'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'g470-gatonet-plugins' ) );
		}

		// Check if displaying module settings page
		$module_id = isset( $_GET['module'] ) ? sanitize_key( $_GET['module'] ) : '';

		if ( ! empty( $module_id ) ) {
			// Load module-specific settings page
			include G470_SECURITY_PATH . 'admin/views/module-settings.php';
		} else {
			// Get current tab for tabbed interface
			$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

			// Load the main tabbed settings page
			include G470_SECURITY_PATH . 'admin/views/settings-page.php';
		}
	}

	/**
	 * Get available tabs.
	 *
	 * @since  1.0.0
	 * @return array Available tabs.
	 */
	public function get_tabs() {
		return array(
			'general' => __( 'General Settings', 'g470-gatonet-plugins' ),
			'patches' => __( 'Available Patches', 'g470-gatonet-plugins' ),
		);
	}

	/**
	 * Get the module manager instance.
	 *
	 * @since  1.0.0
	 * @return G470_Security_Module_Manager Module manager instance.
	 */
	public function get_module_manager() {
		return $this->module_manager;
	}

	/**
	 * AJAX handler for module toggle.
	 *
	 * @since 1.0.0
	 */
	public function ajax_toggle_module() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'g470_toggle_module' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'g470-gatonet-plugins' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'g470-gatonet-plugins' ) ) );
		}

		// Get module ID and enabled state
		$module_id = isset( $_POST['module_id'] ) ? sanitize_key( $_POST['module_id'] ) : '';
		$enabled   = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];

		if ( empty( $module_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid module ID.', 'g470-gatonet-plugins' ) ) );
		}

		// Toggle module
		if ( $enabled ) {
			$success = $this->module_manager->enable_module( $module_id );
		} else {
			$success = $this->module_manager->disable_module( $module_id );
		}

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Module toggled successfully.', 'g470-gatonet-plugins' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to toggle module. It may be locked.', 'g470-gatonet-plugins' ) ) );
		}
	}

	/**
	 * AJAX handler to test REST Users Protection behavior.
	 *
	 * Predicts the outcome for the current user based on settings
	 * without performing an external HTTP request.
	 *
	 * @since 1.0.2
	 */
	public function ajax_test_rest_users_protection() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'g470_test_rest_users_protection' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'g470-gatonet-plugins' ) ) );
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'g470-gatonet-plugins' ) ) );
		}

		$options = $this->settings->get_options();

		// Determine scenario: current (default), guest, or no_cap.
		$scenario = isset( $_POST['scenario'] ) ? sanitize_key( wp_unslash( $_POST['scenario'] ) ) : 'current';

		$enabled         = ! empty( $options['g470_security_enabled'] );
		$required_cap    = ! empty( $options['g470_security_capability'] ) ? $options['g470_security_capability'] : 'list_users';
		$protection_mode = ! empty( $options['g470_security_protection_mode'] ) ? $options['g470_security_protection_mode'] : 'block';

		$current_is_logged_in = is_user_logged_in();
		$current_has_cap      = $current_is_logged_in && current_user_can( $required_cap );

		// Simulate based on scenario.
		$sim_is_logged_in = $current_is_logged_in;
		$sim_has_cap      = $current_has_cap;

		switch ( $scenario ) {
			case 'guest':
				$sim_is_logged_in = false;
				$sim_has_cap      = false;
				break;
			case 'no_cap':
				$sim_is_logged_in = true;
				$sim_has_cap      = false;
				break;
			case 'current':
			default:
				// Use actual current user context.
				break;
		}

		$result = array(
			'enabled'         => $enabled,
			'protection_mode' => $protection_mode,
			'required_cap'    => $required_cap,
			'scenario'        => $scenario,
			'is_logged_in'    => $sim_is_logged_in,
			'has_cap'         => $sim_has_cap,
		);

		if ( ! $enabled ) {
			$result['outcome']     = 'disabled';
			$result['http_status'] = 200;
			$result['message']     = __( 'Protection is disabled. Endpoint behaves as core.', 'g470-gatonet-plugins' );
			wp_send_json_success( $result );
		}

		if ( 'sanitize' === $protection_mode ) {
			if ( ! $sim_has_cap ) {
				$result['outcome']     = 'blocked';
				$result['http_status'] = 403;
				$result['message']     = __( 'Blocked: logged-in without required capability receive 403.', 'g470-gatonet-plugins' );
				wp_send_json_success( $result );
			}
				$result['http_status'] = 200;
				$result['message']     = __( 'Access allowed but data will be sanitized for unauthorized users.', 'g470-gatonet-plugins' );
			}
			wp_send_json_success( $result );
		}

		// Block mode.
		if ( ! $sim_is_logged_in ) {
			$result['outcome']     = 'blocked';
			$result['http_status'] = 401;
			$result['message']     = __( 'Blocked: guest users receive 401.', 'g470-gatonet-plugins' );
			wp_send_json_success( $result );
		}

		if ( ! $sim_has_cap ) {
			$result['outcome']     = 'blocked';
			$result['http_status'] = 403;
			$result['message']     = __( 'Blocked: logged-in without required capability receive 403.', 'g470-gatonet-plugins' );
			wp_send_json_success( $result );
		}

		$result['outcome']     = 'allowed';
		$result['http_status'] = 200;
		$result['message']     = __( 'Access allowed: user has required capability.', 'g470-gatonet-plugins' );
		wp_send_json_success( $result );
	}
}
