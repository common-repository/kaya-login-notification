<?php

/** 
 * Kaya Login Notification - Admin Dashboard Class
 * Manages Kaya Login Notification admin page.
 *
 * @since 1.5.0
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKLN_Admin_Dashboard'))
{
	class WPKLN_Admin_Dashboard
	{
		/**
		 * Menu Page Slug
		 */
		public static $_pageSlug = 'wpkln-kaya-login-notification-admin-settings-page';

		/**
		 * Notice Option Name
		 */
		public static $_noticeOptionName = 'wpkln_kaya_login_notification_admin_notices';

		/**
		 * Main Initialisation
		 * Adds admin menu, enqueue scripts, manage post requests and admin notices.
		 */
		public static function init()
		{
			add_action('admin_menu', array(__CLASS__, 'addAdminMenuPage'));
			add_action('admin_enqueue_scripts', array(__CLASS__, 'addAdminCssJs'));
			add_action('admin_post_wpkln_login_notification_form', array(__CLASS__, 'doAdminPostRequests'));
			add_action('admin_post_wpkln_login_notification_test_email', array(__CLASS__, 'doAdminPostTestEmail'));
			add_action('admin_notices', array(__CLASS__, 'doAdminNotices'));
		}

		/**
		 * Adds admin menu
		 * Adds a submenu for Kaya Login Notification admin page.
		 */
		public static function addAdminMenuPage()
		{
			// add plugin features page
			add_submenu_page(
				WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug,
				esc_html__('Login Notification', WPKLN_TEXT_DOMAIN),
				esc_html__('Login Notification', WPKLN_TEXT_DOMAIN),
				'manage_options',
				self::$_pageSlug,
				array(__CLASS__, 'doAdminPage')
			);
		}

		/**
		 * Return the plugin informations to be added in Plugins List
		 *
		 * @return array
		 */
		public static function getPluginInfos()
		{
			return array(
				'title'		=> esc_attr('Kaya Login Notification'),
				'page_name'	=> esc_attr__('Login Notification settings', WPKLN_TEXT_DOMAIN),
				'page_slug'	=> self::$_pageSlug,
				'page_text'	=> esc_attr__('Sends email notification on successful login, with fully customizable settings.', WPKLN_TEXT_DOMAIN),
			);
		}

		/**
		 * Displays admin page
		 * Includes the page and display it.
		 */
		public static function doAdminPage()
		{
			if (is_file(plugin_dir_path(__FILE__) . '../includes/wpkln-admin-page.php'))
			{
				include_once plugin_dir_path(__FILE__) . '../includes/wpkln-admin-page.php';
				wpkln_admin_doOptionPage();
			}
		}

		/**
		 * Adds admin menu styles and scripts
		 * Registers and enqueue styles and scripts for Kaya Login Notification admin page.
		 *
		 * @param int $hook Hook suffix for the current admin page.
		 */
		public static function addAdminCssJs($hook)
		{
			if (isset($hook) && !empty($hook) && get_plugin_page_hookname(self::$_pageSlug, WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug) === $hook)
			{
				wp_register_style('kayastudio_wp_admin_css', plugin_dir_url(__FILE__) . '../css/kayastudio-admin-page-pkg.min.css', false, '1.0.0');
				wp_enqueue_style('kayastudio_wp_admin_css');
			}
		}

		/**
		 * Manages admin page requests actions
		 * Edit or reset the WPKLN_login_notification object and redirect.
		 *
		 * @return bool
		 */
		public static function doAdminPostRequests()
		{
			if (empty($_POST) || empty($_POST['wpkln_action']) || empty($_POST['wpkln']))
			{
				return false;
			}

			if (!current_user_can('manage_options'))
			{
				return false;
			}

			if (!empty($_POST['wpkln_target']) && 'login_notification' === $_POST['wpkln_target'])
			{
				// include the WPKLN_login_notification class
				require_once(WPKLN_PLUGIN_PATH . 'lib/class.crud_login_notification.php');

				if ($_POST['wpkln_action'] == 'edit')
				{
					// init WPKLN_login_notification object for update
					$kayaLoginNotification = new WPKLN_login_notification('update');
				}
				elseif ($_POST['wpkln_action'] == 'delete')
				{
					// init WPKLN_login_notification object for reset
					$kayaLoginNotification = new WPKLN_login_notification('delete');
					// reset with default settings
					require_once(WPKLN_PLUGIN_PATH . 'lib/functions-database.php');
					wpkln_database_defaultValues();
				}

				// set admin url query with the page and the action message
				$adminUrlQuery = array(
					'page'		=> self::$_pageSlug,
					'message'	=> '1'
				);
				// set the full admin url for redirection
				$redirectURL = esc_url_raw(admin_url('admin.php?' . http_build_query($adminUrlQuery)));

				if (wp_redirect($redirectURL))
				{
					return true;
				}
			}

			return false;
		}

		/**
		 * Manages admin page test email
		 * Sends a test notification email.
		 *
		 * @return bool
		 */
		public static function doAdminPostTestEmail()
		{
			if (empty($_POST) || empty($_POST['wpkln_action']) || 'test_email' != $_POST['wpkln_action'])
			{
				return false;
			}

			if (!current_user_can('manage_options'))
			{
				return false;
			}

			if (!empty($_POST['wpkln_target']) && 'login_notification_test_email' === $_POST['wpkln_target'])
			{
				$currentUser = wp_get_current_user();
				$username = (string) $currentUser->user_login;

				// Manages admin page notice for test email failure
				add_action('wp_mail_failed', array(__CLASS__, 'doAdminPostTestEmailFailed'), 10, 1);

				// sends test email
				$testEmailSent = wpkln_loginSuccessNotifier($username, $currentUser);
				// success notification
				if ($testEmailSent)
				{
					WPKLN_Admin_Dashboard::addAdminNotice(__('Sending a test email', WPKLN_TEXT_DOMAIN), $testEmailSent);
				}

				// set admin url query with the page and the action message
				$adminUrlQuery = array(
					'page'		=> self::$_pageSlug,
					'message'	=> '1'
				);
				// set the full admin url for redirection
				$redirectURL = esc_url_raw(admin_url('admin.php?' . http_build_query($adminUrlQuery)));

				if (wp_redirect($redirectURL))
				{
					return true;
				}
			}

			return false;
		}

		/**
		 * Manages admin page notice for test email failure
		 * Get mailing error.
		 *
		 * @param WP_Error $wp_error Object containing the list of errors.
		 */
		public static function doAdminPostTestEmailFailed($wp_error)
		{
			if (!empty($wp_error))
			{
				WPKLN_Admin_Dashboard::addAdminNotice(__('Email sending test', WPKLN_TEXT_DOMAIN) . ' : ' . $wp_error->get_error_message(), false);
			}
		}

		/**
		 * Adds notice to admin page.
		 *
		 * @param string	$p_message The notice message.
		 * @param bool	$p_success Set this to TRUE for a success notice.
		 *
		 * @since 1.3.0
		 */
		public static function addAdminNotice($p_message = '', $p_success = false)
		{
			// get all notices
			$notices = get_option(self::$_noticeOptionName, array());
			// add the notice to the actual list
			array_push($notices, array(
				'message'	=> $p_message,
				'type'		=> (($p_success) ? '1' : '0')
			));
			// save notices
			update_option(self::$_noticeOptionName, $notices);
		}

		/**
		 * Displays admin page notices
		 * Prints admin screen notices about form requests.
		 *
		 * @since 1.3.0
		 */
		public static function doAdminNotices()
		{
			$currentScreen = get_current_screen();
			if (get_plugin_page_hookname(self::$_pageSlug, WP_KayaStudio_Plugins_Admin_Dashboard::$_pageSlug) !== $currentScreen->id)
			{
				return false;
			}

			// get all notices
			$notices = get_option(self::$_noticeOptionName, array());

			$output = '';
			foreach ($notices as $i_notice)
			{
				// get notice message
				$noticeMessage = $i_notice['message'];

				// set default error notice data
				$noticeClasses	= 'notice-error';
				$noticeTitle	= __('Error!', WPKLN_TEXT_DOMAIN);

				// set success notice data
				if ('1' === $i_notice['type'])
				{
					$noticeClasses	= 'notice-success';
					$noticeTitle	= __('Success!', WPKLN_TEXT_DOMAIN);
				}
				// set notice HTML structure
				$output .= '<div class="notice ' . esc_attr($noticeClasses) . ' is-dismissible">';
				$output .= '<p><b>' . esc_html($noticeTitle) . '</b><br />' . esc_html($noticeMessage) . '</p>';
				$output .= '</div>';
			}

			if (!empty($notices))
			{
				// display the notices
				echo $output;
				// delete notices to prevent other displaying
				delete_option(self::$_noticeOptionName, array());
			}
		}
	}
}
