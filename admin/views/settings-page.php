<?php
/**
 * Settings page template with tabbed navigation.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tabs = $this->get_tabs();
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<?php
	// Render tab navigation
	include G470_SECURITY_PATH . 'admin/views/tabs-navigation.php';
	?>

	<?php
	// Render tab content based on current tab
	switch ( $current_tab ) {
		case 'patches':
			include G470_SECURITY_PATH . 'admin/views/patches-tab.php';
			break;

		case 'general':
		default:
			include G470_SECURITY_PATH . 'admin/views/general-settings-tab.php';
			break;
	}
	?>
</div>
