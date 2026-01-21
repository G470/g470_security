<?php
/**
 * Plugin updater via GitHub releases.
 *
 * @package    G470_Security
 * @subpackage G470_Security/updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages plugin updates via GitHub releases.
 *
 * Hooks into WordPress update system to check for new versions
 * and provide update information from GitHub releases.
 */
class G470_Security_Updater {

	/**
	 * Settings instance.
	 *
	 * @var G470_Security_Settings
	 */
	private $settings;

	/**
	 * GitHub API client.
	 *
	 * @var G470_Security_GitHub_API
	 */
	private $github_api;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin slug (directory name).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Initialize the updater.
	 *
	 * @since 1.0.0
	 * @param G470_Security_Settings $settings Settings instance.
	 */
	public function __construct( G470_Security_Settings $settings ) {
		$this->settings        = $settings;
		$this->plugin_basename = G470_SECURITY_BASENAME;
		$this->plugin_slug     = dirname( $this->plugin_basename );

		$this->init_github_api();
		$this->init_hooks();
	}

	/**
	 * Initialize GitHub API client with settings.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_github_api() {
		$options   = $this->settings->get_options();
		$repo_url  = isset( $options['g470_security_github_repo'] ) ? $options['g470_security_github_repo'] : '';
		$token     = isset( $options['g470_security_github_token'] ) ? $options['g470_security_github_token'] : '';

		$this->github_api = new G470_Security_GitHub_API( $repo_url, $token );
	}

	/**
	 * Register WordPress update hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function init_hooks() {
		// Only register hooks if GitHub repo is configured.
		if ( ! $this->github_api->is_configured() ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * Hooked into 'pre_set_site_transient_update_plugins'.
	 *
	 * @since  1.0.0
	 * @param  mixed $transient Update transient data.
	 * @return mixed Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get latest release from GitHub.
		$release = $this->github_api->get_latest_release();

		if ( is_wp_error( $release ) ) {
			return $transient;
		}

		// Extract version from tag (remove 'v' prefix if present).
		$remote_version = isset( $release->tag_name ) ? ltrim( $release->tag_name, 'v' ) : '';

		if ( empty( $remote_version ) ) {
			return $transient;
		}

		// Compare versions.
		$current_version = G470_SECURITY_VERSION;

		if ( version_compare( $current_version, $remote_version, '<' ) ) {
			// Update available.
			$plugin_data = array(
				'slug'        => $this->plugin_slug,
				'new_version' => $remote_version,
				'package'     => $this->github_api->get_download_url( $release ),
				'url'         => isset( $release->html_url ) ? $release->html_url : '',
			);

			$transient->response[ $this->plugin_basename ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Provide plugin information for update screen.
	 *
	 * Hooked into 'plugins_api'.
	 *
	 * @since  1.0.0
	 * @param  false|object|array $result The result object or array.
	 * @param  string             $action The type of information being requested.
	 * @param  object             $args   Plugin API arguments.
	 * @return false|object Modified result or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		// Only handle plugin_information action for our plugin.
		if ( 'plugin_information' !== $action || $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		// Get latest release from GitHub.
		$release = $this->github_api->get_latest_release();

		if ( is_wp_error( $release ) ) {
			return $result;
		}

		// Build plugin info object.
		$plugin_info = new stdClass();
		$plugin_info->name          = 'G470 Security';
		$plugin_info->slug          = $this->plugin_slug;
		$plugin_info->version       = isset( $release->tag_name ) ? ltrim( $release->tag_name, 'v' ) : '';
		$plugin_info->author        = '<a href="https://gatonet.de/wordpress-support">G470</a>';
		$plugin_info->homepage      = isset( $release->html_url ) ? $release->html_url : '';
		$plugin_info->download_link = $this->github_api->get_download_url( $release );
		$plugin_info->sections      = array(
			'description' => isset( $release->body ) ? wp_kses_post( $release->body ) : __( 'No release notes available.', 'g470-gatonet-plugins' ),
		);
		$plugin_info->last_updated  = isset( $release->published_at ) ? $release->published_at : '';
		$plugin_info->requires      = '6.0';
		$plugin_info->tested        = '6.4';
		$plugin_info->requires_php  = '8.1';

		return $plugin_info;
	}

	/**
	 * Handle post-installation cleanup.
	 *
	 * Renames the extracted folder to match the plugin slug.
	 * Hooked into 'upgrader_post_install'.
	 *
	 * @since  1.0.0
	 * @param  bool  $response   Installation response.
	 * @param  array $hook_extra Extra arguments passed to hooked filters.
	 * @param  array $result     Installation result data.
	 * @return bool|WP_Error Modified response.
	 */
	public function post_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		// Only handle our plugin.
		if ( ! isset( $hook_extra['plugin'] ) || $this->plugin_basename !== $hook_extra['plugin'] ) {
			return $response;
		}

		// Get the destination folder.
		$plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->plugin_slug;
		$wp_filesystem->move( $result['destination'], $plugin_folder, true );
		$result['destination'] = $plugin_folder;

		// Reactivate if needed.
		if ( isset( $hook_extra['type'] ) && 'plugin' === $hook_extra['type'] ) {
			activate_plugin( $this->plugin_basename );
		}

		return $result;
	}

	/**
	 * Clear GitHub release cache after update.
	 *
	 * Hooked into 'upgrader_process_complete'.
	 *
	 * @since 1.0.0
	 * @param WP_Upgrader $upgrader WP_Upgrader instance.
	 * @param array       $options  Array of bulk item update data.
	 */
	public function clear_cache( $upgrader, $options ) {
		if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
			if ( isset( $options['plugins'] ) && in_array( $this->plugin_basename, $options['plugins'], true ) ) {
				$this->github_api->clear_cache();
			}
		}
	}

	/**
	 * Get GitHub API client instance.
	 *
	 * @since  1.0.0
	 * @return G470_Security_GitHub_API GitHub API client.
	 */
	public function get_github_api() {
		return $this->github_api;
	}
}
