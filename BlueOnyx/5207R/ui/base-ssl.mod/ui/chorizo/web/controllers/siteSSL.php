<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SiteSSL extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /ssl/siteSSL.
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
		$i18n = new I18n("base-ssl", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		//
		//--- Get CODB-Object of interest: 
		//

		// We get our $get_form_data early, as this page handles both Vsite and AdmServ SSL certs.
		// Depending on what we modify, we have the "group" information on the URL string - or not.

		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['group'])) {
			$siteGroup = $get_form_data['group'];
		}
		if ($get_form_data['group']) {
			// Extra check to make sure a siteAdmin isn't messing with the URL param for "group"
			// and then tries to get access to another Vsites certs:

			if ((!$Capabilities->getAllowed('adminUser')) && 
				(!$Capabilities->getAllowed('siteAdmin')) && 
				(!$Capabilities->getAllowed('manageSite')) && 
				(($user['site'] != $serverScriptHelper->loginUser['site']) && $Capabilities->getAllowed('siteAdmin')) &&
				(($vsiteObj['createdUser'] != $loginName) && $Capabilities->getAllowed('manageSite'))
				) {

				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#ohcomeon");
			}

		    $CODBDATA =& $cceClient->getObject('Vsite', array('name' => $get_form_data['group']), 'SSL');
		    if ($CODBDATA == "") {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#donthavethat");
		    }
		    $CODBDATA['group'] = $siteGroup;
		}
		else {
		    $CODBDATA =& $cceClient->getObject('System', array(), 'SSL');
		    $CODBDATA['group'] = "";
		}

		// Only 'serverSSL', 'manageSite' and 'siteAdmin' should be here
		if (!$Capabilities->getAllowed('serverSSL') && !$Capabilities->getAllowed('manageSite') && 
			!($Capabilities->getAllowed('siteAdmin') && $CODBDATA['group'] == $user['site'])) {
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

		//
		// -- Export Certs:
		//

	    if (isset($get_form_data['action'])) {
			if ($get_form_data['action'] == "export") {

				$cert = '';
				if ($Capabilities->getAllowed('adminUser')) {
					$runas = "root";
				}
				else {
					$runas = $loginName;
				}

				// Extra check to make sure a siteAdmin isn't messing with the URL param for "group"
				// and then tries to get access to another Vsites certs:
				if (!$Capabilities->getAllowed('manageSite')) {
					if (($Capabilities->getAllowed('siteAdmin')) && ($get_form_data['group'] != $Capabilities->loginUser['site'])) {
						// Nice people say goodbye, or CCEd waits forever:
						$cceClient->bye();
						$serverScriptHelper->destructor();
						Log403Error("/gui/Forbidden403#ohcomeon-seriously");
					}
				}

				if ($serverScriptHelper->shell("/usr/sausalito/sbin/ssl_get.pl " . $get_form_data['type'] . " " . $get_form_data['group'] . "", $cert, $runas, $sessionId) != 0) {
				    // Command failed - Raise an error:
				    $my_errors[] = '<div class="alert dismissible alert_red"><img width="28" height="28" src="/.adm/images/icons/small/white/alert.png"><strong>' . $i18n->getHtml("[[base-ssl.sslGetFailed]]") . '</strong></div>';
				}        
				else {
				    // Prepare download:
				    if ($get_form_data['type'] == 'cert') {
						$filename = 'ssl-certificate.txt';
				    }
				    else if ($get_form_data['type'] == 'csr') {
						$filename = 'signing-request.txt';
				    }

				    // Force download:
					$this->load->helper('download');
					force_download($filename, $cert);
				}
			}
		}

		//
		//--- Handle form validation:
		//

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
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
			// We have no errors. We submit to CODB.
		    if ($get_form_data['group'] != '') {
		        list($oid) = $cceClient->find('Vsite', array('name' => $get_form_data['group']));
		        $cceClient->set($oid, 'SSL', array('enabled' => $attributes['enabled']));
		    }

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("Location: /ssl/siteSSL?group=" . $get_form_data['group']);
				exit;
			}
			else {
				$CODBDATA = $attributes;
				$CODBDATA['group'] = $get_form_data['group'];
			}
		}

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// Pass group along in URL's:
		$urlAppendix = "";
		if (isset($siteGroup)) {
			$urlAppendix = "?group=" . $siteGroup;
		}

		//
		//-- Own page logic:
		//

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-ssl", "/ssl/siteSSL$urlAppendix");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:

		if (isset($siteGroup)) {
			// We are in "Site Management" / "SSL":
			$BxPage->setVerticalMenu('base_sitemanage');
			$BxPage->setVerticalMenuChild('base_ssl');
			$page_module = 'base_sitemanage';
		}
		else {
			// We are in "Security" / "SSL"
			$BxPage->setVerticalMenu('base_security');
			$BxPage->setVerticalMenuChild('base_admin_ssl');
			$page_module = 'base_sysmanage';
		}

		//
		// -- Add the buttons to create/import/export a certificate
		//

		// add buttons to create/import/export a certificate
		$create =& $factory->getButton('/ssl/createCert?group=' . $CODBDATA['group'], 'createCert', 'DEMO-OVERRIDE');
		$request =& $factory->getButton('/ssl/createCert?group=' . $CODBDATA['group'] . '&type=csr', 'request', 'DEMO-OVERRIDE');
		$ca_certs =& $factory->getButton('/ssl/caManager?group=' . $CODBDATA['group'], 'manageCAs', 'DEMO-OVERRIDE');
		$import =& $factory->getButton('/ssl/uploadCert?group=' . $CODBDATA['group'], 'import', 'DEMO-OVERRIDE');
		$exportButton =& $factory->getButton('/ssl/siteSSL?group=' . $CODBDATA['group'] . '&type=cert&action=export', 'export');

		// Set export button to TRUE by default:
		$exportButton->setDisabled(TRUE);

		if ($CODBDATA['group']) {
		    list($oid) = $cceClient->find('Vsite', array('name' => $CODBDATA['group']));
		    $vsite_info = $cceClient->get($oid);
		    $fqdn = $vsite_info['fqdn'];
		}
		else {
		    $fqdn = '[[base-ssl.serverDesktop]]';
		}

	    // Check if certificate and key are present:
	    if ($fqdn != '[[base-ssl.serverDesktop]]') {
	    	$file = $vsite_info['basedir'] . '/certs/certificate';
	    }
	    else {
	    	$file = '/etc/admserv/certs/certificate';
	    }
	    $cmd = '/bin/cat ' . $file . '|/usr/bin/wc -l';
		$serverScriptHelper->shell($cmd, $cert_cmd_return, 'root', $sessionId);
		$certificate_present = rtrim($cert_cmd_return);

	    if ($fqdn != '[[base-ssl.serverDesktop]]') {
	    	$file = $vsite_info['basedir'] . '/certs/key';
	    }
	    else {
	    	$file = '/etc/admserv/certs/key';
	    }
	    $cmd = '/bin/cat ' . $file . '|/usr/bin/wc -l';
		$serverScriptHelper->shell($cmd, $key_cmd_return, 'root', $sessionId);
		$key_present = rtrim($key_cmd_return);

	    // If we have an expiration date, a key and a cert, then we allow the cert to be exported:
		if (($CODBDATA['expires'] != "") && ($certificate_present > 0) && ($key_present > 0)) {
		    $exportButton->setDisabled(FALSE);
		}

		// Add #1 Button-Container:
		$buttonContainer_a = $factory->getButtonContainer("", array($create, $request, $ca_certs));

		// Add #2 Button-Container:
		$buttonContainer_b = $factory->getButtonContainer("", array($import, $exportButton));

		//
		// -- Add PagedBlock with Cert Info:
		//

		$defaultPage = "basic";
		$block =& $factory->getPagedBlock("sslCertInfo", array($defaultPage));
		$block->setCurrentLabel($factory->getLabel('sslCertInfo', false, array('fqdn' => $fqdn)));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Tab: basic
		//

		// Show enabled/disabled checkbox as read only if on the admin server and the user is the adminUser:
		if (($CODBDATA['group'] == '') && ($Capabilities->getAllowed('adminUser'))) {
			$access = "";
		}
		elseif (($CODBDATA['group'] != '') && ($Capabilities->getAllowed('manageSite'))) {
			$access = "rw";
		}
		else {
			$access = "r";
		}

        //
        //-- Reseller: Can the reseller that owns this Vsite modify this?
        //
		if ($CODBDATA['group']) {
	        $VsiteOwnerObj = $cceClient->getObject("User", array("name" => $vsite_info['createdUser']));
	        if ($VsiteOwnerObj['name'] != "admin") {
	            $resellerCaps = $cceClient->scalar_to_array($VsiteOwnerObj['capabilities']);
	            if (!in_array('resellerSSL', $resellerCaps)) {
	                $CODBDATA["enabled"] = '0';
	                $access = 'r';
	            }
	        }
	    }

	    // If we don't have a certificate or key, then we do not allow to enable:
	    if (($certificate_present == 0) || ($key_present == 0)) {
	    	$CODBDATA['enabled'] = '0';
	    	$access = 'r';
	    }
	    $block->addFormField(
	        $factory->getBoolean('enabled', $CODBDATA['enabled'], $access),
	        $factory->getLabel('enabled'),
	        $defaultPage
	        );

	    // If we have an expiration date, a key and a cert, then we show the cert information:
		if (($CODBDATA['expires'] != "") && ($certificate_present > 0) && ($key_present > 0)) {
		    $cert_sections = array(
		                    'location' => array('city', 'state', 'country'), 
		                    'orgInfo' => array('orgName', 'orgUnit'),
		                    'otherInfo' => array('email'));

		    foreach ($cert_sections as $section => $fields) {

				// Add divider:
				$block->addFormField(
				        $factory->addBXDivider($section, ""),
				        $factory->getLabel($section, false),
				        $defaultPage
				        );

		        foreach ($fields as $var) {
		            $value = $CODBDATA[$var];
		            if ($var == 'country') {
		                $value = $i18n->get($CODBDATA[$var]);
		                if (preg_match('/^Project-Id-Version.*/', $value)) {
		                	$value = "";
		                }
		            }
		                
		            $block->addFormField(
		                $factory->getTextField($var, $value, 'r'),
		                $factory->getLabel($var),
		                $defaultPage
		                );
		        }
		    }

		    // Special case expires field
		    $block->addFormField(
		        $factory->getTimeStamp('expires', strtotime($CODBDATA['expires']), 'date', 'r'),
		        $factory->getLabel('expires'),
		        $defaultPage
		        );

		}
		else {
			// We don't have any Cert info:
			$my_TEXT = $i18n->interpolate('[[base-ssl.noCertInfo]]');
			$cert_info_text = $factory->getTextField("_", $my_TEXT, 'r');
			$cert_info_text->setLabelType("nolabel");
			$block->addFormField(
			    $cert_info_text,
			    $factory->getLabel(" "),
			    $defaultPage
			    );
		}

		//
		//--- Add the Save/Cancel buttons, but only in Vsite management:
		//
		if (($CODBDATA['group'] != '') && (($Capabilities->getAllowed('adminUser')) || ($Capabilities->getAllowed('manageSite')))) {
			$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
			$block->addButton($factory->getCancelButton("/ssl/siteSSL?group=" . $CODBDATA['group']));
		}

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $buttonContainer_a->toHtml();
		$page_body[] = $buttonContainer_b->toHtml();
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