<?php

/**
 * Kaya Login Notification - Administration Functions.
 * Functions for the plugin administration.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!is_admin())
{
	exit; // Exit if accessed outside dashboard
}

/**
 * Check for KayaStudio Plugins object and create it if not found.
 *
 * @since 1.5.0
 */
if (!isset($wp_kayastudio_dashboard_pluginsList))
{
	global $wp_kayastudio_dashboard_pluginsList;
	$wp_kayastudio_dashboard_pluginsList = new WP_KayaStudio_Plugins_List_Admin_Dashboard();
}

/**
 * Adds administration plugin menu pages.
 *
 * Adds pages to admin menu (Main page, Plugin Settings), and adds plugin infos in plugins list.
 *
 * @return bool	True if the current user has the specified capability for seeing the menu, or False if not.
 *
 * @since 1.5.0
 */
if (!function_exists('wpkln_admin_addMenuPages'))
{
	function wpkln_admin_addMenuPages()
	{
		if (!current_user_can('manage_options'))
		{
			return false;
		}

		global $wp_kayastudio_dashboard_pluginsList;

		// Add Kaya Studio Main page
		WP_KayaStudio_Plugins_Admin_Dashboard::init();
		// Add Kaya Login Notification page
		WPKLN_Admin_Dashboard::init();
		// Add Kaya Login Notification infos in plugins list
		$wp_kayastudio_dashboard_pluginsList->addPluginInList(WPKLN_Admin_Dashboard::getPluginInfos());

		return true;
	}
	add_action('init', 'wpkln_admin_addMenuPages');
}

/**
 * Return WordPress users roles.
 *
 * Retrieve list of WordPress roles id and names, including the 'no role' and 'super admin' for multisite.
 *
 * @return array	List of roles id and names.
 */
if (!function_exists('wpkln_admin_getUsersRoles'))
{
	function wpkln_admin_getUsersRoles()
	{
		global $wp_roles;
		$usersRoles = array();
		foreach ($wp_roles->get_names() as $i_roleKey => $i_roleValue)
		{
			$usersRoles[] = array('id' => $i_roleKey, 'name' => $i_roleValue);
		}

		// add super admin role if multisite
		if (is_multisite())
		{
			array_unshift($usersRoles, array('id' => 'superadmin', 'name' => __('Super Admin')));
		}

		// add the 'no role' option.
		$usersRoles[] = array('id' => 'none', 'name' => __('No role'));

		return $usersRoles;
	}
}
