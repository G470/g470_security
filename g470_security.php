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
        'rup_enabled'    => true,
        'rup_capability' => 'list_users',
    );
}

/**
 * Internal cache helper to avoid repeated file reads per request.
 */
function rup_cached_options( $value = null, $reset = false ) {
    static $cache = null;

    if ( $reset ) {
        $cache = null;
    } elseif ( null !== $value ) {
        $cache = $value;
    }

    return $cache;
}

/**
 * Location of the settings directory inside uploads.
 */
function rup_settings_dir() {
    $upload_dir = wp_upload_dir();

    return trailingslashit( $upload_dir['basedir'] ) . 'g470-security';
}

/**
 * Full path to the settings JSON file.
 */
function rup_settings_file() {
    return trailingslashit( rup_settings_dir() ) . 'settings.json';
}

/**
 * Sanitize the options before saving.
 */
function rup_sanitize_options( $input ) {
    $defaults = rup_default_options();
    $output   = array();

    $output['rup_enabled'] = ! empty( $input['rup_enabled'] );

    if ( isset( $input['rup_capability'] ) ) {
        $cap = sanitize_text_field( $input['rup_capability'] );
        $cap = preg_replace( '/[^a-z0-9_\-]/', '', $cap );
        $output['rup_capability'] = ! empty( $cap ) ? $cap : $defaults['rup_capability'];
    } else {
        $output['rup_capability'] = $defaults['rup_capability'];
    }

    return $output;
}

/**
 * Load settings from the JSON file or fall back to defaults.
 */
function rup_load_options() {
    $cached = rup_cached_options();

    if ( null !== $cached ) {
        return $cached;
    }

    $defaults = rup_default_options();
    $file     = rup_settings_file();
    $options  = $defaults;

    if ( is_readable( $file ) ) {
        $contents = file_get_contents( $file );

        if ( false !== $contents ) {
            $decoded = json_decode( $contents, true );

            if ( is_array( $decoded ) ) {
                $options = wp_parse_args( $decoded, $defaults );
            }
        }
    }

    $options = rup_sanitize_options( $options );
    rup_cached_options( $options );

    return $options;
}

/**
 * Persist sanitized settings to a JSON file.
 */
function rup_save_options( array $input ) {
    $options = rup_sanitize_options( $input );
    $dir     = rup_settings_dir();

    if ( ! wp_mkdir_p( $dir ) ) {
        return new WP_Error( 'rup_cannot_create_dir', __( 'Cannot create the G470 Security settings directory.', 'rest-users-protect' ) );
    }

    $file = rup_settings_file();
    $json = wp_json_encode( $options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    if ( false === $json ) {
        return new WP_Error( 'rup_encode_failed', __( 'Could not encode settings to JSON.', 'rest-users-protect' ) );
    }

    $bytes = file_put_contents( $file, $json, LOCK_EX );

    if ( false === $bytes ) {
        return new WP_Error( 'rup_write_failed', __( 'Could not write the settings file.', 'rest-users-protect' ) );
    }

    rup_cached_options( $options, true );
    rup_cached_options( $options );

    return $options;
}

/**
 * Add Settings page to WP-Admin.
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
 * Handle settings form submissions and persist to the JSON file.
 */
function rup_handle_settings_save() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to perform this action.', 'rest-users-protect' ) );
    }

    check_admin_referer( 'rup_save_settings' );

    $raw     = isset( $_POST['rup_options'] ) ? (array) wp_unslash( $_POST['rup_options'] ) : array();
    $options = array(
        'rup_enabled'    => ! empty( $raw['rup_enabled'] ),
        'rup_capability' => isset( $raw['rup_capability'] ) ? $raw['rup_capability'] : '',
    );

    $result = rup_save_options( $options );

    $redirect_args = array( 'page' => 'rup-settings' );

    if ( is_wp_error( $result ) ) {
        $redirect_args['rup_error'] = rawurlencode( $result->get_error_message() );
    } else {
        $redirect_args['rup_updated'] = '1';
    }

    $redirect_url = add_query_arg( $redirect_args, admin_url( 'options-general.php' ) );

    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_rup_save_settings', 'rup_handle_settings_save' );

/**
 * Render admin notices for save results.
 */
function rup_admin_notices() {
    if ( ! isset( $_GET['page'] ) || 'rup-settings' !== $_GET['page'] ) {
        return;
    }

    if ( isset( $_GET['rup_updated'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved to file.', 'rest-users-protect' ) . '</p></div>';
    }

    if ( isset( $_GET['rup_error'] ) ) {
        $message = sanitize_text_field( wp_unslash( $_GET['rup_error'] ) );
        echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'rup_admin_notices' );

/**
 * Render the settings page.
 */
function rup_settings_page() {
    $options = rup_load_options();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'REST Users Protect', 'rest-users-protect' ); ?></h1>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'rup_save_settings' ); ?>
            <input type="hidden" name="action" value="rup_save_settings" />

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="rup_enabled"><?php esc_html_e( 'Enable Restriction', 'rest-users-protect' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="rup_enabled" name="rup_options[rup_enabled]" value="1" <?php checked( true, $options['rup_enabled'] ); ?> />
                            <p class="description"><?php esc_html_e( 'Enable restriction on the /wp/v2/users endpoint.', 'rest-users-protect' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rup_capability"><?php esc_html_e( 'Required Capability', 'rest-users-protect' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="rup_capability" name="rup_options[rup_capability]" value="<?php echo esc_attr( $options['rup_capability'] ); ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Only users with this capability can view the users endpoint. Leave empty to use the default capability (list_users).', 'rest-users-protect' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Save Settings', 'rest-users-protect' ) ); ?>
        </form>
    </div>
    <?php
}

/**
 * REST endpoint filter – block public access
 */
function rup_rest_pre_dispatch( $result, $server, $check, $route, $verb ) {
    if ( '/wp/v2/users' !== $route ) {
        return $result;
    }

    $options = rup_load_options();

    if ( empty( $options['rup_enabled'] ) ) {
        return $result;
    }

    $required_cap = ! empty( $options['rup_capability'] ) ? $options['rup_capability'] : 'list_users';

    if ( ! is_user_logged_in() || ! current_user_can( $required_cap ) ) {
        return new WP_Error(
            'rest_user_cannot_view',
            __( 'Sorry, you are not allowed to view the users endpoint.', 'rest-users-protect' ),
            array( 'status' => is_user_logged_in() ? 403 : 401 )
        );
    }

    return $result;
}
add_filter( 'rest_pre_dispatch', 'rup_rest_pre_dispatch', 10, 5 );

/**
 * Activate: ensure settings file exists with defaults.
 */
function rup_activate() {
    $result = rup_save_options( rup_default_options() );

    if ( is_wp_error( $result ) ) {
        error_log( 'G470 Security activation: ' . $result->get_error_message() );
    }
}
register_activation_hook( __FILE__, 'rup_activate' );

/**
 * Deactivate: clean up settings file.
 */
function rup_deactivate() {
    $file = rup_settings_file();

    if ( is_file( $file ) ) {
        unlink( $file );
    }
}
register_deactivation_hook( __FILE__, 'rup_deactivate' );