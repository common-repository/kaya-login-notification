<?php

/**
 * Kaya Login Notification Admin Page.
 * Displays the admin plugin page.
 */

/**
 * Displays Login Notification Page.
 */
if (!function_exists('wpkln_admin_doOptionPage'))
{
	function wpkln_admin_doOptionPage()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die('<p>' . __('You do not have sufficient permissions.') . '</p>');
		}

?>

		<div class="ks-wp-dashboard-page-container">
			<div class="ks-wp-dashboard-page-row">

				<div class="ks-wp-dashboard-page-header">
					<div class="ks-wp-dashboard-page-header-title">
						<h1>Kaya Login Notification</h1>
					</div>
				</div>

				<div class="ks-wp-dashboard-page-content">

					<div class="ks-wp-dashboard-page-content-card">
						<h6 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('Login Notification', WPKLN_TEXT_DOMAIN); ?></h6>
						<p>
							<?php echo esc_html__('Sends email notification on successful login, with fully customizable settings.', WPKLN_TEXT_DOMAIN); ?>
						</p>
						<p>
							<?php echo esc_html__('You can choose here the email settings and users roles to be notified, the following shortcodes can be used in the email subject and content:', WPKLN_TEXT_DOMAIN); ?>
						</p>
						<table class="ks-wp-dashboard-page-content-table">
							<tbody>
								<tr>
									<td><?php echo esc_html__('The site name:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[SITE]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The site URL:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[SITE_URL]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The user name:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[USERNAME]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The login date and hour:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[DATE]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The user IP address:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[IP]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The user browser:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[BROWSER]</code></td>
								</tr>
								<tr>
									<td><?php echo esc_html__('The user operating system:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[PLATFORM]</code></td>
								</tr>
							</tbody>
						</table>
						<p><br><?php echo esc_html__('The following extra shortcode can be used in the email content:', WPKLN_TEXT_DOMAIN); ?></p>
						<table class="ks-wp-dashboard-page-content-table">
							<tbody>
								<tr>
									<td><?php echo esc_html__('UTM (Urchin Tracking Module) parameters:', WPKLN_TEXT_DOMAIN); ?></td>
									<td><code>[UTM]</code></td>
									<td><?php echo esc_html__('Containing the list of the following elements (if available):', WPKLN_TEXT_DOMAIN); ?><br>
										<?php echo esc_html__('referer, request_uri, query_string, utm_campaign, utm_medium, utm_source, utm_term, utm_content and utm_id.', WPKLN_TEXT_DOMAIN); ?></td>
								</tr>
							</tbody>
						</table>
						<p><br><b><?php echo esc_html__('It is strongly recommended to send a test email via the functionality at the bottom of the page, in order to check that your settings are working correctly.', WPKLN_TEXT_DOMAIN); ?></b></p>
					</div>

					<div class="ks-wp-dashboard-page-content-card">
						<h6 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('Login Notification settings', WPKLN_TEXT_DOMAIN); ?></h6>

						<?php

						// include the WPKLN_login_notification class
						require_once(WPKLN_PLUGIN_PATH . 'lib/class.crud_login_notification.php');
						// init WPKLN_login_notification object
						$kayaLoginNotification = new WPKLN_login_notification();

						// Login Notification variables texts
						$wpkln_textSave			= __('Save Changes', WPKLN_TEXT_DOMAIN);
						$wpkln_textReset		= __('Reset settings', WPKLN_TEXT_DOMAIN);
						$wpkln_textResetConfirm	= __('Do you want to delete the current settings?', WPKLN_TEXT_DOMAIN);
						$wpkln_footer			= sprintf(/* translators: 1: Plugin Name 2: Plugin Version */__('%1$s - Version %2$s', WPKLN_TEXT_DOMAIN), 'Kaya Login Notification', WPKLN_VERSION);

						// Login Notification save panel
						$wpkln_admin_panel = '<table class="form-table"><tbody><tr>';
						$wpkln_admin_panel .= '<td><input class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" class="left" type="submit" name="save_login_notification" value="' . esc_attr($wpkln_textSave) . '" /></td>';
						$wpkln_admin_panel .= '</tr></tbody></table>';

						// Login Notification delete panel
						$wpkln_delete_panel = '<table class="form-table"><tbody><tr>';
						$wpkln_delete_panel .= '<td><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
						$wpkln_delete_panel .= '<input type="hidden" name="wpkln[id]" value="' . esc_attr($kayaLoginNotification->id) . '" />';
						$wpkln_delete_panel .= '<input type="hidden" name="wpkln_action" value="delete" />';
						$wpkln_delete_panel .= '<input type="hidden" name="action" value="wpkln_login_notification_form">';
						$wpkln_delete_panel .= '<input type="hidden" name="wpkln_target" value="login_notification" />';
						$wpkln_delete_panel .= wp_nonce_field('wpkln_crud_' . get_current_user_id(), 'wpkln_crud_delete', true, false);
						$wpkln_delete_panel .= '<input class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-warning" class="left" type="submit" name="delete_login_notification" value="' . esc_attr($wpkln_textReset) . '" onclick="return confirm(\'' . esc_attr($wpkln_textResetConfirm) . '\');" />';
						$wpkln_delete_panel .= '</form></td>';
						$wpkln_delete_panel .= '</tr></tbody></table>';

						// Login Notification customs roles inputs
						$wpkln_custom_roles = wpkln_admin_getUsersRoles();
						$wpkln_custom_roles_inputs = '';
						foreach ($wpkln_custom_roles as $i_role)
						{
							$roleKey = esc_attr($i_role['id']);
							$roleValue = esc_html(translate_user_role($i_role['name']));

							$wpkln_custom_roles_inputs .= '<label for="wpkln_email_to_' . $roleKey . '">';
							$wpkln_custom_roles_inputs .= '<input id="wpkln_email_to_' . $roleKey . '" name="wpkln[data][_email_to_' . $roleKey . ']" value="1" type="checkbox" ' . ((isset($kayaLoginNotification->data->{'_email_to_' . $roleKey}) && $kayaLoginNotification->data->{'_email_to_' . $roleKey}) ? 'checked' : '') . '>';
							$wpkln_custom_roles_inputs .=  $roleValue;
							$wpkln_custom_roles_inputs .= '</label><br />';
						}

						?>

						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_from_name"><?php echo esc_html__('Sender name', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<input id="wpkln_email_from_name" name="wpkln[data][_email_from_name]" value="<?php echo esc_attr($kayaLoginNotification->data->_email_from_name); ?>" class="regular-text" type="text">
											<p class="description"><?php echo esc_html__('Sender name displayed on the mail.', WPKLN_TEXT_DOMAIN); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_from_address"><?php echo esc_html__('Sender address', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<input id="wpkln_email_from_address" name="wpkln[data][_email_from_address]" value="<?php echo esc_attr($kayaLoginNotification->data->_email_from_address); ?>" class="regular-text" type="email">
											<p class="description"><?php echo esc_html__('Sender address of the email, if you are using an SMTP emailing service, enter the account email address.', WPKLN_TEXT_DOMAIN); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_subject"><?php echo esc_html__('Email subject line', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<input id="wpkln_email_subject" name="wpkln[data][_email_subject]" value="<?php echo esc_attr($kayaLoginNotification->data->_email_subject); ?>" type="text" style="width:100%">
											<p class="description"><?php echo esc_html__('You can use the following shortcodes in the subject:', WPKLN_TEXT_DOMAIN); ?> [SITE], [SITE_URL], [USERNAME], [DATE], [IP], [BROWSER], [PLATFORM].</p>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_content"><?php echo esc_html__('Email content', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php wp_editor(stripcslashes(esc_textarea($kayaLoginNotification->data->_email_content)), 'wpkln_email_content', array('textarea_name' => 'wpkln[data][_email_content]', 'wpautop' => true, 'media_buttons' => false, 'tinymce' => false, 'quicktags' => false, 'drag_drop_upload' => false)); ?>
											<p class="description"><?php echo esc_html__('You can use the following shortcodes in the content:', WPKLN_TEXT_DOMAIN); ?> [SITE], [SITE_URL], [USERNAME], [DATE], [IP], [BROWSER], [PLATFORM], [UTM].</p>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label><?php echo esc_html__('Email notification', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<?php echo $wpkln_custom_roles_inputs; ?>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_extra_emails"><?php echo esc_html__('Extra emails', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<textarea id="wpkln_email_extra_emails" name="wpkln[data][_email_extra_emails]" rows="2" cols="50" class="large-text code"><?php echo esc_html($kayaLoginNotification->data->_email_extra_emails); ?></textarea>
											<p class="description"><?php echo esc_html__('Send notification to additional email addresses (separate multiple with commas).', WPKLN_TEXT_DOMAIN); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="raw">
											<label for="wpkln_email_whitelist"><?php echo esc_html__('Whitelist IPs', WPKLN_TEXT_DOMAIN); ?></label>
										</th>
										<td>
											<textarea id="wpkln_email_whitelist" name="wpkln[data][_email_whitelist]" rows="2" cols="50" class="large-text code"><?php echo esc_html($kayaLoginNotification->data->_email_whitelist); ?></textarea>
											<p class="description"><?php echo esc_html__('Exclude notification from these IP addresses (separate multiple with commas). You can use individual IP address (like 192.168.0.1), sequential range of IP addresses (like 192.168.), and CIDR range of IP addresses (like 192.168.0.1/24).', WPKLN_TEXT_DOMAIN); ?></p>
										</td>
									</tr>
								</tbody>
							</table>

							<?php echo wp_nonce_field('wpkln_crud_' . get_current_user_id(), 'wpkln_crud_edit', true, false); ?>
							<input type="hidden" name="wpkln[id]" value="<?php echo esc_attr($kayaLoginNotification->id); ?>" />
							<input type="hidden" name="wpkln_action" value="edit" />
							<input type="hidden" name="action" value="wpkln_login_notification_form">
							<input type="hidden" name="wpkln_target" value="login_notification" />

							<?php echo $wpkln_admin_panel; ?>
						</form>

						<?php echo $wpkln_delete_panel; ?>

					</div>

					<div class="ks-wp-dashboard-page-content-card">
						<h6 class="ks-wp-dashboard-page-content-card-title"><?php echo esc_html__('Login Notification email testing', WPKLN_TEXT_DOMAIN); ?></h6>
						<p>
							<?php
							$currentUser = wp_get_current_user();
							$emailTesting = (string) $currentUser->user_email;
							?>
							<?php echo sprintf(/* translators: 1: Account email address */__('Try the email notification with the current saved settings by sending a test email at your account address (%1$s).', WPKLN_TEXT_DOMAIN), esc_html($emailTesting)); ?>
						</p>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<input type="hidden" name="wpkln_action" value="test_email" />
							<input type="hidden" name="action" value="wpkln_login_notification_test_email">
							<input type="hidden" name="wpkln_target" value="login_notification_test_email" />
							<p>
								<input class="ks-wp-dashboard-page-btn ks-wp-dashboard-page-btn-primary" class="left" type="submit" name="save_login_notification" value="<?php echo esc_html__('Send the test email', WPKLN_TEXT_DOMAIN); ?>" />
							</p>
						</form>
					</div>


				</div>

				<div class="ks-wp-dashboard-page-sidebar">
					<?php
					if (is_file(plugin_dir_path(__FILE__) . 'wpkln-admin-page-sidebar.php'))
					{
						include_once plugin_dir_path(__FILE__) . 'wpkln-admin-page-sidebar.php';
						wpkln_admin_doPageSidebar();
					}
					if (is_file(plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php'))
					{
						include_once plugin_dir_path(__FILE__) . 'kayastudio-admin-page-sidebar.php';
						kayastudio_plugins_admin_doMainPageSidebar();
					}
					?>
				</div>

				<div class="ks-wp-dashboard-page-footer">
					<div class="ks-wp-dashboard-page-footer-version">
						<p><?php echo esc_html($wpkln_footer); ?></p>
					</div>
				</div>

			</div>
		</div>

<?php
	}
}
