<?php
/*
 * Plugin Name: Kaya Login Notification
 * Description: Sends email notification on successful login, with fully customizable settings.
 * Tags: admin login notification, email notification, login notification, email notify on admin login, login
 * Author: Kaya Studio
 * Author URI:  https://kayastudio.fr
 * Donate link: http://dotkaya.org/a-propos/
 * Contributors: kayastudio
 * Requires at least: 4.6.0
 * Tested up to: 6.6
 * Stable tag: 1.6.0
 * Version: 1.6.0
 * Requires PHP: 5.5
 * Text Domain: kaya-login-notification
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!defined('WPKLN_VERSION'))					define('WPKLN_VERSION', '1.6.0');
if (!defined('WPKLN_FILE'))						define('WPKLN_FILE', plugin_basename(__FILE__));
if (!defined('WPKLN_PLUGIN_URL'))				define('WPKLN_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WPKLN_PLUGIN_PATH'))				define('WPKLN_PLUGIN_PATH', plugin_dir_path(__FILE__));
if (!defined('WPKLN_TEXT_DOMAIN'))				define('WPKLN_TEXT_DOMAIN', 'kaya-login-notification');
if (!defined('WPKLN_LOGIN_NOTIFICATION_DB'))	define('WPKLN_LOGIN_NOTIFICATION_DB', 'wpkln_login_notification');

// Include the main functions
require_once(WPKLN_PLUGIN_PATH . 'lib/functions.php');

// Include Kaya Studio Admin Dashboard class
require_once(WPKLN_PLUGIN_PATH . 'lib/class.admin_dashboard_kayastudio.php');
// Include Kaya Studio Admin Plugins Dashboard class
require_once(WPKLN_PLUGIN_PATH . 'lib/class.admin_dashboard_kayastudio_plugins.php');
// Include Kaya Login Notification Dashboard class
require_once(WPKLN_PLUGIN_PATH . 'lib/class.admin_dashboard_wpkln.php');

if (is_admin())
{
	// Include the admin functions
	require_once(WPKLN_PLUGIN_PATH . 'lib/functions-admin.php');
}

/**
 * Load Localisation files.
 *
 * @since 1.1.0
 */
if (!function_exists('wpkln_plugin_loadLocalisation'))
{
	function wpkln_plugin_loadLocalisation()
	{
		load_plugin_textdomain(WPKLN_TEXT_DOMAIN, false, dirname(WPKLN_FILE) . '/languages/');
	}
	add_action('plugins_loaded', 'wpkln_plugin_loadLocalisation');
}

/**
 * Install database setup on plugin activation.
 */
if (!function_exists('wpkln_plugin_doActivation'))
{
	function wpkln_plugin_doActivation()
	{
		require_once(WPKLN_PLUGIN_PATH . 'lib/functions-database.php');
		wpkln_database_installSetup();
	}
	register_activation_hook(__FILE__, 'wpkln_plugin_doActivation');
}

/**
 * Install database setup on new site creation in multisite.
 *
 * @param WP_Site $new_site New site object.
 *
 * @since 1.4.0
 */
if (!function_exists('wpkln_plugin_doNewSite'))
{
	function wpkln_plugin_doNewSite($new_site)
	{
		require_once(WPKLN_PLUGIN_PATH . 'lib/functions-database.php');
		wpkln_database_installSetupNewSite($new_site);
	}
	add_action('wp_initialize_site', 'wpkln_plugin_doNewSite', 20, 1);
}

/**
 * Check for plugin update from older version with dedicated database table.
 *
 * @since 1.4.0
 */
if (!function_exists('wpkln_plugin_updateDatabaseTableCheck'))
{
	function wpkln_plugin_updateDatabaseTableCheck()
	{
		global $wpdb;
		$tableName = $wpdb->prefix . WPKLN_LOGIN_NOTIFICATION_DB;
		if ((!get_option(WPKLN_LOGIN_NOTIFICATION_DB)) && ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName)) === $tableName))
		{
			require_once(WPKLN_PLUGIN_PATH . 'lib/functions-database.php');
			// migrate from dedicated table
			wpkln_database_migrateFromDedicatedTable();
		}
	}
	add_action('plugins_loaded', 'wpkln_plugin_updateDatabaseTableCheck');
}

/**
 * Show action links on the plugin screen.
 *
 * @param mixed $links Plugin Action links.
 * @param mixed $file Plugin Base file.
 *
 * @return array
 */
if (!function_exists('wpkln_plugin_displayActionLinks'))
{
	function wpkln_plugin_displayActionLinks($links, $file)
	{
		if (WPKLN_FILE === $file)
		{
			$actionLinks = array(
				'ks_panel'		=> '<a href="' . esc_url(admin_url('admin.php?page=' . WPKLN_Admin_Dashboard::$_pageSlug)) . '" title="' . esc_html__('Login Notification settings', WPKLN_TEXT_DOMAIN) . '">' . esc_html__('Settings', WPKLN_TEXT_DOMAIN) . '</a>',
				'ks_plugins'	=> '<a href="' . esc_url('https://profiles.wordpress.org/kayastudio#content-plugins') . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('Other plugins by Kaya Studio', WPKLN_TEXT_DOMAIN) . '">' . esc_html__('Other plugins', WPKLN_TEXT_DOMAIN) . '</a>',
			);

			return array_merge($actionLinks, $links);
		}

		return (array) $links;
	}
	add_filter('plugin_action_links', 'wpkln_plugin_displayActionLinks', 10, 2);
}

/**
 * Show row meta links on the plugin screen.
 *
 * @param mixed $links Plugin Row Meta links.
 * @param mixed $file Plugin Base file.
 *
 * @return array
 */
if (!function_exists('wpkln_plugin_displayMetaLinks'))
{
	function wpkln_plugin_displayMetaLinks($links, $file)
	{
		if (WPKLN_FILE === $file)
		{
			$metaLinks = array(
				'ks_rate'	=> '<a href="' . esc_url('https://wordpress.org/support/plugin/kaya-login-notification/reviews/?rate=5#new-post') . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('Rate and review this plugin at WordPress.org', WPKLN_TEXT_DOMAIN) . '">' . esc_html__('Rate this plugin', WPKLN_TEXT_DOMAIN) . '&nbsp;&#9733;</a>',
				'ks_donate'	=> '<a href="' . esc_url('http://dotkaya.org/a-propos/') . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('Donate to support the advancement of this plugin', WPKLN_TEXT_DOMAIN) . '">' . esc_html__('Donate to this plugin', WPKLN_TEXT_DOMAIN) . '&nbsp;&#9829;</a>',
			);

			return array_merge($links, $metaLinks);
		}

		return (array) $links;
	}
	add_filter('plugin_row_meta', 'wpkln_plugin_displayMetaLinks', 10, 2);
}
