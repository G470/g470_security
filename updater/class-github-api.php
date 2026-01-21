<?php
/**
 * GitHub API client for plugin updates.
 *
 * @package    G470_Security
 * @subpackage G470_Security/updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles communication with GitHub API for plugin updates.
 *
 * Fetches release information, download URLs, and manages authentication
 * for private repositories.
 */
class G470_Security_GitHub_API {

	/**
	 * GitHub repository owner/organization.
	 *
	 * @var string
	 */
	private $owner;

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private $repo;

	/**
	 * GitHub personal access token (optional, for private repos).
	 *
	 * @var string
	 */
	private $token;

	/**
	 * GitHub API base URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.github.com';

	/**
	 * Cache key for transients.
	 *
	 * @var string
	 */
	private $cache_key = 'g470_security_github_release';

	/**
	 * Cache expiration time (12 hours).
	 *
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * Initialize the GitHub API client.
	 *
	 * @since 1.0.0
	 * @param string $repo_url GitHub repository URL (e.g., https://github.com/owner/repo).
	 * @param string $token    Optional. GitHub personal access token.
	 */
	public function __construct( $repo_url = '', $token = '' ) {
		$this->parse_repo_url( $repo_url );
		$this->token = sanitize_text_field( $token );
	}

	/**
	 * Parse GitHub repository URL to extract owner and repo name.
	 *
	 * @since  1.0.0
	 * @param  string $repo_url GitHub repository URL.
	 * @return void
	 */
	private function parse_repo_url( $repo_url ) {
		$repo_url = trailingslashit( esc_url_raw( $repo_url ) );
		
		// Match patterns like: https://github.com/owner/repo or github.com/owner/repo
		if ( preg_match( '#github\.com/([^/]+)/([^/]+)#i', $repo_url, $matches ) ) {
			$this->owner = sanitize_text_field( $matches[1] );
			$this->repo  = sanitize_text_field( rtrim( $matches[2], '/' ) );
		}
	}

	/**
	 * Check if repository configuration is valid.
	 *
	 * @since  1.0.0
	 * @return bool True if owner and repo are set.
	 */
	public function is_configured() {
		return ! empty( $this->owner ) && ! empty( $this->repo );
	}

	/**
	 * Get the latest release from GitHub.
	 *
	 * @since  1.0.0
	 * @param  bool $bypass_cache Optional. Force fresh API call. Default false.
	 * @return object|WP_Error Release data object or WP_Error on failure.
	 */
	public function get_latest_release( $bypass_cache = false ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'invalid_repo', __( 'GitHub repository not configured.', 'g470-gatonet-plugins' ) );
		}

		// Check cache first.
		if ( ! $bypass_cache ) {
			$cached = get_transient( $this->cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Fetch from GitHub API.
		$url = sprintf( '%s/repos/%s/%s/releases/latest', $this->api_url, $this->owner, $this->repo );
		$response = $this->api_request( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Cache the result.
		set_transient( $this->cache_key, $response, $this->cache_expiration );

		return $response;
	}

	/**
	 * Get release by tag name.
	 *
	 * @since  1.0.0
	 * @param  string $tag Tag name (e.g., 'v1.0.0').
	 * @return object|WP_Error Release data object or WP_Error on failure.
	 */
	public function get_release_by_tag( $tag ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'invalid_repo', __( 'GitHub repository not configured.', 'g470-gatonet-plugins' ) );
		}

		$url = sprintf( '%s/repos/%s/%s/releases/tags/%s', $this->api_url, $this->owner, $this->repo, $tag );
		return $this->api_request( $url );
	}

	/**
	 * Perform API request to GitHub.
	 *
	 * @since  1.0.0
	 * @param  string $url API endpoint URL.
	 * @return object|WP_Error Decoded JSON response or WP_Error.
	 */
	private function api_request( $url ) {
		$args = array(
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
			),
			'timeout' => 15,
		);

		// Add authorization header for private repos.
		if ( ! empty( $this->token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->token;
		}

		$response = wp_remote_get( $url, $args );

		// Check for HTTP errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error(
				'github_api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'GitHub API returned error code %d', 'g470-gatonet-plugins' ),
					$response_code
				)
			);
		}

		$data = json_decode( $body );

		if ( null === $data ) {
			return new WP_Error( 'json_decode_error', __( 'Failed to decode GitHub API response.', 'g470-gatonet-plugins' ) );
		}

		return $data;
	}

	/**
	 * Get download URL for the latest release zipball.
	 *
	 * @since  1.0.0
	 * @param  object $release Release data object from GitHub API.
	 * @return string Download URL.
	 */
	public function get_download_url( $release ) {
		// Prefer zipball_url from release object.
		if ( isset( $release->zipball_url ) ) {
			return $release->zipball_url;
		}

		// Fallback to constructing URL from tag.
		if ( isset( $release->tag_name ) ) {
			return sprintf(
				'https://github.com/%s/%s/archive/refs/tags/%s.zip',
				$this->owner,
				$this->repo,
				$release->tag_name
			);
		}

		return '';
	}

	/**
	 * Clear cached release data.
	 *
	 * @since 1.0.0
	 */
	public function clear_cache() {
		delete_transient( $this->cache_key );
	}

	/**
	 * Get repository owner.
	 *
	 * @since  1.0.0
	 * @return string Repository owner.
	 */
	public function get_owner() {
		return $this->owner;
	}

	/**
	 * Get repository name.
	 *
	 * @since  1.0.0
	 * @return string Repository name.
	 */
	public function get_repo() {
		return $this->repo;
	}
}
