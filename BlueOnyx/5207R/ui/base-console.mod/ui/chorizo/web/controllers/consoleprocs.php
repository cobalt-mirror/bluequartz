<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Consoleprocs extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /console/consolelogins.
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
		$i18n = new I18n("base-console", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not serverConfig? Bye, bye!
		if (!$Capabilities->getAllowed('serverConfig')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Get CODB-Object of interest updated: 
		//

		$ourOID = $cceClient->find("SOL_Console");
		$cceClient->set($ourOID[0], "", array('gui_list_proctrigger' => time()));
		$errors = $cceClient->errors();

		//
		//--- Get CODB-Object of interest loaded: 
		//

		$CODBDATA = $cceClient->getObject("SOL_Console");

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

			$user_kill_action = array(
				"kill_pid" => $get_form_data['pid'],
				"kill_trigger" => time()
			  );

	  		// Actual submit to CODB:
			$cceClient->setObject("SOL_Console", $user_kill_action);		

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /console/consoleprocs");
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

		$iam = '/console/consoleprocs';

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/console/consoleprocs");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_security');
		$BxPage->setVerticalMenuChild('base_console_procs');
		$page_module = 'base_sysmanage';

		$defaultPage = "basic";

		$block =& $factory->getPagedBlock("vserver_processlist", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
//		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Basic Tab
		//

  		$ScrollList = $factory->getScrollList("vserver_processlist", array("PID", "USER", "CPU", "MEM", "VSZ", "RSS", "TTY", "STAT", "START", "TIME", "COMMAND", "KILL"), array());
	    $ScrollList->setAlignments(array("left", "left", "left", "left", "left", "left", "left", "left", "left", "left", "left", "center"));
	    $ScrollList->setDefaultSortedIndex('0');
	    $ScrollList->setSortOrder('ascending');
	    $ScrollList->setSortDisabled(array('8'));
	    $ScrollList->setPaginateDisabled(FALSE);
	    $ScrollList->setSearchDisabled(FALSE);
	    $ScrollList->setSelectorDisabled(FALSE);
	    $ScrollList->enableAutoWidth(FALSE);
	    $ScrollList->setInfoDisabled(FALSE);
	    $ScrollList->setDisplay(10);
	    $ScrollList->setColumnWidths(array("10", "20", "20", "20", "20", "20", "100", "20", "50", "50", "250", "100")); // Max: 739px

		// Populate table rows with the data:

		// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

		// Explode entire strings into separate lines:
		$pieces = explode("#DELI#", $CODBDATA['sol_processes']);

		// How many entries are in $pieces?
		$ps_lines = 0;
		$ps_lines = count($pieces);
		$ps_a = "0";
		$ps_b = "1";

		foreach ($pieces as $line) {
			if ($line != "") {

				// Break $output up after 63 chars:
				$output = substr($line, "0", "64");
				$final_part = substr($line, "65", "500");

				// Replace all whitespaces from $output[0]:
				$pattern = '/\s+/i';
				$replacement = ';';
				$proc_out = preg_replace($pattern, $replacement, $output);

				list($USER, $PID[$ps_b], $CPU[$ps_b], $MEM[$ps_b], $VSZ[$ps_b], $RSS[$ps_b], $TTY[$ps_b], $STAT[$ps_b], $START[$ps_b], $TIME[$ps_b]) = explode(";", $proc_out);

				$shenannigans = array($USER, $PID[$ps_b], $CPU[$ps_b], $MEM[$ps_b], $VSZ[$ps_b], $RSS[$ps_b], $TTY[$ps_b], $STAT[$ps_b], $START[$ps_b], $TIME[$ps_b], $final_part);

				if ($PID[$ps_b] != "PID") {
				    if ((preg_match("/handlers\/base\/console\/generate_process_list.pl/", $final_part)) || 
						(preg_match("/\/bin\/ps auxwf > \/tmp\/console.process-list/", $final_part)) || 
						(preg_match("/\_ \/bin\/ps auxwf/", $final_part))) {

				    }
					else {

						$action = $factory->getCompositeFormField();
						$remove_button = $factory->getRemoveButton("$iam?pid=$PID[$ps_b]");
						$remove_button->setImageOnly(TRUE);
			    	    $action->addFormField($remove_button);

						// Populate Scrollist
					    $ScrollList->addEntry(array(
					        	    $PID[$ps_b],
					        	    $USER,
					        	    formspecialchars($CPU[$ps_b]),
					        	    formspecialchars($MEM[$ps_b]), 
					        	    formspecialchars($VSZ[$ps_b]), 
					        	    formspecialchars($RSS[$ps_b]), 
					        	    formspecialchars($TTY[$ps_b]), 
					        	    formspecialchars($STAT[$ps_b]), 
					        	    formspecialchars($START[$ps_b]), 
					        	    formspecialchars($TIME[$ps_b]), 
					        	    word_wrap(formspecialchars($final_part), 15),
							    	$action
					    ));
					}
				}

			}
		}

		$block->addFormField(
			$factory->getRawHTML("filler", "&nbsp;"),
			$factory->getLabel(" "),
			$defaultPage
		);

		$cmd = "/usr/bin/w|/usr/bin/head -1";
		exec("$cmd 2>&1", $wline);
		$block->addFormField(
			$factory->getRawHTML("filler", "&nbsp;" . $wline[0]),
			$factory->getLabel(" "),
			$defaultPage
		);

		// Commit-Integer: We need at least one form field to be able to submit data.
		// So we use this hidden one:
		$block->addFormField(
			$factory->getTextField('commit', time(), ''),
			$factory->getLabel("commit"), 
			$defaultPage
		);	

		// Show the ScrollList of Logins:
		$block->addFormField(
			$factory->getRawHTML("vserver_loginlist", $ScrollList->toHtml()),
			$factory->getLabel("vserver_loginlist"),
			$defaultPage
		);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/console/consoleprocs"));

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