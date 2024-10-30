<?php

/**
 * Kaya Login Notification - Database Functions.
 * Functions for the plugin database management.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

/**
 * Setup default settings on plugin activation.
 */
if (!function_exists('wpkln_database_installSetup'))
{
	function wpkln_database_installSetup()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		if (!get_option(WPKLN_LOGIN_NOTIFICATION_DB))
		{
			// loading localisation before the first setup.
			wpkln_plugin_loadLocalisation();
			// set default settings
			wpkln_database_defaultValues();
		}

		// setup default settings for multisite
		if (is_multisite())
		{
			$subSites = get_sites();
			foreach ($subSites as $i_site)
			{
				switch_to_blog($i_site->blog_id);
				if (!get_option(WPKLN_LOGIN_NOTIFICATION_DB))
				{
					// loading localisation before the setup.
					wpkln_plugin_loadLocalisation();
					// set default settings
					wpkln_database_defaultValues();
				}
				restore_current_blog();
			}
		}
	}
}

/**
 * Setup default settings on new site creation in multisite.
 *
 * @param WP_Site $new_site New site object.
 *
 * @since 1.4.0
 */
if (!function_exists('wpkln_database_installSetupNewSite'))
{
	function wpkln_database_installSetupNewSite($new_site)
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		// setup default settings for multisite
		if (is_multisite())
		{
			switch_to_blog($new_site->id);
			if (!get_option(WPKLN_LOGIN_NOTIFICATION_DB))
			{
				// loading localisation before the setup.
				wpkln_plugin_loadLocalisation();
				// set default settings
				wpkln_database_defaultValues();
			}
			restore_current_blog();
		}
	}
}

/**
 * Set default database values.
 */
if (!function_exists('wpkln_database_defaultValues'))
{
	function wpkln_database_defaultValues()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		require_once(WPKLN_PLUGIN_PATH . 'lib/class.crud_login_notification.php');
		$kayaLoginNotification = new WPKLN_login_notification('new');
	}
}

/**
 * Migrate settings from dedicated database table to WordPress Options.
 *
 * @since 1.4.0
 */
if (!function_exists('wpkln_database_migrateFromDedicatedTable'))
{
	function wpkln_database_migrateFromDedicatedTable()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

		global $wpdb;
		// get current settings
		$tableName = $wpdb->prefix . WPKLN_LOGIN_NOTIFICATION_DB;
		$settingsQuery = $wpdb->get_results('SELECT * FROM ' . $tableName . ' WHERE name="_login_notification_success" ORDER BY id');
		$settingsData = '';

		if (!empty($settingsQuery) && count($settingsQuery) == 1)
		{
			$login_notification = stripslashes_deep($settingsQuery);
			if (empty($login_notification) || !is_array($login_notification)) return '';

			foreach ($login_notification as $p_login_notification)
			{
				$attributes = get_object_vars($p_login_notification);
				if (!isset($attributes['id']) || empty($attributes['name']) || empty($attributes['data']) || !is_numeric($attributes['id'])) return '';

				// get settings data
				$settingsData = $attributes['data'];
			}
			if (empty($settingsData)) return '';

			// save settings
			$settingsUpdated = update_option(WPKLN_LOGIN_NOTIFICATION_DB, $settingsData, false);

			// Delete data and dedicated table
			if ($settingsUpdated)
			{
				$wpdb->query('DELETE FROM ' . $tableName);
				$wpdb->query('DROP TABLE ' . $tableName);
			}
		}
	}
}
