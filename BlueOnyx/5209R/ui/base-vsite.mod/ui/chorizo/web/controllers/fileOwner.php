<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class fileOwner extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /vsite/fileOwner.
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
		$i18n = new I18n("base-vsite", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Get URL strings:
		$get_form_data = $CI->input->get(NULL, TRUE);

		//
		//-- Validate GET data:
		//

		if (isset($get_form_data['group'])) {
			// We have a delete transaction:
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
		//-- Prepare data:
		//

		// Get data for the Vsite:
		$vsite = $cceClient->getObject('Vsite', array('name' => $group));

		// Get the PHP settings for this Vsite:
		$vsite_php = $cceClient->getObject('Vsite', array('name' => $group), "PHP");

		// Get PHPVsite for this Vsite:
		$systemObj = $cceClient->getObject('Vsite', array('name' => $group), "PHPVsite");

		// Find out which PHP version we use:
		$system_php = $cceClient->getObject('PHP');
		$platform = $system_php["PHP_version"];

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

			// Update CODB:
			$cceClient->set($vsite['OID'], 'PHP', array("prefered_siteAdmin" => $attributes['prefered_siteAdmin']));
			$errors = array_merge($errors, $cceClient->errors());

			// No errors during submit? Reload page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				$redirect_URL = "/vsite/fileOwner?group=$group";
				header("location: $redirect_URL");
				exit;
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/fileOwner?group=$group");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteservices');
		$BxPage->setVerticalMenuChild('base_vsite_fileOwner');
		$page_module = 'base_sitemanage';

		$defaultPage = "defaultPage";
	    $block =& $factory->getPagedBlock("fileOwner_head", array($defaultPage));
	    $block->setLabel($factory->getLabel('fileOwner_head', false, array('vsite' => $vsite['fqdn'])));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Determine current user's access rights to view or edit information
		// here.  Only 'manageSite' can modify things on this page.  Site admins
		// can view it for informational purposes.
		if ($Capabilities->getAllowed('manageSite')) {
		    $is_site_admin = TRUE;
		    $access = 'rw';
		}
		elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
		    $access = 'rw';
		    $is_site_admin = FALSE;
		}
		else {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
		}

		// Get list of all siteAdmin's for this site:
		$my_siteAdmins_list = $cceClient->find('User', array('site' => $group, 'capLevels' => 'siteAdmin', 'enabled' => '1'));

		// Build an array of siteAdmin names, but start sane:
		if ($Capabilities->getAllowed('adminUser')) {
		    // admin users may chown to 'nobody' and 'apache':
		    $my_siteAdmins = array("nobody", "apache");
		}
		else {
		    // siteAdmin users are not allowed to chown to 'apache' for safety reasons:
		    $my_siteAdmins = array("nobody");
		}

		// Fetch siteAdmin names and store them in array $my_siteAdmins:
		foreach ($my_siteAdmins_list as $siteAdmin_Obj) {
		    $user_siteAdmin = $cceClient->get($siteAdmin_Obj);
		    array_push($my_siteAdmins, $user_siteAdmin{'name'});
		}

		// If no prefered_siteAdmin is set, set the default to 'nobody':
		if ($vsite_php['prefered_siteAdmin'] == "") {
		    $current_prefered_siteAdmin = "nobody";
		}
		else {
		    $current_prefered_siteAdmin = $vsite_php['prefered_siteAdmin'];
		}

		// Build the MultiChoice selector:
		$prefered_siteAdmin_select = $factory->getMultiChoice("prefered_siteAdmin",array_values($my_siteAdmins));
		$prefered_siteAdmin_select->setSelected($current_prefered_siteAdmin, true);
		$block->addFormField($prefered_siteAdmin_select,$factory->getLabel("prefered_siteAdmin"), $defaultPage);

		// Add the buttons for those who can edit this page:
		if ($access == 'rw') {
			$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
			$block->addButton($factory->getCancelButton("/vsite/fileOwner?group=$group"));
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