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

	<?php if ( 'rest_users_protection' === $module_id ) : ?>
		<hr />
		<h2><?php esc_html_e( 'Test REST Users Protection', 'g470-gatonet-plugins' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Click to simulate access outcome for the current user with the saved settings.', 'g470-gatonet-plugins' ); ?>
		</p>
		<label for="g470-test-scenario" style="margin-right:8px;">
			<?php esc_html_e( 'Scenario:', 'g470-gatonet-plugins' ); ?>
		</label>
		<select id="g470-test-scenario" class="regular-text" style="width:auto; margin-right:8px;">
			<option value="current"><?php esc_html_e( 'Current User', 'g470-gatonet-plugins' ); ?></option>
			<option value="guest"><?php esc_html_e( 'Guest (not logged in)', 'g470-gatonet-plugins' ); ?></option>
			<option value="no_cap"><?php esc_html_e( 'Logged-in without required capability', 'g470-gatonet-plugins' ); ?></option>
			<option value="has_cap"><?php esc_html_e( 'Logged-in with required capability', 'g470-gatonet-plugins' ); ?></option>
		</select>
		<button id="g470-test-button" class="button">
			<?php esc_html_e( 'Run Test', 'g470-gatonet-plugins' ); ?>
		</button>
		<span id="g470-test-spinner" class="spinner" style="float:none; visibility:hidden;"></span>
		<div id="g470-test-result" style="margin-top:10px;"></div>

		<script type="text/javascript">
			(function($){
				var $btn = $('#g470-test-button');
				var $spinner = $('#g470-test-spinner');
				var $result = $('#g470-test-result');
				var nonce = '<?php echo esc_js( wp_create_nonce( 'g470_test_rest_users_protection' ) ); ?>';
				$btn.on('click', function(e){
					e.preventDefault();
					$btn.prop('disabled', true);
					$spinner.css('visibility', 'visible');
					$result.text('<?php echo esc_js( __( 'Testing...', 'g470-gatonet-plugins' ) ); ?>');
					var scenario = $('#g470-test-scenario').val();
					$.post(ajaxurl, {
						action: 'g470_test_rest_users_protection',
						nonce: nonce,
						scenario: scenario
					})
					.done(function(resp){
						if (resp && resp.success && resp.data) {
							var data = resp.data;
							var labelMap = {
								current: '<?php echo esc_js( __( 'Current User', 'g470-gatonet-plugins' ) ); ?>',
								guest: '<?php echo esc_js( __( 'Guest', 'g470-gatonet-plugins' ) ); ?>',
								no_cap: '<?php echo esc_js( __( 'No Capability', 'g470-gatonet-plugins' ) ); ?>',
								has_cap: '<?php echo esc_js( __( 'Has Required Capability', 'g470-gatonet-plugins' ) ); ?>'
							};
							var scenarioLabel = labelMap[data.scenario] || data.scenario;
							var msg = '[' + scenarioLabel + '] ' + data.message + ' ' + '(status: ' + data.http_status + ', mode: ' + data.protection_mode + ', required cap: ' + data.required_cap + ')';
							$result.text(msg);
						} else {
							$result.text('<?php echo esc_js( __( 'Test failed. Please try again.', 'g470-gatonet-plugins' ) ); ?>');
						}
					})
					.fail(function(){
						$result.text('<?php echo esc_js( __( 'Request error. Please check your network.', 'g470-gatonet-plugins' ) ); ?>');
					})
					.always(function(){
						$btn.prop('disabled', false);
						$spinner.css('visibility', 'hidden');
					});
				});
			})(jQuery);
		</script>
	<?php endif; ?>
</div>
