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
function g470_security_default_options() {
    return array(
        'g470_security_enabled'    => true,
        'g470_security_capability' => 'list_users',
    );
}

/**
 * Sanitize the options before saving.
 */
function g470_security_sanitize_options( $input ) {
    $defaults = g470_security_default_options();
    $output   = array();

    $output['g470_security_enabled'] = ! empty( $input['g470_security_enabled'] );

    if ( isset( $input['g470_security_capability'] ) ) {
        $cap = sanitize_text_field( $input['g470_security_capability'] );
        $cap = preg_replace( '/[^a-z0-9_\-]/', '', $cap );
        $allowed = g470_security_get_available_capabilities();
        $output['g470_security_capability'] = ( ! empty( $cap ) && in_array( $cap, $allowed, true ) ) ? $cap : $defaults['g470_security_capability'];
    } else {
        $output['g470_security_capability'] = $defaults['g470_security_capability'];
    }

    return $output;
}

/**
 * Aggregate available capabilities from roles plus a set of common caps.
 */
function g470_security_get_available_capabilities() {
    static $caps = null;
    if ( null !== $caps ) {
        return $caps;
    }

    $caps_set = array();

    // Common core capabilities to ensure useful defaults
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
    foreach ( $common as $c ) {
        $caps_set[ $c ] = true;
    }

    // Pull capabilities from all registered roles
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

    $caps = array_keys( $caps_set );
    sort( $caps, SORT_STRING | SORT_FLAG_CASE );
    return $caps;
}

/**
 * Register the settings in WP Settings API.
 */
function g470_security_register_settings() {
    register_setting(
        'g470_security_settings_group',
        'g470_security_options',
        'g470_security_sanitize_options'
    );

    add_settings_section(
        'g470_security_main_section',
        __( 'G470 SEC Settings', 'rest-users-protect' ),
        null,
        'g470_security_settings_page'
    );

    add_settings_field(
        'g470_security_enabled',
        __( 'Enable Restriction', 'rest-users-protect' ),
        'g470_security_enabled_callback',
        'g470_security_settings_page',
        'g470_security_main_section'
    );

    add_settings_field(
        'g470_security_capability',
        __( 'Required Capability', 'rest-users-protect' ),
        'g470_security_capability_callback',
        'g470_security_settings_page',
        'g470_security_main_section'
    );
}
add_action( 'admin_init', 'g470_security_register_settings' );

/**
 * Settings page callback – enable checkbox.
 */
function g470_security_enabled_callback() {
    $options = get_option( 'g470_security_options', g470_security_default_options() );
    ?>
    <input type="checkbox" id="g470_security_enabled" name="g470_security_options[g470_security_enabled]" value="1" <?php checked( true, ! empty( $options['g470_security_enabled'] ) ); ?> />
    <label for="g470_security_enabled"><?php esc_html_e( 'Enable restriction on the /wp/v2/users endpoint', 'rest-users-protect' ); ?></label>
    <?php
}

/**
 * Settings page callback – capability field.
 */
function g470_security_capability_callback() {
    $options      = get_option( 'g470_security_options', g470_security_default_options() );
    $capabilities = g470_security_get_available_capabilities();
    ?>
    <select id="g470_security_capability" name="g470_security_options[g470_security_capability]">
        <?php foreach ( $capabilities as $cap ) : ?>
            <option value="<?php echo esc_attr( $cap ); ?>" <?php selected( $options['g470_security_capability'], $cap ); ?>>
                <?php echo esc_html( $cap ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e( 'Choose a capability required to view the users endpoint.', 'rest-users-protect' ); ?></p>
    <?php
}

/**
 * Add Settings page to WP-Admin.
 */
function g470_security_admin_menu() {
    add_options_page(
        __( 'G470 SEC', 'rest-users-protect' ),
        __( 'G470 SEC', 'rest-users-protect' ),
        'manage_options',
        'g470_sec-settings',
        'g470_security_settings_page'
    );
}
add_action( 'admin_menu', 'g470_security_admin_menu' );

// No custom admin-post handler needed; using Settings API submission via options.php.

/**
 * Render the settings page.
 */
function g470_security_settings_page() {
    $options = get_option( 'g470_security_options', g470_security_default_options() );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'G470 SEC', 'rest-users-protect' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'g470_security_settings_group' );
            do_settings_sections( 'g470_security_settings_page' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * REST endpoint filter – block public access
 */
function g470_security_rest_pre_dispatch( $result, $server, $check, $route, $verb ) {
    if ( '/wp/v2/users' !== $route ) {
        return $result;
    }

    $options = wp_parse_args( get_option( 'g470_security_options', array() ), g470_security_default_options() );

    if ( empty( $options['g470_security_enabled'] ) ) {
        return $result;
    }

    $required_cap = ! empty( $options['g470_security_capability'] ) ? $options['g470_security_capability'] : 'list_users';

    if ( ! is_user_logged_in() || ! current_user_can( $required_cap ) ) {
        return new WP_Error(
            'rest_user_cannot_view',
            __( 'Sorry, you are not allowed to view the users endpoint.', 'rest-users-protect' ),
            array( 'status' => is_user_logged_in() ? 403 : 401 )
        );
    }

    return $result;
}
add_filter( 'rest_pre_dispatch', 'g470_security_rest_pre_dispatch', 10, 5 );

/**
 * Activate: ensure option exists with defaults.
 */
function g470_security_activate() {
    if ( ! get_option( 'g470_security_options' ) ) {
        add_option( 'g470_security_options', g470_security_default_options() );
    }
}
register_activation_hook( __FILE__, 'g470_security_activate' );

/**
 * Deactivate: clean up option.
 */
function g470_security_deactivate() {
    delete_option( 'g470_security_options' );
}
register_deactivation_hook( __FILE__, 'g470_security_deactivate' );