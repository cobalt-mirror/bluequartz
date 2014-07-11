<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Routes extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /network/routes.
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
		//--- Get CODB-Object of interest loaded: 
		//

		$routes = $cceClient->find("Route");

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

		$get_form_data = $CI->input->get(NULL, TRUE);

		// Check if we have everything:
		if ((isset($get_form_data['pid'])) && ($get_form_data['pid'] != "")) { 

	  		// Actual submit to CODB:
//			$cceClient->setObject("SOL_Console", $user_kill_action);		

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /network/routes");
				exit;
			}
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		//
		//-- Page Logic:
		//

		$iam = '/network/routes';

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/network/routes");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_serverconfig');
		$BxPage->setVerticalMenuChild('base_ethernet');
		$page_module = 'base_sysmanage';

		$defaultPage = "basic";

		$block =& $factory->getPagedBlock("routeList-list-title", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
//		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

    	// Add-Button:
		$addRoute = "/network/routeModify";
		$addRouteButton = $factory->getAddButton($addRoute, '[[base-network.createRoute]]', "DEMO-OVERRIDE");
		$buttonContainer = $factory->getButtonContainer("aliasSettings", $addRouteButton);
		$block->addFormField(
			$buttonContainer,
			$factory->getLabel("createRoute"),
			$defaultPage
		);

		//
		//--- Basic Tab
		//

  		$ScrollList = $factory->getScrollList("route_form_device", array("route-target", "route-netmask", "route-gateway", "route-device", "routeList_action_header"), array());
	    $ScrollList->setAlignments(array("center", "center", "center", "center", "center"));
	    $ScrollList->setDefaultSortedIndex('0');
	    $ScrollList->setSortOrder('ascending');
	    $ScrollList->setSortDisabled(array('4'));
	    $ScrollList->setPaginateDisabled(FALSE);
	    $ScrollList->setSearchDisabled(FALSE);
	    $ScrollList->setSelectorDisabled(FALSE);
	    $ScrollList->enableAutoWidth(TRUE);
	    $ScrollList->setInfoDisabled(FALSE);
	    $ScrollList->setColumnWidths(array("154", "154", "154", "154", "120")); // Max: 739px

		// Populate ScrollList with the data:
		for ($i = 0; $i < count($routes); $i++) {
			$r = $cceClient->get($routes[$i]);

	    	$modButt = $factory->getModifyButton("/network/routeModify?ACTION=M&_oid=$routes[$i]");
	    	$modButt->setImageOnly(TRUE);
	    	$delButt = $factory->getRemoveButton("/network/routeModify?ACTION=D&_oid=$routes[$i]");
	    	$delButt->setImageOnly(TRUE);


	    	$ScrollList->addEntry(
	    			      array(
	    				    $r["target"],
	    				   	$r["netmask"], 
	    				    ($r["gateway"] == "0.0.0.0" ? "&nbsp;" : $r["gateway"]),
	    				    ($r["device"] ? $r["device"] : "&nbsp;"),
	    				    $factory->getCompositeFormField(
	    								    array(
	    									  $modButt,
	    									  $delButt
	    									  )
	    								    )
	    				    ));
		}

		// Commit-Integer: We need at least one form field to be able to submit data.
		// So we use this hidden one:
		$block->addFormField(
			$factory->getTextField('commit', time(), ''),
			$factory->getLabel("commit"), 
			$defaultPage
		);	

		// Show the ScrollList of Logins:
		$block->addFormField(
			$factory->getRawHTML("routeList", $ScrollList->toHtml()),
			$factory->getLabel("routeList"),
			$defaultPage
		);

		// Add the buttons
		$block->addButton($factory->getCancelButton("/network/ethernet"));

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