<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wizard extends MX_Controller {

	/**
	 * Index Page for the web based Setup-Wizard.
	 *
	 * NOTE: This page doesn't follow the usual semantics that we use for the
	 * rest of the GUI. You HAVE to be REALLY careful with $cceClient, or you
	 * leave a lot of unneeded /usr/sausalito/sbin/cced childs around.
	 * So be REALLY sure to close all of them that you don't need. 
	 * And there is a REASON why we use $cceClient->bye(); so often in here!
	 */

	public function index()	{
	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

	    // Profiling and Benchmarking:
	    bx_profiler();

	    // Find out if CCEd is running. If it is not, we display an error message and quit:
	    $cceClient = new CceClient();

	    // Get authentication cookies - if present:
		$CI =& get_instance();
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

  		// Need to load 'BxPage' for page rendering:
		$MX =& get_instance();
		$this->load->library('BxPage');

		// If we have no cookies, login name is empty. Set default:
	    if ($loginName == "") {
	    	$loginName = 'admin';
	    }

	    // Connect to CCEd:
	    $cceClient->connect();

	    // We do have a $sessionId? Try it:
	    if ($sessionId != "") {
			$cceClient->authkey($loginName, $sessionId);
			// The sessionId we had was from a cookie and it was invalid.
			// Delete the cookies:
			delete_cookie("loginName");
			delete_cookie("sessionId");
			delete_cookie("userip");
			$cceClient->bye();
	    }
	    else {
	    	// Try the default password that is set after BlueOnyx installations.
	    	// This will (on success) yield a valid sessionId:
			$sessionId = $cceClient->auth("admin", "blueonyx");
			$cceClient->bye();
		}

		// Check if either of the two login methods worked and if we DO have
		// a valid sessionId now:
		if ($sessionId == "") {
			// We don't! Login failed!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();

			// If we're here, the password was changed and is no longer the default one. 
			// Redirect to the login page and let the user enter the new password.
			// Afterwards the login page will redirect him back to us. With valid
			// Cookies for username and sessionId.
			// 
			header("Location: /login?action=wizard");
      		exit;
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();

	    // Basics:
	    //
	    // If we get to this point, we do have a loginName and a sessionId. Run them
	    // against $serverScriptHelper. If that works, we automatically go on with the
	    // rest of the page. But if $serverScriptHelper gets handed incorrect login
	    // details, then it will bump us back to the /login page. So this method does
	    // two things: It provides us with $serverScriptHelper, a working $cceClient
	    // and on top of that it also checks if we're privileged to be here with
	    // the correct login details:
	    //
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$i18n = new I18n("base-wizard", $locale);

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);
		$get_data = $CI->input->get(NULL, TRUE);

	    // locale and charset setup:
	    $browsercheck = TRUE;
		if (isset($get_data['action'])) {
			if ($get_data['action'] == "post") {
				$browsercheck = FALSE;
			}
		}
		if ($locale != "") {
			$browsercheck = FALSE;
		}
	    $ini_langs = initialize_languages($browsercheck);
	    $locale = $ini_langs['locale'];
	    $localization = $ini_langs['localization'];
	    $charset = $ini_langs['charset'];

		// Send cookies that expire in one hour: 
		setcookie("loginName", 'admin', time()+60*60*24*365, "/");
		if ($sessionId != "") {
			setcookie("sessionId", $sessionId, "0", "/");
		}

		// Set new locale to cookie, too, but set an expiry of 365 days:
		$cookie = array('name' => 'locale', 'path' => '/', 'value' => $locale, 'expire' => '31536000');
		$this->input->set_cookie($cookie);

		// Check if the visitor is using a browser or a mobile device:
		$mobile = '';
		if ($CI->agent->is_browser()) {
		    $layout = "layout_fixed.css";
		    $agent = $CI->agent->is_browser();
		}
		else {
		    $layout = "layout_fixed.css";
		}
		// Special (nut)case: is_browser() always overrides is_mobile(). See: https://github.com/EllisLab/CodeIgniter/issues/1347
		if ($CI->agent->is_mobile()) {
			$mobile = TRUE;
		    $layout = "layout_fluid.css";
		    $agent = $CI->agent->is_mobile();
		}

		//$i18n = new I18n("base-wizard", $locale);

	    // We start without any active errors:
	    $errors = array();
	    $ci_errors = array();
	    $my_errors = array();

		// Form fields that are required to have input:
		$required_keys = array();

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text", "_serialized_errors");

		// Get $errors from ServerScriptHandler POST vars:
		if (isset($form_data['_serialized_errors'])) {
			$TMPerrors = array_merge($errors, unserialize($form_data['_serialized_errors']));
			foreach ($TMPerrors as $errNum => $errMsg) {
				$errors[$errNum] = urldecode($errMsg);
			}
			$attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
		}
		else {

			if (is_array($form_data)) {
				// Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
				// submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
				// In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
				// It also transformes the value of the ticked checkboxes from "on" to "1". 
				//
				// Additionally it generates the form_validation rules for CodeIgniter.
				//
				// params: $i18n				i18n Object of the error messages
				// params: $form_data			array with form_data array from CI
				// params: $required_keys		array with keys that must have data in it. Needed for CodeIgniter's error checks
				// params: $ignore_attributes	array with items we want to ignore. Such as Labels.
				// return: 						array with keys and values ready to submit to CCE.
				$attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
			}
			//Setting up error messages:
			$CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));		

		    // Do we have validation related errors?
		    if ($CI->form_validation->run() == FALSE) {

				if (validation_errors()) {
					// Set CI related errors:
					$ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
				}		    
				else {
					// No errors. Pass empty array along:
					$ci_errors = array();
				}
			}

			//
			//--- Own error checks:
			//

			if ($CI->input->post(NULL, TRUE)) {

				//    [localeField] => en_US
				//    [license_acceptance] => on
				//    [hostNameField] => ng2
				//    [domainNameField] => blueonyx.it
				//    [dnsAddressesField] => &127.0.0.1&
				//    [gatewayField] => 186.116.135.82
				//    [ipAddressFieldeth0] => 186.116.135.83
				//    [netMaskFieldeth0] => 255.255.255.240
				//    [macAddressFieldeth0] => 08:00:27:D4:2C:4E
				//    [hasAliaseseth0] => 0
				//    [ipAddressOrigeth0] => 186.116.135.83
				//    [netMaskOrigeth0] => 255.255.255.240
				//    [bootProtoFieldeth0] => none
				//    [enabledeth0] => 0
				//    [deviceList] => &eth0&
				//    [adminNameField] => admin
				//    [newPasswordField] => XXXXX
				//    [_newPasswordField_repeat] => XXXXX
				//	  [sql_rootpassword] => XXXXX
				//    [systemDate] => 1401198752
				//    [_systemDate_oyear] => 2014
				//    [_systemDate_omonth] => 5
				//    [_systemDate_ohour] => 9
				//    [_systemDate_ominute] => 52
				//    [_systemDate_osecond] => 32
				//    [_systemDate_month] => 05
				//    [_systemDate_day] => 27
				//    [_systemDate_year] => 2014
				//    [_systemDate_hour] => 9
				//    [_systemDate_minute] => 52
				//    [_systemDate_amPm] => AM
				//    [timezoneSelectDropdown] => US/Eastern
				//    [oldTimeZone] => US/Eastern

				// Password empty?
				if (bx_pw_check($i18n, $attributes['newPasswordField'], $attributes['_newPasswordField_repeat']) != "") {
					$my_errors = bx_pw_check($i18n, $attributes['newPasswordField'], $attributes['_newPasswordField_repeat']);
				}
				// License accepted?
				if (!isset($attributes['license_acceptance'])) {
					$my_errors = ErrorMessage($i18n->get("[[base-wizard.accept_help]]"). '<br>' . $i18n->get("[[base-wizard.decline_help]]"));
				}
				if ((!isset($attributes['hostNameField'])) || (!isset($attributes['domainNameField']))) {
					$my_errors = ErrorMessage($i18n->get("[[base-wizard.enterFqdn_help]]"));
				}
			}

			//
			//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
			//

			// Join the various error messages:
			$errors = array_merge($ci_errors, $my_errors);
			// If we have no errors and have POST data, we submit to CODB:
			if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

				// We have no errors. We submit to CODB.

				//
				//-- Set Locale:
				//

				$user_attributes = array("localePreference" => $attributes['localeField']);

				// Username = Password? Baaaad idea!
				if (strcasecmp('admin', $attributes['newPasswordField']) == 0) {
				        $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
				        $errors[] = new Error($error_msg);
				}

				// Password Check:
				if (isset($attributes['newPasswordField'])) {
					$passwd = $attributes['newPasswordField'];
				}
				$passwd_repeat = "";
				if (isset($attributes['_newPasswordField_repeat'])) {
					$passwd_repeat = $attributes['_newPasswordField_repeat'];
				}
				if (bx_pw_check($i18n, $passwd, $passwd_repeat) != "") {
					$my_errors = bx_pw_check($i18n, $passwd, $passwd_repeat);
				}
				if ($attributes['newPasswordField']) {
  					$user_attributes["password"] = $attributes['newPasswordField'];
  				}

				// Set User locale and password first:
				$cceClient->setObject("User", $user_attributes, "", array("name" => 'admin'));
				$errors = array_merge($errors, $cceClient->errors());

				// Set system-language
				$oids = $cceClient->find("System");
				$cceClient->set($oids[0], "", array("productLanguage" => $attributes['localeField']));
				$errors = array_merge($errors, $cceClient->errors());

				// Set new locale to cookie, too:
				$cookie = array('name' => 'locale', 'path' => '/', 'value' => $attributes['localeField'], 'expire' => '31536000');
				$CI->input->set_cookie($cookie);

				//
				//-- Set MySQL root password:
				//

				$mysql_data = array(
						'oldpass' => '',
						'username' => 'root',
						'newpass' => $attributes['sql_rootpassword'],
						'mysqluser' => 'root',
						'onoff' => time(),
						'password' => '',
						'changepass' => time(),
						'enabled' => '1'
					);

		  		// Actual submit to CODB:
		  		$cceClient->setObject("System", $mysql_data, "mysql");

				// Track MySQL Password change separately:
				$m_error = $cceClient->errors();
				$mysql_error = array();
				$errors = array_merge($errors, $m_error);
				$mysql_error = array_merge($errors, $m_error);

				// Now handle the set to the CODB object "MySQL" as well.
				// But only if we don't have errors:
				if (count($mysql_error) == "0") {
					$getthisOID = $cceClient->find("MySQL");
					$mysql_settings_exists = 0;
					$mysql_settings = $cceClient->get($getthisOID[0]);
					if (!isset($mysql_settings['timestamp'])) {
						$mysqlOID = $cceClient->create("MySQL",
							array(
								'sql_host' => 'localhost',
								'sql_port' => '3306',
								'sql_root' => 'root',
								'sql_rootpassword' => $attributes['sql_rootpassword'],
								'savechanges' => time(),
								'timestamp' => time()
							)
						);
					}
					else {
						$mysqlOID = $cceClient->find("MySQL");
						$cceClient->set($mysqlOID[0], "",
							array(
								'sql_host' => 'localhost',
								'sql_port' => '3306',
								'sql_root' => 'root',
								'sql_rootpassword' => $attributes['sql_rootpassword'],
								'savechanges' => time(),
								'timestamp' => time()
							)
						);
					}
					$errors = array_merge($errors, $cceClient->errors());
				}

				//
				//-- Set TimeZone:
				//

				if ($attributes['timezoneSelectDropdown'] != $attributes['oldTimeZone']) {
					$timeZone = $attributes['timezoneSelectDropdown'];
					putenv("TZ=$timeZone");
				}
				else {
					$timeZone = $attributes['timezoneSelectDropdown'];
				}
				if ($timeZone == "") {
					// Got nothing? Set a default:
					$timeZone == "US/Eastern";
				}

				$date = mktime($attributes['_systemDate_hour'], $attributes['_systemDate_minute'], "00", $attributes['_systemDate_month'], $attributes['_systemDate_day'], $attributes['_systemDate_year']);

		  		// Actual submit to CODB:
				// "deferCommit" is used by the setup wizard:
				$cceClient->setObject('System', array(
				                            'deferCommit' => '1',
				                            'epochTime' => $date,
				                            'timeZone' => $timeZone,
				                            ), 'Time');
				$errors = array_merge($errors, $cceClient->errors());

				// Work around for 5106R oddity. We use the extra handler to set the timezone instead:
				$cceClient->setObject('System', array(
				                            'epochTime' => $date,
				                            'timeZone' => $timeZone,
				                            'trigger' => time()
				                            ), 'TempTime');
				$errors = array_merge($errors, $cceClient->errors());

				$serverScriptHelper->shell("/usr/sausalito/sbin/setTime " . $date . " " . $timeZone . " " . "" . " false", $output, "root", $sessionId);

				//
				//-- Set Network:
				//

				//    [hostNameField] => ng
				//    [domainNameField] => blueonyx.it
				//    [dnsAddressesField] => &208.67.251.180&208.77.221.199&8.8.8.8&4.2.2.2&
				//    [gatewayField] => 192.0.2.1


				if (!file_exists("/proc/user_beancounters")) {
				    // Regular Network Interfaces
				    $ok = $cceClient->set($oids[0], "", array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField'], "gateway" => $attributes['gatewayField']));
					$errors = array_merge($errors, $cceClient->errors());
				}
				else {
				    // OpenVZ Network Interfaces
				    $ok = $cceClient->set($oids[0], "", array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField']));
					$errors = array_merge($errors, $cceClient->errors());
				}				

				// Check if 'localhost' or our own IP are used as DNS servers:
				if (isset($attributes['ipAddressFieldeth0'])) {
					// We're using our own DNS. Enable the DNS server:
					$cceClient->setObject("System", array("enabled" => '1'), "DNS");
				}
				$ownDNS = $cceClient->scalar_to_array($attributes['dnsAddressesField']);
				if (in_array('127.0.0.1', $ownDNS)) {
					// We're using our own DNS. Enable the DNS server:
					$cceClient->setObject("System", array("enabled" => '1'), "DNS");
				}

				$adminIf = "eth1";

				if ((!file_exists("/proc/user_beancounters")) && (!file_exists("/etc/is_aws"))) {
				    // Regular Network Interfaces

				    // Handle all devices
					$devices = array('eth0', 'eth1');
				    if (isset($attributes['deviceList'])) {
				    	$devices = json_decode(urldecode($attributes['deviceList']));
					}
					// Screw ith. Only handle eth0 and eth1:
					if (!is_array($devices)) {
						$devices = array('eth0', 'eth1');
					}

					// Only set Network objects if we have interfaces to begin with:
					if (isset($devices['eth0'])) {
					    // special array for admin if errors
					    $admin_if_errors = array();
						for ($i = 0; $i < 1; $i++) { // Screw it, we only do the first two devices.
							$var_name = "ipAddressField" . $devices[$i];
							$ip_field = $attributes[$var_name];
							$var_name = "ipAddressOrig" . $devices[$i];
							$ip_orig = $attributes[$var_name];
							$var_name = "netMaskField" . $devices[$i];
							$nm_field = $attributes[$var_name];
							$var_name = "netMaskOrig" . $devices[$i];
							$nm_orig = $attributes[$var_name];
							$var_name = "bootProtoField" . $devices[$i];
							$boot_field = $attributes[$var_name];

							// setup or set disabled
							if ($ip_field == '') {
								// first migrate any aliases to eth0 (possibly do this better)
								$aliases = $cceClient->findx('Network', array(), array('device' => "^$devices[$i]:"));
								for ($k = 0; $k < count($aliases); $k++) {
									$new_device = find_free_device($cceClient, 'eth0');
									$ok = $cceClient->set($aliases[$k], '', array('device' => $new_device));
									$errors = array_merge($errors, $cceClient->errors());
								}

								$cceClient->setObject(
									'Network', 
									array("enabled" => "0"), 
									"",
									array("device" => $devices[$i])
								);

								if ($devices[$i] == $adminIf) {
									$admin_if_errors = $cceClient->errors();
								}
								else {
									$errors = array_merge($errors, $cceClient->errors());
								}
							}
							elseif ($ip_field && (($ip_field != $ip_orig) || ($nm_field != $nm_orig))) {

								// Set redirect IP for when we're done:
								$redirect_to_new_ip = $ip_field;

								// since we only deal with real interfaces here, things are simpler
								// than they could be
								if ($ip_field != $ip_orig) {
									// check to see if there is an alias that is already using
									// the new ip address.  if there is, destroy the Network object
									// for this device, and assign the alias this device name.

									$alias = $cceClient->find('Network', 
														array(
															'real' => 0,
															'ipaddr' => $ip_field
															));

									if (isset($alias[0])) {
										$ok = $cceClient->set($alias, '',
											array(
												'device' => $devices[$i],
												'real' => 1,
												'ipaddr' => $ip_field,
												'netmask' => $nm_field,
												'enabled' => 1,
												'bootproto' => 'none'
												));
										$errors = array_merge($errors, $cceClient->errors());
										if (!$ok) {
											break;
										}
										else {
											continue;
										}
									}
								}
								$cceClient->setObject('Network',
										array(
											'ipaddr' => $ip_field,
											'netmask' => $nm_field,
											'enabled' => 1,
											'bootproto' => 'none'
											),
									   '', array('device' => $devices[$i]));

								if ($devices[$i] == $adminIf) {
									$admin_if_errors = $cceClient->errors();
								}
								else {
									$errors = array_merge($errors, $cceClient->errors());
								}
							}
						}
					}
				}

				//
				//-- Finalize if we have no errors:
				//

				if (count($errors) == "0") {
					$cceClient->setObject('System', array('isLicenseAccepted' => '1', 'isRegistered' => '0'), '');

					// Send cookies that expire in one hour:
					setcookie("loginName", 'admin', time()+60*60*24*365, "/");
					if ($sessionId != "") {
						setcookie("sessionId", $sessionId, "0", "/");
					}

					// Set new locale to cookie, too, but set an expiry of 365 days:
					$cookie = array('name' => 'locale', 'path' => '/', 'value' => $locale, 'expire' => '31536000');
					$this->input->set_cookie($cookie);

					//
					//-- Set Theme cookies:
					//

					// Default Style:
					$ChorizoDefaultStyle =	array(
							'theme_switcher_php-style'   => 'theme_blue.css',
							'layout_switcher_php-style'  => 'layout_fixed.css',
							'nav_switcher_php-style'	 => 'switcher.css',
							'skin_switcher_php-style'    => 'skin_light.css',
							'bg_switcher_php-style'      => 'switcher.css'
						);

					// Push out cookies for the Users known Style:
					foreach ($ChorizoDefaultStyle as $key => $value) {
						$theme_cookie = array('name' => $key, 'path' => '/', 'value' => $value, 'expire' => '31536000');
						$this->input->set_cookie($theme_cookie);
					}

					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();

					if (!isset($redirect_to_new_ip)) {
						// Simple redirect as IP hasn't changed:
						header("Location: /gui");
			      		exit;
			      	}
			      	else {
						// Redirect to the new IP:
						header("Location: http://$redirect_to_new_ip:444/gui");
			      		exit;
			      	}
				}
			}
		}

		//
	    //-- Generate page:
	    //

	    // Find out if the web based initial setup has been completed:
	    $system = $cceClient->getObject('System');

	    if ($system['isLicenseAccepted'] == "1" ) {
		    // Web based setup *has* been completed. Redirect to /gui
		    header("Location: /gui");
		    exit;
	    }

		// Set default Wait Overlay:
		$this->Overlay = "			<script>
				$(document).ready(function() {
				  $('#fade_overlay').popup({
					  	blur: false
				  	});
				});
			</script>
			<!-- End: Wait overlay -->
			<!-- Start: Wait overlay -->
			<div id=\"fade_overlay\" class=\"display_none\">
				<img src=\"/.adm/images/wait/loading_bar.gif\" id=\"loading_image\" height=\"75\" width=\"450\">
			</div>\n";

	    // Get the IP address of the user accessing the GUI:
	    $userip = $this->input->ip_address();

	    // Set headers:
	    $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
	    $this->output->set_header("Cache-Control: post-check=0, pre-check=0");
	    $this->output->set_header("Pragma: no-cache"); 
	    $this->output->set_header("Content-language: $locale");
	    $this->output->set_header("Content-type: text/html; charset=$charset");

	    // Get default theme cookie (if it exists):
	    if ($this->input->cookie('skin_switcher_php-style')) {
	    	$skin = $this->input->cookie('skin_switcher_php-style');
	    }
	    else {
	    	$skin = 'skin_light.css';
	    }

	    // Set page title:
	    preg_match("/^([^:]+)/", $_SERVER['HTTP_HOST'], $matches);
	    $hostname = $matches[0];
	    // Strip out the :444 or :81 from the hostname - if present:
	    if (preg_match('/:/', $hostname)) {
			$hn_pieces = explode(":", $hostname);
			$hostname = $hn_pieces[0];
	    }
	    //$i18n = new I18n("base-wizard", $locale);
	    preg_match("/([^:]+):?.*/", $hostname, $matches);
	    $hostname_new = $matches[1] ? $matches[1] : `/bin/hostname --fqdn`;

	    //
	    //-- Check if we are authed against CCEd:
	    //

		// Check if either of the two login methods worked:
		if($sessionId == "") {
			// If we're here, the password was changed and is no longer the default one. 
			// Redirect to the login page:
			$cceClient->bye();
			header("Location: /login?action=wizard");
      		exit;
		}
		else {
			$cceClient->bye();
			$cceClient->bye();

			$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/wizard");
            $defaultPage = "Basic";
			//$i18n = new I18n("base-wizard", $locale);
			$BxPage = $factory->getPage();
			$BxPage->setI18n($i18n);

			//
            //-- Step #1: Language
            //
			$step_1_title = $i18n->getHtml("wizard_locale_header", "base-wizard");
			$step_1_title_sub = $i18n->getHtml("wizard_locale_header_sub", "base-wizard");

            // Locale selector:
			$step_1 = $factory->getSimpleBlock(" ", $i18n);
            $localeField = $factory->getLocale("localeField", $locale);
            $localeField->setPossibleLocales(array('en_US', 'da_DK', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'ja_JP', 'nl_NL', 'pt_PT'));
            $step_1->addHtmlComponent(
              $localeField,
              $factory->getLabel("localeField"), $defaultPage
            );

			//
            //-- Step #2: License
            //

			$step_2_title = $i18n->getHtml("wizard_license_header", "base-wizard");
			$step_2_title_sub = $i18n->getHtml("wizard_license_header_sub", "base-wizard");

			$step_2 = $factory->getSimpleBlock(" ", $i18n);
            $licenseClick = $factory->getTextField("licenseClick", '<H3>' . $i18n->getHtml("licenseClick") . '</H3>', 'html');
            $licenseClick->setLabelType("nolabel");
            $step_2->addHtmlComponent(
              $licenseClick,
              $factory->getLabel("licenseClick"), $defaultPage
            );

            $license = $factory->getTextField("license", '<p>' . $i18n->get("license") . '</p>', 'html');
            $license->setLabelType("nolabel");
            $step_2->addHtmlComponent(
              $license,
              $factory->getLabel("license"), $defaultPage
            );

$licTextBody = '
------ SUN-modified-BSD-License for BlueOnyx: ------
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.';

            $licText = $factory->getTextField("licText", '<pre>' . $licTextBody . '</pre>', 'html');
            $licText->setLabelType("nolabel");
            $step_2->addHtmlComponent(
              $licText,
              $factory->getLabel("licText"), $defaultPage
            );

			$license_accept_block = '
                                        <fieldset class="label_side top">
                                            <label for="license_acceptance" title="' . $i18n->getWrapped("mustAcceptToUse") . '" class="tooltip right uniform">' . $i18n->getHtml("license_acceptance") . '<span></span></label>
                                            <div>
                                                <div class="jqui_radios">
                                                    <input type="radio" name="license_acceptance" id="accept"  class="required" /><label for="accept">' . $i18n->getHtml("accept") . '</label>
                                                    <input type="radio" name="license_acceptance" id="decline" /><label for="decline">' . $i18n->getHtml("decline") . '</label>                                                                                     
                                                </div>
                                                <div class="required_tag"></div>
                                            </div>
                                        </fieldset>' . "\n";
            $step_2->addHtmlComponent(
              $factory->getRawHTML("license_acceptance", $license_accept_block),
              $factory->getLabel("license_acceptance"), $defaultPage
            );

			//
            //-- Step #3: System Settings
            //

			$step_3_title = $i18n->getHtml("serverconfig", "base-alpine");
			$step_3_title_sub = $i18n->getHtml("wizardSysSettings_help", "base-wizard");

			$step_3 = $factory->getSimpleBlock(" ", $i18n);

			// Network settings

			$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
			$cceClient = $serverScriptHelper->getCceClient();

			$networkObj = $cceClient->getObject("System", array(), "Network");

			// Add divider:
			$step_3->addHtmlComponent(
			        $factory->addBXDivider("networkSettings", ""),
			        $factory->getLabel("networkSettings", false),
			        $defaultPage
			        );

			//host and domain names
			if (($system['hostname'] == 'localhost') &&
			    ($system['domainname'] == '')) {
				// assume this is first boot if domainname is not set
				$defaultHostname = '';
			} else {
				$defaultHostname = $system['hostname'];
			}

			// host and domain names
			$hostfield = $factory->getDomainName("hostNameField", $defaultHostname, 'rw');
			$hostfield->setOptional(FALSE);
			$domainfield = $factory->getDomainName("domainNameField", $system["domainname"], 'rw');
			$domainfield->setOptional(FALSE);

			$fqdn = $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp;.&nbsp;');

			$step_3->addHtmlComponent(
				$fqdn,
				$factory->getLabel("enterFqdn"), 
				$defaultPage
			);

			$dns = $factory->getIpAddressList("dnsAddressesField", $system["dns"]);
			$dns->setOptional(TRUE);
			$step_3->addHtmlComponent(
			  $dns,
			  $factory->getLabel("dnsAddressesField")
			);

			// Protect certain form fields read-only inside VPS's:
			if (is_file("/proc/user_beancounters")) {
			    $fieldprot = "r";
			}
			else {
			    $fieldprot = "rw";
			}

			// Are we running on AWS?
			if (is_file("/etc/is_aws")) {
			    $is_aws = "1";
			}
			else {
			    $is_aws = "0";
			}

			$gw = $factory->getIpAddress("gatewayField", $system["gateway"], $fieldprot);
			$gw->setOptional(true);
			$step_3->addHtmlComponent($gw, $factory->getLabel("gatewayField"), $defaultPage);

			// real interfaces
			// ascii sorted, this may be a problem if there are more than 10 interfaces
			$interfaces = $cceClient->findx('Network', array('real' => 1, 'enabled' => 1), array(), 'ascii', 'device');
			$devices = array();
			$deviceList = array();
			$devnames = array();
			$admin_if = '';
			for ($i = 0; $i < count($interfaces); $i++) {

				$is_admin_if = false;
				$iface = $cceClient->get($interfaces[$i]);
				$device = $iface['device'];
				
				// save the devices and strings for javascript fun
				$deviceList[] = $device;
				$devices[] = "'$device'";    
				$devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

			        // Devices:
			        $dev[$device] = array (
			                        'ipaddr' => $iface["ipaddr"],
			                        'netmask' => $iface["netmask"],
			                        'mac' => $iface["mac"],
			                        'device' => $device,
			                        'bootproto' => $iface["bootproto"],
			                        'enabled' => $iface["enabled"]
			                        );

			}

			if (isset($dev['eth0'])) {
			    $ipaddr = $dev['eth0']['ipaddr'];
			    $netmask = $dev['eth0']['netmask'];
			    $device = $dev['eth0']['device'];
			    $mac = $dev['eth0']['mac'];
			    $enabled = $dev['eth0']['enabled'];
			    $bootproto = $dev['eth0']['bootproto'];
			    
			    $ip_label = '[[base-network.ipAddressField1]]';
			    $nm_label = '[[base-network.netMaskField1]]';

				// Add divider:
				$divider = $factory->addBXDivider("interface$device", "");
				$divider->setCurrentLabel($i18n->getHtml("[[base-network.interface$device]]", false));
				$step_3->addHtmlComponent(
				        $divider,
				        $factory->getLabel("[[base-network.interface$device]]", false),
				        $defaultPage
				        );

			    if ($is_aws == "0") {
					$devprot = "rw";
			    }
			    else {
					$devprot = "r";
			    }

			    // IP Address:
			    $ip_field0 = $factory->getIpAddress("ipAddressField$device", $ipaddr, $devprot);
			    $ip_field0->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
				$ip_field0->setCurrentLabel($i18n->getHtml($ip_label, true, array(), array('name' => "[[base-network.help$device]]")));
				$ip_field0->setDescription($i18n->getWrapped('[[base-network.ipAddressField1_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
			    $step_3->addHtmlComponent(
			            $ip_field0,
			            $factory->getLabel($ip_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    // Netmask:
			    $netmask_field0 = $factory->getIpAddress("netMaskField$device", $netmask, $devprot);
			    $netmask_field0->setInvalidMessage($i18n->getJs('netMaskField_invalid'));

			    // Netmask is not optional for the admin iface and for eth0
			    $netmask_field0->setOptional(false);

				$netmask_field0->setCurrentLabel($i18n->getHtml($nm_label, true, array(), array('name' => "[[base-network.help$device]]")));
				$netmask_field0->setDescription($i18n->getWrapped('[[base-network.netMaskField1_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
			    
			    $step_3->addHtmlComponent(
			            $netmask_field0,
			            $factory->getLabel($nm_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    // MAC Address:
				$mac0 = $factory->getMacAddress("macAddressField$device", $mac, "r");
				$mac0->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
				$mac0->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));
			    $step_3->addHtmlComponent(
			            $mac0,
			            $factory->getLabel("macAddressField"),
			            $defaultPage
			        );

			    // retain orginal information
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("hasAliases$device", 0, ''));

			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
			            '',
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getTextField("bootProtoField$device", $bootproto, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("enabled$device", $enabled, ""),
			            "",
			            $defaultPage
			            );

			}
			if (isset($dev['eth1'])) {
			    $ipaddr = $dev['eth1']['ipaddr'];
			    $netmask = $dev['eth1']['netmask'];
			    $device = $dev['eth1']['device'];
			    $mac = $dev['eth1']['mac'];
			    $enabled = $dev['eth1']['enabled'];
			    $bootproto = $dev['eth1']['bootproto'];

			    if ($enabled == "0") {
					$ipaddr = "";
					$netmask = "";
			    }
			    
			    $ip_label = 'ipAddressField1';
			    $nm_label = 'netMaskField1';

				// Add divider:
				$divider = $factory->addBXDivider("interface$device", "");
				$divider->setCurrentLabel($i18n->getHtml("[[base-network.interface$device]]", false));
				$step_3->addHtmlComponent(
				        $divider,
				        $factory->getLabel("[[base-network.interface$device]]", false),
				        $defaultPage
				        );

			    $ip_field1 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
			    $ip_field1->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
				$ip_field1->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$ip_field1->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

			    $ip_field1->setOptional(true);

			    $step_3->addHtmlComponent(
			            $ip_field1,
			            $factory->getLabel($ip_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    $netmask_field1 = $factory->getIpAddress("netMaskField$device", $netmask);
			    $netmask_field1->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
			    $netmask_field1->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
				$netmask_field1->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$netmask_field1->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

			    $netmask_field1->setOptional(true);
			    
			    $step_3->addHtmlComponent(
			            $netmask_field1,
			            $factory->getLabel($nm_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    // MAC:
			    $macaddress_field1 = $factory->getMacAddress("macAddressField$device", $mac, "r");
				$macaddress_field1->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
				$macaddress_field1->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

			    $step_3->addHtmlComponent(
			            $macaddress_field1,
			            $factory->getLabel("macAddressField"),
			            $defaultPage
			        );

			    // retain orginal information
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("hasAliases$device", 0, ''));
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
			            '',
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getTextField("bootProtoField$device", $bootproto, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("enabled$device", $enabled, ""),
			            "",
			            $defaultPage
			            );

			}
			if (isset($dev['eth2'])) {
			    $ipaddr = $dev['eth2']['ipaddr'];
			    $netmask = $dev['eth2']['netmask'];
			    $device = $dev['eth2']['device'];
			    $mac = $dev['eth2']['mac'];
			    $enabled = $dev['eth2']['enabled'];
			    $bootproto = $dev['eth2']['bootproto'];
			    
			    if ($enabled == "0") {
					$ipaddr = "";
					$netmask = "";
			    }

			    $ip_label = 'ipAddressField';
			    $nm_label = 'netMaskField';

				// Add divider:
				$divider = $factory->addBXDivider("interface$device", "");
				$divider->setCurrentLabel($i18n->getHtml("[[base-network.interface$device]]", false));
				$step_3->addHtmlComponent(
				        $divider,
				        $factory->getLabel("[[base-network.interface$device]]", false),
				        $defaultPage
				        );

			    $ip_field2 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
			    $ip_field2->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
				$ip_field2->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$ip_field2->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
			    $ip_field2->setOptional(true);

			    $step_3->addHtmlComponent(
			            $ip_field2,
			            $factory->getLabel($ip_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    $netmask_field2 = $factory->getIpAddress("netMaskField$device", $netmask);
			    $netmask_field2->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
			    $netmask_field2->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
				$netmask_field2->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$netmask_field2->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

			    $netmask_field2->setOptional(true);
			    
			    $step_3->addHtmlComponent(
			            $netmask_field2,
			            $factory->getLabel($nm_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    // MAC:
			    $macaddress_field2 = $factory->getMacAddress("macAddressField$device", $mac, "r");
				$macaddress_field2->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
				$macaddress_field2->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

			    $step_3->addHtmlComponent(
			            $macaddress_field2,
			            $factory->getLabel("macAddressField"),
			            $defaultPage
			        );

			    // retain orginal information
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("hasAliases$device", 0, ''));
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
			            '',
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getTextField("bootProtoField$device", $bootproto, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("enabled$device", $enabled, ""),
			            "",
			            $defaultPage
			            );

			}
			if (isset($dev['eth3'])) {
			    $ipaddr = $dev['eth3']['ipaddr'];
			    $netmask = $dev['eth3']['netmask'];
			    $device = $dev['eth3']['device'];
			    $mac = $dev['eth3']['mac'];
			    $enabled = $dev['eth3']['enabled'];
			    $bootproto = $dev['eth3']['bootproto'];
			    
			    if ($enabled == "0") {
					$ipaddr = "";
					$netmask = "";
			    }

			    $ip_label = 'ipAddressField';
			    $nm_label = 'netMaskField';

				// Add divider:
				$divider = $factory->addBXDivider("interface$device", "");
				$divider->setCurrentLabel($i18n->getHtml("[[base-network.interface$device]]", false));
				$step_3->addHtmlComponent(
				        $divider,
				        $factory->getLabel("[[base-network.interface$device]]", false),
				        $defaultPage
				        );

			    $ip_field3 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
			    $ip_field3->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
				$ip_field3->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$ip_field3->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

			    $ip_field3->setOptional(true);

			    $step_3->addHtmlComponent(
			            $ip_field3,
			            $factory->getLabel($ip_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    $netmask_field3 = $factory->getIpAddress("netMaskField$device", $netmask);
			    $netmask_field3->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
			    $netmask_field3->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
				$netmask_field3->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
				$netmask_field3->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

			    $netmask_field3->setOptional(true);
			    
			    $step_3->addHtmlComponent(
			            $netmask_field3,
			            $factory->getLabel($nm_label, true,
			                        array(), array('name' => "[[base-network.help$device]]")),
			            $defaultPage
			        );

			    // MAC:
			    $macaddress_field3 = $factory->getMacAddress("macAddressField$device", $mac, "r");
				$macaddress_field3->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
				$macaddress_field3->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

			    $step_3->addHtmlComponent(
			            $macaddress_field3,
			            $factory->getLabel("macAddressField"),
			            $defaultPage
			        );

			    // retain orginal information
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("hasAliases$device", 0, ''));
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
			            '',
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getTextField("bootProtoField$device", $bootproto, ""),
			            "",
			            $defaultPage
			            );
			    $step_3->addHtmlComponent(
			            $factory->getBoolean("enabled$device", $enabled, ""),
			            "",
			            $defaultPage
			            );
			}

			// Add list of seen Network devices:
		    $step_3->addHtmlComponent(
		            $factory->getTextField("deviceList", urlencode(json_encode($deviceList)), ""),
		            "",
		            $defaultPage
		            );

			//
			//-- Admin Password:
			//

			// Add divider:
			$step_3->addHtmlComponent(
			        $factory->addBXDivider("wizardAdmin", ""),
			        $factory->getLabel("wizardAdmin", false),
			        $defaultPage
			        );

			// User-Name:
			$adminName = $factory->getFullName("adminNameField", 'admin', 'r');
			$adminName->setOptional(TRUE);
			$step_3->addHtmlComponent(
			        $adminName,
			        $factory->getLabel("adminNameField"),
			        $defaultPage
			        );

			// Password:
			$mypw = $factory->getPassword("newPasswordField", "", "rw");
			$mypw->setConfirm(TRUE);
			$mypw->setOptional(FALSE);
			$mypw->setCheckPass(TRUE);
			$step_3->addHtmlComponent(
			  $mypw,
			  $factory->getLabel("newPasswordField"), $defaultPage
			);

			//
			//--- MySQL password:
			//

			// Add divider:
			$step_3->addHtmlComponent(
			        $factory->addBXDivider("wizardMySQLpassHeader", ""),
			        $factory->getLabel("wizardMySQLpassHeader", false),
			        $defaultPage
			        );

			// sql_rootpassword:
			$line_sql_rootpassword = $factory->getPassword("sql_rootpassword", "", "rw");
			$line_sql_rootpassword->setOptional(FALSE);
			$line_sql_rootpassword->setConfirm(FALSE);
			$line_sql_rootpassword->setCheckPass(FALSE);
			$step_3->addHtmlComponent($line_sql_rootpassword, $factory->getLabel("sql_rootpassword"), $defaultPage);			

			//
			//-- Timezone:
			//

			// Add divider:
			$step_3->addHtmlComponent(
			        $factory->addBXDivider("wizardTime", ""),
			        $factory->getLabel("wizardTime", false),
			        $defaultPage
			        );

			// Get current time from time():
			$t = time();

			$CODBDATA = $cceClient->getObject("System", array(), "Time");

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			if ($CODBDATA["timeZone"] == "") {
				// Got nothing? Set a default:
				$CODBDATA["timeZone"] == "US/Eastern";
			}

			$systemDisplayedDate = $factory->getTimeStamp("systemDate", $t, "datetime");
			$systemDisplayedDate->setCurrentLabel($i18n->getHtml("[[base-time.systemDisplayedDate]]"));
			$systemDisplayedDate->setDescription($i18n->getWrapped("[[base-time.systemDisplayedDate_help]]"));
			$step_3->addHtmlComponent($systemDisplayedDate, $factory->getLabel("systemDisplayedDate"));

			$systemDisplayedTimeZone = $factory->getTimeZone("systemTimeZone", $CODBDATA["timeZone"]);
			$systemDisplayedTimeZone->setCurrentLabel($i18n->getHtml("[[base-time.systemDisplayedTimeZone]]"));
			$systemDisplayedTimeZone->setDescription($i18n->getWrapped("[[base-time.systemDisplayedTimeZone_help]]"));
			$step_3->addHtmlComponent($systemDisplayedTimeZone, $factory->getLabel("systemDisplayedTimeZone"));

			$oldTimeZone = $factory->getTextField("oldTimeZone", $CODBDATA["timeZone"], "");
			$step_3->addHtmlComponent($oldTimeZone);

			//
            //-- Step #4: Finalize
            //

			$step_4_title = $i18n->getHtml("wiz_finalize", "base-wizard");
			$step_4_title_sub = $i18n->getHtml("wiz_finalize_help", "base-wizard");

			$step_4 = $factory->getSimpleBlock(" ", $i18n);

            $finalize_blurb_header = $factory->getRawHTML("finalize_blurb_header", '<p><H3>' . $i18n->getHtml("finalize_blurb_header") . '</H3></p>');
            $step_4->addHtmlComponent(
              $finalize_blurb_header,
              $factory->getLabel("finalize_blurb_header"), $defaultPage
            );

            $finalize_blurb_text = $factory->getRawHTML("finalize_blurb_text", '<p>' . $i18n->getHtml("finalize_blurb_text") . '</p>');
            $step_4->addHtmlComponent(
              $finalize_blurb_text,
              $factory->getLabel("finalize_blurb_text"), $defaultPage
            );

            $finalize_help_us = $factory->getRawHTML("finalize_help_us", '<p>' . $i18n->get("finalize_help_us") . '</p>');
            $step_4->addHtmlComponent(
              $finalize_help_us,
              $factory->getLabel("finalize_help_us"), $defaultPage
            );

            $PayPal = '
            			<div align="center">
	            			<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KTKZNMW3F2WUU" target="_blank">
	            				<img src="https://www.paypalobjects.com/en_US/DE/i/btn/btn_donateCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
	            			</a>
                        </div>' . "\n";

			$donate = $factory->getRawHTML("finalize_help_us", $PayPal);
            $step_4->addHtmlComponent(
              $donate,
              $factory->getLabel("finalize_help_us"), $defaultPage
            );

            // Register with BlueOnyx: No reason to get paranoid. We just track
            // the usage of the Wizard and get to know your servers IP and which
            // version of BlueOnyx you are using. The Serial Number is usually 
            // empty at this point and is passed along, too. Beyond this no further
            // tracking is done.
			$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
			$cceClient = $serverScriptHelper->getCceClient();
			if (file_exists("/proc/user_beancounters")) {
				// VENET interface:
				$venetNetObj = $cceClient->find('Network', 
									array(
										'device' => 'venet0:0'
										));
				$venetNet = $cceClient->get($venetNetObj[0]);
				$venetNetipAddr = $venetNet['ipaddr'];
			}
			$cceClient->bye();
			$serverScriptHelper->destructor();
            $productBuild = $system['productBuild'];
			if (isset($dev['eth0'])) {
			    $ipaddr = $dev['eth0']['ipaddr'];
			}
			elseif (isset($venetNetipAddr)) {
				$ipaddr = $venetNetipAddr;
			}
			else {
				$ipaddr = $system['gateway'];
			}
			$serialNumber = $system['serialNumber'];

			// According to a bug report w/o much info there was a case where the $errors might
			// have been a string instead of an array. Hence the explode failed. So we play it
			// double safe here:
			if (is_string($errors)) {
				if (!is_object($errors)) {
					$errors = implode('', $errors);
				}
			}
			if (is_array($errors)) {
				if (count($errors) == "0") {
					$errors = '';
				}
				else {
					$errors = implode('', $errors);
				}
			}

			// Assemble data:
			$data = array(
				'charset' => $charset,
				'localization' => $localization,
				'loginName' => 'admin',
				'page_title' => $hostname_new . ': ' . $i18n->getHtml("[[base-wizard.iso_wizard_title]]"),
				'errors' => $errors,
				'fullName' => 'Administrator',
				'layout' => $layout,
				'extra_headers' => '',
				'body_open_tag' => '<body>',
				'overlay' => $this->Overlay,
				'debug' => '',
				'iso_wizard_title' => $i18n->getHtml("[[base-wizard.iso_wizard_title]]"),
				'step_1_title' => $step_1_title,
				'step_1_title_sub' => $step_1_title_sub,
				'step_1' => $step_1->toHtml(),
				'step_2_title' => $step_2_title,
				'step_2_title_sub' => $step_2_title_sub,
				'step_2' => $step_2->toHtml(),
				'step_3_title' => $step_3_title,
				'step_3_title_sub' => $step_3_title_sub,
				'step_3' => $step_3->toHtml(),
				'step_4_title' => $step_4_title,
				'step_4_title_sub' => $step_4_title_sub,
				'step_4' => $step_4->toHtml(),
				'next' => $i18n->getHtml("[[palette.next]]"),
				'previous' => $i18n->getHtml("[[palette.previous]]"),
				'done' => $i18n->getHtml("[[palette.done]]"),
				'productBuild' => $productBuild,
				'ipaddr' => $ipaddr,
				'serialNumber' => $serialNumber
			);

			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();

			// Show the login form:
			$this->load->view('wizard_view', $data);
		}
	}
}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>