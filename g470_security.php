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
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Default options
 */
function rup_default_options() {
    return array(
        'rup_enabled'        => true,          // toggle restriction on/off
        'rup_capability'     => 'list_users',  // capability required to view users
    );
}

/**
 * Register the settings
 */
function rup_register_settings() {
    register_setting(
        'rup_settings_group',        // option group
        'rup_options',               // option name (single array)
        'rup_sanitize_options'       // sanitization callback
    );

    add_settings_section(
        'rup_main_section',          // ID
        __( 'REST Users Protect Settings', 'rest-users-protect' ),
        null,
        'rup_settings_page'
    );

    add_settings_field(
        'rup_enabled',
        __( 'Enable Restriction', 'rest-users-protect' ),
        'rup_enabled_callback',
        'rup_settings_page',
        'rup_main_section'
    );

    add_settings_field(
        'rup_capability',
        __( 'Required Capability', 'rest-users-protect' ),
        'rup_capability_callback',
        'rup_settings_page',
        'rup_main_section'
    );
}
add_action( 'admin_init', 'rup_register_settings' );

/**
 * Sanitize the options before saving
 */
function rup_sanitize_options( $input ) {
    $defaults = rup_default_options();
    $output   = array();

    // Enabled checkbox
    $output['rup_enabled'] = isset( $input['rup_enabled'] ) && $input['rup_enabled'] ? true : false;

    // Capability – allow only valid capability strings (letters, dashes)
    if ( isset( $input['rup_capability'] ) ) {
        $cap = sanitize_text_field( $input['rup_capability'] );
        $cap = preg_replace( '/[^a-z0-9_\-]/', '', $cap ); // keep only valid chars
        $output['rup_capability'] = ! empty( $cap ) ? $cap : $defaults['rup_capability'];
    } else {
        $output['rup_capability'] = $defaults['rup_capability'];
    }

    return $output;
}

/**
 * Settings page callback – enable checkbox
 */
function rup_enabled_callback() {
    $options = get_option( 'rup_options', rup_default_options() );
    ?>
    <input type="checkbox" id="rup_enabled" name="rup_options[rup_enabled]" value="1"
        <?php checked( true, $options['rup_enabled'] ); ?> />
    <label for="rup_enabled"><?php esc_html_e( 'Enable restriction on the /wp/v2/users endpoint', 'rest-users-protect' ); ?></label>
    <?php
}

/**
 * Settings page callback – capability field
 */
function rup_capability_callback() {
    $options = get_option( 'rup_options', rup_default_options() );
    ?>
    <input type="text" id="rup_capability" name="rup_options[rup_capability]" value="<?php echo esc_attr( $options['rup_capability'] ); ?>" />
    <p class="description"><?php esc_html_e( 'Only users with this capability can view the users endpoint. Leave empty to use the default capability (list_users).', 'rest-users-protect' ); ?></p>
    <?php
}

/**
 * Add Settings page to WP‑Admin
 */
function rup_admin_menu() {
    add_options_page(
        __( 'REST Users Protect', 'rest-users-protect' ),
        __( 'REST Users Protect', 'rest-users-protect' ),
        'manage_options',
        'rup-settings',
        'rup_settings_page'
    );
}
add_action( 'admin_menu', 'rup_admin_menu' );

/**
 * Render the settings page
 */
function rup_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'REST Users Protect', 'rest-users-protect' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'rup_settings_group' ); // output nonce, hidden fields
            do_settings_sections( 'rup_settings_page' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * REST endpoint filter – block public access
 */
function rup_rest_pre_dispatch( $result, $server, $check, $route, $verb ) {
    // Only act on /wp/v2/users
    if ( $route !== '/wp/v2/users' ) {
        return $result;
    }

    // Fetch options (with defaults)
    $options = wp_parse_args( get_option( 'rup_options', array() ), rup_default_options() );

    // If restriction disabled, allow the request
    if ( ! $options['rup_enabled'] ) {
        return $result;
    }

    // Capability required
    $required_cap = $options['rup_capability'] ?: 'list_users';

    // User must be logged in *and* have capability
    if ( ! is_user_logged_in() || ! current_user_can( $required_cap ) ) {
        return new WP_Error(
            'rest_user_cannot_view',
            __( 'Sorry, you are not allowed to view the users endpoint.', 'rest-users-protect' ),
            array( 'status' => is_user_logged_in() ? 403 : 401 )
        );
    }

    // All checks passed – allow request to continue
    return $result;
}
add_filter( 'rest_pre_dispatch', 'rup_rest_pre_dispatch', 10, 5 );

/**
 * Activate: ensure option exists
 */
function rup_activate() {
    if ( ! get_option( 'rup_options' ) ) {
        add_option( 'rup_options', rup_default_options() );
    }
}
register_activation_hook( __FILE__, 'rup_activate' );

/**
 * Deactivate: clean up (optional)
 */
function rup_deactivate() {
    delete_option( 'rup_options' );
}
register_deactivation_hook( __FILE__, 'rup_deactivate' );