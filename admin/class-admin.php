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
	 * Initialize the admin functionality.
	 *
	 * @since 1.0.0
	 * @param G470_Security_Settings $settings Settings instance.
	 */
	public function __construct( G470_Security_Settings $settings ) {
		$this->settings = $settings;
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
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
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'g470-gatonet-plugins' ) );
		}

		// Load the view template.
		include G470_SECURITY_PATH . 'admin/views/settings-page.php';
	}
}
