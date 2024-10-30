<?php

/**
 * Kaya Login Notification - Main Functions.
 * Functions to automatically notify any successful connection.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!function_exists('wpkln_loginSuccessNotifier'))
{
	/**
	 * Successfull Login Notification.
	 *
	 * Sends email to notify the user has successfully logged in.
	 *
	 * @param string	$user_login WordPress Username.
	 * @param WP_User	$user WP_User object of the logged-in user.
	 *
	 * @return bool		True if the notification is sent, or False if not.
	 */
	function wpkln_loginSuccessNotifier($user_login, $user)
	{
		// get and verify WP_User object
		$currentUser = (!empty($user) ? $user : false);
		if (empty($currentUser))
		{
			return false;
		}

		// set WPKLN_login_notification object
		require_once(WPKLN_PLUGIN_PATH . 'lib/class.crud_login_notification.php');
		$kayaLoginNotification = new WPKLN_login_notification();

		// check for user with no role
		if (isset($currentUser->roles) && empty($currentUser->roles))
		{
			// add the 'no role' option.
			$currentUser->roles[] = 'none';
		}

		// check for user with super admin role
		if (is_multisite() && is_super_admin($currentUser->ID))
		{
			// add the 'super admin' option.
			$currentUser->roles[] = 'superadmin';
		}

		// check if the current IP is in the whitelist
		$isIpWhitelisted = false;
		if (!wpkln_checkIpToNotification($kayaLoginNotification->data->_email_whitelist))
		{
			$isIpWhitelisted = true;
		}

		// set login notification marker
		$isRoleLoginNotification = false;
		if (!$isIpWhitelisted)
		{
			foreach ($currentUser->roles as $i_userRole)
			{
				$klnDataKey = '_email_to_' . esc_attr($i_userRole);
				// check if the current role need to be notified.
				if (isset($kayaLoginNotification->data->{$klnDataKey}) && $kayaLoginNotification->data->{$klnDataKey})
				{
					$isRoleLoginNotification = true;
					break;
				}
			}
		}

		// if the current role is notification enabled
		if ($isRoleLoginNotification)
		{
			// get shortcodes data
			$shortcodeSiteName		= (!empty(get_bloginfo('name')) ? wp_specialchars_decode(strip_tags(stripslashes(get_bloginfo('name'))), ENT_QUOTES) : '');
			$shortcodeSiteURL		= (!empty(get_bloginfo('url')) ? get_bloginfo('url') : '');
			$shortcodeUserName		= sanitize_user($currentUser->user_login);
			$shortcodeUserIP		= sanitize_text_field(wpkln_getClientIP());
			$clientBrowserInfos		= wpkln_getClientBrowserInfos();
			$shortcodeUserPlatform	= (!empty($clientBrowserInfos['platform']) ? sanitize_text_field($clientBrowserInfos['platform']) : '');
			$shortcodeUserBrowser	= (!empty($clientBrowserInfos['browser']) ? sanitize_text_field($clientBrowserInfos['browser']) : '');
			$wpDateFormat			= (!empty(get_option('date_format')) ? get_option('date_format') : 'Y-m-d');
			$wpTimeFormat			= (!empty(get_option('time_format')) ? get_option('time_format') : 'H:i');
			$shortcodeDate			= sanitize_text_field(date_i18n("{$wpDateFormat} {$wpTimeFormat}", current_time('timestamp')));

			// set shortcodes replacement index
			$shortcodeReplacement = array(
				'[SITE]'		=> $shortcodeSiteName,
				'[SITE_URL]'	=> $shortcodeSiteURL,
				'[USERNAME]'	=> $shortcodeUserName,
				'[IP]'			=> $shortcodeUserIP,
				'[PLATFORM]'	=> $shortcodeUserPlatform,
				'[BROWSER]'		=> $shortcodeUserBrowser,
				'[DATE]'		=> $shortcodeDate
			);

			// set extra shortcodes replacement index
			$extraShortcodeReplacement = array(
				'[UTM]'	=> wpkln_getUtmParameters()
			);

			// replace shortcodes in subject and content
			$emailSubject = wpkln_replaceShortcodes($shortcodeReplacement, $kayaLoginNotification->data->_email_subject);
			$emailContent = wpkln_replaceShortcodes($shortcodeReplacement, $kayaLoginNotification->data->_email_content);
			// replace extra shortcodes in content
			$emailContent = wpkln_replaceShortcodes($extraShortcodeReplacement, $emailContent);

			// set sender name and address
			$emailFromName		= (!empty($kayaLoginNotification->data->_email_from_name) ? $kayaLoginNotification->data->_email_from_name : '');
			$emailFromAddress	= (!empty($kayaLoginNotification->data->_email_from_address) ? $kayaLoginNotification->data->_email_from_address : '');

			// set extras emails
			$emailAdresses = $kayaLoginNotification->data->_email_extra_emails;
			$emailAdresses = $emailAdresses ? array_map('trim', explode(',', $emailAdresses)) : array();

			// set current user address
			$emailAdresses[] = (!empty($currentUser->user_email) ? $currentUser->user_email : '');

			// init email object
			require_once(WPKLN_PLUGIN_PATH . 'lib/class.emails.php');
			$kayaEmail = new WPKLN_Emails();
			// set email data
			$kayaEmail->set_content($emailSubject, $emailContent);
			$kayaEmail->set_from($emailFromName, $emailFromAddress);
			$kayaEmail->set_to($emailAdresses);

			// send the email notification
			$emailNotoficationSent = $kayaEmail->send();

			return $emailNotoficationSent;
		}

		return false;
	}
	add_action('wp_login', 'wpkln_loginSuccessNotifier', 999, 2);
}

if (!function_exists('wpkln_replaceShortcodes'))
{
	/**
	 * Shortcodes Replacement.
	 *
	 * @param array		$p_replacement list of shortcodes with each values.
	 * @param string	$p_content string that contains shortcodes to replace.
	 *
	 * @return string	$replacedContent the content with shortcodes replaced.
	 */
	function wpkln_replaceShortcodes($p_replacement, $p_content)
	{
		if (empty($p_replacement) || empty($p_content) || !is_array($p_replacement) || !is_string($p_content))
		{
			return '';
		}

		$replacedContent = $p_content;
		foreach ($p_replacement as $i_replaceFrom => $i_replaceTo)
		{
			if (is_string($i_replaceTo))
			{
				$replacedContent = str_replace($i_replaceFrom, $i_replaceTo, $replacedContent);
			}
			elseif (is_array($i_replaceTo))
			{
				$replacementString = '';
				foreach ($i_replaceTo as $j_replaceToindex => $j_replaceToString)
				{
					$replacementString .= $j_replaceToindex . ': ' . $j_replaceToString . "\n";
				}
				$replacedContent = str_replace($i_replaceFrom, $replacementString, $replacedContent);
			}
		}

		return $replacedContent;
	}
}

if (!function_exists('wpkln_getClientIP'))
{
	/**
	 * Get the client IP address.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	function wpkln_getClientIP()
	{
		// default remote undefined IP
		$remoteIP = __('undefined', WPKLN_TEXT_DOMAIN);

		if (!isset($_SERVER)) return $remoteIP;

		// CloudFlare client IP
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP))
		{
			$remoteIP = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		// Behind proxy client IP
		else if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') > 0)
			{
				$addresses = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
				$address = trim($addresses[0]);
				if (filter_var($address, FILTER_VALIDATE_IP))
				{
					$remoteIP = $address;
				}
			}
			else if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
			{
				$remoteIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		// Behind proxy client IP with other non standard header
		else if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
		{
			$remoteIP = $_SERVER['HTTP_CLIENT_IP'];
		}
		// Most reliable client IP
		else if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP))
		{
			$remoteIP = $_SERVER['REMOTE_ADDR'];
		}

		return $remoteIP;
	}
}

if (!function_exists('wpkln_getClientBrowserInfos'))
{
	/**
	 * Get the browser informations of the client.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	function wpkln_getClientBrowserInfos()
	{
		if (!class_exists('WPKLN_ASSET_Browser'))
		{
			require_once(WPKLN_PLUGIN_PATH . 'assets/Browser.php');
		}
		$httpUserAgent	= (isset($_SERVER) && !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
		$client			= (!empty($httpUserAgent) ? substr($httpUserAgent, 0, 254) : '');
		$browserInfos	= new WPKLN_ASSET_Browser($httpUserAgent);
		$platform		= $browserInfos->getPlatform();
		$browser		= $browserInfos->getBrowser();

		return compact('client', 'platform', 'browser');
	}
}

if (!function_exists('wpkln_checkIpToNotification'))
{
	/**
	 * Checks if the current IP address is eligible for notification.
	 *
	 * @since 1.6.0
	 *
	 * @param string	$p_whitelist string that contains IP addresses to exclude.
	 *
	 * @return bool		True if the IP address is eligible for notification, or False if not.
	 */
	function wpkln_checkIpToNotification($p_whitelist)
	{
		$whitelist	= array_filter(array_map('trim', explode(',', $p_whitelist)));
		$userIP		= sanitize_text_field(wpkln_getClientIP());

		if (!empty($whitelist))
		{
			foreach ($whitelist as $i_ip)
			{
				// If IP address is not a range (no '/'), simple check
				if (strpos($i_ip, '/') === false)
				{
					// Extracts a substring from the current IP address to check sequential range
					if (substr($userIP, 0, strlen($i_ip)) === $i_ip)
					{
						return false;
					}
				}
				// If IP address is a range, check if the current IP address is in the range
				else
				{
					if (wpkln_checkIpInRange($userIP, $i_ip))
					{
						return false;
					}
				}
			}
		}

		return true;
	}
}

if (!function_exists('wpkln_checkIpInRange'))
{
	/**
	 * Checks if a given IP address falls within a specified range.
	 *
	 * @since 1.6.0
	 *
	 * @param string	$p_givenIp contains the given IP address.
	 * @param string	$p_ipRange contains the IP range.
	 *
	 * @return bool		True if the given IP address is within the specified range, or False if not.
	 */
	function wpkln_checkIpInRange($p_givenIp, $p_ipRange)
	{
		// Breaking down the IP range
		list($range, $netmask) = explode('/', $p_ipRange, 2);

		// Decimal conversion
		$decimalRange = ip2long($range);
		$decimalIp = ip2long($p_givenIp);

		// Calculates netmask in decimal
		$decimalWildcard = pow(2, (32 - $netmask)) - 1;
		$decimalNetmask = ~$decimalWildcard;

		// Binary comparison of network parts of specified and given IP addresses
		return (($decimalIp & $decimalNetmask) == ($decimalRange & $decimalNetmask));
	}
}

if (!function_exists('wpkln_getUtmParameters'))
{
	/**
	 * Get the Urchin Tracking Module (UTM) parameters.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	function wpkln_getUtmParameters()
	{
		$utmParameters = array();
		$refererQuerry = array();

		if (isset($_SERVER) && isset($_SERVER['HTTP_REFERER']))
		{
			$utmParameters['referer'] = sanitize_text_field($_SERVER['HTTP_REFERER']);
			$urlQuerry = parse_url($utmParameters['referer'], PHP_URL_QUERY);
			parse_str($urlQuerry, $refererQuerry);
		}
		elseif (wp_get_referer())
		{
			$utmParameters['referer'] = sanitize_text_field(wp_get_referer());
			$urlQuerry = parse_url($utmParameters['referer'], PHP_URL_QUERY);
			parse_str($urlQuerry, $refererQuerry);
		}

		if (isset($_SERVER) && isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']))
		{
			$utmParameters['request_uri'] = sanitize_text_field($_SERVER['REQUEST_URI']);
		}

		if (isset($_SERVER) && isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
		{
			$utmParameters['query_string'] = sanitize_text_field($_SERVER['QUERY_STRING']);
		}

		if (isset($_GET['utm_campaign']))
		{
			$utmParameters['utm_campaign'] = sanitize_text_field($_GET['utm_campaign']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_campaign']))
		{
			$utmParameters['utm_campaign'] = sanitize_text_field($refererQuerry['utm_campaign']);
		}

		if (isset($_GET['utm_medium']))
		{
			$utmParameters['utm_medium'] = sanitize_text_field($_GET['utm_medium']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_medium']))
		{
			$utmParameters['utm_medium'] = sanitize_text_field($refererQuerry['utm_medium']);
		}

		if (isset($_GET['utm_source']))
		{
			$utmParameters['utm_source'] = sanitize_text_field($_GET['utm_source']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_source']))
		{
			$utmParameters['utm_source'] = sanitize_text_field($refererQuerry['utm_source']);
		}

		if (isset($_GET['utm_term']))
		{
			$utmParameters['utm_term'] = sanitize_text_field($_GET['utm_term']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_term']))
		{
			$utmParameters['utm_term'] = sanitize_text_field($refererQuerry['utm_term']);
		}

		if (isset($_GET['utm_content']))
		{
			$utmParameters['utm_content'] = sanitize_text_field($_GET['utm_content']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_content']))
		{
			$utmParameters['utm_content'] = sanitize_text_field($refererQuerry['utm_content']);
		}

		if (isset($_GET['utm_id']))
		{
			$utmParameters['utm_id'] = sanitize_text_field($_GET['utm_id']);
		}
		elseif (!empty($refererQuerry) && !empty($refererQuerry['utm_id']))
		{
			$utmParameters['utm_id'] = sanitize_text_field($refererQuerry['utm_id']);
		}

		return $utmParameters;
	}
}
