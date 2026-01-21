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
			'g470_security_enabled'          => true,
			'g470_security_protection_mode'  => 'block',
			'g470_security_capability'       => 'list_users',
			'g470_security_github_repo'      => '',
			'g470_security_github_token' => '',
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

		// === REST Users Protection Settings (Module-specific) ===
		add_settings_section(
			'g470_security_rest_users_section',
			__( 'REST Users Endpoint Protection', 'g470-gatonet-plugins' ),
			null,
			'g470_security_rest_users_settings'
		);

		add_settings_field(
			'g470_security_enabled',
			__( 'Enable Protection', 'g470-gatonet-plugins' ),
			array( $this, 'render_enabled_field' ),
			'g470_security_rest_users_settings',
			'g470_security_rest_users_section'
		);

		add_settings_field(
			'g470_security_protection_mode',
			__( 'Protection Mode', 'g470-gatonet-plugins' ),
			array( $this, 'render_protection_mode_field' ),
			'g470_security_rest_users_settings',
			'g470_security_rest_users_section'
		);

		add_settings_field(
			'g470_security_capability',
			__( 'Required Capability', 'g470-gatonet-plugins' ),
			array( $this, 'render_capability_field' ),
			'g470_security_rest_users_settings',
			'g470_security_rest_users_section'
		);

		// === Plugin Update Settings (General Tab) ===
		add_settings_section(
			'g470_security_updater_section',
			__( 'Plugin Update Settings', 'g470-gatonet-plugins' ),
			array( $this, 'render_updater_section_description' ),
			$this->settings_page
		);

		add_settings_field(
			'g470_security_github_repo',
			__( 'GitHub Repository', 'g470-gatonet-plugins' ),
			array( $this, 'render_github_repo_field' ),
			$this->settings_page,
			'g470_security_updater_section'
		);

		add_settings_field(
			'g470_security_github_token',
			__( 'GitHub Token', 'g470-gatonet-plugins' ),
			array( $this, 'render_github_token_field' ),
			$this->settings_page,
			'g470_security_updater_section'
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

		// Sanitize protection mode.
		if ( isset( $input['g470_security_protection_mode'] ) ) {
			$mode                                 = sanitize_text_field( $input['g470_security_protection_mode'] );
			$output['g470_security_protection_mode'] = in_array( $mode, array( 'block', 'sanitize' ), true )
				? $mode
				: 'block';
		} else {
			$output['g470_security_protection_mode'] = 'block';
		}

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

		// Sanitize GitHub repository URL.
		if ( isset( $input['g470_security_github_repo'] ) ) {
			$output['g470_security_github_repo'] = esc_url_raw( trim( $input['g470_security_github_repo'] ) );
		} else {
			$output['g470_security_github_repo'] = '';
		}

		// Sanitize GitHub token (keep private).
		if ( isset( $input['g470_security_github_token'] ) ) {
			$output['g470_security_github_token'] = sanitize_text_field( trim( $input['g470_security_github_token'] ) );
		} else {
			$output['g470_security_github_token'] = '';
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
	 * Render the protection mode field.
	 *
	 * @since 1.0.1
	 */
	public function render_protection_mode_field() {
		$options = $this->get_options();
		$mode    = isset( $options['g470_security_protection_mode'] ) ? $options['g470_security_protection_mode'] : 'block';
		?>
		<fieldset>
			<label>
				<input type="radio"
				       name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_protection_mode]"
				       value="block"
				       <?php checked( 'block', $mode ); ?> />
				<strong><?php esc_html_e( 'Block Access', 'g470-gatonet-plugins' ); ?></strong> -
				<?php esc_html_e( 'Completely block unauthorized users (returns 401/403 error)', 'g470-gatonet-plugins' ); ?>
			</label>
			<br>
			<label>
				<input type="radio"
				       name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_protection_mode]"
				       value="sanitize"
				       <?php checked( 'sanitize', $mode ); ?> />
				<strong><?php esc_html_e( 'Sanitize Data', 'g470-gatonet-plugins' ); ?></strong> -
				<?php esc_html_e( 'Allow access but replace sensitive information with generic placeholders', 'g470-gatonet-plugins' ); ?>
			</label>
		</fieldset>
		<p class="description">
			<?php esc_html_e( 'Choose how to protect the users endpoint. Block mode is more secure, but sanitize mode allows public access while hiding usernames and sensitive data.', 'g470-gatonet-plugins' ); ?>
		</p>
		<?php
	}

	/**
	 * Render updater section description.
	 *
	 * @since 1.0.0
	 */
	public function render_updater_section_description() {
		?>
		<p><?php esc_html_e( 'Configure GitHub repository for automatic plugin updates. Leave blank to disable.', 'g470-gatonet-plugins' ); ?></p>
		<?php
	}

	/**
	 * Render the GitHub repository URL field.
	 *
	 * @since 1.0.0
	 */
	public function render_github_repo_field() {
		$options = $this->get_options();
		?>
		<input type="url"
		       id="g470_security_github_repo"
		       name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_github_repo]"
		       value="<?php echo esc_attr( isset( $options['g470_security_github_repo'] ) ? $options['g470_security_github_repo'] : '' ); ?>"
		       class="regular-text"
		       placeholder="https://github.com/owner/repo" />
		<p class="description">
			<?php esc_html_e( 'Full GitHub repository URL (e.g., https://github.com/yourusername/g470_security)', 'g470-gatonet-plugins' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the GitHub token field.
	 *
	 * @since 1.0.0
	 */
	public function render_github_token_field() {
		$options = $this->get_options();
		$token   = isset( $options['g470_security_github_token'] ) ? $options['g470_security_github_token'] : '';
		?>
		<input type="password"
		       id="g470_security_github_token"
		       name="<?php echo esc_attr( $this->option_name ); ?>[g470_security_github_token]"
		       value="<?php echo esc_attr( $token ); ?>"
		       class="regular-text"
		       placeholder="ghp_xxxxxxxxxxxxxxxxxxxx" />
		<p class="description">
			<?php esc_html_e( 'Optional. GitHub Personal Access Token (required for private repositories). Keep this secret!', 'g470-gatonet-plugins' ); ?>
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
