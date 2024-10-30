<?php

/** 
 * Kaya Login Notification - Emails Class
 * Manages emails sending.
 */

if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('WPKLN_Emails'))
{
	class WPKLN_Emails
	{
		// sender name
		private $_email_from_name;
		// sender address
		private $_email_from_address;
		// receiver address
		private $_send_to;
		// email subject
		private $_subject;
		// email message
		private $_message;

		/**
		 * Sends email with object settings.
		 *
		 * @return bool
		 */
		public function send()
		{
			$this->_email_from_name		= $this->filterName(!empty($this->_email_from_name) ? $this->_email_from_name : wp_specialchars_decode(strip_tags(stripslashes(get_bloginfo('name'))), ENT_QUOTES));
			$this->_email_from_address	= $this->filterEmail(!empty($this->_email_from_address) ? $this->_email_from_address : get_bloginfo('admin_email'));

			$this->_subject = (!empty($this->_subject) ? $this->_subject : 'Login Notification');
			$this->_message = (!empty($this->_message) ? $this->_message : 'Login Notification');

			$this->_send_to	= (!empty($this->_send_to) ? $this->_send_to : array(get_bloginfo('admin_email')));

			$headers = array();
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-type: text/html; charset="' . esc_attr(get_option('blog_charset')) . '"';
			$headers[] = 'From: ' . $this->_email_from_name . ' <' . esc_attr($this->_email_from_address) . '>';
			$headers[] = 'X-Mailer: PHP/' . esc_attr(phpversion());
			$headers = $this->sanitizeHeaders($headers);

			foreach ($this->_send_to as $address)
			{
				$emailNotoficationSent = wp_mail($address, $this->_subject, $this->_message, implode("\r\n", $headers));
				if (!$emailNotoficationSent)
				{
					$header = 'From: ' . $this->_email_from_name . ' <' . esc_attr($this->_email_from_address) . '>' . "\r\n";
					$emailNotoficationSent = mail($address, $this->_subject, $this->_message, $header);
				}
			}

			return $emailNotoficationSent;
		}

		/**
		 * Set email content settings.
		 */
		public function set_content($p_subject, $p_message)
		{

			// prepare and set the mail subject
			$emailSubject = (!empty($p_subject) ? sanitize_text_field($p_subject) : 'Login Notification');
			$this->_subject = wp_specialchars_decode(strip_tags(stripslashes($emailSubject)), ENT_QUOTES);

			// prepare and set the mail message
			$this->_message = $this->prepareMessage($p_message);
		}

		/**
		 * Set sender settings.
		 */
		public function set_from($p_email_from_name, $p_email_from_address)
		{
			// prepare and set the sender name
			$emailFromName = sanitize_text_field($p_email_from_name);
			$emailFromName = wp_specialchars_decode(strip_tags(stripslashes(!empty($emailFromName) ? $emailFromName : get_bloginfo('name'))), ENT_QUOTES);
			$this->_email_from_name = $emailFromName;

			// prepare and set the sender address
			$emailFromAddress = sanitize_email($p_email_from_address);
			$emailFromAddress = (!empty($emailFromAddress) ? $emailFromAddress : get_bloginfo('admin_email'));
			$this->_email_from_address = $emailFromAddress;
		}

		/**
		 * Set receiver address settings.
		 */
		public function set_to($p_send_to)
		{
			if (!empty($p_send_to) && is_array($p_send_to))
			{
				$this->_send_to = array();
				foreach ($p_send_to as $p_email_to)
				{
					if (is_email($p_email_to))
					{
						$this->_send_to[] = sanitize_email($p_email_to);
					}
				}
			}

			if (empty($this->_send_to))
			{
				$this->_send_to = array();
				$this->_send_to[] = sanitize_email(get_bloginfo('admin_email'));
			}
		}

		/**
		 * Prepare and return the mail content.
		 */
		private function prepareMessage($p_content)
		{
			// Sanitize content for allowed HTML tags for post content.
			$preparedContent = wp_kses_post($p_content);
			// replace line breaks to HTML code
			$preparedContent = str_replace(array("\r\n", "\r", "\n"), "<br />", $preparedContent);

			return $preparedContent;
		}

		/**
		 * Filter name field.
		 */
		private function filterName($p_name)
		{
			$nameRule = array(
				"\r" => '',
				"\n" => '',
				"\t" => '',
				'"'  => "'",
				'<'  => '[',
				'>'  => ']',
			);

			return trim(strtr($p_name, $nameRule));
		}

		/**
		 * Filter email field.
		 */
		private function filterEmail($p_email)
		{
			$emailRule = array(
				"\r" => '',
				"\n" => '',
				"\t" => '',
				'"'  => '',
				','  => '',
				'<'  => '',
				'>'  => '',
			);

			return strtr($p_email, $emailRule);
		}

		/**
		 * Sanitize headers.
		 */
		function sanitizeHeaders($p_headers)
		{
			foreach ($p_headers as $key => $value)
			{
				$p_headers[$key] = preg_replace('=((<CR>|<LF>|0x0A/%0A|0x0D/%0D|\\n|\\r)\S).*=i', '', $value);
			}

			return $p_headers;
		}
	}
}
