<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ticket extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /support/ticket.
	 *
	 */

	public function index() {

		$CI =& get_instance();

	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');
		$MX =& get_instance();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-support", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'managePackage'? Bye, bye!
		if (!$Capabilities->getAllowed('managePackage')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Get CODB-Object of interest: 
		//

		// Get Support-Settings:
		$Support = $cceClient->getObject("System", array(), "Support");

		// Tempfile for the JSON encoded ticket:
		$TicketTmpPath = '/var/cache/admserv/' . $loginName . '_ticket.tmp';

		// Location (URLs) of the various NewLinQ query resources:
		$bluelinq_server	= 'newlinq.blueonyx.it';
		$newlinq_url 		= "http://$bluelinq_server/showshops/";
		$serialNumber		= $system['serialNumber'];
		$client_email 		= get_data("http://$bluelinq_server/username/$serialNumber");

		// Array for expiry pulldown:
        $sa_expiry_reverse = array(
            'never' => '0',
            '3_days' => '3',
            '5_days' => '5',
            '7_days' => '7',
            '10_days' => '10',
            '14_days' => '14',
            '30_days' => '30',
            '90_days' => '90',
            '180_days' => '180',
            '365_days' => '365'
        );

        $sa_expiry_forward = array(
            '0' => 'never',
            '3' => '3_days',
            '5' => '5_days',
            '7' => '7_days',
            '10' => '10_days',
            '14' => '14_days',
            '30' => '30_days',
            '90' => '90_days',
            '180' => '180_days',
            '365' => '365_days' 
        );

        $prio_forward_num = array(
			'prio_urgent' 		=> '0',
			'prio_high' 		=> '0',
			'prio_medium' 		=> '0',
			'prio_low' 			=> '0',
			'prio_unspecified' 	=> '1'
		);

        $severity_forward_num = array(
			'severity_urgent' 		=> '0',
			'severity_high' 		=> '0',
			'severity_medium' 		=> '0',
			'severity_low' 			=> '0',
			'severity_unspecified' 	=> '1'
		);

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		// Form fields that are required to have input:
		$required_keys = array();
    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text");
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
			if (($client_email != "0") && ($client_email != "")) {
				$attributes['client_email'] = $client_email;
			}
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// Check if we are online:
		if (areWeOnline($newlinq_url)) {

		  // Get Serial:
		  $SystemObj = $cceClient->getObject("System", array(), "");
		  $serialNumber = $SystemObj['serialNumber'];

		  // Poll NewLinQ about our status:
		  $snstatus = "RED";
		  $snstatus = get_data("http://$bluelinq_server/snstatus/$serialNumber");
		  if (!$snstatus === "RED") {
		     $string = $i18n->interpolateHtml("[[status-sn$snstatus]]");
		  }
		  else {
		  	if ($snstatus === "ORANGE") {
		  	    $string = $i18n->interpolateHtml("[[status-sn$snstatus]]");
		  	    $snstatusx = get_data("http://$bluelinq_server/snchange/$serialNumber");
		  	} 
		  	else {
		  	    $ipstatus = get_data("http://$bluelinq_server/ipstatus/$serialNumber");
		  	    $string = $i18n->interpolateHtml("[[status-ip$ipstatus]]");
		  	    if ( $ipstatus === "ORANGE" ) {
		      		$string = $i18n->interpolateHtml("[[status-ip$ipstatus]]");
		      		$ipstatusx = get_data("http://$bluelinq_server/ipchange/$serialNumber");
		  	    }
		  	}
		  }
		  // Are we online and in the green?
		  if ($snstatus == "GREEN") {
				$online = "1";
				// Get existing ticket numbers (if there are any, newest to oldest):
				$existing_tickets = get_data("http://$bluelinq_server/ticketlist/$serialNumber");
				if (strlen($existing_tickets) > '4') {
					$existing_tickets = preg_replace('/"/', '', $existing_tickets);
					$existing_tickets = preg_split("/\\r\\n|\\r|\\n/", $existing_tickets);
				}
		  }
		}
		else {
			// Not online, poll of 'newlinq.blueonyx.it' failed. Show error message:
		   	$online = "0";
		   	$errors[] = '<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Error_NewLinQ_Down]]") . '</strong></div>';
		}

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			$cleaned_attributes = array();

			// Clone $attributes:
			$attributes_clone = $attributes;

			//
			//-- Handle creation of 'Support-Account':
			//

			if ($attributes['allow_access'] == '1') {
				// Check if the 'Support-Account' already exists:
				$SAadmins = $cceClient->findx('User', 
				                array("capLevels" => 'adminUser', 'name' => $Support['support_account']),
				                array(), 
				                "",
				                "");

				$SA_Password = createRandomPassword('15', 'alpha');

				if (count($SAadmins) == '0') {
					// 'Support-Account' does not exists yet. Create it:
			        $big_ok = $cceClient->create('User',
			                        array(
			                            'fullName' => $Support['support_account_name'],
					                 	'sortName' => "",
			                            'name' => $Support['support_account'],
			                            'password' => $SA_Password,
			                            'capLevels' => '&adminUser&',
			                            'password' => $SA_Password
			                            ));
			        $errors = array_merge($errors, $cceClient->errors());

			        // Get the OID of this transaction:
	                if ($big_ok) {
	            		$_oid = $big_ok;
	            	}

					// Create succeeded. So we set the rest as well:
					if ($big_ok) {
						// Set the disk quota:
						$cceClient->set($_oid, 'Disk', array('quota' => '200'));
						$errors = array_merge($errors, $cceClient->errors());

						// Set the rest of the settings:
				        $new_settings = array(
						                 	'systemAdministrator' => '1',
				                            'ui_enabled' => '1'
				                            );
						$big_ok = $cceClient->set($_oid, '', $new_settings);
						$errors = array_merge($errors, $cceClient->errors());

						// Activate Shell Access:
				        $ok = $cceClient->set($_oid, 'Shell', array('enabled' => 1));
						$errors = array_merge($errors, $cceClient->errors());

						// Activate 'RootAccess':
			        	$ok = $cceClient->set($_oid, 'RootAccess', array('enabled' => '1'));
						$errors = array_merge($errors, $cceClient->errors());

						// Set the 'Sites' settings as well just for sake of completness:
				        $Sites_settings = array(
						                 	'quota' => '500000',
						                 	'user' => '1',
				                            'max' => '1'
				                            );
						$ok = $cceClient->set($_oid, 'Sites', $Sites_settings);
						$errors = array_merge($errors, $cceClient->errors());
					}
				}
				else {
					// 'Support-Account' already exists. Modify it:
					$_oid = $SAadmins[0];

					// Set the disk quota:
					$cceClient->set($_oid, 'Disk', array('quota' => '200'));
					$errors = array_merge($errors, $cceClient->errors());

					// Set the rest of the settings:
			        $new_settings = array(
					                 	'systemAdministrator' => '1',
			                            'ui_enabled' => '1',
			                            'fullName' => $Support['support_account_name'],
					                 	'sortName' => "",
			                            'capLevels' => '&adminUser&',
			                            'password' => $SA_Password
			                            );
					$big_ok = $cceClient->set($_oid, '', $new_settings);
					$errors = array_merge($errors, $cceClient->errors());

					// Activate Shell Access:
			        $ok = $cceClient->set($_oid, 'Shell', array('enabled' => 1));
					$errors = array_merge($errors, $cceClient->errors());

					// Activate 'RootAccess':
		        	$ok = $cceClient->set($_oid, 'RootAccess', array('enabled' => '1'));
					$errors = array_merge($errors, $cceClient->errors());
				}

				//
				//-- Handle SSH Key/Certs:
				//

				// Defaults:
				$SA_account_name = $Support['support_account'];
				$runas = 'root';

				// Delete existing .ssh directory:
				$ret = $serverScriptHelper->shell("/bin/ls --directory ~$SA_account_name/.ssh", $is_there, $runas, $sessionId);
				if ((preg_match('/^\/home\/\.users\/(.*)$/', $is_there)) || (preg_match('/^\/home\/\.sites\/(.*)$/', $is_there))) {
					# ~$SA_account_name/.ssh exists
					$full_path_to_dotsshdir = chop($is_there);
					$ret = $serverScriptHelper->shell("/bin/rm -Rf ~$SA_account_name/.ssh", $nfk, $runas, $sessionId);
				}

				// Array for SSH-Key Reset:
				$ssh_reset = array(
									'bits' => '2048',
									'keycreate' => '0',
									'certcreate' => '0'
									);

				// Array for SSH-Key Generation:
				$ssh_creation = array(
									'bits' => '4096',
									'keycreate' => '1',
									'certcreate' => '1'
									);

				// Reset:
				$ok = $cceClient->set($_oid, 'SSH', $ssh_reset);
				$errors = array_merge($errors, $cceClient->errors());

				// Key/Cert generation:
				//
				// NOTE: This takes a moment to finish.
				$ok = $cceClient->set($_oid, 'SSH', $ssh_creation);
				$errors = array_merge($errors, $cceClient->errors());

				// Include the PEM file:
				$action_file = $SA_account_name . '.pem';
				$ret = $serverScriptHelper->shell("/bin/cat ~$SA_account_name/.ssh/$action_file", $output, $runas, $sessionId);
			    if ($ret != 0) {
			    	# File not present.
			    }
			    else {
				    // Attach:
				    $attributes_clone['PEMcert'] = $output;
			    }

			    // Note down that a support account has been generated:
			    $cleaned_attributes['access_generate'] = '1';

				//
				//-- Handle 'Support-Account' expiry:
				//
				if ($sa_expiry_reverse[$attributes['SAExpiry']] != '0') {
					// Expire on a given date and time in the future:
					$SAExpiry = $sa_expiry_reverse[$attributes['SAExpiry']]*24*60*60+time();
					$cleaned_attributes['access_epoch'] = $SAExpiry;
					$ndt = new DateTime("@$SAExpiry");
					$reported_SAExpiry = $ndt->format('Y-m-d H:i:s');
					$attributes_clone['SAExpiry'] = $reported_SAExpiry;
				}
				else {
					// Set 'access_epoch' to '0' to mark it to never expire:
					$cleaned_attributes['access_epoch'] = '0';
					$attributes_clone['SAExpiry'] = 'Never';
				}
			}
			else {
			    // Note down that a support account is NOT part of the ticket:
			    $cleaned_attributes['access_generate'] = '0';
			}

			if (isset($attributes_clone['include_sos'])) {
				// Ticket includes SOS-Report:
				if ($attributes_clone['include_sos'] == '1') {
					unset($attributes_clone['include_sos']);
					$cleaned_attributes['sos_generate'] = time();
					$cleaned_attributes['include_sos'] = '1';
					$SOSreportUrl = 'http://' . $system['hostname'] . '.' . $system['domainname'] . ':444' . $Support['sos_external'];
					$attributes_clone['sos_report'] = $SOSreportUrl;
				}
				else {
					// Ticket does NOT include SOS-Report:
					$cleaned_attributes['include_sos'] = '0';
					$cleaned_attributes['ticket_trigger'] = time();
				}
			}

			if ($attributes_clone['ticket_num_selector'] == 'new_ticket') {
				// Prefix Ticket Subject with type of message and build number:
				$attributes_clone['ticket_subject'] = 'Ticket(' . $system['productBuild'] . '): ' . $attributes_clone['ticket_subject'];
				$cleaned_attributes['ticket_number'] = '';
			}
			else {
				// Prefix Ticket Subject with type of message and build number and append ticket ID of existing ticket:
				$attributes_clone['ticket_subject'] = 'Ticket(' . $system['productBuild'] . '): ' . $attributes_clone['ticket_subject'] . ' [#' . $attributes_clone['ticket_num_selector'] . ']';
				$cleaned_attributes['ticket_number'] = $attributes_clone['ticket_num_selector'];
			}

			// We use the raw 'ticketDescription', as GetFormAttributes() has stripped the formatting
			// turned it into a scalar. Which is not what we want to email:
			unset($attributes_clone['ticketDescription']);
			$attributes_clone['ticketDescription'] = $form_data['ticketDescription'];

			unset($attributes_clone['support_account']);

			if ($attributes_clone['allow_access'] == '1') {
				$attributes_clone['support_account'] = $Support['support_account'];
				$attributes_clone['password'] = $SA_Password;

				// Add Hostname:
				$attributes_clone['FQDN'] = $system['hostname'] . '.' . $system['domainname'];

				// Add IP-Address:
				$interfaces = $cceClient->findx('Network', array('real' => 1, 'enabled' => 1), array(), 'ascii', 'device');
				$NET = $cceClient->get($interfaces[0]);
				$attributes_clone['ipaddr'] = $NET['ipaddr'];

				// Get SSH Settings and include them:
				$SSH = $cceClient->getObject("System", array(), "SSH");
				$attributes_clone['SSH_Enabled'] = $SSH['enabled'];
				$attributes_clone['SSH_Port'] = $SSH['Port'];
				$attributes_clone['XPasswordAuthentication'] = $SSH['XPasswordAuthentication'];
				$attributes_clone['PubkeyAuthentication'] = $SSH['PubkeyAuthentication'];
				$attributes_clone['PermitRootLogin'] = $SSH['PermitRootLogin'];
			}

			//
			//-- Handle Recipient Selector:
			//
			if (isset($attributes_clone['recipient_selector'])) {
				// Had a selector for the email address:
				if ($attributes_clone['recipient_selector'] == $Support['isp_support_name']) {
					// Email the ISP:
					unset($attributes_clone['recipient_selector']);
					$attributes_clone['recipient_name'] = $Support['isp_support_name'];
					$attributes_clone['recipient_email'] = $Support['isp_support_email'];
				}
				else {
					// Email BlueOnyx Support:
					unset($attributes_clone['recipient_selector']);
					$attributes_clone['recipient_name'] = $Support['bx_support_name'];
					$attributes_clone['recipient_email'] = $Support['bx_support_email'];
				}
			}
			else {
				// ISP data hasn't been set and there was only the choice to mail BlueOnyx Support:
				$attributes_clone['recipient_name'] = $Support['bx_support_name'];
				$attributes_clone['recipient_email'] = $Support['bx_support_email'];
			}
			//
			//-- Priority/Severity:
			//

			$attributes_clone['Priority'] = $cceClient->scalar_to_string($attributes_clone['Priority']);
			$attributes_clone['Severity'] = $cceClient->scalar_to_string($attributes_clone['Severity']);

			// Assemble JSON encoded Bug-Report:
			$ticket = json_encode($attributes_clone);

			// Write the Ticket temporary file:
			if (!write_file($TicketTmpPath, $ticket)) {
			     $errors[] = '<div class="alert alert_white"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Err_writing_tempfile]]") . '</strong></div>';
			}
			else {
				$ret = $serverScriptHelper->shell("/bin/chmod 00640 $TicketTmpPath", $output, 'admserv', $sessionId);
			}

			// Add Ticket tempfile path to CODB:
			$cleaned_attributes['ticket'] = $TicketTmpPath;

	  		// Actual submit to CODB:
	  		$cceClient->setObject("System", $cleaned_attributes, "Support");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /support/ticket?sent=TRUE");
				exit;
			}
			else {
				$errors[] = '<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Err_problem_sending_ticket]]") . '</strong></div>';
			}
		}

		//
		//-- Own page logic:
		//

		if (($Support['client_name'] == "") || ($Support['client_email'] == "")) {
			$errors[] = '<div class="alert alert_white"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Err_sender_contact_details]]") . '</strong></div>';
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-support", "/support/ticket");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_support');
		$page_module = 'base_software';

		$defaultPage = 'default';

		$block =& $factory->getPagedBlock("ticket", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- defaultPage:
		//

		// Check if we're here after a submit transaction:
		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['sent'])) {
			if ($get_form_data['sent'] == 'TRUE') {
				// Report has been sent:
				$report_sent = $factory->getHTMLField("report_sent", $i18n->getHtml("[[base-support.TicketSent]]"), "r");
				$report_sent->setLabelType("nolabel");
				$block->addFormField(
				  $report_sent,
				  $factory->getLabel("report_sent"),
				  $defaultPage
				);
			}
		}
		else {

			// Show the form:

	        // Add divider:
	        $block->addFormField(
	                $factory->addBXDivider("sender", ""),
	                $factory->getLabel("sender", false),
	                $defaultPage
	                );

			$client_name = $factory->getTextField("client_name", $Support['client_name'], 'r');
			$client_name->setType("");
			$block->addFormField(
			  $client_name,
			  $factory->getLabel("client_name"),
			  $defaultPage
			);

			$client_email = $factory->getEmailAddress("client_email", $Support['client_email'], 'r');
			$block->addFormField(
			  $client_email,
			  $factory->getLabel("client_email"),
			  $defaultPage
			);

	        // Add divider:
	        $block->addFormField(
	                $factory->addBXDivider("recipient", ""),
	                $factory->getLabel("recipient", false),
	                $defaultPage
	                );

	        if (($Support['isp_support_name'] != "") && ($Support['isp_support_email'] != "")) {

				// Recipient seclector:
		        $recipient_selector_array = array(
		            'blueonyx' => $Support['bx_support_name'],
		            'isp' => $Support['isp_support_name']
		        );

		        // Add pulldown for recipient selector:
		        $recipient_selector = $factory->getMultiChoice("recipient_selector", array_values($recipient_selector_array));
		        $recipient_selector->setSelected('blueonyx', true);
		        $recipient_selector->setOptional(false);
		        $block->addFormField(
		    		$recipient_selector, 
		    		$factory->getLabel("recipient_selector"), 
		    		$defaultPage
		    	);

	        }
	        else {
				$recipient_name = $factory->getTextField("recipient_name", $Support['bx_support_name'], 'r');
				$client_name->setType("");
				$block->addFormField(
				  $recipient_name,
				  $factory->getLabel("recipient_name"),
				  $defaultPage
				);

				$recipient_email = $factory->getEmailAddress("recipient_email", $Support['bx_support_email'], 'r');
				$block->addFormField(
				  $recipient_email,
				  $factory->getLabel("recipient_email"),
				  $defaultPage
				);
			}

	        // Add divider:
	        $block->addFormField(
	                $factory->addBXDivider("ticketTitle", ""),
	                $factory->getLabel("ticket", false),
	                $defaultPage
	                );

			$ticket_subject = $factory->getTextField("ticket_subject", '', 'rw');
			$ticket_subject->setType("");
			$block->addFormField(
			  $ticket_subject,
			  $factory->getLabel("ticket_subject"),
			  $defaultPage
			);

			// Allow to append to existing ticket if there are any:
			if (!isset($existing_tickets)) {
				$existing_tickets = array();
			}
			if (is_array($existing_tickets)) {

					$opt_new_ticket = array('new_ticket' => 'new_ticket');
					$ticket_selector = array_merge($opt_new_ticket, $existing_tickets);

			        // Add pulldown for Ticket selector:
			        $ticket_num_selector = $factory->getMultiChoice("ticket_num_selector", array_values($ticket_selector));
			        $ticket_num_selector->setSelected('new_ticket', true);
			        $ticket_num_selector->setOptional(false);
			        $block->addFormField(
			    		$ticket_num_selector, 
			    		$factory->getLabel("ticket_num_selector"), 
			    		$defaultPage
			    	);
		    }

			$server_model = $factory->getTextField("server_model", $system['productName'] . ' (' . $system['productBuildString'] . ')', 'r');
			$server_model->setType("");
			$block->addFormField(
			  $server_model,
			  $factory->getLabel("server_model"),
			  $defaultPage
			);

			// Priority:
	        $block->addFormField(
                $factory->getRadio("Priority", $prio_forward_num, "rw"),
                $factory->getLabel("Priority"),
                $defaultPage
			);

			// Severity:
	        $block->addFormField(
                $factory->getRadio("Severity", $severity_forward_num, "rw"),
                $factory->getLabel("Severity"),
                $defaultPage
			);

			$ticketURL = $factory->getTextField("ticketURL", '', 'rw');
			$ticketURL->setOptional(TRUE);
			$ticketURL->setType("");
			$block->addFormField(
			  $ticketURL,
			  $factory->getLabel("ticketURL"),
			  $defaultPage
			);

			$include_sos = $factory->getBoolean("include_sos", '0', "rw");
			$block->addFormField(
			  $include_sos,
			  $factory->getLabel("include_sos"),
			  $defaultPage
			);

			//
			//-- Enable alter-admin account:
			//

			// This is a bit of a cheat: Within a getMultiChoice() we can't use read-only formfields.
			// So we do a getHTMLField() instead:
			$support_account = $factory->getHTMLField("support_account", $Support['support_account'], "r");

			// Prepare getMultiChoice():
	        $allow_accessToggle =& $factory->getMultiChoice('allow_access');
	        $enable =& $factory->getOption('enable', '0');
	        $enable->setLabel($factory->getLabel('enable', false));

	        // Add FormFields to it:
	        $enable->addFormField($support_account, $factory->getLabel("support_account"), $defaultPage);

	        // Add pulldown for 'alter-admin' expiry:
	        $SAExpiry = $factory->getMultiChoice("SAExpiry", array_values($sa_expiry_forward));
	        $SAExpiry->setSelected($sa_expiry_forward['7'], true);
	        $SAExpiry->setOptional(false);
	        $enable->addFormField(
	    		$SAExpiry, 
	    		$factory->getLabel("SAExpiry"), 
	    		$defaultPage);

	        // Add it all:
	        $allow_accessToggle->addOption($enable);

	        // Out with the constructed getMultiChoice():
	    	$block->addFormField(
	    		    $allow_accessToggle,
	    		    $factory->getLabel('allow_access'),
	    		    $defaultPage
	            );

			$ticketDescription = $factory->getTextList("ticketDescription", '', 'rw');
			$ticketDescription->setOptional(FALSE);
			$ticketDescription->setType("");
			$block->addFormField(
			  $ticketDescription,
			  $factory->getLabel("ticketDescription"),
			  $defaultPage
			);

			//
			//--- Add the buttons
			//

			// Disable the Save-Button if the Support-Settings haven't been configured yet:
			$save_button = $factory->getSaveButton($BxPage->getSubmitAction());
			if (($Support['client_name'] == "") || ($Support['client_email'] == "")) {
				$save_button->setDisabled(TRUE);
			}

			$block->addButton($save_button);
			$block->addButton($factory->getCancelButton("/support/ticket"));
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		// Out with the page:
	    $BxPage->render($page_module, $page_body);

	}		
}
/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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