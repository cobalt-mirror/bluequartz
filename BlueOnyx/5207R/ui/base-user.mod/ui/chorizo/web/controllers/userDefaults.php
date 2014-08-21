<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserDefaults extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /user/userDefaults.
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
		$i18n = new I18n("base-user", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// Required array setup:
		$errors = array();
		$extra_headers = array();

		// Get URL params:
		$get_form_data = $CI->input->get(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['group'])) {
			// We have a group URL string:
			$group = $get_form_data['group'];
		}
		else {
			// Don't play games with us!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#1");
		}

		//
		//-- Access Rights Check for Vsite level pages:
		// 
		// 1.) Checks if the Group/Vsite exists.
		// 2.) Checks if the user is systemAdministrator
		// 3.) Checks if the user is Reseller of the given Group/Vsite
		// 4.) Checks if the iser is siteAdmin of the given Group/Vsite
		// Returns Forbidden403 if *none* of that is the case.
		if (!$Capabilities->getGroupAdmin($group)) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
		}

		//
		//-- Get Vsite data
		//
		if ($group) {
			$userDefaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");
			if (count($cceClient->find("Vsite", array("name" => $group))) == "0") {
				// Don't play games with us!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#3");
			}
			else {
				list($vsite) = $cceClient->find("Vsite", array("name" => $group));
				$vsiteObj = $cceClient->get($vsite);
	    		list($userServices) = $cceClient->find("UserServices", array("site" => $group));
	    	}
		}
		else {
			$userDefaults = $cceClient->getObject("System", array(), "UserDefaults");
		}

		// Second stage of capability check. More thorough here:
		// Only adminUser and siteAdmin should be here
		// NOTE: Needs testing if this is restructive enough (!!!!!!!!!!!!!!!!!!!!!!!!!!)
		if ((!$Capabilities->getAllowed('adminUser')) && 
			(!$Capabilities->getAllowed('siteAdmin')) && 
			(!$Capabilities->getAllowed('manageSite')) && 
			(($user['site'] != $serverScriptHelper->loginUser['site']) && $Capabilities->getAllowed('siteAdmin')) &&
			(($vsiteObj['createdUser'] != $loginName) && $Capabilities->getAllowed('manageSite'))
			) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#4");
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
		// Setting up error messages:
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
			$attribs = array();
			if ($attributes['maxDiskSpaceField'] < 0) {
				$attributes['maxDiskSpaceField'] = "20M";
			}
			$maxDiskSpaceField = (unsimplify_number($attributes['maxDiskSpaceField'], "K")/1000);
			$attribs = array_merge($attribs, array("quota" => $maxDiskSpaceField, "emailDisabled" => $attributes['emailDisabled']));
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
			if (isset($group) && $group != "") {
				$cceClient->setObject("Vsite", $attribs, "UserDefaults", array("name" => $group));
				$site = $group;
			}
			else {
				$cceClient->setObjectForce("System", $attribs, "UserDefaults");
			}
			$errors = array_merge($errors, $cceClient->errors());

			// Set autofeatures defaults
			list($userservices) = $cceClient->find("UserServices", array("site" => $group));
			$autoFeatures = new AutoFeatures($serverScriptHelper, $attributes);
			$af_errors = $autoFeatures->handle("defaults.User", array("CCE_SERVICES_OID" => $userservices), $attributes);
			$errors = array_merge($errors, $af_errors);

			// No errors during submit? Reload page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				$redirect_URL = "/user/userDefaults?group=$group";
				header("location: $redirect_URL");
				exit;
			}
		}

	    //-- Generate page:

		// Get AutoFeatures:
		$autoFeatures = new AutoFeatures($serverScriptHelper);

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/user/userDefaults?group=$group");
		$BxPage = $factory->getPage();
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteadmin');
		$BxPage->setVerticalMenuChild('base_userList');
		$page_module = 'base_sitemanage';

		$basic_tab = 'basicDefaults';
		$service_tab = 'serviceDefaults';

		$block =& $factory->getPagedBlock("userDefaults", array($basic_tab));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($basic_tab);

		// Get defaults
		if (isset($group) && $group != "") {
			$defaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");
		}
		else {
			$defaults = $cceClient->getObject("System", array(), "UserDefaults");
		}

		// Load site quota
		if ($group) {
			list($vsite_oid) = $cceClient->find('Vsite', array("name" => $group));
			$disk = $cceClient->get($vsite_oid, 'Disk');
			$max_quota = $disk['quota'];
		}

		// Prepare quota definition:
		$site_quota = ($max_quota == -1 ? 499999999 : $max_quota)*1000*1000;
		if ($defaults["quota"] < 0) {
			$defaults["quota"] = "1";
		}
		$default_quota = $defaults["quota"]*1000*1000;
		$quota = $factory->getInteger("maxDiskSpaceField", simplify_number($default_quota, "K", "0"), 1, simplify_number($site_quota, "K", "0"));
		$quota->setOptional('silent');
	    $quota->showBounds('dezi');
	    $quota->setType('memdisk');

		if ($max_quota && $max_quota != -1) {
			$quota->showBounds(1);
		}

		$block->addFormField(
				$quota,
				$factory->getLabel("maxDiskSpaceFieldDefault"),
				$basic_tab
			);

		// Is email disabled?
		$block->addFormField(
				$factory->getBoolean("emailDisabled", $defaults["emailDisabled"]),
				$factory->getLabel("emailDisabled"),
				$basic_tab
			);

		// Handle Auto-Features:
		if (isset($group) && $group != "") {
			list($userServices) = $cceClient->find("UserServices", array("site" => $group));
			list($vsite) = $cceClient->find("Vsite", array("name" => $group));
			if (!$autoFeatures->display($block, "defaults.User", 
				array(
					"CCE_SERVICES_OID" => $userServices, 
					"VSITE_OID" => $vsite,
					'PAGED_BLOCK_DEFAULT_PAGE' => $basic_tab
					))) {
						error_log(__FILE__ . '.' . __LINE__ . ": autoFeatures->display failed");
			}
		}
		else {
			list($userServices) = $cceClient->find("UserServices");
			$autoFeatures->display($block, "defaults.User", 
			    array(
			        "CCE_SERVICES_OID" => $userServices,
			        'PAGED_BLOCK_DEFAULT_PAGE' => $basic_tab
			        ));
		}

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/user/userList?group=$group"));

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