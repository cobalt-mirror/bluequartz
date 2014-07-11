<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PoolingModify extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /network/routeModify.
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
		$i18n = new I18n("base-network", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverNetwork'? Bye, bye!
		if (!$Capabilities->getAllowed('serverNetwork')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
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
		$required_keys = array("");

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
	    if (($CI->form_validation->run() == FALSE) && (!isset($get_form_data['_TARGET']))) {

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

		// Get TARGET of Modification request:
		$action = "";
		$get_form_data = $CI->input->get(NULL, TRUE);
		if(isset($get_form_data['ACTION'])) {
			if ($get_form_data['ACTION'] == "M") {
				$action = "modify";
				if ($CI->input->post(NULL, TRUE)) {
					// Security Check:
					$oid = $get_form_data['_oid'];
					$oidData = $cceClient->get($get_form_data['_oid']);
					if ($oidData['CLASS'] != "IPPoolingRange") {
						// These are not the droids we are looking for!
						// Nice people say goodbye, or CCEd waits forever:
						$cceClient->bye();
						$serverScriptHelper->destructor();
						Log403Error("/gui/Forbidden403");
					}
					// construct object:
					$obj = array(
						"min" => $attributes["range_min"],
						"max" => $attributes["range_max"],
						"admin" => $attributes["admin"],
						"creation_time" => time());

					// Set Object:
					$ok = $cceClient->set($oid, "", $obj);
				}
			}
			if ($get_form_data['ACTION'] == "D") {
				// Security Check:
				$oid = $get_form_data['_oid'];
				$oidData = $cceClient->get($get_form_data['_oid']);
				if ($oidData['CLASS'] != "IPPoolingRange") {
					// These are not the droids we are looking for!
					// Nice people say goodbye, or CCEd waits forever:
					$cceClient->bye();
					$serverScriptHelper->destructor();
					Log403Error("/gui/Forbidden403");
				}
				if (!is_file("/etc/DEMO")) {
					$ok = $cceClient->destroy($oid);
	    		}
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /network/pooling");
				exit;
			}
		}
		else {
			$action = "create";
				if ($CI->input->post(NULL, TRUE)) {
					// construct object:
					$obj = array(
						"min" => $attributes["range_min"],
						"max" => $attributes["range_max"],
						"admin" => $attributes["admin"],
						"creation_time" => time());

					// Set Object:
					$ok = $cceClient->create("IPPoolingRange", $obj);
				}			
		}

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// We have no errors and have POST data, we submitted to CODB without errors? Redirect.
			if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE)) && (count($CCEerrors) == 0)) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /network/pooling");
				exit;
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$post_URL = "/network/poolingModify";
		if ((isset($get_form_data['ACTION'])) && (isset($get_form_data['_oid']))) {
			if ($get_form_data['ACTION'] == "M") {
				$post_URL = "/network/poolingModify?ACTION=M&_oid=" . $get_form_data['_oid'];
			}
			if ($get_form_data['ACTION'] == "D") {
				$post_URL = "/network/poolingModify?ACTION=D&_oid=" . $get_form_data['_oid'];
			}			
		}
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", $post_URL);
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_serverconfig');
		$BxPage->setVerticalMenuChild('base_sitepooling');
		$page_module = 'base_sysmanage';

		$defaultPage = "basicSettingsTab";

		if (isset($get_form_data['_oid'])) {
			$add = false;
			$pbTitle = 'sitepooling';
			$oid = $get_form_data['_oid'];
			$current = $cceClient->get($oid);
			$min_string = "range_min";
  			$max_string = "range_max";
  		}
		else {
			$add = true;
			$pbTitle = 'sitepooling';
			$current['min'] = "";
			$current['max'] = "";
			$min_string = "range_min";
  			$max_string = "range_max";
		}

		$block =& $factory->getPagedBlock($pbTitle, array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		$minfield = $factory->getIpAddress($min_string, $current['min']);
		$block->addFormField($minfield,$factory->getLabel('min'), $defaultPage);

		$maxfield = $factory->getIpAddress($max_string, $current['max']);
		$block->addFormField($maxfield,$factory->getLabel('max'), $defaultPage);

		// Get all adminUsers:
		$oids = $cceClient->findx('User', 
		                                array('capLevels' => 'adminUser'), 
		                                array(), 
		                                'ascii', 
		                                'name'); 
		 
		foreach ($oids as $oid) { 
			$admins[$oid] = $cceClient->get($oid); 
		} 

		// Add 'admin' as well:
		$oids = $cceClient->findx('User', 
		                                array('name' => 'admin'), 
		                                array(), 
		                                'ascii', 
		                                'name');
		foreach ($oids as $oid) { 
			$admins[$oid] = $cceClient->get($oid); 
		}

		foreach ($admins as $adm) {
			$adminNames[] = $adm["name"];
		}

		if (isset($current['admin'])) {
			if ($current['admin'] == "") {
				$current['admin'] = "admin";
			}
		}
		else {
			$current['admin'] = "admin";
		}

    	$adminArray = $cceClient->scalar_to_array($current['admin']); 

		$select_caps =& $factory->getSetSelector(
								'admin',
			                    $cceClient->array_to_scalar($adminArray),
			                    $cceClient->array_to_scalar($adminNames), 
			                    '', '',
			                    'rw', 
			                    $cceClient->array_to_scalar($adminArray),
			                    $cceClient->array_to_scalar($adminNames)
		                    );
		   
		$select_caps->setOptional(true);

		$block->addFormField($select_caps, 
		            $factory->getLabel('adminPowers'),
		            $defaultPage
		            );

		// Add the buttons
		$block->addButton($factory->getSaveButton("/network/poolingModify"));
		$block->addButton($factory->getCancelButton("/network/pooling"));

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