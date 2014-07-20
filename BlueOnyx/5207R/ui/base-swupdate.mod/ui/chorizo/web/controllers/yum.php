<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Yum extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /swupdate/yum.
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

		$CODBDATA = $cceClient->getObject("System", array(), "yum");

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
    	$ignore_attributes = array("BlueOnyx_Info_Text", "_", "yumlog", "yum_last_updated");
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
			$cleaned_autoupdate_choices = array($i18n->getClean("[[palette.enabled_short]]") => "On", $i18n->getClean("[[palette.disabled_short]]") => "Off");
			$attributes['autoupdate'] = $cleaned_autoupdate_choices[$attributes['autoupdate']];
			$attributes['y_force_update'] = mt_rand();
		}
		else {
		    // We're not saving changes. So we set 'skiplock' to call a handler that runs a
		    // chmod 444 over our files in /tmp so that this PHP page can access them:
		    mt_srand((double)microtime() * 1000000);
		    $skiplock = mt_rand();
		    $config = array(
			"skiplock" => $skiplock
		    );
		    $cceClient->setObject("System", $config, "yum");
		    $errors = $cceClient->errors();			
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
	  		$cceClient->setObject("System", $attributes, "yum");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /swupdate/yum");
				exit;
			}
			else {
				$CODBDATA = $attributes;
			}
		}

		//
		//-- Own page logic:
		//

		//
	    //-- Generate page:
	    //


		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum", "/swupdate/yum");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_software');
		$BxPage->setVerticalMenuChild('yum_gui');
		$page_module = 'base_software';

		$defaultPage = "yumTitle";

		$block =& $factory->getPagedBlock("yumgui_head", array($defaultPage, "Settings", "Logs"));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		// Display date of last update:
		if (file_exists("/var/log/yum.log") ) {
			$yum_last_updated = `/usr/bin/stat /var/log/yum.log |grep "Modify:"|sed 's/Modify: //g'|sed 's/\..*//g'`;
			$block->addFormField(
				$factory->getTextField("yum_last_updated", $yum_last_updated, "r"),
				$factory->getLabel("yum_last_updated"),
				$defaultPage
		  	);
		}

		// Display notice that system is currently installing updates:
		if (file_exists("/tmp/yum.updating") ) {
			$block->addFormField(
				$factory->getTextField("yum_is_pulling_updates", $i18n->getHtml("yum_is_pulling_updates_help"), "r"),
				$factory->getLabel("yum_is_pulling_updates"), 
				$defaultPage
		  	);
		}
		else {
			// Add ButtonContainer and button to manually check for updates:
			$yumCheck[] = $factory->getRawHTML("yumCheck", '<button title="' . $i18n->getWrapped("yumCheck_help") . '" class="close_dialog tooltip right link_button waiter" data-link="/swupdate/checkupdates" target="_self"><img src="/.adm/images/icons/small/white/refresh_3.png"><span>' . $i18n->getHtml("yumCheck") . '</span></button>');
			$yumCheckButton = $factory->getButton("/swupdate/yumupdate", "yumNOW");
			$yumCheckButton->setWaiter(TRUE);
			$yumCheck[] = $yumCheckButton;

			$buttonContainer = $factory->getButtonContainer("", $yumCheck);
			$block->addFormField(
				$buttonContainer,
				$factory->getLabel("yumCheck"),
				$defaultPage
			);
		}

		//
		//--- Available YUM updates:
		//

		// Set up ScrollList:
  		$ScrollList = $factory->getScrollList("yumTitle", array("name", "version", "status"), array()); 
	    $ScrollList->setAlignments(array("left", "center", "center"));
	    $ScrollList->setDefaultSortedIndex('0');
	    $ScrollList->setSortOrder('descending');
	    $ScrollList->setSortDisabled(array());
	    $ScrollList->setPaginateDisabled(FALSE);
	    $ScrollList->setSearchDisabled(FALSE);
	    $ScrollList->setSelectorDisabled(FALSE);
	    $ScrollList->enableAutoWidth(FALSE);
	    $ScrollList->setInfoDisabled(FALSE);
	    $ScrollList->setDisplay(25);
	    $ScrollList->setColumnWidths(array("344", "150", "244")); // Max: 739px

		// Do we have any updates to install?
		if (file_exists("/tmp/yum.check-update") ) {
			$yum_output = read_file("/tmp/yum.check-update");
			$a_yum = preg_split("/\n/", $yum_output);
			$count = count($a_yum);
			$start = 0;
			for ( $i = 0; $i < $count; $i++ ) {
				if ( $a_yum[$i] == "" ) { $start = 1; }
					if ( $start == 1 AND $a_yum[$i] ) {
			  			$updates[] = $a_yum[$i];
					}
			}

			if (isset($updates) && count($updates) > 0 ) { 
				foreach ( $updates as $entry ) {
		  			$yum_update = 1;
					$entry = preg_replace("/\s+/", " ", $entry);
					$a_entry = preg_split("/ /", $entry);
					if (($a_entry[0] != "") && ((isset($a_entry[0])) && (isset($a_entry[1])) && (isset($a_entry[2])))) {
					    $ScrollList->addEntry(array(
						    $a_entry[0],
						    $a_entry[1],
						    $a_entry[2],
					    ));
		  			}
				}
			}
		}
		else {
			$yum_output = "";
		}

		// Show the ScrollList for the Updates:
		$block->addFormField(
			$factory->getRawHTML("yumTitle", $ScrollList->toHtml()),
			$factory->getLabel("yumTitle"),
			$defaultPage
		);

		//
		//--- Settings:
		//

		// Settings:
		if($CODBDATA["autoupdate"] == "On") {
		    $autoupdate_choices=array("On" => $i18n->getClean("[[palette.enabled_short]]"), "Off" => $i18n->getClean("[[palette.disabled_short]]"));
		}
		else {
			//Strict, but safe default:
		    $autoupdate_choices=array("Off" => $i18n->getClean("[[palette.disabled_short]]"), "On" => $i18n->getClean("[[palette.enabled_short]]"));
		}

		// Display YUM enabler switch:
		$autoupdate_select = $factory->getMultiChoice("autoupdate", array($i18n->getClean("[[palette.enabled_short]]"), $i18n->getClean("[[palette.disabled_short]]")), array($autoupdate_choices));
		$autoupdate_select->setSelected($autoupdate_choices[$CODBDATA["autoupdate"]], true);
		$block->addFormField($autoupdate_select, $factory->getLabel("autoupdate"), "Settings");

		$exclude_box = $factory->getTextBlock("yumguiEXCLUDE", $CODBDATA["yumguiEXCLUDE"]);
		$exclude_box->setHeight("5");
		$exclude_box->setWidth("40");
		$exclude_box->setOptional(true);

		$block->addFormField(
		  $exclude_box,
		  $factory->getLabel("yumguiEXCLUDE"),
		  "Settings"
		  );

		$block->addFormField(
		  $factory->getBoolean("yumguiEMAIL", $CODBDATA["yumguiEMAIL"]),
		  $factory->getLabel("yumguiEMAIL"),
		  "Settings"
		  );

		// Work around for yumguiEMAILADDY: Need to change it to fully qualified email address:
		if (!preg_match('/\@/', $CODBDATA["yumguiEMAILADDY"])) {
			$CODBDATA["yumguiEMAILADDY"] = $CODBDATA["yumguiEMAILADDY"] . '@' . $system['hostname'] . '.' . $system['domainname'];
		}

		$yumguiEMAILADDYField = $factory->getTextField("yumguiEMAILADDY", $CODBDATA['yumguiEMAILADDY']);
		$yumguiEMAILADDYField->setOptional ('silent');
		$yumguiEMAILADDYField->setType ('email');
		$block->addFormField(
		  $yumguiEMAILADDYField,
		  $factory->getLabel("yumguiEMAILADDY"),
		  "Settings"
		);

		$time_to_update = array();
		for ($i = 0; $i < 24 ; $i++ ) {
		  $time_to_update []= "$i:00";
		  $time_to_update []= "$i:30";
		}

		$yumUpdateTime= $factory->getMultiChoice("yumUpdateTime", $time_to_update);
		$yumUpdateTime->setSelected($CODBDATA["yumUpdateTime"], true);
		$block->addFormField(
		  $yumUpdateTime,
		  $factory->getLabel("yumUpdateTime"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateSU", $CODBDATA["yumUpdateSU"]),
		  $factory->getLabel("yumUpdateSU"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateMO", $CODBDATA["yumUpdateMO"]),
		  $factory->getLabel("yumUpdateMO"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateTU", $CODBDATA["yumUpdateTU"]),
		  $factory->getLabel("yumUpdateTU"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateWE", $CODBDATA["yumUpdateWE"]),
		  $factory->getLabel("yumUpdateWE"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateTH", $CODBDATA["yumUpdateTH"]),
		  $factory->getLabel("yumUpdateTH"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateFR", $CODBDATA["yumUpdateFR"]),
		  $factory->getLabel("yumUpdateFR"),
		  "Settings"
		);

		$block->addFormField(
		  $factory->getBoolean("yumUpdateSA", $CODBDATA["yumUpdateSA"]),
		  $factory->getLabel("yumUpdateSA"),
		  "Settings"
		);

		//
	    //-- YUM Logfile:
	    //

		// Logfile viewer:
		$the_file_data = $i18n->getClean("yumlog_empty");
		if ((file_exists("/var/log/yum.log")) && (is_readable("/var/log/yum.log"))) {
			$file_info = get_file_info("/var/log/yum.log");
		    $datei_yum = "/var/log/yum.log";
		    $array_yum = file($datei_yum);
		    $array_yum = array_reverse($array_yum);

			$the_file_data = '';
		    for($x=0;$x<count($array_yum);$x++){
		        // Replace
		        $array_yum[$x] = nl2br($array_yum[$x]); //#newline conversion
		        $array_yum[$x] = br2nl($array_yum[$x]);
		        // Get last 500 lines of yum.log:
				if ($x < 500) {
				        $the_file_data = $the_file_data.$array_yum[$x];
				}
		    }

		    // Logfile is present, but empty:
		    if ($file_info['size'] == '0') {
		    	$the_file_data = $i18n->getClean("yumlog_empty");
		    }
		}

		$box = $factory->getTextBlock("yumlog", $the_file_data, "r");
		$block->addFormField($box, $factory->getLabel("yumlog"), "Logs");

		//
		//--- Add the buttons
		//

		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/swupdate/yum"));

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