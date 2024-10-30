<?php

/** 
 * Kaya Login Notification - Login Notification CRUD Class
 * Loads, Saves and Reset Login Notification Object.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKLN_login_notification'))
{
	class WPKLN_login_notification
	{
		public $id;
		public $data;
		public $new;
		public $saved;
		public $deleted;

		/**
		 * Creates Login Notification Object.
		 * Preloads with all Login Notification through 'all'
		 *
		 * @param string $action : 'all' will load all Login Notification records into $login_notification
		 * @param string $action : 'new' will load new Login Notification with defaults
		 */
		public function __construct($action = 'all')
		{
			if ($action == 'all') $this->load_all();
			elseif ($action == 'new') $this->load_new();
			elseif ($action == 'update') $this->update();
			elseif ($action == 'delete') $this->destroy();
		}

		/**
		 * Defaults data
		 *
		 * @return array
		 */
		private function default_new()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$new_ln_success = array();
			$new_ln_success['new'] = true;
			$new_ln_success['id'] = WPKLN_LOGIN_NOTIFICATION_DB;
			$new_ln_success['data'] = array();
			$new_ln_success['data']['_email_from_name'] = get_bloginfo('name');
			$new_ln_success['data']['_email_from_address'] = get_bloginfo('admin_email');
			$new_ln_success['data']['_email_subject'] = sprintf(/* translators: 1: Site name 2: Username 3: Browser 4: Platform */__('[%1$s] New sign-in for your account %2$s from %3$s on %4$s', WPKLN_TEXT_DOMAIN), '[SITE]', '[USERNAME]', '[BROWSER]', '[PLATFORM]');
			$new_ln_success['data']['_email_content'] = sprintf(/* translators: Site name */__('This notice has been automatically sent as a result of a successful sign-in to your administration interface on %s.', WPKLN_TEXT_DOMAIN), '[SITE]') . "\n\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: Username */__('Username: %s', WPKLN_TEXT_DOMAIN), '[USERNAME]') . "\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: IP address */__('IP address: %s', WPKLN_TEXT_DOMAIN), '[IP]') . "\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: Browser */__('Browser: %s', WPKLN_TEXT_DOMAIN), '[BROWSER]') . "\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: Platform */__('Platform: %s', WPKLN_TEXT_DOMAIN), '[PLATFORM]') . "\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: Date */__('Date: %s', WPKLN_TEXT_DOMAIN), '[DATE]') . "\n\n";
			$new_ln_success['data']['_email_content'] .= sprintf(/* translators: 1: Site URL */__('This email is intended to make you aware of the security of the services you have at <a href="%1$s">%1$s</a> and to better protect them.', WPKLN_TEXT_DOMAIN), '[SITE_URL]');
			$new_ln_success['data']['_email_to_administrator'] = true;
			$new_ln_success['data']['_email_to_editor'] = false;
			$new_ln_success['data']['_email_to_author'] = false;
			$new_ln_success['data']['_email_to_contributor'] = false;
			$new_ln_success['data']['_email_to_subscriber'] = false;
			// add super admin role if multisite
			if (is_multisite())
			{
				$new_ln_success['data']['_email_to_superadmin'] = true;
			}

			// add customs roles
			$wpkln_custom_roles = wpkln_admin_getUsersRoles();
			foreach ($wpkln_custom_roles as $i_role)
			{
				$role_key = esc_attr($i_role['id']);
				$data_role_id = esc_attr('_email_to_' . $role_key);

				if (!isset($new_ln_success['data'][$data_role_id]))
				{
					$new_ln_success['data'][$data_role_id] = false;
				}
			}

			// add empty extra emails list
			$new_ln_success['data']['_email_extra_emails'] = "";

			// add empty whitelist IPs
			$new_ln_success['data']['_email_whitelist'] = "";

			return $new_ln_success;
		}

		/**
		 * Loads defaults into class attributes
		 */
		private function load_new()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$login_notification = stripslashes_deep($this->default_new());
			if (empty($login_notification)) return '';

			$this->create($login_notification);
		}

		/**
		 * Creates new Login Notification record
		 */
		private function create($login_notification)
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$this->saved = false;
			if (empty($login_notification)) return '';

			if ($login_notification['new'])
			{
				$this->saved = update_option(WPKLN_LOGIN_NOTIFICATION_DB, $this->prepare($login_notification), false);
			}
		}

		/**
		 * Updates Login Notification record and call update functions.
		 * Set $this->saved on success.
		 */
		private function update()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			$this->saved = false;
			if (empty($_POST) || empty($_POST['wpkln']) || empty($_POST['wpkln_crud_edit'])) return '';

			if (wp_verify_nonce($_POST['wpkln_crud_edit'], 'wpkln_crud_' . get_current_user_id()))
			{
				$login_notification = $_POST['wpkln'];
				if (empty($login_notification) || !isset($login_notification['id'])) return '';

				$this->saved = update_option(WPKLN_LOGIN_NOTIFICATION_DB, $this->prepare($login_notification), false);
			}
			WPKLN_Admin_Dashboard::addAdminNotice(__('Saving notification settings', WPKLN_TEXT_DOMAIN), $this->saved);
		}

		/**
		 * Delete Login Notification record from $_POST
		 * Set $this->deleted on success.
		 */
		private function destroy()
		{
			if (!current_user_can('manage_options'))
			{
				wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
			}

			if (empty($_POST) || empty($_POST['wpkln']) || empty($_POST['wpkln_crud_delete'])) return '';

			if (wp_verify_nonce($_POST['wpkln_crud_delete'], 'wpkln_crud_' . get_current_user_id()))
			{
				$login_notification = $_POST['wpkln'];
				if (empty($login_notification) || !isset($login_notification['id'])) return '';

				$this->deleted = update_option(WPKLN_LOGIN_NOTIFICATION_DB, '', false);
			}
			WPKLN_Admin_Dashboard::addAdminNotice(__('Reset notification settings', WPKLN_TEXT_DOMAIN), $this->deleted);
		}

		/**
		 * Converts array input values into values need for database storage
		 *
		 * @param array $q : raw Login Notification associative array from $_POST
		 *
		 * @return array : array prepared for database insertion
		 */
		private function prepare($q)
		{
			if (empty($q) || empty($q['data']) || empty($q['id'])) return '';

			$attributes = array();
			foreach ($q['data'] as $attr => $val)
			{
				if ($attr == '_email_from_name')
				{
					$val = sanitize_text_field($val);
					$attributes[$attr] = wp_specialchars_decode(strip_tags(stripslashes($val)), ENT_QUOTES);
				}
				elseif ($attr == '_email_from_address' && is_email($val))
				{
					$attributes[$attr] = sanitize_email($val);
				}
				elseif ($attr == '_email_subject')
				{
					$val = sanitize_text_field($val);
					$attributes[$attr] = wp_specialchars_decode(strip_tags(stripslashes($val)), ENT_QUOTES);
				}
				elseif ($attr == '_email_content')
				{
					$attributes[$attr] = wp_kses_post($val);
				}
				elseif ($attr == '_email_extra_emails')
				{
					$attributes[$attr] = ($val) ? wp_filter_nohtml_kses($val) : '';
				}
				elseif ($attr == '_email_whitelist')
				{
					$attributes[$attr] = ($val) ? sanitize_text_field($val) : '';
				}
			}

			$wpkln_custom_roles = wpkln_admin_getUsersRoles();
			$bools = array();
			foreach ($wpkln_custom_roles as $i_role)
			{
				$role_key = esc_attr($i_role['id']);
				$bools[] = esc_attr('_email_to_' . $role_key);
			}

			foreach ($bools as $b)
			{
				$attributes[$b] = empty($q['data'][$b]) ? 0 : 1;
			}

			$result = base64_encode(serialize($attributes));

			return $result;
		}

		/**
		 * Load all Login Notification
		 * Stripslashes on DB return values.
		 */
		private function load_all()
		{
			$settingsData = get_option(WPKLN_LOGIN_NOTIFICATION_DB);

			if (!empty($settingsData))
			{
				$login_notification = stripslashes_deep($settingsData);
				$this->id = WPKLN_LOGIN_NOTIFICATION_DB;
				$data_attributes = stripslashes_deep(unserialize(base64_decode($login_notification)));

				if (empty($data_attributes) || !is_array($data_attributes)) return '';

				if (!isset($this->data))
					$this->data = new stdClass();

				foreach ($data_attributes as $attr => $val)
				{
					$this->data->$attr = $val;
				}
				$this->new = false;
			}
		}
	}
}
