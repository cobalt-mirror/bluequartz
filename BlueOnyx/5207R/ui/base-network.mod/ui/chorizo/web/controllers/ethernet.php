<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ethernet extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /network/ethernet.
	 *
	 * Note: This page has some caveats. On IP address change of the primary
	 * interface we can't redirect. Because either the redirect happens before
	 * the network address is changed. In which case the network address change
	 * never happens.
	 * 
	 * Or the network address change happens and then the redirect to the new IP
	 * never happens. 
	 *
	 * With the standard methods available this is a no-show. Need to figure out
	 * something else for the long run.
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
		$i18n = new I18n("base-network", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not 'serverNetwork'? Bye, bye!
		if (!$Capabilities->getAllowed('serverNetwork')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		// Protect certain form fields read-only inside VPS's:
		if (is_file("/proc/user_beancounters")) {
		    $fieldprot = "r";
		}
		else {
		    $fieldprot = "rw";
		}

		// Are we running on AWS?
		if (is_file("/etc/is_aws")) {
		    $is_aws = "1";
		}
		else {
		    $is_aws = "0";
		}

		$redirect = "";

		//
		//--- Get CODB-Object of interest: 
		//

		$system = $cceClient->getObject("System");

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

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.

			$oids = $cceClient->find("System");
			$product = new Product( $cceClient );

			//			Array
			//			(
			//			    [hostNameField] => ng2
			//			    [domainNameField] => blueonyx.it
			//			    [dnsAddressesField] => &8.8.8.8&127.0.0.1&
			//			    [gatewayField] => 186.116.135.82
			//			    [ipAddressFieldeth0] => 186.116.135.83
			//			    [netMaskFieldeth0] => 255.255.255.240
			//			    [macAddressFieldeth0] => 08:00:27:D4:2C:4E
			//			    [hasAliaseseth0] => 0
			//			    [ipAddressOrigeth0] => 186.116.135.83
			//			    [netMaskOrigeth0] => 255.255.255.240
			//			    [bootProtoFieldeth0] => none
			//			    [enabledeth0] => 0
			//			    [adminIf] => eth0
			//			    [deviceList] => &eth0&
			//			)

			if ($product->isRaq()) {
				$cceClient->set($oids[0], "", array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField'], "gateway" => $attributes['gatewayField']));
			}
			else {
				$cceClient->set($oids[0], "", array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField']));
			}

			// Figure out if this is the admin if changing
			if (isset($attributes['adminIf'])) {
				if ($attributes['adminIf'] == "eth0") {
					if ((isset($attributes['ipAddressFieldeth0'])) && (isset($attributes['ipAddressOrigeth0']))) {
						if ($attributes['ipAddressFieldeth0'] != $attributes['ipAddressOrigeth0']) {
							// IP of admin interface eth0 is changing!
							$redirect = $attributes['ipAddressFieldeth0'];
						}
					}
				}
				elseif ($attributes['adminIf'] == "eth1") {
					if ((isset($attributes['ipAddressFieldeth1'])) && (isset($attributes['ipAddressOrigeth1']))) {
						if ($attributes['ipAddressFieldeth1'] != $attributes['ipAddressOrigeth1']) {
							// IP of admin interface eth1 is changing!
							$redirect = $attributes['ipAddressFieldeth1'];
						}
					}
				}
				elseif ($attributes['adminIf'] == "eth2") {
					if ((isset($attributes['ipAddressFieldeth2'])) && (isset($attributes['ipAddressOrigeth2']))) {
						if ($attributes['ipAddressFieldeth2'] != $attributes['ipAddressOrigeth2']) {
							// IP of admin interface eth2 is changing!
							$redirect = $attributes['ipAddressFieldeth2'];
						}
					}
				}
				elseif ($attributes['adminIf'] == "eth3") {
					if ((isset($attributes['ipAddressFieldeth3'])) && (isset($attributes['ipAddressOrigeth3']))) {
						if ($attributes['ipAddressFieldeth3'] != $attributes['ipAddressOrigeth3']) {
							// IP of admin interface eth3 is changing!
							$redirect = $attributes['ipAddressFieldeth3'];
						}
					}
				}
				elseif ($attributes['adminIf'] == "eth4") {
					if ((isset($attributes['ipAddressFieldeth4'])) && (isset($attributes['ipAddressOrigeth4']))) {
						if ($attributes['ipAddressFieldeth4'] != $attributes['ipAddressOrigeth4']) {
							// IP of admin interface eth4 is changing!
							$redirect = $attributes['ipAddressFieldeth4'];
						}
					}
				}				
			}

			//--> Redirect needs to be handled here.

			// handle all devices
			$devices = array('eth0', 'eth1');
			$devices_new = array();
			if (isset($attributes['deviceList'])) {
				$devices = $cceClient->scalar_to_array($attributes['deviceList']);
			}

			// Ok, this is nuts. Somehow our '&eth0&' got turned into '&eth0;&' and I have no idea 
			// where or why this happened. So we have to walk through the $devides array and every
			// device in it needs to have any superfluxous ';' removed:
			foreach ($devices as $key => $value) {
				$devices_new[] = preg_replace('/;/', '', $value);
			}
			$devices = $devices_new;

			// special array for admin if errors
			$admin_if_errors = array();
			for ($i = 0; $i < count($devices); $i ++) {
				$var_name = "ipAddressField" . $devices[$i];
				$ip_field = $attributes[$var_name];
				$var_name = "ipAddressOrig" . $devices[$i];
				$ip_orig = $attributes[$var_name];
				$var_name = "netMaskField" . $devices[$i];
				$nm_field = $attributes[$var_name];
				$var_name = "netMaskOrig" . $devices[$i];
				$nm_orig = $attributes[$var_name];
				$var_name = "bootProtoField" . $devices[$i];
				$boot_field = $attributes[$var_name];
			   
				// setup or set disabled
				if ($ip_field == '') {
					// first migrate any aliases to eth0 (possibly do this better)
					$aliases = $cceClient->findx('Network', array(), array('device' => "^$devices[$i]:"));
					for ($k = 0; $k < count($aliases); $k++) {
						$new_device = find_free_device($cceClient, 'eth0');
						$ok = $cceClient->set($aliases[$k], '', array('device' => $new_device));
						
						$errors = array_merge($errors, $cceClient->errors());
					}

					$cceClient->setObject('Network',
							array(
								'enabled' => 0,
								'bootproto' => 'none'
								),
						   '', array('device' => $devices[$i]));
					$errors = array_merge($errors, $cceClient->errors());

					if (count($errors == 0)) {
						if ($devices[$i] == 'eth0') {
							$BxPage->setOnLoad("top.location = 'http://$ip_field:444/network/ethernet'");
							// Nice people say goodbye, or CCEd waits forever:
							$cceClient->bye();
							$serverScriptHelper->destructor();

							$page_body[] = "";

							// Out with the page:
						    $BxPage->render($page_module, $page_body);
						}
					}
				}
				else if ($ip_field && (($ip_field != $ip_orig) || ($nm_field != $nm_orig))) {

					// since we only deal with real interfaces here, things are simpler
					// than they could be
					if (false && $ip_field != $ip_orig) {
						// check to see if there is an alias that is already using
						// the new ip address.  if there is, destroy the Network object
						// for this device, and assign the alias this device name.
						list($alias) = $cceClient->find('Network', 
											array(
												'real' => 0,
												'ipaddr' => $ip_field
												));
						if ($alias) {
							$ok = $cceClient->set($alias, '',
								array(
									'device' => $devices[$i],
									'real' => 1,
									'ipaddr' => $ip_field,
									'netmask' => $nm_field,
									'enabled' => 1,
									'bootproto' => 'none'
									));
							$errors = array_merge($errors, $cceClient->errors());
							if (!$ok) {
								break;
							}
							else {
								continue;
							}
						}
					}
					$cceClient->setObject('Network',
							array(
								'ipaddr' => $ip_field,
								'netmask' => $nm_field,
								'enabled' => 1,
								'bootproto' => 'none'
								),
						   '', array('device' => $devices[$i]));
					$errors = array_merge($errors, $cceClient->errors());
				}
			}

			// CCE errors that might have happened during submit to CODB:
			foreach ($errors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				if ($redirect != "") {
					header("Location: /network/ethernet");
					print_rp("Local");
					exit;
				}
				else {
					// Nice idea, but at that point the new network config is already in place.
					// So this never happens:
					$port = $_SERVER['SERVER_PORT'];
					$redirect = $attributes['ipAddressFieldeth0'];
					if ($port == '81') {
						header("Location: https://$redirect:$port/network/ethernet");
						exit;
					}
					else {
						header("Location: http://$redirect:$port/network/ethernet");
						exit;
					}
				}
			}
			else {
				$system = $cceClient->getObject("System");
			}
		}

		//
		//-- Own page logic:
		//

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/network/ethernet");
		$BxPage = $factory->getPage();

		// Primary IP is changing. Show redirect 'error' message:
		if ($redirect != "") {
			$redir_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml('[[base-network.adminRedirect]]') . '</strong></div>';
			$errors = array_merge($redir_msg, $errors);
		}

		// Show OpenVZ message:
		if (is_file("/proc/user_beancounters")) {
			$vps_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml('[[base-network.openvz_vps]]') . '</strong></div>';
			$errors = array_merge($vps_msg, $errors);
		}

		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_serverconfig');
		$BxPage->setVerticalMenuChild('base_ethernet');
		$page_module = 'base_sysmanage';

		$default_page = 'primarySettings';
		if (($fieldprot == "rw") && ($is_aws == "0")) {
		    // Show "Interface Aliasses" if not inside a VPS:
		    $pages = array($default_page, 'aliasSettings');
		}
		else {
		    // Hide "Interface Aliasses" inside a VPS:
		    $pages = array($default_page);
		}

		$block =& $factory->getPagedBlock("tcpIpSettings", $pages);

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($default_page);

		if ($redirect != "") {
			$oldIP = $_SERVER['SERVER_ADDR'];
			$port = $_SERVER['SERVER_PORT'];
			$reconnect = $factory->getButton("http://$redirect:$port/network/ethernet", 'reconnect');
			$fallback = $factory->getButton("http://$oldIP:$port/network/ethernet", 'oldIPReconnect');

			$buttonRSContainer = $factory->getButtonContainer("tcpIpSettings", array($reconnect, $fallback));
			$block->addFormField(
				$buttonRSContainer,
				$factory->getLabel("tcpIpSettings"),
				$default_page
			);
		}

		//
		//--- TAB: primarySettings
		//

		// host and domain names
		$hostfield = $factory->getDomainName("hostNameField", $system["hostname"], $fieldprot);
		$domainfield = $factory->getDomainName("domainNameField", $system["domainname"], $fieldprot);

		$fqdn = $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp;.&nbsp;');

		$block->addFormField(
			$fqdn,
			$factory->getLabel("enterFqdn"), 
			$default_page
		);

		$dns = $factory->getIpAddressList("dnsAddressesField", $system["dns"], $fieldprot);
		$dns->setOptional(true);
		$block->addFormField(
		  $dns,
		  $factory->getLabel("dnsAddressesField"),
		  $default_page
		);

		if (($product->isRaq()) && ($is_aws == "0")) {
			$gw = $factory->getIpAddress("gatewayField", $system["gateway"], $fieldprot);
			$gw->setOptional(true);
			$block->addFormField($gw, $factory->getLabel("gatewayField"), $default_page);
		}

		// real interfaces
		// ascii sorted, this may be a problem if there are more than 10 interfaces
		$interfaces = $cceClient->findx('Network', array('real' => 1), array(), 'ascii', 'device');
		$devices = array();
		$deviceList = array();
		$devnames = array();
		$i18n = $factory->getI18n();
		$admin_if = '';
		for ($i = 0; $i < count($interfaces); $i++) {

			$is_admin_if = false;
			$iface = $cceClient->get($interfaces[$i]);
			$device = $iface['device'];
			
			// save the devices and strings for javascript fun
			$deviceList[] = $device;
			$devices[] = "'$device'";    
			$devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

		        // Devices:
		        $dev[$device] = array (
		                        'ipaddr' => $iface["ipaddr"],
		                        'netmask' => $iface["netmask"],
		                        'mac' => $iface["mac"],
		                        'device' => $device,
		                        'bootproto' => $iface["bootproto"],
		                        'enabled' => $iface["enabled"]
		                        );

		}

		if (isset($dev['eth0'])) {
		    $ipaddr = $dev['eth0']['ipaddr'];
		    $netmask = $dev['eth0']['netmask'];
		    $device = $dev['eth0']['device'];
		    $mac = $dev['eth0']['mac'];
		    $enabled = $dev['eth0']['enabled'];
		    $bootproto = $dev['eth0']['bootproto'];
		    
		    $ip_label = 'ipAddressField1';
		    $nm_label = 'netMaskField1';

			// Add divider:
			$block->addFormField(
			        $factory->addBXDivider("interface$device", ""),
			        $factory->getLabel("interface$device", false),
			        $default_page
			        );

		    if ($is_aws == "0") {
				$devprot = "rw";
		    }
		    else {
				$devprot = "r";
		    }

		    $ip_field0 = $factory->getIpAddress("ipAddressField$device", $ipaddr, $devprot);
		    $ip_field0->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
			$ip_field0->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField1]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$ip_field0->setDescription($i18n->getWrapped('[[base-network.ipAddressField1_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $block->addFormField(
		            $ip_field0,
		            $factory->getLabel($ip_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    $netmask_field0 = $factory->getIpAddress("netMaskField$device", $netmask, $devprot);
		    $netmask_field0->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
			$netmask_field0->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField1]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$netmask_field0->setDescription($i18n->getWrapped('[[base-network.netMaskField1_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    // Netmask is not optional for the admin iface and for eth0
		    $netmask_field0->setOptional(false);
		    
		    $block->addFormField(
		            $netmask_field0,
		            $factory->getLabel($nm_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    // MAC:
		    $macaddress_field0 = $factory->getMacAddress("macAddressField$device", $mac, "r");
			$macaddress_field0->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
			$macaddress_field0->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

		    $block->addFormField(
		            $macaddress_field0,
		            $factory->getLabel("macAddressField"),
		            $default_page
		        );

		    // retain orginal information
		    $block->addFormField(
		            $factory->getBoolean("hasAliases$device", 0, ''));

		    $block->addFormField(
		            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
		            '',
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getTextField("bootProtoField$device", $bootproto, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getBoolean("enabled$device", $enabled, ""),
		            "",
		            $default_page
		            );

		}
		if (isset($dev['eth1'])) {
		    $ipaddr = $dev['eth1']['ipaddr'];
		    $netmask = $dev['eth1']['netmask'];
		    $device = $dev['eth1']['device'];
		    $mac = $dev['eth1']['mac'];
		    $enabled = $dev['eth1']['enabled'];
		    $bootproto = $dev['eth1']['bootproto'];

		    if ($enabled == "0") {
				$ipaddr = "";
				$netmask = "";
		    }
		    
		    $ip_label = 'ipAddressField1';
		    $nm_label = 'netMaskField1';

			// Add divider:
			$block->addFormField(
			        $factory->addBXDivider("interface$device", ""),
			        $factory->getLabel("interface$device", false),
			        $default_page
			        );


		    $ip_field1 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
		    $ip_field1->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
			$ip_field1->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$ip_field1->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $ip_field1->setOptional(true);

		    $block->addFormField(
		            $ip_field1,
		            $factory->getLabel($ip_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    $netmask_field1 = $factory->getIpAddress("netMaskField$device", $netmask);
		    $netmask_field1->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
		    $netmask_field1->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
			$netmask_field1->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$netmask_field1->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $netmask_field1->setOptional(true);
		    
		    $block->addFormField(
		            $netmask_field1,
		            $factory->getLabel($nm_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    // MAC:
		    $macaddress_field1 = $factory->getMacAddress("macAddressField$device", $mac, "r");
			$macaddress_field1->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
			$macaddress_field1->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

		    $block->addFormField(
		            $macaddress_field1,
		            $factory->getLabel("macAddressField"),
		            $default_page
		        );

		    // retain orginal information
		    $block->addFormField(
		            $factory->getBoolean("hasAliases$device", 0, ''));
		    $block->addFormField(
		            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
		            '',
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getTextField("bootProtoField$device", $bootproto, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getBoolean("enabled$device", $enabled, ""),
		            "",
		            $default_page
		            );

		}
		if (isset($dev['eth2'])) {
		    $ipaddr = $dev['eth2']['ipaddr'];
		    $netmask = $dev['eth2']['netmask'];
		    $device = $dev['eth2']['device'];
		    $mac = $dev['eth2']['mac'];
		    $enabled = $dev['eth2']['enabled'];
		    $bootproto = $dev['eth2']['bootproto'];
		    
		    if ($enabled == "0") {
				$ipaddr = "";
				$netmask = "";
		    }

		    $ip_label = 'ipAddressField';
		    $nm_label = 'netMaskField';

			// Add divider:
			$block->addFormField(
			        $factory->addBXDivider("interface$device", ""),
			        $factory->getLabel("interface$device", false),
			        $default_page
			        );

		    $ip_field2 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
		    $ip_field2->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
			$ip_field2->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$ip_field2->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $ip_field2->setOptional(true);

		    $block->addFormField(
		            $ip_field2,
		            $factory->getLabel($ip_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    $netmask_field2 = $factory->getIpAddress("netMaskField$device", $netmask);
		    $netmask_field2->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
		    $netmask_field2->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
			$netmask_field2->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$netmask_field2->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $netmask_field2->setOptional(true);
		    
		    $block->addFormField(
		            $netmask_field2,
		            $factory->getLabel($nm_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    // MAC:
		    $macaddress_field2 = $factory->getMacAddress("macAddressField$device", $mac, "r");
			$macaddress_field2->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
			$macaddress_field2->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

		    $block->addFormField(
		            $macaddress_field2,
		            $factory->getLabel("macAddressField"),
		            $default_page
		        );

		    // retain orginal information
		    $block->addFormField(
		            $factory->getBoolean("hasAliases$device", 0, ''));
		    $block->addFormField(
		            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
		            '',
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getTextField("bootProtoField$device", $bootproto, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getBoolean("enabled$device", $enabled, ""),
		            "",
		            $default_page
		            );

		}
		if (isset($dev['eth3'])) {
		    $ipaddr = $dev['eth3']['ipaddr'];
		    $netmask = $dev['eth3']['netmask'];
		    $device = $dev['eth3']['device'];
		    $mac = $dev['eth3']['mac'];
		    $enabled = $dev['eth3']['enabled'];
		    $bootproto = $dev['eth3']['bootproto'];
		    
		    if ($enabled == "0") {
				$ipaddr = "";
				$netmask = "";
		    }

		    $ip_label = 'ipAddressField';
		    $nm_label = 'netMaskField';

			// Add divider:
			$block->addFormField(
			        $factory->addBXDivider("interface$device", ""),
			        $factory->getLabel("interface$device", false),
			        $default_page
			        );

		    $ip_field3 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
		    $ip_field3->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
			$ip_field3->setCurrentLabel($i18n->getHtml('[[base-network.ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$ip_field3->setDescription($i18n->getWrapped('[[base-network.ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $ip_field3->setOptional(true);

		    $block->addFormField(
		            $ip_field3,
		            $factory->getLabel($ip_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    $netmask_field3 = $factory->getIpAddress("netMaskField$device", $netmask);
		    $netmask_field3->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
		    $netmask_field3->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
			$netmask_field3->setCurrentLabel($i18n->getHtml('[[base-network.netMaskField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
			$netmask_field3->setDescription($i18n->getWrapped('[[base-network.netMaskField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));

		    $netmask_field3->setOptional(true);
		    
		    $block->addFormField(
		            $netmask_field3,
		            $factory->getLabel($nm_label, true,
		                        array(), array('name' => "[[base-network.help$device]]")),
		            $default_page
		        );

		    // MAC:
		    $macaddress_field3 = $factory->getMacAddress("macAddressField$device", $mac, "r");
			$macaddress_field3->setCurrentLabel($i18n->getHtml('[[base-network.macAddressField]]', true));
			$macaddress_field3->setDescription($i18n->getWrapped('[[base-network.macAddressField_help]]', true));

		    $block->addFormField(
		            $macaddress_field3,
		            $factory->getLabel("macAddressField"),
		            $default_page
		        );

		    // retain orginal information
		    $block->addFormField(
		            $factory->getBoolean("hasAliases$device", 0, ''));
		    $block->addFormField(
		            $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""),
		            '',
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getIpAddress("netMaskOrig$device", $netmask, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getTextField("bootProtoField$device", $bootproto, ""),
		            "",
		            $default_page
		            );
		    $block->addFormField(
		            $factory->getBoolean("enabled$device", $enabled, ""),
		            "",
		            $default_page
		            );
		}

		// add a hidden field indicating which interface is the admin interface
		$block->addFormField($factory->getTextField('adminIf', 'eth0', ''));
		$block->addFormField($factory->getTextField('deviceList', $cceClient->array_to_scalar($deviceList), ''));

		//
		//--- TAB: aliasSettings
		//

		if ((!is_file("/proc/user_beancounters")) && (!is_file("/etc/is_aws"))) {

	    	// Add-Button:
			$addAlias = "/network/aliasModify";
			$addbutton = $factory->getAddButton($addAlias, '[[base-network.addAliasButton]]', "DEMO-OVERRIDE");
			$buttonContainer = $factory->getButtonContainer("aliasSettings", $addbutton);
			$block->addFormField(
				$buttonContainer,
				$factory->getLabel("aliasSettings"),
				'aliasSettings'
			);


	    	// add scrollist of aliases
	  		$alias_list = $factory->getScrollList("aliasSettings", array('aliasName', 'aliasIpaddr', 'aliasNetmask', 'aliasActions'), array()); 
		    $alias_list->setAlignments(array("left", "left", "center", "center"));
		    $alias_list->setDefaultSortedIndex('0');
		    $alias_list->setSortOrder('ascending');
		    $alias_list->setSortDisabled(array('3'));
		    $alias_list->setPaginateDisabled(FALSE);
		    $alias_list->setSearchDisabled(FALSE);
		    $alias_list->setSelectorDisabled(FALSE);
		    $alias_list->enableAutoWidth(FALSE);
		    $alias_list->setInfoDisabled(FALSE);
		    $alias_list->setColumnWidths(array("320", "178", "120", "120")); // Max: 739px
	    
		    $sort_map = array('device', 'ipaddr', 'netmask');
		    $networks = $cceClient->findx(
						  'Network', array('real' => 0), array(),
						  'ascii', $sort_map[$alias_list->getSortedIndex()]);

			for($i=0; $i < count($networks); $i++) {
		    	// must be an alias
		    	$alias = $cceClient->get($networks[$i]);
		    	$device_info = preg_split('/:/', $alias['device']);
		    	$alias_name = $i18n->interpolateHtml('[[base-network.alias' .
		    					     $device_info[0] . ']]',
		    					     array('num' => $device_info[1]));
		    	
		    	$modButt = $factory->getModifyButton("/network/aliasModify?ACTION=M&_oid=$networks[$i]");
		    	$modButt->setImageOnly(TRUE);
		    	$delButt = $factory->getRemoveButton("/network/aliasModify?ACTION=D&_oid=$networks[$i]");
		    	$delButt->setImageOnly(TRUE);
	    	
		    	$alias_list->addEntry(
		    			      array(
		    				    $alias_name,
		    				   	$alias['ipaddr'],
		    				    $alias['netmask'],
		    				    $factory->getCompositeFormField(
		    								    array(
		    									  $modButt,
		    									  $delButt
		    									  )
		    								    )
		    				    ));
		      }

			// Push out the Scrollist with the aliasSettings:
			$block->addFormField(
				$factory->getRawHTML("aliasSettings", $alias_list->toHtml()),
				$factory->getLabel("aliasSettings"),
				'aliasSettings'
			);
		}

		//
		//--- Add the buttons
		//

		// Only add the save button if looking at primary settings AND we're not inside a VPS:
		if ($fieldprot == "rw") {
			$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
			$block->addButton($factory->getCancelButton("/network/ethernet"));
		}

		$routeButton = $factory->getButton("/network/routes", "routes", "DEMO-OVERRIDE");
		$buttonRouteContainer = $factory->getButtonContainer(" ", array($routeButton));
		$page_body[] = $buttonRouteContainer->toHtml();

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