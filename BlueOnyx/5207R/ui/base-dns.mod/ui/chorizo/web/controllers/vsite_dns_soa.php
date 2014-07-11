<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vsite_dns_soa extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /dns/vsite_dns_soa.
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

		$iam = "/dns/vsite_dns_soa?group=$group";
		$parent = "/dns/vsiteDNS?group=$group";

		// Not siteDNS? Bye, bye!
		if (!$Capabilities->getAllowed('siteDNS')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#3");
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

    	$ignore_attributes = array("BlueOnyx_Info_Text", "network_soa", "domain_soa", "OID", "netauth", "domauth");

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

		if (isset($form_data['domauth'])) {
			$domauth = $form_data['domauth'];
		}
		if (isset($form_data['netauth'])) {
			$netauth = $form_data['netauth'];
		}

		if (isset($form_data['OID'])) {
			$_TARGET = $form_data['OID'];
			// Get the Object in question:
			$dns_SOA = $cceClient->get($_TARGET);
			// Verify if it's an SOA record:
			if (($dns_SOA['CLASS'] != "DnsSOA") && (($dns_SOA['domainname'] != $domauth) || ($dns_SOA['network'] != $netauth))) { 
				// This is not what we're looking for! Stop poking around!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
			}			
		}

		$domauth = '';
		$netauth = '';
		$records_title_separator = '   -   ';

		$nm_to_dec = array(
			"0.0.0.0"   => "0",
			"128.0.0.0" => "1", "255.128.0.0" => "9",  "255.255.128.0" => "17", "255.255.255.128" => "25",
			"192.0.0.0" => "2", "255.192.0.0" => "10", "255.255.192.0" => "18", "255.255.255.192" => "26",
			"224.0.0.0" => "3", "255.224.0.0" => "11", "255.255.224.0" => "19", "255.255.255.224" => "27",
			"240.0.0.0" => "4", "255.240.0.0" => "12", "255.255.240.0" => "20", "255.255.255.240" => "28",
			"248.0.0.0" => "5", "255.248.0.0" => "13", "255.255.248.0" => "21", "255.255.255.248" => "29",
			"252.0.0.0" => "6", "255.252.0.0" => "14", "255.255.252.0" => "22", "255.255.255.252" => "30",
			"254.0.0.0" => "7", "255.254.0.0" => "15", "255.255.248.0" => "23", "255.255.255.254" => "31",
			"255.0.0.0" => "8", "255.255.0.0" => "16", "255.255.255.0" => "24", "255.255.255.255" => "32" );

		$dec_to_nm = array_flip($nm_to_dec);

		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['domauth'])) {
			$ret_url = $parent.'&domauth='.urlencode(urldecode($get_form_data['domauth']));
			$domauth = urldecode($get_form_data['domauth']);
		}
		elseif (isset($get_form_data['netauth'])) {
			$ret_url = $parent.'&netauth='.urlencode(urldecode($get_form_data['netauth']));
			$netauth = urldecode($get_form_data['netauth']);
		}
		else {
 			$ret_url = $parent;
		}

		//
        //--- Extended Security Check:
        //
		$allAliases = array();
        $vsite = $cceClient->getObject('Vsite', array('name' => $group));
        $vsite_dns = $cceClient->getObject('Vsite', array('name' => $group), "DNS");
		if ($vsite_dns["domains"] != "") {
			$allAliases = $cceClient->scalar_to_array($vsite_dns["domains"]);
		}
		else {
			$allAliases = array();
		}

        // Now make sure that $domauth is among the managed domains:
        if (!in_array($domauth, $allAliases)) {
			// Trying to mess with us? No, thanks!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#nope");
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
			$cceClient->set($_TARGET, "", $attributes);

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// Also commit the changes to restart the DNS server:
			$update['commit'] = time();
			$cceClient->setObject("System", $update, "DNS");

			// No errors during submit? Redirect to previous page:
			if (count($errors) == "0") {
				$cceClient->bye();
				$serverScriptHelper->destructor();
				header("location: $ret_url");
				exit;
			}

			// Replace the CODB obtained values in our Form with the one we just posted to CCE:
			$dns_SOA = $form_data;
		}

		//
		//-- Page Logic:
		//

		// mapping: lists a form field name to an object attribute.
		$mapping = array (
			"primary_dns" => "primary_dns",
			"secondary_dns" => "secondary_dns",
			"domain_admin" => "domain_admin",
			"refresh" => "refresh",
			"retry" => "retry",
			"expire" => "expire",
			"ttl" => "ttl"
		  );

		// handler:
		if ((!isset($_TARGET)) && (isset($get_form_data['_LOAD']))) {
			$_TARGET = $get_form_data['_LOAD'];
		}
		elseif ($_TARGET) {
			// We have a $_TARGET, nothing to do. This is just for readability.
		}
		else {
			// We have no $_TARGET Object ID? Then you should not be here!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#3");
		}

		// Get the Object in question:
		$dns_SOA = $cceClient->get($_TARGET);

		// Verify if it's an SOA record:
		if (($dns_SOA['CLASS'] != "DnsSOA") && (($dns_SOA['domainname'] != $domauth) || ($dns_SOA['network'] != $netauth))) { 
			// This is not what we're looking for! Stop poking around!
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#4");
		}

		// Actually default
		$title_authority = '';
		if (isset($domauth)) {
			$title_authority = $domauth;
		}
		if ($title_authority == '') {
			$title_authority = urldecode($netauth);
		}
		if (($domauth == '') && ($netauth == '')) { 
			$domauth = $default_domauth;
			if ($title_authority == '') {
				$title_authority = $default_domauth;
			}
			$netauth = $default_netauth; 
			if ($title_authority == '') {
				$title_authority = urldecode($default_netauth);
			}
		}
		//if ($title_authority != '') { 
		if (!isset($title_authority)) { 
			$title_members = preg_split('/\//', $title_authority);
			$title_authority = $records_title_separator . $title_members[0];
			if ($title_members[1] != '') {
				$title_authority .= '/' . $dec_to_nm[$title_members[1]];
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		if ($domauth != "") {
			$url_suffix = "&domauth=" . $domauth;
		}
		if ($netauth != "") {
			$url_suffix = "&netauth=" . $netauth;
		}		
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", $iam . "&_LOAD=" . $_TARGET . $url_suffix);
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_siteservices');
		$BxPage->setVerticalMenuChild('base_dns_vsite');
		$page_module = 'base_sitemanage';

		$defaultPage = "basic";

		$block =& $factory->getPagedBlock("modify_soa", array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
//		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Basic Tab
		//
	
		// Domain Authority:
		if (!$dns_SOA['domainname'] == "") {
			$block->addFormField(
				$factory->getTextField('domain_soa', $dns_SOA['domainname'], 'r'),
				$factory->getLabel("domain_soa"), 
				$defaultPage
			);
		}
		// Network Authority:
		else {
			$block->addFormField(
				$factory->getTextField('network_soa', $dns_SOA['ipaddr'] .'/'. $dns_SOA['netmask'], 'r'),
				$factory->getLabel("network_soa"), 
				$defaultPage
			);
		}

		// Primary DNS:
		$pri_dns = $factory->getDomainName('primary_dns', $dns_SOA['primary_dns'], 'rw');
		$pri_dns->setOptional(TRUE);
		$block->addFormField(
			$pri_dns,
			$factory->getLabel("primary_dns"), 
			$defaultPage
		);

		// Secondary DNS:
		$sec_dns = $factory->getDomainNameList('secondary_dns', $dns_SOA['secondary_dns'], 'rw');
		$sec_dns->setOptional(TRUE);
		$block->addFormField(
			$sec_dns,
			$factory->getLabel("secondary_dns"), 
			$defaultPage
		);

		// Admin Email address:
		$adm_email = $factory->getEmailAddress('domain_admin', $dns_SOA['domain_admin'], 'rw');
		$adm_email->setOptional(TRUE);
		$block->addFormField(
			$adm_email,
			$factory->getLabel("domain_admin"), 
			$defaultPage
		);

		// Refresh:
		$refresh = $factory->getInteger("refresh", $dns_SOA["refresh"], 1, "4096000");
        $refresh->setWidth(5);
        $refresh->showBounds(1);
		$block->addFormField(
			$refresh,
			$factory->getLabel("refresh"), 
			$defaultPage
		);

		// Retry:
		$retry = $factory->getInteger("retry", $dns_SOA["retry"], 1, "4096000");
        $retry->setWidth(5);
        $retry->showBounds(1);
		$block->addFormField(
			$retry,
			$factory->getLabel("retry"), 
			$defaultPage
		);

		// Expire:
		$expire = $factory->getInteger("expire", $dns_SOA["expire"], 1, "4096000");
        $expire->setWidth(5);
        $expire->showBounds(1);
		$block->addFormField(
			$expire,
			$factory->getLabel("expire"), 
			$defaultPage
		);

		// ttl:
		$ttl = $factory->getInteger("ttl", $dns_SOA["ttl"], 1, "4096000");
        $ttl->setWidth(5);
        $ttl->showBounds(1);
		$block->addFormField(
			$ttl,
			$factory->getLabel("ttl"), 
			$defaultPage
		);

		// We silently pass along the OID of the Object:
		$block->addFormField(
			$factory->getTextField('OID', $_TARGET, ''),
			$factory->getLabel("OID"), 
			$defaultPage
		);

		// We silently pass along the domauth of the Object:
		$block->addFormField(
			$factory->getTextField('domauth', $domauth, ''),
			$factory->getLabel("domauth"), 
			$defaultPage
		);

		// We silently pass along the netauth of the Object:
		$block->addFormField(
			$factory->getTextField('netauth', $netauth, ''),
			$factory->getLabel("netauth"), 
			$defaultPage
		);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton($ret_url));

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