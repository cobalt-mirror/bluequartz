<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AmSettings extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /am/amSettings.
	 *
	 * This is based on some pretty good code from Phil Ploquin & Tim Hockin.
	 *
	 */

	public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-am", $CI->BX_SESSION['loginUser']['localePreference']); 
        $system = $CI->getSystem();

		// -- Actual page logic start:

		// Not 'serverActiveMonitor'? Bye, bye!
		if (!$CI->serverScriptHelper->getAllowed('serverActiveMonitor')) {
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Get CODB-Object of interest: 
		//

		$CODBDATA = $CI->cceClient->getObject("ActiveMonitor");

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

			// We have no errors. We submit to CODB.

	  		// Actual submit to CODB:
			$CI->cceClient->setObject("ActiveMonitor", array("enabled" => $attributes['enableAMField'], "alertEmailList" => $attributes['alertEmailList']), "");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $CI->cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			//
			//--- Handle the updating of all "ActiveMonitor" items:
			//

			$amobj = $CI->cceClient->getObject("ActiveMonitor");
			if (isset($attributes['itemsToMonitor'])) {
				$items = stringToArray($attributes['itemsToMonitor']);
			}
			else {
				$items = array();
			}
			$names = $CI->cceClient->names($amobj["OID"]);

			// for each namespace on ActiveMonitor
			for ($i=0; $i < count($names); ++$i) {
				$namespace = $CI->cceClient->get($amobj["OID"], $names[$i]);
				if (isset($namespace["hideUI"])) {
					if ($namespace["hideUI"]) {
						continue;
					}
				}
				$val = 0;
				// try see if the nameTag for this namespace is in the list
				for ($j=0; $j < count($items); ++$j) {
					if ($namespace["NAMESPACE"] == $items[$j]) {
						$val = 1;
						break;
					}
				}
				/* only set it if it is a boolean change */
				if (($val && !$namespace["monitor"]) || (!$val && $namespace["monitor"])) {
					/*
					// If we are changing an "aggregate" service, then
					// also enable/disable the typeData fields too.
					// (ie. if Email, then do SMTP, POP3, IMAP too)
					*/
					if ($namespace["type"] == "aggregate") {
						$amServices = preg_split("/ /",$namespace["typeData"]);
						foreach($amServices as $agServ) {
							$CI->cceClient->set($amobj["OID"], $agServ, array("monitor" => $val));
							$errors = array_merge($errors, $CI->cceClient->errors());
						}
					}

					$CI->cceClient->set($amobj["OID"], $names[$i], array("monitor" => $val));
					$errors = array_merge($errors, $CI->cceClient->errors());
				}
			}

			//--- Done with AM-Item-Handling.

			// We fill $CODBDATA with our form results before presenting them again:
			$CODBDATA = array('alertEmailList' => $attributes['alertEmailList'], 'OID' => $amobj["OID"], 'enabled' => $attributes['enableAMField']);
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-am", "/am/amSettings");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_monitor');
		$BxPage->setVerticalMenuChild('base_amSettings');
		$page_module = 'base_sysmanage';

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("amSettings", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// enabled checkbox
		$block->addFormField(
			$factory->getBoolean("enableAMField", $CODBDATA["enabled"]),
			$factory->getLabel("enableAMField"), 
			$defaultPage
			);

		//		
		// Alert Notification List:
		//
		// Work around for getEmailAddressList(): These days it only takes "emailAddresses"
		// and just a username like "admin" is no longer a valid email address. 
		$fixed_addies = array();
		$alertEmailList = $CI->cceClient->scalar_to_array($CODBDATA["alertEmailList"]);
		foreach ($alertEmailList as $key => $value) {
			if (!preg_match('/\@/', $value)) {
				$fixed_addies[] = $value . '@' . $system['hostname'] . '.' . $system['domainname'];
			}
			else {
				$fixed_addies[] = $value;
			}
		}
		$CODBDATA["alertEmailList"] = $CI->cceClient->array_to_scalar($fixed_addies);

		$alerts = $factory->getEmailAddressList("alertEmailList", $CODBDATA["alertEmailList"]);
		$alerts->setOptional(true);

		$block->addFormField(
			$alerts,
			$factory->getLabel("alertEmailList"), 
			$defaultPage
			);

		$selected = array();
		$selectedVals = array();
		$notSelected = array();
		$notSelectedVals = array();

		$names = $CI->cceClient->names($CODBDATA["OID"]);
		$namespaces = array();

		for ($i=0; $i < count($names); ++$i) {
			$nspace = $CI->cceClient->get($CODBDATA["OID"], $names[$i]);
			$name = $i18n->get($nspace["nameTag"]);
			$namespaces[$name] = $nspace;
		}

		// sort by i18n'ed strings
		ksort($namespaces);
	
		$all_monitor_items = array();
		$all_monitor_itemsVals = array();
		while (list($name, $nspace) = each($namespaces)) {
			if (isset($nspace["hideUI"])) {
				if ($nspace["hideUI"] == "0") {
					$all_monitor_items[] = $name;
					$all_monitor_itemsVals[] = $nspace["NAMESPACE"];
				}
			}
			else {
				$all_monitor_items[] = $name;
				$all_monitor_itemsVals[] = $nspace["NAMESPACE"];
			}

			if ($nspace["monitor"]) {
				if (isset($nspace["hideUI"])) {
					if ($nspace["hideUI"] == "0") {
						$selected[] = $name;
						$selectedVals[] = $nspace["NAMESPACE"];
					}
				}
				else {
					$selected[] = $name;
					$selectedVals[] = $nspace["NAMESPACE"];			
				}
			}
			else {
				$notSelected[] = $name;
				$notSelectedVals[] = $nspace["NAMESPACE"];
			}
		}

		$select_caps =& $factory->getSetSelector('itemsToMonitor',
		                    $CI->cceClient->array_to_scalar($selected), 
		                    $CI->cceClient->array_to_scalar($all_monitor_items),
		                    'selected', 'notSelected', 'rw',
		                    $CI->cceClient->array_to_scalar($selectedVals),
		                    $CI->cceClient->array_to_scalar($all_monitor_itemsVals));
	   
		$select_caps->setOptional(true);

		$block->addFormField($select_caps, 
		            $factory->getLabel('itemsToMonitor'),
		            $defaultPage
		            );


		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/am/amSettings"));

		$page_body[] = $block->toHtml();

		// Out with the page:
	    $BxPage->render($page_module, $page_body);

	}		
}

/*
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
nuclear facility.

*/
?>