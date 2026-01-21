<?php
/**
 * Plugin Name:       G470 Security
 * Description:       Custom security related Plugin
 * Author:            G470
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author URI:        https://gatonet.de/wordpress-support
 * Text Domain:       g470-gatonet-plugins
 *
 * @package    G470_Security
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define( 'G470_SECURITY_VERSION', '1.0.0' );
define( 'G470_SECURITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'G470_SECURITY_URL', plugin_dir_url( __FILE__ ) );
define( 'G470_SECURITY_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load core plugin class and lifecycle handlers.
 */
require_once G470_SECURITY_PATH . 'includes/class-plugin.php';
require_once G470_SECURITY_PATH . 'includes/class-activator.php';
require_once G470_SECURITY_PATH . 'includes/class-deactivator.php';

/**
 * Register activation hook.
 *
 * Runs when the plugin is activated.
 *
 * @since 1.0.0
 */
register_activation_hook( __FILE__, array( 'G470_Security_Activator', 'activate' ) );

/**
 * Register deactivation hook.
 *
 * Runs when the plugin is deactivated.
 *
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, array( 'G470_Security_Deactivator', 'deactivate' ) );

/**
 * Begin plugin execution.
 *
 * Initializes the plugin on plugins_loaded to ensure WordPress
 * core functions are available.
 *
 * @since 1.0.0
 */
function g470_security_init() {
	$plugin = new G470_Security_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'g470_security_init' );