<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Secondarydnsmod extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /dns/secondarydnsmod.
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

		$iam = '/dns/secondarydnsmod';
		$parent = '/dns/secondarydns';

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
		$required_keys = array();

    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();

    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text", "_TARGET");

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

		// Find out the TYPE of entry we're dealing with:
		if (isset($get_form_data['TYPE'])) {
			$TYPE = $get_form_data['TYPE'];
		}
		if (!isset($TYPE)) {
			$TYPE = $form_data['TYPE'];
		}

		if ((!isset($TYPE)) && (!isset($get_form_data['_RTARGET']))) {
			// We *still* have no $TYPE set? Then you should not be here!
			// Exception: We want to delete an object specified via _RTARGET
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403#2");
		}

		// Check the $_TARGET to see if this is a new entry or if it contains the OID of an object we edit:
		if ((!isset($_TARGET)) && (isset($form_data['_TARGET']))) {
			// We have form data of a $_TARGET OID:
			$_TARGET =  $form_data['_TARGET'];
		}
		else {
			// We don't? Assume it's a new object:
			$_TARGET = "NEW";
		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.
			if ($_TARGET == "NEW") {
				// Create a new Object:
				$cceClient->create("DnsSlaveZone", $attributes);
			}
			else {
				// We update an existing Object:
				$cceClient->set($_TARGET, "", $attributes);
			}

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
				header("location: $parent");
				exit;
			}
		}

		//
		//-- Page Logic:
		//

		$nm_to_dec = array(
		  "0.0.0.0"   => "0",
		  "128.0.0.0" => "1",	"255.128.0.0" => "9",	"255.255.128.0" => "17",	"255.255.255.128" => "25",
		  "192.0.0.0" => "2", 	"255.192.0.0" => "10",	"255.255.192.0" => "18",	"255.255.255.192" => "26",
		  "224.0.0.0" => "3",	"255.224.0.0" => "11",	"255.255.224.0" => "19",	"255.255.255.224" => "27",
		  "240.0.0.0" => "4",	"255.240.0.0" => "12",	"255.255.240.0" => "20",	"255.255.255.240" => "28",
		  "248.0.0.0" => "5",	"255.248.0.0" => "13",	"255.255.248.0" => "21",	"255.255.255.248" => "29",
		  "252.0.0.0" => "6",	"255.252.0.0" => "14",	"255.255.252.0" => "22",	"255.255.255.252" => "30",
		  "254.0.0.0" => "7",	"255.254.0.0" => "15",	"255.255.248.0" => "23",	"255.255.255.254" => "31",
		  "255.0.0.0" => "8",	"255.255.0.0" => "16",	"255.255.255.0" => "24",	"255.255.255.255" => "32" );

		// Get the Object in question for edit:
		if ((isset($get_form_data['_LOAD'])) && (isset($get_form_data['_TARGET']))) {
			$_TARGET = $get_form_data['_TARGET'];
			$DnsSlaveZone = $cceClient->get($_TARGET);
		}

		// Get the Object in question for the delete action:
		if (isset($get_form_data['_RTARGET'])) {
			$_RTARGET = $get_form_data['_RTARGET'];
			$DnsSlaveZone = $cceClient->get($_RTARGET);
		}

		if (isset($DnsSlaveZone)) {
			// Verify if it's an DnsSlaveZone Object:
			if ($DnsSlaveZone['CLASS'] != "DnsSlaveZone") { 
				// This is not what we're looking for! Stop poking around!
				// Nice people say goodbye, or CCEd waits forever:
				$cceClient->bye();
				$serverScriptHelper->destructor();
				Log403Error("/gui/Forbidden403#3");
			}
			else {

				// Handle the delete action if appropriate:
				if (isset($_RTARGET)) {
					$cceClient->destroy($_RTARGET);

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
						header("location: $parent");
						exit;
					}
				}

				// Pre-populate the formfield strings for presentation:
				if (isset($DnsSlaveZone['ipaddr'])) { 
					$slave_ipaddr = $DnsSlaveZone['ipaddr'];
				}
				if (isset($DnsSlaveZone['domain'])) { 
					$slave_domain = $DnsSlaveZone['domain'];
				}
				if (isset($DnsSlaveZone['netmask'])) { 
					$slave_netmask = $DnsSlaveZone['netmask'];
				}
				if (isset($DnsSlaveZone['masters'])) { 
					$slave_masters = $DnsSlaveZone['masters'];
				}
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		if ($TYPE == "NETWORK") {
			$url_suffix = "&TYPE=NETWORK";
		}
		if ($TYPE == "FORWARD") {
			$url_suffix = "&TYPE=FORWARD";
		}

		$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", $iam . "?_TARGET=" . $_TARGET . $url_suffix);
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$BxPage->setVerticalMenuChild('base_dns');
		$page_module = 'base_sysmanage';

		$defaultPage = "basic";

		if ($_TARGET == "NEW") {
			$title = "create_slave_rec";
		}
		else {
			$title = "modify_slave_rec";
		}

		$block =& $factory->getPagedBlock($title, array($defaultPage));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
//		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- Basic Tab
		//
	
		if ($TYPE == 'NETWORK') {
			// Secondary Network Auth:
			if (!isset($slave_netmask)) { 
				$slave_netmask = '255.255.255.0';
			}
			if (!isset($slave_ipaddr)) { 
				$slave_ipaddr = '';
			}
			if (!isset($slave_masters)) { 
				$slave_masters = '';
			}

			// Slave IP:
			$slave_ip = $factory->getIpAddress('ipaddr', $slave_ipaddr, 'rw');
			$slave_ip->setOptional(FALSE);
			$block->addFormField(
				$slave_ip,
				$factory->getLabel("slave_ipaddr"), 
				$defaultPage
			);

			// Slave Subnet Netmask:
			$slave_nm = $factory->getIpAddress('netmask', $slave_netmask, 'rw');
			$slave_nm->setOptional(FALSE);
			$block->addFormField(
				$slave_nm,
				$factory->getLabel("slave_netmask"), 
				$defaultPage
			);

			// Slave's Master:
			$slave_master = $factory->getIpAddress('masters', $slave_masters, 'rw');
			$slave_master->setOptional(FALSE);
			$block->addFormField(
				$slave_master,
				$factory->getLabel("slave_net_masters"), 
				$defaultPage
			);
		}
		else {

			if (!isset($slave_domain)) { 
				$slave_domain = '';
			}
			if (!isset($slave_masters)) { 
				$slave_masters = '';
			}

			// Slave Domain:
			$slave_ip = $factory->getDomainName('domain', $slave_domain, 'rw');
			$slave_ip->setOptional(FALSE);
			$block->addFormField(
				$slave_ip,
				$factory->getLabel("slave_domain"), 
				$defaultPage
			);

			// Slave's Master:
			$slave_master = $factory->getIpAddress('masters', $slave_masters, 'rw');
			$slave_master->setOptional(FALSE);
			$block->addFormField(
				$slave_master,
				$factory->getLabel("slave_dom_masters"), 
				$defaultPage
			);
		}

		// We silently pass along the OID of the Object:
		$block->addFormField(
			$factory->getTextField('_TARGET', $_TARGET, ''),
			$factory->getLabel("_TARGET"), 
			$defaultPage
		);

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton($parent));

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