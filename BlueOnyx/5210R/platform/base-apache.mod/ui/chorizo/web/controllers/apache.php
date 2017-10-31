<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Apache extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /apache/apache.
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

        $i18n = new I18n("base-apache", $CI->BX_SESSION['loginUser']['localePreference']); // really? base-vsite??
        $system = $CI->getSystem();
		$user = $CI->BX_SESSION['loginUser'];

        // Not 'serverHttpd'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('serverHttpd')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
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
		$required_keys = array("maxClients", "minSpare", 'maxSpare');

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

			// get web
			$web = $CI->cceClient->getObject("System", array(), "Web");

			// min spares needs to be less than or equal to max spares
			if ($form_data['minSpare'] > $form_data['maxSpare']) {
				$my_errors[] = ErrorMessage($i18n->get("[[base-apache.MinMaxError]]"));
			}

			// maxClientsField must be smaller than maxSpareField:
			if ($form_data['maxClients'] < $form_data['maxSpare']) {
				$my_errors[] = ErrorMessage($i18n->get("[[base-apache.ClientMaxError]]"));
			}

			// Check if the HTTP/SSL ports are in use:
			$httpPortField = $form_data['httpPort'];
			$sslPortField = $form_data['sslPort'];
			$HTTPportInUse = `/bin/netstat -tupan|/bin/grep LISTEN|awk '{print \$4}'|cut -d : -f2|egrep -v '^[[:space:]]*\$'| egrep -E '^$httpPortField\$'|wc -l`;
			$SSLportInUse = `/bin/netstat -tupan|/bin/grep LISTEN|awk '{print \$4}'|cut -d : -f2|egrep -v '^[[:space:]]*\$'| egrep -E '^$sslPortField\$'|wc -l`;

	 		$HTTPportInUse = preg_replace('/\n$/','',$HTTPportInUse); 
	 		$SSLportInUse = preg_replace('/\n$/','',$SSLportInUse); 

			if (($HTTPportInUse != "0") && ($web['httpPort'] != $httpPortField)) {
				$my_errors[] = ErrorMessage($i18n->get("[[base-apache.httpPortInUse]]"));
			}
			if (($SSLportInUse != "0") && ($web['sslPort'] != $sslPortField)) {
				$my_errors[] = ErrorMessage($i18n->get("[[base-apache.SSLportInUse]]"));
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

			// Any additional parameters that we need to pass on?
			$attributes['Writeback_BlueOnyx_Conf'] = time();

	  		// Actual submit to CODB:
			$CI->cceClient->setObject("System", $attributes, "Web");

			if (($web['httpPort'] != $httpPortField) || ($web['sslPort'] != $sslPortField) || ($web['HSTS'] != $attributes['HSTS'])) {
			    // In case the HTTP-port, SSL-port or HSTS settings are changed, we also need to update all 
			    // VHost containers with the new port information. Which is a bit messy. But
			    // We can simply do so by running /usr/sausalito/sbin/SSL_fixer.pl. And as that
			    // may take a while to finish, we simply shoot it into the background via fork():
				$nfk = '';
			    $ret = $CI->serverScriptHelper->fork("/usr/sausalito/sbin/SSL_fixer.pl", $nfk, 'root', $CI->BX_SESSION['sessionId']);
			}

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $CI->cceClient->errors();
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
		$factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-apache", "/apache/apache");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$page_module = 'base_sysmanage';

		// get web
		$web = $CI->cceClient->getObject("System", array(), "Web");

		$defaultPage = "basicSettingsTab";

		$block =& $factory->getPagedBlock("apacheSettings", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setDefaultPage($defaultPage);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("DIVIDER_TOP", ""),
		        $factory->getLabel("DIVIDER_TOP", false),
		        $defaultPage
		        );			

		$block->addFormField(
			$factory->getBoolean("hostnameLookups", $web["hostnameLookups"]),
			$factory->getLabel("hostnameLookupsField")
		);

		// HTTP Port:
		$httpPortField = $factory->getInteger("httpPort", $web["httpPort"], "80", "65535");
		$httpPortField->setWidth(5);
		$httpPortField->showBounds(1);
		$block->addFormField(
			$httpPortField,
			$factory->getLabel("httpPortField")
		);

		// SSL Port:
		$sslPortField = $factory->getInteger("sslPort", $web["sslPort"], "443", "65535");
		$sslPortField->setWidth(5);
		$sslPortField->showBounds(1);
		$block->addFormField(
			$sslPortField,
			$factory->getLabel("sslPortField")
		);

		// Checkbox for 'Header add Strict-Transport-Security': 
		$block->addFormField( 
		        $factory->getBoolean("HSTS", $web["HSTS"]), 
		        $factory->getLabel("HSTS") 
		); 

		$max_client = $factory->getInteger("maxClients", $web["maxClients"], 1, $web["maxClientsAdvised"]);
		$max_client->setWidth(5);
		$max_client->showBounds(1);

		$block->addFormField(
			$max_client,
			$factory->getLabel("maxClientsField")
		);


		$min = $factory->getInteger("minSpare", $web["minSpare"], 1, $web["minSpareAdvised"]);
	        $min->setWidth(5);
	        $min->showBounds(1);

		$block->addFormField(
			$min,
			$factory->getLabel("minSpareField")
		);

		$max_spare = $factory->getInteger("maxSpare", $web["maxSpare"], 1, $web["maxSpareAdvised"]);
		$max_spare->setWidth(5);
		$max_spare->showBounds(1);

		$block->addFormField(
			$max_spare,
			$factory->getLabel("maxSpareField")
		);

		// BlueOnyx.conf modification stuff:

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("DIVIDER_EXPLANATION", ""),
		        $factory->getLabel("DIVIDER_EXPLANATION", false),
		        $defaultPage
		        );				

		$my_TEXT = $i18n->getClean("[[base-apache.BlueOnyx_Info_Text]]");
		$infotext = $factory->getTextField("BlueOnyx_Info_Text", $my_TEXT, 'r');
		$infotext->setLabelType("nolabel");
		$block->addFormField(
		  $infotext,
		  $factory->getLabel(" ", false)
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("DIVIDER_OPTIONS", ""),
		        $factory->getLabel("DIVIDER_OPTIONS", false),
		        $defaultPage
		        );		

		$block->addFormField(
			$factory->getBoolean("Options_All", $web["Options_All"]),
			$factory->getLabel("Options_AllField")
		);
		$block->addFormField(
			$factory->getBoolean("Options_FollowSymLinks", $web["Options_FollowSymLinks"]),
			$factory->getLabel("Options_FollowSymLinksField")
		);
		$block->addFormField(
			$factory->getBoolean("Options_Includes", $web["Options_Includes"]),
			$factory->getLabel("Options_IncludesField")
		);
		$block->addFormField(
			$factory->getBoolean("Options_Indexes", $web["Options_Indexes"]),
			$factory->getLabel("Options_IndexesField")
		);
		$block->addFormField(
			$factory->getBoolean("Options_MultiViews", $web["Options_MultiViews"]),
			$factory->getLabel("Options_MultiViewsField")
		);
		$block->addFormField(
			$factory->getBoolean("Options_SymLinksIfOwnerMatch", $web["Options_SymLinksIfOwnerMatch"]),
			$factory->getLabel("Options_SymLinksIfOwnerMatchField")
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("DIVIDER_ALLOWOVERRIDE", ""),
		        $factory->getLabel("DIVIDER_ALLOWOVERRIDE", false),
		        $defaultPage
		        );

		$block->addFormField(
			$factory->getBoolean("AllowOverride_All", $web["AllowOverride_All"]),
			$factory->getLabel("AllowOverride_AllField")
		);
		$block->addFormField(
			$factory->getBoolean("AllowOverride_AuthConfig", $web["AllowOverride_AuthConfig"]),
			$factory->getLabel("AllowOverride_AuthConfigField")
		);
		$block->addFormField(
			$factory->getBoolean("AllowOverride_FileInfo", $web["AllowOverride_FileInfo"]),
			$factory->getLabel("AllowOverride_FileInfoField")
		);
		$block->addFormField(
			$factory->getBoolean("AllowOverride_Indexes", $web["AllowOverride_Indexes"]),
			$factory->getLabel("AllowOverride_IndexesField")
		);
		$block->addFormField(
			$factory->getBoolean("AllowOverride_Limit", $web["AllowOverride_Limit"]),
			$factory->getLabel("AllowOverride_LimitField")
		);
		$block->addFormField(
			$factory->getBoolean("AllowOverride_Options", $web["AllowOverride_Options"]),
			$factory->getLabel("AllowOverride_OptionsField")
		);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/apache/apache"));

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