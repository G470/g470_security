<?php
/**
 * General Settings Tab - Plugin Update Settings Only.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="g470-tab-content active" id="tab-general">
	<form method="post" action="options.php">
		<?php
		// Output security fields, sections, and settings.
		settings_fields( $this->settings->get_settings_group() );
		do_settings_sections( $this->settings->get_settings_page() );
		submit_button();
		?>
	</form>
</div>
