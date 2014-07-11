<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dnsmanager extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /dns/dnsmanager.
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
		$i18n = new I18n("base-dns", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not siteDNS? Bye, bye!
		if (!$Capabilities->getAllowed('siteDNS')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
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
		$required_keys = array(	"default_refresh", "default_retry", "default_expire", "default_ttl", "responses_per_second", "window");
    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("");
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
			$attributes['commit'] = time();

	  		// Actual submit to CODB:
			$cceClient->setObject("System", $attributes, "DNS");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// Replace the CODB obtained values in our Form with the one we just posted to CCE:
			$dns = $form_data;
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", "/dns/dnsmanager");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$page_module = 'base_sysmanage';

		// get DNS
		$dns = $cceClient->getObject("System", array(), "DNS");

		//
		// -- Button-Header:
		//

		$p_button = $factory->getButton("/dns/primarydns", 'primary_service_button', "DEMO-OVERRIDE");
		$s_button = $factory->getButton("/dns/secondarydns", 'secondary_service_button', "DEMO-OVERRIDE");
		$buttonContainer = $factory->getButtonContainer("", array($p_button, $s_button));

		//
		// -- Initialize PagedBlock:
		//

		$defaultPage = "basic";

		$block =& $factory->getPagedBlock("modifyDNS", array($defaultPage, "advanced", "zone_format_tab", "auto_dns"));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Basic Tab
		//

		// Enable DNS:
		$block->addFormField(
			$factory->getBoolean("enabled", $dns["enabled"]),
			$factory->getLabel("enabled"),
			$defaultPage
		);


		//
		//--- Advanced Tab
		//

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("soa_defaults", ""),
		        $factory->getLabel("soa_defaults", false),
		        "advanced"
		        );

		$admin_email = $factory->getEmailAddress("admin_email", $dns["admin_email"]);
		$admin_email->setOptional(true);
		$block->addFormField(
		  $admin_email,
		  $factory->getLabel("admin_email"),
		  "advanced"
		);

		$default_refresh = $factory->getInteger("default_refresh", $dns["default_refresh"], 1, "4096000");
		$default_refresh->setWidth(5);
		$default_refresh->showBounds(1);
		$block->addFormField(
			$default_refresh,
			$factory->getLabel("default_refresh"),
			"advanced"
		);

		$default_retry = $factory->getInteger("default_retry", $dns["default_retry"], 1, "4096000");
		$default_retry->setWidth(5);
		$default_retry->showBounds(1);
		$block->addFormField(
			$default_retry,
			$factory->getLabel("default_retry"),
			"advanced"
		);

		$default_expire = $factory->getInteger("default_expire", $dns["default_expire"], 1, "4096000");
		$default_expire->setWidth(5);
		$default_expire->showBounds(1);
		$block->addFormField(
			$default_expire,
			$factory->getLabel("default_expire"),
			"advanced"
		);

		$default_ttl = $factory->getInteger("default_ttl", $dns["default_ttl"], 1, "4096000");
		$default_ttl->setWidth(5);
		$default_ttl->showBounds(1);
		$block->addFormField(
			$default_ttl,
			$factory->getLabel("default_ttl"),
			"advanced"
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("global_settings", ""),
		        $factory->getLabel("global_settings", false),
		        "advanced"
		        );

		$block->addFormField(
			$factory->getBoolean("query", $dns["query"]),
			$factory->getLabel("query"),
			"advanced"
		);

		$block->addFormField(
			$factory->getBoolean("query_all_allowed", $dns["query_all_allowed"]),
			$factory->getLabel("query_all_allowed"),
			"advanced"
		);

		$query_inetaddr = $factory->getInetAddressList("query_inetaddr", $dns["query_inetaddr"]);
		$query_inetaddr->setOptional(true);
		$block->addFormField(
			$query_inetaddr,
			$factory->getLabel("query_inetaddr"),
			"advanced"
		);

		$block->addFormField(
			$factory->getBoolean("caching", $dns["caching"]),
			$factory->getLabel("caching"),
			"advanced"
		);

		$block->addFormField(
			$factory->getBoolean("caching_all_allowed", $dns["caching_all_allowed"]),
			$factory->getLabel("caching_all_allowed"),
			"advanced"
		);

		$recursion_inetaddr = $factory->getInetAddressList("recursion_inetaddr", $dns["recursion_inetaddr"]);
		$recursion_inetaddr->setOptional(true);
		$block->addFormField(
			$recursion_inetaddr,
			$factory->getLabel("recursion_inetaddr"),
			"advanced"
		);

		$forwarders = $factory->getIpAddressList("forwarders", $dns["forwarders"]);
		$forwarders->setOptional(true);
		$block->addFormField(
			$forwarders,
			$factory->getLabel("forwarders"),
			"advanced"
		);

		$zone_xfer_ipaddr = $factory->getIpAddressList("zone_xfer_ipaddr", $dns["zone_xfer_ipaddr"]);
		$zone_xfer_ipaddr->setOptional(true);
		$block->addFormField(
			$zone_xfer_ipaddr,
			$factory->getLabel("zone_xfer_ipaddr"),
			"advanced"
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("rate_limits", ""),
		        $factory->getLabel("rate_limits", false),
		        "advanced"
		        );

		$block->addFormField(
			$factory->getBoolean("rate_limits_enabled", $dns["rate_limits_enabled"]),
			$factory->getLabel("rate_limits_enabled"),
			"advanced"
		);

		$responses_per_second = $factory->getInteger("responses_per_second", $dns["responses_per_second"], 1, "1024");
		$responses_per_second->setWidth(4);
		$responses_per_second->showBounds(1);
		$block->addFormField(
			$responses_per_second,
			$factory->getLabel("responses_per_second"),
			"advanced"
		);

		$window = $factory->getInteger("window", $dns["window"], 1, "128");
		$window->setWidth(3);
		$window->showBounds(1);
		$block->addFormField(
			$window,
			$factory->getLabel("window"),
			"advanced"
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("dns_logging", ""),
		        $factory->getLabel("dns_logging", false),
		        "advanced"
		        );

		$block->addFormField(
			$factory->getBoolean("enable_dns_logging", $dns["enable_dns_logging"]),
			$factory->getLabel("enable_dns_logging"),
			"advanced"
		);

		//
		//-- Zone Format:
		//

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("zone_format_settings_divider", ""),
		        $factory->getLabel("zone_format_settings_divider", false),
		        "zone_format_tab"
		        );

		// Note: in 5106R/5107R/5108R we disabled 'DION','OCN-JT','USER'. Hence the next formfield
		// doesn't have to be a read-only multichoice. Hence I made it a read-only getTextField:
		//$zone_format_array = array('RFC2317','DION','OCN-JT','USER');
		$zone_format = $factory->getTextField("zone_format", $dns["zone_format"], "r");
		$zone_format->setOptional(true);
		$block->addFormField(
		  $zone_format,
		  $factory->getLabel("zone_format"),
		  "zone_format_tab"
		);

		//
		//-- Auto-DNS
		//

		$auto_a = $factory->getTextList("auto_a", $dns["auto_a"]);
		$auto_a->setOptional(TRUE);
		$auto_a->setType("alphanum_plus_multiline");
		$block->addFormField(
		        $auto_a,
		        $factory->getLabel("auto_a"),
		        "auto_dns"
		        );

		$auto_mx = $factory->getTextField("auto_mx", $dns["auto_mx"], "rw");
		$auto_mx->setOptional(true);
		$auto_mx->setType("alphanum_plus");
		$block->addFormField(
		  $auto_mx,
		  $factory->getLabel("auto_mx"),
		  "auto_dns"
		);


		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/dns/dnsmanager"));

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $buttonContainer->toHtml();
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