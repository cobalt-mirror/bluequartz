<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tomcatmanager extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /java/tomcatmanager.
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
		$i18n = new I18n("base-java", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not adminUser? Bye, bye!
		if (!$Capabilities->getAllowed('adminUser')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		// Set trigger in CCE to update CODB with status info about Tomcat:
		$java_status = array(
		    'TomcatUITrigger' => time()
		);
		$cceClient->setObject("System", $java_status, "JavaStatus");
		$errors = array_merge($errors, $cceClient->errors());

		$sysConfig = $cceClient->getObject("System", array());

		// Get Status of Tomcat out of CCE:
		$javaStatus = $cceClient->getObject("System", array(), "JavaStatus");

		//
		//--- Handle form validation:
		//

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		// Form fields that are required to have input:
		$required_keys = array("maxClients", "minSpare", 'maxSpare');

    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("_JavaAdminPass_repeat");
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
			// Not needed. Thank you, jQuery!
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
			$cceClient->setObject("System", $attributes, "Java");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// Replace the CODB obtained values in our Form with the one we just posted to CCE:
			$web = $form_data;
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-java", "/java/tomcatmanager");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$page_module = 'base_sysmanage';

		// get web
		$web = $cceClient->getObject("System", array(), "Java");

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("TomcatAdminPassHeader", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("AdminPassInformation", ""),
		        $factory->getLabel("AdminPassInformation", false),
		        $defaultPage
		        );			

		$pass_field = $factory->getPassword('JavaAdminPass');
		$pass_field->setPreserveData(false);

		$block->addFormField(
		    $pass_field,
		    $factory->getLabel('TomcatAdminPassField')
		    );

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/java/tomcatmanager"));

		// Info about Tomcat-Status:
		$tomcat_statusbox = $factory->getPagedBlock("TomcatStausBox_header", array("Default"));
		$warning = $i18n->getClean("TomCatStatusBox_info");
		$tom_not_running = $factory->getTextField("_", $warning, 'r');
		$tom_not_running->setLabelType("nolabel");
		$tomcat_statusbox->addFormField(
		    $tom_not_running,
		    $factory->getLabel(" "),
		    "Default"
		    );

		// ScrolList for Tomcat management buttons:

		$adminURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/admin";
		$managerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/html";
		$hostManagerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/host-manager/html";
		$managerStatusURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/status";

		$scrollList = $factory->getScrollList("amJavaNameTag", array(" ", " "), array()); 
	    $scrollList->setAlignments(array("left", "right"));
	    $scrollList->setDefaultSortedIndex('0');
	    $scrollList->setSortOrder('descending');
	    $scrollList->setSortDisabled(array('0', '1'));
	    $scrollList->setPaginateDisabled(TRUE);
	    $scrollList->setSearchDisabled(TRUE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("80%", "150")); // Max: 739px

		// Manager URL:
		$linkButton = $factory->getUrlButton($managerURL);
		if ($javaStatus["TomcatStatus"] == "0") {
		    $linkButton->setDisabled(TRUE);
		}
	    $linkButton->setButtonSite("small");
		$namefield = $i18n->interpolate("[[base-java.TomcatManagerInterface]]");
		$desc_html = $factory->getTextField("", $namefield, "r");
		$desc_html->setLabelType("nolabel");
		$scrollList->addEntry( array($desc_html, $linkButton), "", false );

		// Tomcat Host Manager Interface:
		$linkButton = $factory->getUrlButton($hostManagerURL);
		if ($javaStatus["TomcatStatus"] == "0") {
		    $linkButton->setDisabled(true);

		}
		$linkButton->setButtonSite("small");
		$namefield = $i18n->interpolate("[[base-java.TomcatHostManagerInterface]]");
		$desc_html = $factory->getTextField("", $namefield, "r");
		$desc_html->setLabelType("nolabel");
		$scrollList->addEntry( array($desc_html, $linkButton), "", false );

		// Tomcat Manager Status:
		$linkButton = $factory->getUrlButton($managerStatusURL);
		if ($javaStatus["TomcatStatus"] == "0") {
		    $linkButton->setDisabled(true);
		}
		$linkButton->setButtonSite("small");
		$namefield = $i18n->interpolate("[[base-java.TomcatManagerStatus]]");
		$desc_html = $factory->getTextField("", $namefield, "r");
		$desc_html->setLabelType("nolabel");
		$scrollList->addEntry( array($desc_html, $linkButton), "", false );

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		if ($javaStatus["TomcatStatus"] == "0") {
			$page_body[] = $tomcat_statusbox->toHtml();
		}

		$page_body[] = $scrollList->toHtml();

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