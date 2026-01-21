<?php
/**
 * Settings page template.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<form method="post" action="options.php">
		<?php
		// Output security fields, sections, and settings.
		settings_fields( $this->settings->get_settings_group() );
		do_settings_sections( $this->settings->get_settings_page() );
		submit_button();
		?>
	</form>
</div>
