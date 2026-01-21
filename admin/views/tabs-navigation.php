<?php
/**
 * Tab navigation component.
 *
 * @package    G470_Security
 * @subpackage G470_Security/admin/views
 * @var string $current_tab Current active tab.
 * @var array  $tabs        Available tabs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="nav-tab-wrapper g470-nav-tab-wrapper">
	<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
		<?php
		$tab_url    = add_query_arg(
			array(
				'page' => 'g470-security-settings',
				'tab'  => $tab_key,
			),
			admin_url( 'options-general.php' )
		);
		$active_class = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';
		?>
		<a href="<?php echo esc_url( $tab_url ); ?>" 
		   class="nav-tab <?php echo esc_attr( $active_class ); ?>">
			<?php echo esc_html( $tab_label ); ?>
		</a>
	<?php endforeach; ?>
</h2>
