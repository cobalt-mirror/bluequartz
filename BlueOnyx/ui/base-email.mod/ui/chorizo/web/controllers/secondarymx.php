<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SecondaryMX extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /email/secondarymx.
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
		$i18n = new I18n("base-email", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverEmail'? Bye, bye!
		if (!$Capabilities->getAllowed('serverEmail')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		// Get TARGET of Modification request:
		$get_form_data = $CI->input->get(NULL, TRUE);
		if(isset($get_form_data['_TARGET'])) {
			$oid = $cceClient->get($get_form_data['_TARGET']);
			if ($oid['CLASS'] == "mx2") {
				$domain = $oid["domain"];
				$mapto = $oid["mapto"];
			}
			else {
				// These are not the droids we are looking for!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403");
			}
		 }
		 else {
			$domain = "";
			$mapto = "";
		 }

		// Get TARGET of Delete request:
		if(isset($get_form_data['_RTARGET'])) {
			$oid = $get_form_data['_RTARGET'];
			$oidData = $cceClient->get($get_form_data['_RTARGET']);

			if ($oidData['CLASS'] != "mx2") {
				// These are not the droids we are looking for!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403");
			}

			$secondaryOid = $cceClient->get($oid);
			$sysOid = $cceClient->getObject("System", array(), "Email");
			$relayFor = $sysOid["relayFor"];
			$domain = $secondaryOid["domain"];
			if(strstr($relayFor, "&$domain&")) {
				$relayFor = str_replace("&$domain&", "&", $relayFor);
				if($relayFor == "&") {
				  $relayFor = "";
				}
				$cceClient->setObject("System", array( 'relayFor' => $relayFor), "Email");
			}
			$cceClient->destroy($oid, "");
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /email/emailsettings?mx#tabs-3");
				exit;
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
		$required_keys = array("domainField", "maptoField");

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

		if ($CI->input->post(NULL, TRUE)) {
			// None
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.

			// Any additional parameters that we need to pass on?
			$sysOid = $cceClient->getObject("System", array(), "Email");
			$oids = $cceClient->findx("mx2", array('domain' => $attributes['domainField']), array(),"","");

			if(isset($form_data['_TARGET'])) {
			  $old = $cceClient->get($form_data['_TARGET']);
			  $oldDomain = $old["domain"];
			 }

			$relayFor = $sysOid["relayFor"];
			if(strstr($relayFor, "&" . $attributes['domainField'] . "&") && (!isset($form_data['_TARGET']))) {
			  $msg = "[[base-email.domainExists_in_relay_error]]";
			  $errors = array_merge($errors, array((new Error($msg))));
			 }
			 elseif (strstr($relayFor, "&" . $attributes['domainField'] . "&") && !(count($oids) == 1 && $form_data['_TARGET'] == $oids[0])) {
			  $msg = "[[base-email.domainExists_in_relay_error]]";
			  $errors = array_merge($errors, array((new Error($msg))));
			 }
			 else { 
			 	if (isset($oldDomain)) {
					if(strstr($relayFor, "&$oldDomain&")) {
						$relayFor = str_replace("&$oldDomain&", "&" . $attributes['domainField'] . "&", $relayFor);
						$relayFinished = 1;
					}
				}
				if(strstr($relayFor, "&" . $attributes['domainField'] . "&")) {
					$relayFinished = 1;
				}
			}

			$siteOids = $cceClient->find("Vsite");

			while (list($key, $siteOid) = each($siteOids)) {
			  $siteInfo = $cceClient->get($siteOid);
			  if(strstr($siteInfo["mailAliases"], "&" . $attributes['domainField'] . "&")) {
			    $msg = "[[base-email.domainExists_in_site_error]]";
			    $errors = array_merge($errors, array((new Error($msg))));
			  }
			}

			if(!$errors) {
			  if((count($oids) < 1) || (count($oids) == 1 && $form_data['_TARGET'] == $oids[0] )) {
			    if(isset($form_data['_TARGET'])) {
			      $oid = $form_data['_TARGET'];
			      $vals = array(
					    "domain" => $attributes['domainField'], 
					    "mapto" => $attributes['maptoField']);
			      
			      $cceClient->set($oid, "", $vals);
			    }
			    else {
			      $cceClient->create("mx2", 
						 array(
						       "domain" => $attributes['domainField'], 
						       "mapto" => $attributes['maptoField']));
			    }
			    if(!isset($relayFinished)) {
			      if(!$relayFor) {
					$relayFor = "&".$relayFor.$attributes['domainField']."&";
			      }
			      else {
					$relayFor = $relayFor.$attributes['domainField']."&";
			      }
			    }
			    $cceClient->setObject("System", array( 
								  "relayFor" => $relayFor),
						  "Email");
			  }
			  else {
			    $msg = "[[base-email.domainExists_error]]";
			    $errors = array_merge($errors, array((new Error($msg))));
			  }
			 }

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// Replace the CODB obtained values in our Form with the one we just posted to CCE:
			$web = $form_data;

			// We have no errors and have POST data, we submitted to CODB without errors? Redirect to /email/emailsettings#tabs-3
			if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE)) && (count($CCEerrors) == 0)) {
			  // Redirect to /email/emailsettings#tabs-3:
			  header("Location: /email/emailsettings?mx#tabs-3");
			  exit;
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/email/secondarymx");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$BxPage->setVerticalMenuChild('base_emailServers');
		$page_module = 'base_sysmanage';

		// get web
		$web = $cceClient->getObject("System", array(), "Web");

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("secondarySettings", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		$block->addFormField(
				     $factory->getDomainName("domainField", $domain, "rw"),
				     $factory->getLabel("domainField"),
				     $defaultPage
				     );

		$mapto_field = $factory->getTextField("maptoField", $mapto, "rw");

		$block->addFormField(
				     $mapto_field,
				     $factory->getLabel("maptoField"),
				     $defaultPage
				     );

		if (isset($get_form_data['_TARGET'])) {
			$target = $factory->getTextField('_TARGET', $get_form_data['_TARGET'], '');
			$block->addFormField(
					     $target,
					     "",
					     $defaultPage
					     );
		}

		// Add the buttons
		$block->addButton($factory->getSaveButton("/email/secondarymx"));
		$block->addButton($factory->getCancelButton("/email/emailsettings?mx#tabs-3"));

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