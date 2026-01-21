<?php
/**
 * REST API security filtering.
 *
 * @package    G470_Security
 * @subpackage G470_Security/security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage REST API endpoint security.
 *
 * Filters REST API requests to restrict access based on
 * configured capabilities.
 */
class G470_Security_REST_Security {

	/**
	 * Settings instance.
	 *
	 * @var G470_Security_Settings
	 */
	private $settings;

	/**
	 * Initialize REST API security.
	 *
	 * @since 1.0.0
	 * @param G470_Security_Settings $settings Settings instance.
	 */
	public function __construct( G470_Security_Settings $settings ) {
		$this->settings = $settings;
		add_filter( 'rest_pre_dispatch', array( $this, 'filter_users_endpoint' ), 10, 3 );
		add_filter( 'rest_prepare_user', array( $this, 'sanitize_user_data' ), 10, 3 );
	}

	/**
	 * Filter REST API requests to the users endpoint.
	 *
	 * Blocks access to /wp/v2/users unless user is logged in
	 * and has the required capability. Supports two protection modes:
	 * - 'block': Completely block access (returns error)
	 * - 'sanitize': Allow access but sanitize sensitive data
	 *
	 * @since  1.0.0
	 * @param  mixed           $result  Response to replace the requested version with.
	 * @param  WP_REST_Server  $server  Server instance.
	 * @param  WP_REST_Request $request Request used to generate the response.
	 * @return mixed Original result or WP_Error if access denied.
	 */
	public function filter_users_endpoint( $result, $server, $request ) {
		// Only filter the /wp/v2/users endpoint.
		if ( '/wp/v2/users' !== $request->get_route() ) {
			return $result;
		}

		// Get current settings.
		$options = $this->settings->get_options();

		// If protection is disabled, allow the request.
		if ( empty( $options['g470_security_enabled'] ) ) {
			return $result;
		}

		// Get required capability.
		$required_cap = ! empty( $options['g470_security_capability'] )
			? $options['g470_security_capability']
			: 'list_users';

		// Get protection mode (default to 'block' for backward compatibility).
		$protection_mode = ! empty( $options['g470_security_protection_mode'] )
			? $options['g470_security_protection_mode']
			: 'block';

		// Check if user has the required capability.
		if ( ! is_user_logged_in() || ! current_user_can( $required_cap ) ) {
			// In 'sanitize' mode, allow the request but data will be sanitized.
			if ( 'sanitize' === $protection_mode ) {
				return $result;
			}

			// In 'block' mode, return error.
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to view the users endpoint.', 'g470-gatonet-plugins' ),
				array( 'status' => is_user_logged_in() ? 403 : 401 )
			);
		}

		// Allow the request to proceed.
		return $result;
	}

	/**
	 * Sanitize user data in REST API responses.
	 *
	 * Replaces sensitive user information with generic placeholders
	 * when the user doesn't have the required capability.
	 *
	 * @since  1.0.1
	 * @param  WP_REST_Response $response Response object.
	 * @param  WP_User          $user     User object.
	 * @param  WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response Modified response object.
	 */
	public function sanitize_user_data( $response, $user, $request ) {
		// Get current settings.
		$options = $this->settings->get_options();

		// If protection is disabled, return original response.
		if ( empty( $options['g470_security_enabled'] ) ) {
			return $response;
		}

		// Get protection mode.
		$protection_mode = ! empty( $options['g470_security_protection_mode'] )
			? $options['g470_security_protection_mode']
			: 'block';

		// Only sanitize in 'sanitize' mode.
		if ( 'sanitize' !== $protection_mode ) {
			return $response;
		}

		// Get required capability.
		$required_cap = ! empty( $options['g470_security_capability'] )
			? $options['g470_security_capability']
			: 'list_users';

		// If user has the required capability, return original response.
		if ( is_user_logged_in() && current_user_can( $required_cap ) ) {
			return $response;
		}

		// Sanitize the response data.
		$data = $response->get_data();

		// Replace sensitive fields with generic placeholders.
		$sanitized_fields = array(
			'name'        => __( 'User', 'g470-gatonet-plugins' ) . ' #' . $data['id'],
			'slug'        => 'user-' . $data['id'],
			'description' => '',
			'link'        => '',
		);

		// Apply sanitization.
		foreach ( $sanitized_fields as $field => $value ) {
			if ( isset( $data[ $field ] ) ) {
				$data[ $field ] = $value;
			}
		}

		// Remove avatar URLs if present.
		if ( isset( $data['avatar_urls'] ) ) {
			$data['avatar_urls'] = array();
		}

		// Remove meta field if present.
		if ( isset( $data['meta'] ) ) {
			$data['meta'] = array();
		}

		// Set the sanitized data back to the response.
		$response->set_data( $data );

		return $response;
	}
}
