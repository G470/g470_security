<?php
/**
 * Module Settings Page.
 *
 * Displays settings for a specific module based on the 'module' query parameter.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$module_id  = isset( $_GET['module'] ) ? sanitize_key( $_GET['module'] ) : '';
$module_mgr = $this->get_module_manager();
$module     = $module_mgr->get_module( $module_id );

if ( ! $module ) {
	wp_safe_redirect( admin_url( 'options-general.php?page=g470-security-settings' ) );
	exit;
}
?>

<div class="wrap">
	<h1>
		<?php echo esc_html( get_admin_page_title() ); ?> 
		<span class="subtitle" style="font-size: 14px; font-weight: normal; color: #666;">
			<?php echo esc_html( $module['name'] ); ?>
		</span>
	</h1>

	<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'g470-security-settings', 'tab' => 'patches' ), admin_url( 'options-general.php' ) ) ); ?>"
	   class="button button-secondary" style="margin-bottom: 20px;">
		<?php esc_html_e( '← Back to Patches', 'g470-gatonet-plugins' ); ?>
	</a>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		// Output settings for this module
		settings_fields( $this->settings->get_settings_group() );

		// Render section for this specific module
		switch ( $module_id ) {
			case 'rest_users_protection':
				do_settings_sections( 'g470_security_rest_users_settings' );
				break;
			default:
				echo '<p>' . esc_html__( 'No settings available for this module.', 'g470-gatonet-plugins' ) . '</p>';
		}

		submit_button();
		?>
	</form>
</div>
