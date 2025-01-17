<?php

/**
 * Delete data and table during plugin uninstall.
 */
if (!defined('WP_UNINSTALL_PLUGIN'))
{
	die();
}

// delete main settings an notices options
delete_option('wpkln_login_notification');
delete_option('wpkln_kaya_login_notification_admin_notices');

// delete multisite settings an notices options
if (is_multisite())
{
	$subSites = get_sites();
	foreach ($subSites as $i_site)
	{
		switch_to_blog($i_site->blog_id);
		if (get_option('wpkln_login_notification'))
		{
			delete_option('wpkln_login_notification');
			delete_option('wpkln_kaya_login_notification_admin_notices');
		}
		restore_current_blog();
	}
}
