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
	}

	/**
	 * Filter REST API requests to the users endpoint.
	 *
	 * Blocks access to /wp/v2/users unless user is logged in
	 * and has the required capability.
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

		// Check if user is logged in and has the required capability.
		if ( ! is_user_logged_in() || ! current_user_can( $required_cap ) ) {
			return new WP_Error(
				'rest_user_cannot_view',
				__( 'Sorry, you are not allowed to view the users endpoint.', 'g470-gatonet-plugins' ),
				array( 'status' => is_user_logged_in() ? 403 : 401 )
			);
		}

		// Allow the request to proceed.
		return $result;
	}
}
