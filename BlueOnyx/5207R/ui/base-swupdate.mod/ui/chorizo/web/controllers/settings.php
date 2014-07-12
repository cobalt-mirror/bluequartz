<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Settings extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /swupdate/settings.
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
		$i18n = new I18n("base-swupdate", $user['localePreference']);
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
		$swUpdate = $cceClient->getObject("System", array(), "SWUpdate");

		// We use the first server object as the default for properties like proxies
		// because they have the same value anyway. These properties should actually be
		// in System.SWUpdate
		$oids = $cceClient->findNSorted("SWUpdateServer", "orderPreference");
		$servers = array();

		for($i = 0; $i < count($oids); $i++) {
			$servers[] = $cceClient->get($oids[$i]);
		}

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

			$newScheduleMap = array("never" => "Never", "hourly" => "Hourly", "daily" => "Daily", "weekly" => "Weekly", "monthly" => "Monthly");
			$attributes['updateInterval'] = $newScheduleMap[$attributes['updateInterval']];

			$notificationLightField = $attributes['notificationLightField'];
			unset($attributes['notificationLightField']);

		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.

	  		// Actual submit to CODB:
	  		$cceClient->setObject("System", $attributes, "SWUpdate");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// Remove all the existing servers first
			$cceClient->destroyObjects("SWUpdateServer");

			// Add back all the specified ones
			$notifyModeMap = array("all" => "AllNew", "updates" => "UpdatesOnly");
			$servers = stringToArray($attributes['servers']);
			if (!count($servers)) { $servers = array(""); }
			for($i = 0; $i < count($servers); $i++) {
			  $cceClient->create("SWUpdateServer", array("location" => $servers[$i],
			    "notificationMode" => $notifyModeMap[$notificationLightField], "orderPreference" => $i+1));
			  $errors = array_merge($errors, $cceClient->errors());
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /swupdate/settings");
				exit;
			}
			else {
				$swUpdate = $attributes;
			}
		}

		//
		//-- Own page logic:
		//

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/swupdate/settings");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_software');
		$BxPage->setVerticalMenuChild('base_softwareSettings');
		$page_module = 'base_software';

		$defaultPage = "basic";

		$block =& $factory->getPagedBlock("softwareInstallSettings", array($defaultPage, 'advanced'));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Basic:
		//

		$scheduleMap = array("Never" => "never", "Hourly" => "hourly", "Daily" => "daily", "Weekly" => "weekly", "Monthly" => "monthly");
		$scheduleField = $factory->getMultiChoice("updateInterval", array("hourly", "daily", "weekly", "monthly", "never"), array($scheduleMap));
		$scheduleField->setSelected($scheduleMap[$swUpdate['updateInterval']], true);
		$block->addFormField(
		  $scheduleField,
		  $factory->getLabel("scheduleField"),
		  "basic"
		);

		$notifyMap = array("AllNew" => "all", "UpdatesOnly" => "updates");
		$notificationLightField = $factory->getMultiChoice("notificationLightField", array("all", "updates"), array($notifyMap));
		$notificationLightField->setSelected($notifyMap[$servers[0]["notificationMode"]], true);
		$block->addFormField(
		  $notificationLightField,
		  $factory->getLabel("notificationLightField"),
		  "basic"
		);

		// Use ActiveMonitor's email contact list if possible
		$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
		if( ! $am_obj["alertEmailList"] ) {
		  $email = $factory->getEmailAddressList("emailField", $swUpdate["updateEmailNotification"]);
		  $email->setOptional(true);
		  $block->addFormField(
		    $email,
		    $factory->getLabel("emailField"),
		    "basic"
		  );
		}

		//
		//--- Advanced
		//

		$locations = array();
		for($i = 0; $i < count($servers); $i++) {
			$locations[] = $servers[$i]["location"];
		}
		$updateServer = $factory->getUrlList("servers", arrayToString($locations));
		$updateServer->setOptional(true);
		$block->addFormField(
		  $updateServer,
		  $factory->getLabel("serverField"),
		  "advanced"
		);

		$httpProxy = $factory->getUrl("httpProxy", $swUpdate["httpProxy"], "", "", "rw");
		$httpProxy->setOptional(true);
		$httpProxy->setType("");
		$block->addFormField(
		  $httpProxy,
		  $factory->getLabel("httpProxyField"),
		  "advanced"
		);

		$ftpProxy = $factory->getUrl("ftpProxy", $swUpdate["ftpProxy"]);
		$ftpProxy->setOptional(true);
		$ftpProxy->setType("");
		$block->addFormField(
		  $ftpProxy,
		  $factory->getLabel("ftpProxyField"),
		  "advanced"
		);

		/*
		$typeMap = array("All" => "all", "Updates" => "updates");
		$block->addFormField(
		  $factory->getMultiChoice("checkSetField", array("all", "updates"), array($typeMap[$swUpdate["updateType"]])),
		  $factory->getLabel("checkSetField")
		);
		*/

		/*
		$block->addFormField(
		  $factory->getBoolean("autoField", $servers[0]["autoUpdate"]),
		  $factory->getLabel("autoField"),
		  "advanced"
		);
		*/

		$block->addFormField(
		  $factory->getBoolean("requireSignature", $swUpdate["requireSignature"]),
		  $factory->getLabel("requireSignatureField"),
		  "advanced"
		);


		//
		//--- Add the buttons
		//

		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/swupdate/settings"));

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