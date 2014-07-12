<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bugreport extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /support/bugreport.
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

		// Get settings
		$Support = $cceClient->getObject("System", array(), "Support");

		// Tempfile for the JSON encoded bugreport:
		$BugreportTmpPath = '/var/cache/admserv/' . $loginName . '_bugreport.tmp';

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

		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			$cleaned_attributes = array();

			// Clone $attributes:
			$attributes_clone = $attributes;
			if (isset($attributes_clone['include_sos'])) {
				// Bugreport includes SOS-Report:
				if ($attributes_clone['include_sos'] == '1') {
					unset($attributes_clone['include_sos']);
					$cleaned_attributes['sos_generate'] = time();
					$cleaned_attributes['include_sos'] = '1';
					$SOSreportUrl = 'http://' . $system['hostname'] . '.' . $system['domainname'] . ':444' . $Support['sos_external'];
					$attributes_clone['sos_report'] = $SOSreportUrl;
				}
				else {
					// Bugreport does NOT include SOS-Report:
					$cleaned_attributes['include_sos'] = '0';
					$cleaned_attributes['bugreport_trigger'] = time();
				}
			}

			// Prefix Bugreport Subject with type of message and build number:
			$attributes_clone['bugreport_subject'] = 'Bug(' . $system['productBuild'] . '): ' . $attributes_clone['bugreport_subject'];

			// We use the raw 'bugDescription', as GetFormAttributes() has stripped the formatting
			// turned it into a scalar. Which is not what we want to email:
			unset($attributes_clone['bugDescription']);
			$attributes_clone['bugDescription'] = $form_data['bugDescription'];

			//
			//-- Priority/Severity:
			//

			$attributes_clone['Priority'] = $cceClient->scalar_to_string($attributes_clone['Priority']);
			$attributes_clone['Severity'] = $cceClient->scalar_to_string($attributes_clone['Severity']);

			// Assemble JSON encoded Bug-Report:
			$bugreport = json_encode($attributes_clone);

			// Write the Bugreport temporary file:
			if (!write_file($BugreportTmpPath, $bugreport)) {
			     $errors[] = '<div class="alert alert_white"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Err_writing_tempfile]]") . '</strong></div>';
			}
			else {
				$ret = $serverScriptHelper->shell("/bin/chmod 00640 $BugreportTmpPath", $output, 'admserv', $sessionId);
			}

			// Add bugreport tempfile path to CODB:
			$cleaned_attributes['bugreport'] = $BugreportTmpPath;

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
				header("Location: /support/bugreport?sent=TRUE");
				exit;
			}
			else {
				$errors[] = '<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->getHtml("[[base-support.Err_problem_sending_bugreport]]") . '</strong></div>';
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
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-support", "/support/bugreport");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_support');
		$page_module = 'base_software';

		$defaultPage = 'default';

		$block =& $factory->getPagedBlock("bugreportTitle", array($defaultPage));

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
				$report_sent = $factory->getHTMLField("report_sent", $i18n->getHtml("[[base-support.BugreportSent]]"), "r");
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

			$recipient_name = $factory->getTextField("recipient_name", $Support['bx_bugreport_name'], 'r');
			$client_name->setType("");
			$block->addFormField(
			  $recipient_name,
			  $factory->getLabel("recipient_name"),
			  $defaultPage
			);

			$recipient_email = $factory->getEmailAddress("recipient_email", $Support['bx_bugreport_email'], 'r');
			$block->addFormField(
			  $recipient_email,
			  $factory->getLabel("recipient_email"),
			  $defaultPage
			);

	        // Add divider:
	        $block->addFormField(
	                $factory->addBXDivider("bugreportTitle", ""),
	                $factory->getLabel("bugreportTitle", false),
	                $defaultPage
	                );

			$bugreport_subject = $factory->getTextField("bugreport_subject", '', 'rw');
			$bugreport_subject->setType("");
			$block->addFormField(
			  $bugreport_subject,
			  $factory->getLabel("bugreport_subject"),
			  $defaultPage
			);

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

			$bugURL = $factory->getTextField("bugURL", '', 'rw');
			$bugURL->setOptional(TRUE);
			$bugURL->setType("");
			$block->addFormField(
			  $bugURL,
			  $factory->getLabel("bugURL"),
			  $defaultPage
			);

			$include_sos = $factory->getBoolean("include_sos", '0', "rw");
			$block->addFormField(
			  $include_sos,
			  $factory->getLabel("include_sos"),
			  $defaultPage
			);

			$bugDescription = $factory->getTextList("bugDescription", '', 'rw');
			$bugDescription->setOptional(FALSE);
			$bugDescription->setType("");
			$block->addFormField(
			  $bugDescription,
			  $factory->getLabel("bugDescription"),
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
			$block->addButton($factory->getCancelButton("/support/bugreport"));
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