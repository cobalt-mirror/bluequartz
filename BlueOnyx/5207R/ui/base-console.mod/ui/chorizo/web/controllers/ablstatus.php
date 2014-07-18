<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ablstatus extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /console/ablstatus.
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
		//--- Get CODB-Object of interest: 
		//

	    // Get 'fail_hosts' information out of CCE:
	    $hostnum = 0;
	    $oids = $cceClient->findx('fail_hosts');
	    foreach ($oids as $oid) {
			$HOSTS = $cceClient->get($oid);
			if ($HOSTS['host_ip']) {
	    	    $HOSTSLIST[$hostnum] = array(
	        							'host_ip' => $HOSTS['host_ip'],
							        	'host_fqdn' => $HOSTS['host_fqdn'],
						    	        'failcnt' => $HOSTS['failcnt'],
							        	'blocking' => $HOSTS['blocking'],
						    	        'activity' => $HOSTS['activity']
	    	    );
	    	    $hostnum++;
			}
	    }

	    // Get 'fail_users' information out of CCE:
	    $usernum = 0;
	    $oids = $cceClient->findx('fail_users');
	    foreach ($oids as $oid) {
			$USERS = $cceClient->get($oid);
			if ($USERS['username']) {
	    	    $USERLIST[$usernum] = array(
							        	'username' => $USERS['username'],
							        	'failcnt' => $USERS['failcnt'],
							        	'blocking' => $USERS['blocking'],
							        	'activity' => $USERS['activity']
	    	    );
	    	    $usernum++;
			}
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
    	$ignore_attributes = array("BlueOnyx_Info_Text", "_");
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

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();				
				header("Location: /console/ablstatus");
				exit;
			}
		}

		//
		//-- Own page logic:
		//

		$get_form_data = $CI->input->get(NULL, TRUE);

		// Other (button initiated) action taking place:
		if (isset($get_form_data['action'])) {
			$cceClient->setObject("pam_abl_settings", array($get_form_data['action'] => time()));
		    $errors = $cceClient->errors();
			if ((count($errors) == "0")) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /console/ablstatus");
				exit;
			}		    
		}

		// Unblocking action taking place:
		if ((isset($get_form_data['host'])) || (isset($get_form_data['user']))) {
		    if (isset($get_form_data['host'])) {
				$OID = $cceClient->find("fail_hosts", array("host_ip" => $get_form_data['host']));
				$cceClient->set($OID[0], "",
			    	    array(
			            "host_remove" => time())
				);
		    }
		    if (isset($get_form_data['user'])) {
				$OID = $cceClient->find("fail_users", array("username" => $get_form_data['user']));
				$cceClient->set($OID[0], "",
			    	    array(
			            "user_remove" => time())
				);
		    }
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
		}

		//
	    //-- Generate page:
	    //


		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/console/ablstatus");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_security');
		$BxPage->setVerticalMenuChild('pam_abl_status');
		$page_module = 'base_sysmanage';

		$defaultPage = "blocked_hosts";

		$block =& $factory->getPagedBlock("pam_abl_blocked_users_and_hosts", array($defaultPage, 'blocked_users'));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- TAB: blocked_hosts
		//

  		$scrollList = $factory->getScrollList("pam_abl_blocked_hosts", array("host_ip", "host_fqdn", "whois", "failcnt", "access", "Action"), array()); 
	    $scrollList->setAlignments(array("center", "center", "center", "center", "center", "center"));
	    $scrollList->setDefaultSortedIndex('0');
	    $scrollList->setSortOrder('ascending');
	    $scrollList->setSortDisabled(array('2', '5'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("180", "255", "75", "75", "75", "75")); // Max: 739px

	    // Populate host table rows with the data:
	    while ( $hostnum >= 0 ) {
	        if (isset($HOSTSLIST[$hostnum]['host_ip'])) {

			    // Whois button:
				$whois_button = $factory->getFancyButton("/console/whois?q=" . $HOSTSLIST[$hostnum]['host_ip'], "whois");
				$whois_button->setImageOnly(TRUE);

				// Remove button:
				$remove_button = $factory->getRemoveButton("/console/ablstatus?host=". $HOSTSLIST[$hostnum]['host_ip']);
				$remove_button->setImageOnly(TRUE);

				// Access icon:
			    if ($HOSTSLIST[$hostnum]['blocking'] == "0") {
					$status = '					<button class="red tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '"><span>' . $i18n->getHtml("[[palette.Yes]]") . '</span></button>';
				}
				else {
					$status = '					<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '"><span>' . $i18n->getHtml("[[palette.No]]") . '</span></button>';
				}
			    if ($HOSTSLIST[$hostnum]['blocking'] == "0") {
		        	//$actions->setDisabled(true);
	    	   	}
	            $scrollList->addEntry(array(
	                $HOSTSLIST[$hostnum]['host_ip'],
	                $HOSTSLIST[$hostnum]['host_fqdn'], 
	                $whois_button,
	                $HOSTSLIST[$hostnum]['failcnt'],
					$status,
	                $remove_button
	            ));
			}
	        $hostnum--;
	    }

		// Page selector:
		$ps = "d";

		// Purge buttons:
		$reset_hosts_button = $factory->getButton("/console/ablstatus?action=reset_hosts&ps=$ps", 'reset_hosts_button');
		$reset_users_button = $factory->getButton("/console/ablstatus?action=reset_users&ps=$ps", 'reset_users_button');
		$reset_all_button = $factory->getButton("/console/ablstatus?action=reset_all&ps=$ps", 'reset_all_button');
		$purge_button = $factory->getButton("/console/ablstatus?action=purge&ps=$ps", 'purge_button');
	
		$buttonContainer = $factory->getButtonContainer("pam_abl_blocked_hosts", array($reset_hosts_button, $reset_users_button, $reset_all_button, $purge_button));

		$block->addFormField(
			$buttonContainer,
			$factory->getLabel("pam_abl_blocked_hosts"),
			"blocked_hosts"
		);

		$block->addFormField(
			$factory->getRawHTML("pam_abl_blocked_hosts", $scrollList->toHtml()),
			$factory->getLabel("pam_abl_blocked_hosts"),
			"blocked_hosts"
		);

		//
		//--- TAB: blocked_users
		//

  		$scrollListusers = $factory->getScrollList("pam_abl_blocked_users", array("username", "failcnt", "access", "Action"), array()); 
	    $scrollListusers->setAlignments(array("center", "center", "center", "center"));
	    $scrollListusers->setDefaultSortedIndex('0');
	    $scrollListusers->setSortOrder('ascending');
	    $scrollListusers->setSortDisabled(array('3'));
	    $scrollListusers->setPaginateDisabled(FALSE);
	    $scrollListusers->setSearchDisabled(FALSE);
	    $scrollListusers->setSelectorDisabled(FALSE);
	    $scrollListusers->enableAutoWidth(FALSE);
	    $scrollListusers->setInfoDisabled(FALSE);
	    $scrollListusers->setColumnWidths(array("505", "75", "75", "75")); // Max: 739px

	    // Populate user table rows with the data:
	    while ( $usernum >= 0 ) {
			if (isset($USERLIST[$usernum]['username'])) {
		        if ($USERLIST[$usernum]['username']) {

					// Access icon:
				    if ($USERLIST[$usernum]['blocking'] == "0") {
						$status = '					<button class="red tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '"><span>' . $i18n->getHtml("[[palette.Yes]]") . '</span></button>';
					}
					else {
						$status = '					<button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '"><span>' . $i18n->getHtml("[[palette.No]]") . '</span></button>';
					}

					// Remove button:
					$uremove_button = $factory->getRemoveButton("/console/ablstatus?user=". $USERLIST[$usernum]['username']);
					$uremove_button->setImageOnly(TRUE);

				    if ($USERLIST[$usernum]['blocking'] == "0") {
			        	//$actions->setDisabled(true);
					}
		            $scrollListusers->addEntry(array(
		                $USERLIST[$usernum]['username'],
		                $USERLIST[$usernum]['failcnt'],
		                $status,
		                $uremove_button
		            ));
				}
			}
			$usernum--;
	    }

		$reset_hosts_button = $factory->getButton("/console/ablstatus?action=reset_hosts&ps=$ps", 'reset_hosts_button');
		$reset_users_button = $factory->getButton("/console/ablstatus?action=reset_users&ps=$ps", 'reset_users_button');
		$reset_all_button = $factory->getButton("/console/ablstatus?action=reset_all&ps=$ps", 'reset_all_button');
		$purge_button = $factory->getButton("/console/ablstatus?action=purge&ps=$ps", 'purge_button');
	
		$buttonContainerUsers = $factory->getButtonContainer("pam_abl_blocked_users", array($reset_hosts_button, $reset_users_button, $reset_all_button, $purge_button));

		$block->addFormField(
			$buttonContainerUsers,
			$factory->getLabel("pam_abl_blocked_users"),
			"blocked_users"
		);

		$block->addFormField(
			$factory->getRawHTML("pam_abl_blocked_users", $scrollListusers->toHtml()),
			$factory->getLabel("pam_abl_blocked_users"),
			"blocked_users"
		);

		//
		//--- Add the buttons
		//

		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/console/ablstatus"));

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