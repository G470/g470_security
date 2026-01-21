<?php
/**
 * Settings management for the plugin.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register and manage plugin settings via WordPress Settings API.
 *
 * Handles settings registration, field callbacks, and sanitization.
 */
class G470_Security_Settings {

	/**
	 * Option name in wp_options table.
	 *
	 * @var string
	 */
	private $option_name = 'g470_security_options';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	private $settings_group = 'g470_security_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $settings_page = 'g470_security_settings_page';

	/**
	 * Initialize the settings.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get default option values.
	 *
	 * @since  1.0.0
	 * @return array Default settings.
	 */
	public function get_defaults() {
		return array(
			'g470_security_enabled'    => true,
			'g470_security_capability' => 'list_users',
		);
	}

	/**
	 * Get current option values with defaults fallback.
	 *
	 * @since  1.0.0
	 * @return array Current settings.
	 */
	public function get_options() {
		return wp_parse_args( get_option( $this->option_name, array() ), $this->get_defaults() );
	}

	/**
	 * Register settings, sections, and fields.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			$this->settings_group,
			$this->option_name,
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'g470_security_main_section',
			__( 'REST API Protection Settings', 'g470-gatonet-plugins' ),
			null,
			$this->settings_page
		);

		add_settings_field(
			'g470_security_enabled',
			__( 'Enable Protection', 'g470-gatonet-plugins' ),
			array( $this, 'render_enabled_field' ),
			$this->settings_page,
			'g470_security_main_section'
		);

		add_settings_field(
			'g470_security_capability',
			__( 'Required Capability', 'g470-gatonet-plugins' ),
			array( $this, 'render_capability_field' ),
			$this->settings_page,
			'g470_security_main_section'
		);
	}

	/**
	 * Sanitize options before saving.
	 *
	 * @since  1.0.0
	 * @param  array $input Raw input from form.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$defaults = $this->get_defaults();
		$output   = array();

		// Sanitize enabled checkbox.
		$output['g470_security_enabled'] = ! empty( $input['g470_security_enabled'] );

		// Sanitize and validate capability.
		if ( isset( $input['g470_security_capability'] ) ) {
			$cap     = sanitize_text_field( $input['g470_security_capability'] );
			$cap     = preg_replace( '/[^a-z0-9_\-]/', '', $cap );
			$allowed = G470_Security_Capability_Manager::get_available_capabilities();
			$output['g470_security_capability'] = ( ! empty( $cap ) && in_array( $cap, $allowed, true ) )
				? $cap
				: $defaults['g470_security_capability'];
		} else {
			$output['g470_security_capability'] = $defaults['g470_security_capability'];
		}

		return $output;
	}

	/**
	 * Render the enabled checkbox field.
	 *
	 * @since 1.0.0
	 */
	public function render_enabled_field() {
		$options = $this->get_options();
		?>
		<input type="checkbox"
		       id="g470_security_enabled"
		       name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_enabled]"
		       value="1"
		       <?php checked( true, ! empty( $options['g470_security_enabled'] ) ); ?> />
		<label for="g470_security_enabled">
			<?php esc_html_e( 'Restrict access to /wp/v2/users REST endpoint', 'g470-gatonet-plugins' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the capability dropdown field.
	 *
	 * @since 1.0.0
	 */
	public function render_capability_field() {
		$options      = $this->get_options();
		$capabilities = G470_Security_Capability_Manager::get_available_capabilities();
		?>
		<select id="g470_security_capability"
		        name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_capability]">
			<?php foreach ( $capabilities as $cap ) : ?>
				<option value="<?php echo esc_attr( $cap ); ?>"
				        <?php selected( $options['g470_security_capability'], $cap ); ?>>
					<?php echo esc_html( $cap ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Users must have this capability to access the users endpoint.', 'g470-gatonet-plugins' ); ?>
		</p>
		<?php
	}

	/**
	 * Get settings group name.
	 *
	 * @since  1.0.0
	 * @return string Settings group name.
	 */
	public function get_settings_group() {
		return $this->settings_group;
	}

	/**
	 * Get settings page slug.
	 *
	 * @since  1.0.0
	 * @return string Settings page slug.
	 */
	public function get_settings_page() {
		return $this->settings_page;
	}
}
