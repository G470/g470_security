<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This file is called before the plugin's directory is removed.
 * - WordPress has loaded all of the plugin files, but no plugin
 *   hooks have been called yet (since the plugin is being uninstalled).
 * - This file is only called if the user has explicitly asked to
 *   delete the plugin (not just deactivate it).
 *
 * @package G470_Security
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin options on uninstall.
 *
 * Removes all plugin options from the database.
 */
function g470_security_uninstall() {
	// Remove plugin options.
	delete_option( 'g470_security_options' );

	// For multisite installations, remove options from all sites.
	if ( is_multisite() ) {
		global $wpdb;

		// Get all blog IDs.
		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			delete_option( 'g470_security_options' );
			restore_current_blog();
		}
	}
}

g470_security_uninstall();
