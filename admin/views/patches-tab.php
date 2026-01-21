<?php
/**
 * Available Patches Tab.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$module_manager = $this->get_module_manager();
$modules        = $module_manager->get_modules();
?>

<div class="g470-tab-content active tab-patches" id="tab-patches">
	<p><?php esc_html_e( 'Enable or disable security patches/modules. Some modules may have additional configuration options.', 'g470-gatonet-plugins' ); ?></p>

	<?php if ( ! empty( $modules ) ) : ?>
		<div class="g470-modules-list">
			<?php foreach ( $modules as $module_id => $module ) : ?>
				<?php
				$is_enabled = $module_manager->is_module_enabled( $module_id );
				$is_locked  = ! empty( $module['locked'] );
				$item_class = $is_locked ? 'g470-module-item g470-module-locked card' : 'g470-module-item card';
				?>
				<div class="<?php echo esc_attr( $item_class ); ?>" data-module-id="<?php echo esc_attr( $module_id ); ?>">
					<div class="g470-module-header">
						<div class="g470-module-info">
							<h3>
								<?php echo esc_html__( $module['name'], 'g470-gatonet-plugins' ); ?>
								<?php if ( $is_locked ) : ?>
									<span class="dashicons dashicons-lock" title="<?php esc_attr_e( 'Core module (cannot be disabled)', 'g470-gatonet-plugins' ); ?>"></span>
								<?php endif; ?>
							</h3>
							<p><?php echo esc_html__( $module['description'], 'g470-gatonet-plugins' ); ?></p>
						</div>
						<div class="g470-module-toggle">
							<?php if ( $is_locked ) : ?>
								<span class="description"><?php esc_html_e( 'Always Active', 'g470-gatonet-plugins' ); ?></span>
							<?php else : ?>
								<label class="switch g470-style-toggleswitch">
									<input type="checkbox" 
									       class="g470-module-toggle-input"
									       data-module-id="<?php echo esc_attr( $module_id ); ?>"
									       <?php checked( $is_enabled ); ?> />
									<span><?php echo $is_enabled ? esc_html__( 'Enabled', 'g470-gatonet-plugins' ) : esc_html__( 'Disabled', 'g470-gatonet-plugins' ); ?></span>
								</label>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( $is_enabled && ! empty( $module['has_settings'] ) ) : ?>
						<div class="g470-module-settings-link">
							<?php
							// Build settings URL
							// When settings_callback is defined, use custom callback
							// Otherwise, link to dedicated module settings page
							if ( ! empty( $module['settings_callback'] ) ) {
								// Custom settings via callback (future use)
								$settings_url = add_query_arg(
									array(
										'page' => 'g470-security-settings',
										'tab'  => 'general',
									),
									admin_url( 'options-general.php' )
								);
							} else {
								// Dedicated module settings page
								$settings_url = add_query_arg(
									array(
										'page'   => 'g470-security-settings',
										'module' => $module_id,
									),
									admin_url( 'options-general.php' )
								);
							}
							?>
							<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Configure Settings', 'g470-gatonet-plugins' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<script type="text/javascript">
		(function() {
			document.addEventListener('DOMContentLoaded', function() {
				const toggles = document.querySelectorAll('.g470-module-toggle-input');
				
				toggles.forEach(function(checkbox) {
					checkbox.addEventListener('change', function() {
						const moduleId = this.dataset.moduleId;
						const isEnabled = this.checked;
						
						const formData = new FormData();
						formData.append('action', 'g470_toggle_module');
						formData.append('module_id', moduleId);
						formData.append('enabled', isEnabled ? 1 : 0);
						formData.append('nonce', '<?php echo esc_js( wp_create_nonce( 'g470_toggle_module' ) ); ?>');
						
						fetch(ajaxurl, {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(response => {
							if (response.success) {
								location.reload();
							} else {
								alert(response.data.message || '<?php esc_html_e( 'Failed to toggle module.', 'g470-gatonet-plugins' ); ?>');
								checkbox.checked = !isEnabled;
							}
						})
						.catch(() => {
							alert('<?php esc_html_e( 'An error occurred. Please try again.', 'g470-gatonet-plugins' ); ?>');
							checkbox.checked = !isEnabled;
						});
					});
				});
			});
		})();
		</script>

	<?php else : ?>
		<p><?php esc_html_e( 'No modules available.', 'g470-gatonet-plugins' ); ?></p>
	<?php endif; ?>
</div>
