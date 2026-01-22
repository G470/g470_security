<?php
/**
 * ACF Field Debugging for Elementor.
 *
 * @package    G470_Security
 * @subpackage G470_Security/security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug ACF field values in Elementor Pro dynamic tags.
 *
 * Logs warnings when ACF fields return arrays without proper indexing,
 * which can cause issues in Elementor Pro templates.
 */
class G470_Security_ACF_Debug {

	/**
	 * Settings instance.
	 *
	 * @var G470_Security_Settings
	 */
	private $settings;

	/**
	 * Initialize ACF debugging.
	 *
	 * @since 1.0.5
	 * @param G470_Security_Settings $settings Settings instance.
	 */
	public function __construct( G470_Security_Settings $settings ) {
		$this->settings = $settings;
		add_filter( 'elementor_pro/dynamic_tags/acf/value', array( $this, 'debug_acf_value' ), 10, 3 );
	}

	/**
	 * Debug ACF field values for Elementor Pro dynamic tags.
	 *
	 * Logs diagnostic information when an ACF field returns an array
	 * without the expected structure. This helps identify problematic
	 * ACF field configurations that may cause display issues in Elementor.
	 *
	 * @since  1.0.5
	 * @param  mixed $value    The ACF field value.
	 * @param  array $field    The ACF field configuration.
	 * @param  array $settings The Elementor dynamic tag settings.
	 * @return mixed The original value (unchanged).
	 */
	public function debug_acf_value( $value, $field, $settings ) {
		// Get current settings.
		$options = $this->settings->get_options();

		// Check if module is enabled.
		if ( empty( $options['g470_security_module_acf_debug'] ) ) {
			return $value;
		}

		// Check if value is an array without proper indexing.
		if ( is_array( $value ) && ! isset( $value[1] ) ) {
			// Log warning with diagnostic information.
			error_log( 'ELEMENTOR ACF WARNING' );
			error_log( 'Field name: ' . ( isset( $field['name'] ) ? $field['name'] : 'unknown' ) );
			error_log( 'Field type: ' . ( isset( $field['type'] ) ? $field['type'] : 'unknown' ) );
			error_log( 'Value: ' . print_r( $value, true ) );
			error_log( 'Page ID: ' . get_the_ID() );
			error_log( '---' );
		}

		return $value;
	}
}
