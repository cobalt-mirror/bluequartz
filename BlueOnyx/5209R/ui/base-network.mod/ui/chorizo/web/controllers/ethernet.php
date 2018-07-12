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
        

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');
        

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-network", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Not 'serverNetwork'? Bye, bye!
        if (!$Capabilities->getAllowed('serverNetwork')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Protect certain form fields read-only inside VPS's:
        //if (in_array($system['IPType'], array('VZv4', 'VZv6', 'VZBOTH'))) {
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
            // params: $i18n                i18n Object of the error messages
            // params: $form_data           array with form_data array from CI
            // params: $required_keys       array with keys that must have data in it. Needed for CodeIgniter's error checks
            // params: $ignore_attributes   array with items we want to ignore. Such as Labels.
            // return:                      array with keys and values ready to submit to CCE.
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

            $oids = $CI->cceClient->find("System");
            $product = new Product( $CI->cceClient );

            //          Array
            //          (
            //              [hostNameField] => ng2
            //              [domainNameField] => blueonyx.it
            //              [dnsAddressesField] => &8.8.8.8&127.0.0.1&
            //              [gatewayField] => 186.116.135.82
            //              [ipAddressFieldeth0] => 186.116.135.83
            //              [netMaskFieldeth0] => 255.255.255.240
            //              [macAddressFieldeth0] => 08:00:27:D4:2C:4E
            //              [hasAliaseseth0] => 0
            //              [ipAddressOrigeth0] => 186.116.135.83
            //              [netMaskOrigeth0] => 255.255.255.240
            //              [bootProtoFieldeth0] => none
            //              [enabledeth0] => 0
            //              [adminIf] => eth0
            //              [deviceList] => &eth0&
            //          )

            // Remove any pre-existing CCE Replay-File:
            $CI->cceClient->replayReset();

            // Prevent handler change_route.pl from firing before we are entirely done with the network settings:
            $CI->cceClient->record($oids['0'], '', array("nw_update" => '0'));

            // Determine IPtype:
            if (!is_file("/proc/user_beancounters")) {
                $got_IPv4 = '0';
                $got_IPv6 = '0';
                $got_BOTH = '0';
                // Assume a safe default:
                $IPType = 'IPv4';
                if ((isset($attributes['ipAddressFieldeth0'])) && (isset($attributes['netMaskFieldeth0'])) && (isset($attributes['gatewayField']))) {
                    if (($attributes['ipAddressFieldeth0'] != "") && ($attributes['netMaskFieldeth0'] != "") && ($attributes['gatewayField'] != "")) {
                        $got_IPv4 = '1';
                        $IPType = 'IPv4';
                    }
                }
                if ((isset($attributes['IPv6_ipAddressFieldeth0'])) && (isset($attributes['gatewayField_IPv6']))) {
                    if (($attributes['IPv6_ipAddressFieldeth0'] != "") && ($attributes['gatewayField_IPv6'] != "")) {
                        $got_IPv6 = '1';
                        $IPType = 'IPv6';
                    }
                }
                if (($got_IPv4 == '1') && ($got_IPv6 == '1')) {
                    $got_BOTH = '1';
                    $IPType = 'BOTH';
                }
                // Record CCE Replay-Transaction:
                $CI->cceClient->record($oids['0'], '', array("IPType" => $IPType));
            }

            if ($product->isRaq()) {
                // Record CCE Replay-Transaction:
                $CI->cceClient->record($oids['0'], '', array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField'], "gateway" => $attributes['gatewayField'], "gateway_IPv6" => $attributes['gatewayField_IPv6']));
            }
            else {
                // Record CCE Replay-Transaction:
                $CI->cceClient->record($oids['0'], '', array("hostname" => $attributes['hostNameField'], "domainname" => $attributes['domainNameField'], "dns" => $attributes['dnsAddressesField']));
            }

            //--> Redirect needs to be handled here.

            // handle all devices
            $devices = array('eth0', 'eth1');
            $devices_new = array();
            if (isset($attributes['deviceList'])) {
                $devices = $CI->cceClient->scalar_to_array($attributes['deviceList']);
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

            $eth0_ipaddr = '';
            $eth0_ipaddr_IPV6 = '';

            for ($i = 0; $i < count($devices); $i ++) {
                $var_name = "ipAddressField" . $devices[$i];
                $ip_field = $attributes[$var_name];
                $var_name_IPv6 = "IPv6_ipAddressField" . $devices[$i];
                $var_name_orig_IPv6 = "IPv6_ipAddressOrig" . $devices[$i];
                $ip_orig_IPv6 = $attributes[$var_name_orig_IPv6];

                if ($attributes['gatewayField_IPv6'] != '') {
                    error_log("Setting interface " . $devices[$i] . " to " . $attributes[$var_name_IPv6]);
                    $ip_field_IPv6 = $attributes[$var_name_IPv6];
                }
                else {
                    // No IPv6 Gateway? Then remove IPv6 IP as well:
                    error_log("Stripping interface " . $devices[$i] . " of " . $attributes[$var_name_IPv6]);
                    $ip_field_IPv6 = '';
                }
                $var_name = "ipAddressOrig" . $devices[$i];
                $ip_orig = $attributes[$var_name];
                $var_name = "netMaskField" . $devices[$i];
                $nm_field = $attributes[$var_name];
                $var_name = "netMaskOrig" . $devices[$i];
                $nm_orig = $attributes[$var_name];
                $var_name = "bootProtoField" . $devices[$i];
                $boot_field = $attributes[$var_name];

                // No IPv4 Gateway? Then remove IPv4 IP and Netmask as well:
                if ($attributes['gatewayField'] == '') {
                    error_log("Stripping interface " . $devices[$i] . " of " . $ip_field . "/" . $nm_field);
                    $ip_field = '';
                    $nm_field = '';
                    $ReplayType = 'full';
                }
               
                $target_OID = $CI->cceClient->findx('Network', array('device' => "$devices[$i]"), array(), 'ascii', 'device');
                error_log("target_OID of interface " . $devices[$i] . ": " . $target_OID['0']);
                if (isset($target_OID['0'])) {

                    if ($devices[$i] == 'eth0') {
                        $eth0_ipaddr = $ip_field;
                        $eth0_ipaddr_IPV6 = $ip_field_IPv6;
                    }

                    // We *always* update 'eth0' on saving. No if, no but, we just do it. Because we *must* be sure that the network config is in sync with the GUI. And it might not be.
                    // Any other network object only gets updated if there are changes in IP/Netmask or IPv6 IP address.
                    if (($devices[$i] == 'eth0') || (($ip_field != $ip_orig) || ($ip_field_IPv6 != $ip_orig_IPv6) || ($nm_field != $nm_orig) || ($attributes['gatewayField'] != $attributes['gatewayFieldOrig']) || ($attributes['gatewayField_IPv6'] != $attributes['gatewayFieldOrig_IPv6']))) {
                        $CI->cceClient->record($target_OID['0'], '', array('ipaddr' => $ip_field, 'netmask' => $nm_field, 'ipaddr_IPv6' => $ip_field_IPv6, 'enabled' => 1, 'bootproto' => 'none', 'refresh' => time()));
                    }
                }
            }

            // Set nw_update:
            if ($product->isRaq()) {
                // Record CCE Replay-Transaction:
                $CI->cceClient->record($oids['0'], '', array("nw_update" => time()));
            }

            // Redirect to our new progress-display for CCE Stored-Transactions:
            $system = $CI->getSystem();
            $http_server_name = $_SERVER['SERVER_NAME'];
            $http_server_name = preg_replace('/\[/', '', $http_server_name);
            $http_server_name = preg_replace('/\]/', '', $http_server_name);
            error_log("SERVER_NAME: " . $http_server_name);
            if ((count($errors) == "0")) {
                if ((filter_var($http_server_name, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) && ($eth0_ipaddr_IPV6 != '' )) { 
                    // GUI is currently accessed via an IPv6 IP!
                    $targetProto = 'ipv6';
                    error_log("Redirect-Check: IPv6 possible");
                }
                elseif ((filter_var($http_server_name, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) && ($eth0_ipaddr != '' )) { 
                    // GUI is currently accessed via an IPv4 IP!
                    $targetProto = 'ipv4';
                    error_log("Redirect-Check: IPv4 possible");
                }
                else {
                    // GUI is currently accessed via FQDN:
                    error_log("Redirect-Check: Using 'standard'");
                    $targetProto = 'standard';
                }
            
                if (!isset($ReplayType)) {
                    $ReplayType = 'full';
                }
                error_log("redirectType: " . $targetProto);
                error_log("ReplayType: " . $ReplayType);
                header("Location: /gui/working?statusId=1&VM=base_serverconfig&VMC=base_ethernet&PM=base_sysmanage&redirectType=$targetProto&ReplayType=$ReplayType");
                exit;
            }
            else {
                $system = $CI->getSystem();
            }
        }

        //
        //-- Own page logic:
        //

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-network", "/network/ethernet");
        $BxPage = $factory->getPage();

        // Primary IP is changing. Show redirect 'error' message:
        if ($redirect != "") {
            $redir_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml('[[base-network.adminRedirect]]') . '</strong></div>';
            $errors = array_merge($redir_msg, $errors);
        }

        // Show OpenVZ message:
        if (in_array($system['IPType'], array('VZv4', 'VZv6', 'VZBOTH'))) {
            $vps_msg[] = '<div class="alert dismissible alert_green"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>' . $i18n->interpolateHtml('[[base-network.openvz_vps]]') . '</strong></div>';
            $errors = array_merge($vps_msg, $errors);
        }

        // Get errorMsg from URL string.
        $get_form_data = $CI->input->get(NULL, TRUE);
        if (isset($get_form_data['errorMsg'])) {
            $errors[] = @json_decode(urldecode($get_form_data['errorMsg']));
        }

        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_serverconfig');
        $BxPage->setVerticalMenuChild('base_ethernet');
        $page_module = 'base_sysmanage';

        $default_page = 'primarySettings';
        if (($fieldprot == "rw") && ($is_aws == "0")) {
            // Show "Interface Aliasses" if not inside a VPS:
            //$pages = array($default_page, 'aliasSettings');
            $pages = array($default_page);
        }
        else {
            // Hide "Interface Aliasses" inside a VPS:
            $pages = array($default_page);
        }

        $block =& $factory->getPagedBlock("tcpIpSettings", $pages);

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        //$block->setShowAllTabs("#");
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
        $dns->setType('ipaddr_list_IPv4IPv6');
        $block->addFormField(
          $dns,
          $factory->getLabel("dnsAddressesField"),
          $default_page
        );

        if ($product->isRaq()) {
            if ($is_aws == "1") {
                if (!isset($system["gateway"])) {
                    // AWS and Gateway not defined. Make it editable:
                    $gwFprot = 'rw';
                }
                else {
                    if ($system["gateway"] == "") {
                        // AWS and Gateway not set. Make it editable:
                        $gwFprot = 'rw';
                    }
                    else {
                        // AWS, Gateway is set and not empty. Show it.
                        // But do not allow to edit it:
                        $gwFprot = 'r';
                    }
                }
            }
            else {
                // Not AWS. Allow edits if they are allowed for any of
                // the other network related fields:
                $gwFprot = $fieldprot;
            }
            $gw = $factory->getIpAddress("gatewayField", $system["gateway"], $gwFprot);
            $gw->setOptional(true);
            $block->addFormField($gw, $factory->getLabel("gatewayField"), $default_page);

            $block->addFormField(
                    $factory->getIpAddress("gatewayFieldOrig", $system["gateway"], ""),
                    '',
                    $default_page
                    );
        }

        if ($product->isRaq()) {
            if ($is_aws == "1") {
                if (!isset($system["gateway_IPv6"])) {
                    // AWS and Gateway not defined. Make it editable:
                    $gwFprot = 'rw';
                }
                else {
                    if ($system["gateway_IPv6"] == "") {
                        // AWS and Gateway not set. Make it editable:
                        $gwFprot = 'rw';
                    }
                    else {
                        // AWS, Gateway is set and not empty. Show it.
                        // But do not allow to edit it:
                        $gwFprot = 'r';
                    }
                }
            }
            else {
                // Not AWS, but OpenVZ Container: 
                if (in_array($system['IPType'], array('VZv4', 'VZv6', 'VZBOTH'))) {
                    $gwFprot = '';
                }
                else {
                    // Allow edits if they are allowed for any of
                    // the other network related fields:
                    $gwFprot = $fieldprot;
                }
            }
            $gw_IPv6 = $factory->getIpAddress("gatewayField_IPv6", $system["gateway_IPv6"], $gwFprot);
            $gw_IPv6->setOptional(true);
            $gw_IPv6->setType('ipaddrIPv6');
            $block->addFormField($gw_IPv6, $factory->getLabel("gatewayField_IPv6"), $default_page);

            $block->addFormField(
                    $factory->getIpAddress("gatewayFieldOrig_IPv6", $system["gateway_IPv6"], ""),
                    '',
                    $default_page
                    );
        }

        // real interfaces
        // ascii sorted, this may be a problem if there are more than 10 interfaces
        $interfaces = $CI->cceClient->findx('Network', array('real' => 1), array(), 'ascii', 'device');
        $devices = array();
        $deviceList = array();
        $devnames = array();
        $i18n = $factory->getI18n();
        $admin_if = '';
        for ($i = 0; $i < count($interfaces); $i++) {

            $is_admin_if = false;
            $iface = $CI->cceClient->get($interfaces[$i]);
            $device = $iface['device'];
            
            // save the devices and strings for javascript fun
            $deviceList[] = $device;
            $devices[] = "'$device'";    
            $devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

                // Devices:
                $dev[$device] = array (
                                'ipaddr' => $iface["ipaddr"],
                                'netmask' => $iface["netmask"],
                                'ipaddr_IPv6' => $iface["ipaddr_IPv6"],
                                'mac' => $iface["mac"],
                                'device' => $device,
                                'bootproto' => $iface["bootproto"],
                                'enabled' => $iface["enabled"]
                                );

        }

        if (isset($dev['eth0'])) {
            $ipaddr = $dev['eth0']['ipaddr'];
            $netmask = $dev['eth0']['netmask'];
            $ipaddr_IPv6 = $dev['eth0']['ipaddr_IPv6'];
            $device = $dev['eth0']['device'];
            $mac = $dev['eth0']['mac'];
            $enabled = $dev['eth0']['enabled'];
            $bootproto = $dev['eth0']['bootproto'];
            
            $ip_label = 'ipAddressField1';
            $nm_label = 'netMaskField1';
            $ip_label_IPv6 = 'IPv6_ipAddressField1';

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
            $ip_field0->setOptional(true);

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
            $netmask_field0->setOptional(true);
            
            $block->addFormField(
                    $netmask_field0,
                    $factory->getLabel($nm_label, true,
                                array(), array('name' => "[[base-network.help$device]]")),
                    $default_page
                );

            // IPv6:
            $IPv6_ip_field0 = $factory->getIpAddress("IPv6_ipAddressField$device", $ipaddr_IPv6, $devprot);
            $IPv6_ip_field0->setInvalidMessage($i18n->getJs('IPv6_ipAddressField_invalid'));
            $IPv6_ip_field0->setCurrentLabel($i18n->getHtml('[[base-network.IPv6_ipAddressField1]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field0->setDescription($i18n->getWrapped('[[base-network.IPv6_ipAddressField1_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field0->setOptional(true);
            $IPv6_ip_field0->setType('ipaddrIPv6');

            $block->addFormField(
                    $IPv6_ip_field0,
                    $factory->getLabel($ip_label_IPv6, true,
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
                    $factory->getIpAddress("IPv6_ipAddressOrig$device", $ipaddr_IPv6, ""),
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
            $ipaddr_IPv6 = $dev['eth1']['ipaddr_IPv6'];
            $device = $dev['eth1']['device'];
            $mac = $dev['eth1']['mac'];
            $enabled = $dev['eth1']['enabled'];
            $bootproto = $dev['eth1']['bootproto'];
            
            $ip_label = 'ipAddressField2';
            $nm_label = 'netMaskField2';
            $ip_label_IPv6 = 'IPv6_ipAddressField2';

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

            // IPv6:
            $IPv6_ip_field1 = $factory->getIpAddress("IPv6_ipAddressField$device", $ipaddr_IPv6, $devprot);
            $IPv6_ip_field1->setInvalidMessage($i18n->getJs('IPv6_ipAddressField_invalid'));
            $IPv6_ip_field1->setCurrentLabel($i18n->getHtml('[[base-network.IPv6_ipAddressField2]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field1->setDescription($i18n->getWrapped('[[base-network.IPv6_ipAddressField2_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field1->setOptional(true);
            $IPv6_ip_field1->setType('ipaddrIPv6');

            $block->addFormField(
                    $IPv6_ip_field1,
                    $factory->getLabel($ip_label_IPv6, true,
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
                    $factory->getIpAddress("IPv6_ipAddressOrig$device", $ipaddr_IPv6, ""),
                    '',
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
            $ipaddr_IPv6 = $dev['eth2']['ipaddr_IPv6'];
            $device = $dev['eth2']['device'];
            $mac = $dev['eth2']['mac'];
            $enabled = $dev['eth2']['enabled'];
            $bootproto = $dev['eth2']['bootproto'];
            
            $ip_label = 'ipAddressField3';
            $nm_label = 'netMaskField3';
            $ip_label_IPv6 = 'IPv6_ipAddressField3';

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

            // IPv6:
            $IPv6_ip_field2 = $factory->getIpAddress("IPv6_ipAddressField$device", $ipaddr_IPv6, $devprot);
            $IPv6_ip_field2->setInvalidMessage($i18n->getJs('IPv6_ipAddressField_invalid'));
            $IPv6_ip_field2->setCurrentLabel($i18n->getHtml('[[base-network.IPv6_ipAddressField3]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field2->setDescription($i18n->getWrapped('[[base-network.IPv6_ipAddressField3_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field2->setOptional(true);
            $IPv6_ip_field2->setType('ipaddrIPv6');

            $block->addFormField(
                    $IPv6_ip_field2,
                    $factory->getLabel($ip_label_IPv6, true,
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
                    $factory->getIpAddress("IPv6_ipAddressOrig$device", $ipaddr_IPv6, ""),
                    '',
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
            $ipaddr_IPv6 = $dev['eth3']['ipaddr_IPv6'];
            $device = $dev['eth3']['device'];
            $mac = $dev['eth3']['mac'];
            $enabled = $dev['eth3']['enabled'];
            $bootproto = $dev['eth3']['bootproto'];
            
            $ip_label = 'ipAddressField3';
            $nm_label = 'netMaskField3';
            $ip_label_IPv6 = 'IPv6_ipAddressField3';

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

            // IPv6:
            $IPv6_ip_field3 = $factory->getIpAddress("IPv6_ipAddressField$device", $ipaddr_IPv6, $devprot);
            $IPv6_ip_field3->setInvalidMessage($i18n->getJs('IPv6_ipAddressField_invalid'));
            $IPv6_ip_field3->setCurrentLabel($i18n->getHtml('[[base-network.IPv6_ipAddressField3]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field3->setDescription($i18n->getWrapped('[[base-network.IPv6_ipAddressField3_help]]', true, array(), array('name' => "[[base-network.help$device]]")));
            $IPv6_ip_field3->setOptional(true);
            $IPv6_ip_field3->setType('ipaddrIPv6');

            $block->addFormField(
                    $IPv6_ip_field3,
                    $factory->getLabel($ip_label_IPv6, true,
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
                    $factory->getIpAddress("IPv6_ipAddressOrig$device", $ipaddr_IPv6, ""),
                    '',
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
        $block->addFormField($factory->getTextField('deviceList', $CI->cceClient->array_to_scalar($deviceList), ''));

        //
        //--- TAB: aliasSettings
        //

        if ((in_array($system['IPType'], array('IPv4', 'IPv6', 'BOTH'))) && (!is_file("/etc/is_aws"))) {
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
            $networks = $CI->cceClient->findx(
                          'Network', array('real' => 0), array(),
                          'ascii', $sort_map[$alias_list->getSortedIndex()]);

            for($i=0; $i < count($networks); $i++) {
                // must be an alias
                $alias = $CI->cceClient->get($networks[$i]);
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

        //$routeButton = $factory->getButton("/network/routes", "routes", "DEMO-OVERRIDE");
        //$buttonRouteContainer = $factory->getButtonContainer(" ", array($routeButton));
        //$page_body[] = $buttonRouteContainer->toHtml();

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}

/*
Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
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