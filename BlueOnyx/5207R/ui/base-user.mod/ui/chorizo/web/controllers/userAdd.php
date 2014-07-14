<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class UserAdd extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /user/userAdd.
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

		// Get UserDefaults:
		$defaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");

		// Find out if FTP access for non-siteAdmins is enabled or disabled for this site:
		list($ftpvsite) = $cceClient->find("Vsite", array("name" => $group));
		$ftpPermsObj = $cceClient->get($ftpvsite, 'FTPNONADMIN');
		$ftpnonadmin = $ftpPermsObj['enabled'];

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

			// Handle setting the proper volume for vsite users
			if (isset($group)) {
			    $vsite = $cceClient->getObject("Vsite", array("name" => $group));
			    $attributes['volume'] = $vsite['volume'];
			}
			else {
			    // Default to wherever the home directory is:
			    $attributes['volume'] = "/home";
			}

			// Handle FTP access clauses:
			if (!isset($attributes['ftpForNonSiteAdmins'])) {
		    	$attributes['hasNoFTPaccess'] = "1";
			}
			else {
			    $attributes['hasNoFTPaccess'] = "0";
			}

			if ($attributes['siteAdministrator'] == "1") {
			    $attributes['hasNoFTPaccess'] = "0";
			}

			// If a prefix is given, prepend it to the userName:
			if (isset($attributes['prefix'])) {
			    $UserNameArray = array($attributes['prefix'], $attributes['userName']);
			    $newUserName = implode("_", $UserNameArray);
			    
			    // If someone uses a really long username, then a prefix may make it too long.
			    // So we need to check how long the username now is and if need be, we need to shorten it:
			    $unameLength = strlen($newUserName);
			    if ($unameLength > '31') {
					// Ok, the name is too long. We need to shorten it back down to 32 characters:
					$newUserNameShort = (mb_substr($newUserName, '0', '31'));
					$newUserName = $newUserNameShort;
			    }
			}
			else {
			    $newUserName = $attributes['userName'];
			}

			$out_attributes = array(
			                "name" => $newUserName, 
			                "sortName" => "", 
			                "fullName" =>$attributes['fullNameField'], 
			                "password" => $attributes['passwordField'], 
			        		"emailDisabled" => $attributes['emailDisabled'],
			        		"ftpDisabled" => $attributes['hasNoFTPaccess'],
			                "localePreference" => "browser", 
			                "stylePreference" => "BlueOnyx", 
			                "volume" => $attributes['volume'],
			                "description" => $attributes['userDescField']);

			if (isset($group)) {
			    $out_attributes["site"] = $group;
			    $out_attributes['enabled'] = ($vsite['suspend'] ? 0 : 1);
			}

			if (isset($attributes['siteAdministrator'])) {
			    $out_attributes["capLevels"] = ($attributes['siteAdministrator'] ? '&siteAdmin&' : '');
			}

			if (isset($attributes['dnsAdministrator'])) {
			    $out_attributes["capLevels"] .= ($attributes['dnsAdministrator'] ? '&siteDNS&' : '');
			}

			// Dirty trick for cleanup:
			$out_attributes["capLevels"] = str_replace("&&", "&", $out_attributes["capLevels"]);

			// Username = Password? Baaaad idea!
			if (strcasecmp($newUserName, $attributes['passwordField']) == 0) {
			        $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
			        $errors[] = new Error($error_msg);
			}

			// Password Check:
			if (isset($attributes['passwordField'])) {
				$passwd = $attributes['passwordField'];
			}
			$passwd_repeat = "";
			if (isset($attributes['_passwordField_repeat'])) {
				$passwd_repeat = $attributes['_passwordField_repeat'];
			}

			if (bx_pw_check($i18n, $passwd, $passwd_repeat) != "") {
				$my_errors[] = bx_pw_check($i18n, $passwd, $passwd_repeat);
			}

		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// Handle create of user if necessary:
    		if (!isset($_oid)) {

    			// Create the User:
		        $big_ok = $cceClient->create('User', $out_attributes);
		        $errors = array_merge($errors, $cceClient->errors());

		        // Get the OID of this transaction:
                if ($big_ok) {
            		$_oid = $big_ok;

					// Set quota:
					if (!isset($attributes['maxDiskSpaceField'])) {
						// Somehow no quota was set. Assume unlimited:
					    $quota = "-1";
					}
					else {
						// Quota is set. Check if it has a unit or not:
					    $pattern = '/^(\d*\.{0,1}\d+)(K|M|G|T)$/';
					    if (preg_match($pattern, $attributes['maxDiskSpaceField'], $matches, PREG_OFFSET_CAPTURE)) {
					    	// Quota has a unit:
					    	$quota = (unsimplify_number($attributes['maxDiskSpaceField'], "K")/1000);
					    }
					    else {
					    	// Quota has no unit:
					    	$quota = $attributes['maxDiskSpaceField'];
					    }
					}

					$cceClient->set($_oid, "Disk", array("quota" => $quota));
					$errors = array_merge($errors, $cceClient->errors());

					// Handle AutoFeatures:
					list($userservices) = $cceClient->find("UserServices", array("site" => $group));
					$autoFeatures = new AutoFeatures($serverScriptHelper, $attributes);
					$af_errors = $autoFeatures->handle("create.User", array("CCE_SERVICES_OID" => $userservices, "CCE_OID" => $_oid), $attributes);
					$errors = array_merge($errors, $af_errors);

					// Set email information and prune the duplicate email aliases:
					$emailAliasesFieldArray = $cceClient->scalar_to_array($attributes['emailAliasesField']);
					$emailAliasesFieldArray = array_unique($emailAliasesFieldArray);
					$emailAliasesField = $cceClient->array_to_scalar($emailAliasesFieldArray);

					// Replace && with & to avoid always getting a blank alias in the field
					// in cce. This also skirts around dealing with browser issues:
					$emailAliasesField = str_replace("&&", "&", $emailAliasesField);
					if ($emailAliasesField == '&') {
						$emailAliasesField = '';
					}

					$alias_attributes = array("aliases" => $emailAliasesField);

					$cceClient->set($_oid, "Email", $alias_attributes);
					$errors = array_merge($errors, $cceClient->errors());

					// At this point we're done. We may have errors, though.
            	}
    		}

			// CCE errors that might have happened during submit to CODB:
			$z = "0";
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[$z] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
				$z++;
			}

			// No errors during submit? Reload page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				$redirect_URL = "/user/userList?group=$group";
				header("location: $redirect_URL");
				exit;
			}
			else {
				// We do have errors. So we roll back and destroy the created User object:
				if (isset($_oid)) {
					$cceClient->destroy($_oid);
				}
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/user/userAdd?group=$group");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteadmin');
		$BxPage->setVerticalMenuChild('base_userList');
		$page_module = 'base_sitemanage';

		$defaultPage = "pageID";
		$block =& $factory->getPagedBlock("addNewUser", array($defaultPage));
		$block->setLabel($factory->getLabel('addNewUserTitle', false, array('fqdn' => $vsite['fqdn'])));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Add hidden field for Group:
		$block->addFormField($factory->getTextField("group", $group, ""), $defaultPage);

		// Full name:
		$block->addFormField(
		    $factory->getFullName("fullNameField", ""),
		    $factory->getLabel("fullNameField"),
		    $defaultPage
		);

		// # Username - start
		if ($vsite['userPrefixEnabled'] == "1") { 
		    $userPrefixField = $vsite['userPrefixField']; 
		     
		    if (!$userPrefixField) { 
		       $octets = explode(".", $vsite['fqdn']); 
		       $userPrefixField = ""; 
		       foreach($octets as $octet) { 
					$userPrefixField .= substr($octet, 0, 1); 
		       } 
		       $userPrefixField .= time(); 
		       $userPrefixField .= "_"; 
		    } 

			$userPrefix = $factory->getUserName("prefix", $userPrefixField, 'r'); 
			$userPrefix->setType("alphanum_plus");
			$userPrefix->setLabelType("label_top no_lines");

			$userSuffix = $factory->getTextField("userName", "", 'rw');
			$userSuffix->setType("alphanum_plus");
			$userSuffix->setLabelType("label_top no_lines");

			$userNameField =& $factory->getCompositeFormField(array($factory->getLabel("userNameField"), $userPrefix, $userSuffix), '');
			$userNameField->setColumnWidths(array('col_25', 'col_25', 'col_50'));

		} 
		else { 
			$userNameField = $factory->getTextField("userName", "");
		} 
		 
		$block->addFormField( 
		    $userNameField, 
		    $factory->getLabel("userNameField"),
		    $defaultPage
		); 
		// # Username - end

		// Password:
		$block->addFormField(
		    $factory->getPassword("passwordField", ""),
		    $factory->getLabel("passwordField"),
		    $defaultPage
		);

		// Load site quota
		list($vsite_oid) = $cceClient->find('Vsite', array("name" => $group));
		$disk = $cceClient->get($vsite_oid, 'Disk');
		$vsite_quota = $disk['quota']*1024*1024;
		$default_quota = $defaults['quota']*1024*1024;

		$site_quota = $factory->getInteger('maxDiskSpaceField', simplify_number($default_quota, "KB", "0"), 1, $vsite_quota, 'rw'); 
	    $site_quota->showBounds('dezi');
	    $site_quota->setType('memdisk');
		$block->addFormField(
		        $site_quota,
		        $factory->getLabel('maxDiskSpaceField'),
		        $defaultPage
		        );

		// Add other features
		$autoFeatures = new AutoFeatures($serverScriptHelper);

		if (isset($group) && $group != "") {
		    list($userServices) = $cceClient->find("UserServices", array("site" => $group));
		    list($vsite) = $cceClient->find("Vsite", array("name" => $group));
		    $autoFeatures->display($block, "create.User", array("CCE_SERVICES_OID" => $userServices, 'PAGED_BLOCK_DEFAULT_PAGE' => $defaultPage, "VSITE_OID" => $vsite));

		    $block->addFormField(
		        $factory->getBoolean("siteAdministrator", ""),
		        $factory->getLabel("siteAdministratorField"),
		        $defaultPage
		    );
		    $block->addFormField(
		        $factory->getBoolean("dnsAdministrator", ""),
		        $factory->getLabel("dnsAdministratorField"),
		        $defaultPage
		    );
		 
		}
		else {
		    list($userServices) = $cceClient->find("UserServices");
		    $autoFeatures->display($block, "create.User", array("CCE_SERVICES_OID" => $userServices));
		}

		$block->addFormField(
		  $factory->getBoolean("emailDisabled", $defaults["emailDisabled"]),
		  $factory->getLabel("emailDisabled"),
		  $defaultPage
		);

		$emailAliases = $factory->getEmailAliasList("emailAliasesField");
		$emailAliases->setOptional(true);
		$block->addFormField(
		    $emailAliases,
		    $factory->getLabel("emailAliasesField"),
		    $defaultPage
		);

		if (isset($defaults["description"])) {
			$description = $i18n->interpolate($defaults["description"]);
		}
		else {
			$description = "";
		}

		$textblock = $factory->getTextBlock("userDescField", $description);
		$textblock->setWidth(2*$textblock->getWidth());
		$textblock->setOptional(true);
		$block->addFormField(
		    $textblock,
		    $factory->getLabel("userDescField"),
		    $defaultPage
		);

		// Add the buttons for those who can edit this page:
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