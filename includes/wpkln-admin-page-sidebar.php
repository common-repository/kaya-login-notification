<?php

/**
 * Kaya Login Notification - Admin Page Sidebar
 * Displays Kaya Login Notification admin page sidebar.
 */

/**
 * Displays Login Notification page sidebar.
 */
if (!function_exists('wpkln_admin_doPageSidebar'))
{
	function wpkln_admin_doPageSidebar()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

?>

		<div class="ks-wp-dashboard-page-card">
			<div class="ks-wp-dashboard-page-card-header">
				<?php echo esc_html__('Reviews', WPKLN_TEXT_DOMAIN); ?>
			</div>
			<div class="ks-wp-dashboard-page-card-body">
				<h5 class="ks-wp-dashboard-page-card-title"><?php echo esc_html__('Rate and review this plugin at WordPress.org', WPKLN_TEXT_DOMAIN); ?>&nbsp;&#9733;</h5>
				<p class="ks-wp-dashboard-page-card-text">
					<?php echo esc_html__('Please take the time to let me know about your experience and rate this plugin.', WPKLN_TEXT_DOMAIN); ?>
				</p>
				<p class="ks-wp-dashboard-page-card-text">
					<a href="<?php echo esc_url('https://wordpress.org/support/plugin/kaya-login-notification/reviews/?rate=5#new-post'); ?>" class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr__('Rate and review this plugin at WordPress.org', WPKLN_TEXT_DOMAIN); ?>"><?php echo esc_html__('Rate this plugin', WPKLN_TEXT_DOMAIN); ?></a>
				</p>
			</div>
		</div>

<?php
	}
}
