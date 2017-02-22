<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vsite_dns_add extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /dns/vsite_dns_add.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-apache", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

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
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
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
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        $iam = "/dns/vsite_dns_add?group=$group";
        $parent = "/dns/vsiteDNS?group=$group";

        // Not siteDNS? Bye, bye!
        if (!$Capabilities->getAllowed('siteDNS')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
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
        $ignore_attributes = array("BlueOnyx_Info_Text", "_TARGET", "domain", "mx_allow", "ip_allow", "host_allow", "ip_additional", "host_additional", "domain_additional", "restrict");

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

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Find out the TYPE of entry we're dealing with:
        if (isset($get_form_data['TYPE'])) {
            $TYPE = $get_form_data['TYPE'];
        }
        if (!isset($TYPE)) {
            $TYPE = $form_data['TYPE'];
        }

        if (!isset($TYPE)) {
            // We *still* have no $TYPE set? Then you should not be here!
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#4");
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

        // Get domauth / netauth - if set:
        $get_form_data = $CI->input->get(NULL, TRUE);
        if(isset($get_form_data['domauth'])) {
            $domauth = urldecode($get_form_data['domauth']);
            $netauth = '';
            $url_suffix = "&domauth=" . $get_form_data['domauth'];
            $dom_default = $domauth;
        }
        else {
            $netauth = '';
            $domauth = '';
            $url_suffix = '';
            $dom_default = '';
            $net_defaults = '';
        }

        //
        //--- Extended Security Check:
        //
        $allAliases = array();
        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));
        $vsite_dns = $CI->cceClient->getObject('Vsite', array('name' => $group), "DNS");
        if ($vsite_dns["domains"] != "") {
            $allAliases = $CI->cceClient->scalar_to_array($vsite_dns["domains"]);
        }
        else {
            $allAliases = array();
        }

        // Now make sure that $domauth is among the managed domains:
        if (!in_array($domauth, $allAliases)) {
            // Trying to mess with us? No, thanks!
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#nope");
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // Add the Record Type to the Attribute set as well:
            if ($TYPE == 'SUBDOM') { 
                $attributes['type'] = 'SN';
            }
            else {
                $attributes['type'] = $TYPE;
            }

            // We have no errors. We submit to CODB.
            if ($_TARGET == "NEW") {
                // Create a new Object:
                $CI->cceClient->create("DnsRecord", $attributes);
            }
            else {
                // We update an existing Object:
                $CI->cceClient->set($_TARGET, "", $attributes);
            }

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // Also commit the changes to restart the DNS server:
            $update['commit'] = time();
            $CI->cceClient->set($system['OID'], "DNS",  $update);

            // No errors during submit? Redirect to previous page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                if ($domauth != "") {
                    $redirect_URL = $parent . "&domauth=" . urlencode($domauth);
                }
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Page Logic:
        //

        $nm_to_dec = array(
          "0.0.0.0"   => "0",
          "128.0.0.0" => "1",   "255.128.0.0" => "9",   "255.255.128.0" => "17",    "255.255.255.128" => "25",
          "192.0.0.0" => "2",   "255.192.0.0" => "10",  "255.255.192.0" => "18",    "255.255.255.192" => "26",
          "224.0.0.0" => "3",   "255.224.0.0" => "11",  "255.255.224.0" => "19",    "255.255.255.224" => "27",
          "240.0.0.0" => "4",   "255.240.0.0" => "12",  "255.255.240.0" => "20",    "255.255.255.240" => "28",
          "248.0.0.0" => "5",   "255.248.0.0" => "13",  "255.255.248.0" => "21",    "255.255.255.248" => "29",
          "252.0.0.0" => "6",   "255.252.0.0" => "14",  "255.255.252.0" => "22",    "255.255.255.252" => "30",
          "254.0.0.0" => "7",   "255.254.0.0" => "15",  "255.255.248.0" => "23",    "255.255.255.254" => "31",
          "255.0.0.0" => "8",   "255.255.0.0" => "16",  "255.255.255.0" => "24",    "255.255.255.255" => "32" );
        $dec_to_nm = array_flip($nm_to_dec);

        // Get the Object in question for edit:
        if ((isset($get_form_data['_LOAD'])) && (isset($get_form_data['_TARGET']))) {
            $_TARGET = $get_form_data['_TARGET'];
            $_LOAD = $get_form_data['_LOAD'];
            $DnsRecord = $CI->cceClient->get($_TARGET);
        }
        else {
            // We're not editing an existing DNS entry:
            $_TARGET = 'NEW';
            $_LOAD = '';
        }

        if (isset($DnsRecord)) {
            // Verify if it's an DnsRecord Object:
            if ($DnsRecord['CLASS'] != "DnsRecord") { 
                // This is not what we're looking for! Stop poking around!
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403#5");
            }
        }
        else {
            $DnsRecord = '';
        }

        //
        //-- Generate page:
        //

        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-dns", $iam . "&_TARGET=" . $_TARGET . '&TYPE=' . $TYPE . $url_suffix);
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('base_dns_vsite');
        $page_module = 'base_sitemanage';

        $defaultPage = "basic";

        if ($_LOAD == "1") {
            $title = "modify_dns_rec" . $TYPE;
        }
        else {
            $title = "create_dns_rec" . $TYPE;
        }

        $block =& $factory->getPagedBlock($title, array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
//      $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic Tab
        //

        // Pre-populate the formfield arrays:
        if (($_LOAD == "1") && (isset($DnsRecord))) {
            $mail_server_name = $DnsRecord['mail_server_name'];
            $strings = $DnsRecord['strings'];
            $alias_hostname = $DnsRecord['alias_hostname'];
            $alias_domainname = $DnsRecord['alias_domainname'];
            $netmask = $DnsRecord['netmask'];
            $network = $DnsRecord['network'];
            $delegate_dns_servers = $DnsRecord['delegate_dns_servers'];
            $domainname = $DnsRecord['domainname'];
            $ipaddr = $DnsRecord['ipaddr'];
            $network_delegate = $DnsRecord['network_delegate'];
            $mail_server_priority = $DnsRecord['mail_server_priority'];
            $hostname = $DnsRecord['hostname'];
        }
        else {
            $mail_server_name = '';
            $strings = '';
            $alias_hostname = '';
            $alias_domainname = '';
            $netmask = '';
            $network = '';
            $delegate_dns_servers = '';
            $domainname = '';
            $ipaddr = '';
            $network_delegate = '';
            $mail_server_priority = '';
            $hostname = '';
        }
    
        if ($TYPE == 'PTR') {
            // PTR Record:
            if ((isset($net_defaults)) && ($netmask == '')) {
                if (isset($net_defaults[1])) {
                    $netmask = $dec_to_nm[$net_defaults[1]];
                }
                else {
                    $netmask = '255.255.255.0';
                }
            }
            if ($netmask == '') {
                // Still empty? Use a default:
                $netmask = '255.255.255.0';
            }
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }

            // ptr_ip_address:
            $slave_ip = $factory->getIpAddress('ipaddr', $ipaddr, 'rw');
            $slave_ip->setOptional(FALSE);
            $block->addFormField(
                $slave_ip,
                $factory->getLabel("ptr_ip_address"), 
                $defaultPage
            );

            // PTR Subnet Netmask:
            $ptr_nm = $factory->getIpAddress('netmask', $netmask, 'rw');
            $ptr_nm->setOptional(FALSE);
            $block->addFormField(
                $ptr_nm,
                $factory->getLabel("ptr_mask"), 
                $defaultPage
            );

            // PTR hostname:
            $ptr_hostname = $factory->getDomainName('hostname', $hostname, 'rw');
            $ptr_hostname->setType("hostname");
            $ptr_hostname->setOptional(TRUE);
            $block->addFormField(
                $ptr_hostname,
                $factory->getLabel("ptr_host_name"), 
                $defaultPage
            );

            // PTR domain name:
            $ptr_domainname = $factory->getDomainName('domainname', $domainname, 'rw');
            $ptr_domainname->setOptional(FALSE);
            $block->addFormField(
                $ptr_domainname,
                $factory->getLabel("ptr_domain_name"), 
                $defaultPage
            );

        }
        elseif ($TYPE == 'CNAME') {
            // CNAME:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }

            // cname_host_name:
            $cname_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $cname_host_name->setType("hostname");
            $cname_host_name->setOptional(FALSE);
            $block->addFormField(
                $cname_host_name,
                $factory->getLabel("cname_host_name"), 
                $defaultPage
            );

            // cname_domain_name:
            $cname_domain_name = $factory->getDomainName('domainname', $domainname, 'rw');
            $cname_domain_name->setOptional(FALSE);
            $block->addFormField(
                $cname_domain_name,
                $factory->getLabel("cname_domain_name"), 
                $defaultPage
            );

            // cname_host_target:
            $cname_host_target = $factory->getDomainName('alias_hostname', $alias_hostname, 'rw');
            $cname_host_target->setType("hostname");
            $cname_host_target->setOptional(TRUE);
            $block->addFormField(
                $cname_host_target,
                $factory->getLabel("cname_host_target"), 
                $defaultPage
            );

            // cname_domain_target:
            $cname_domain_target = $factory->getDomainName('alias_domainname', $alias_domainname, 'rw');
            $cname_domain_target->setOptional(FALSE);
            $block->addFormField(
                $cname_domain_target,
                $factory->getLabel("cname_domain_target"), 
                $defaultPage
            );
        }
        elseif ($TYPE == 'MX') {
            // MX:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }
            if ($mail_server_priority == '') {
                $mail_server_priority = 'very_high';
            }

            // mx_host_name:
            $mx_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $mx_host_name->setType("hostnamePlusWildcard");
            $mx_host_name->setOptional(TRUE);
            $block->addFormField(
                $mx_host_name,
                $factory->getLabel("mx_host_name"), 
                $defaultPage
            );

            // mx_domain_name:
            $mx_domain_name = $factory->getDomainName('domainname', $domainname, 'rw');
            $mx_domain_name->setOptional(FALSE);
            $block->addFormField(
                $mx_domain_name,
                $factory->getLabel("mx_domain_name"), 
                $defaultPage
            );

            // mx_target_server:
            $mx_target_server = $factory->getDomainName('mail_server_name', $mail_server_name, 'rw');
            $mx_target_server->setOptional(TRUE);
            $block->addFormField(
                $mx_target_server,
                $factory->getLabel("mx_target_server"), 
                $defaultPage
            );

            // MX Priority:
            $mx_priority_select = $factory->getMultiChoice("mail_server_priority", array_values(array("extremely_high", "very_high", "high", "low", "very_low")));
            $mx_priority_select->setSelected($mail_server_priority, true);
            $block->addFormField(
                $mx_priority_select, 
                $factory->getLabel("mx_priority"), 
                $defaultPage
            );
        }
        elseif ($TYPE == 'SUBDOM') {
            // Subdomain delegation:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
                $subdom_domain_name_access = "r";
            }
            else {
                $subdom_domain_name_access = "rw";
            }

            // subdom_domain_name:
            $subdom_domain_name = $factory->getDomainName('domainname', $domainname, $subdom_domain_name_access);
            $subdom_domain_name->setOptional(FALSE);
            $block->addFormField(
                $subdom_domain_name,
                $factory->getLabel("subdom_domain_name"), 
                $defaultPage
            );

            // subdom_host_name:
            $subdom_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $subdom_host_name->setType("hostname");
            $subdom_host_name->setOptional(FALSE);
            $block->addFormField(
                $subdom_host_name,
                $factory->getLabel("subdom_host_name"), 
                $defaultPage
            );

            // subdom_nameservers:
            $subdom_nameservers = $factory->getDomainNameList('delegate_dns_servers', $delegate_dns_servers, 'rw');
            $subdom_nameservers->setOptional(FALSE);
            $block->addFormField(
                $subdom_nameservers,
                $factory->getLabel("subdom_nameservers"), 
                $defaultPage
            );
        }
        elseif ($TYPE == 'SUBNET') {
            // Subnet delegation:
            // This is no longer supported and must have been removed a while ago.
            // So no need to bother with adding it here.
        }
        elseif ($TYPE == 'TXT') {
            // TXT records:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }
            // txt_host_name:
            $txt_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $txt_host_name->setType("hostnamePlusWildcard");
            $txt_host_name->setOptional(TRUE);
            //$txt_host_name->setType("TXThostname"); // Set valiadtion to 'TXThostname'.
            $block->addFormField(
                $txt_host_name,
                $factory->getLabel("txt_host_name"), 
                $defaultPage
            );
            // txt_domain_name:
            $txt_domain_name = $factory->getDomainName('domainname', $domainname, 'rw');
            $txt_domain_name->setOptional(FALSE);
            $block->addFormField(
                $txt_domain_name,
                $factory->getLabel("txt_domain_name"), 
                $defaultPage
            );
            // txt_strings:
            $txt_strings = $factory->getTextField('strings', $strings, 'rw');
            $txt_strings->setOptional(FALSE);
            $txt_strings->setType(""); // Turn valiadtion off for greater flexibility.
            $block->addFormField(
                $txt_strings,
                $factory->getLabel("txt_strings"), 
                $defaultPage
            );

            //
            //--- SPF Wizard:
            //

            $BxPage->setExtraHeaders('<script src="/.adm/scripts/adminica/spf-min.js"></script>');
            $BxPage->setExtraHeaders('<link href="/.adm/styles/adminica/spf.css" rel="stylesheet" type="text/css" media="screen">');

            $spf_quatsch = '
                <div class="flat_area grid_16">
                        <div>
                            <h2>' . $i18n->getClean("spf_wizard") . '</h2>
                        </div>
                        <div>
                            <p>' . $i18n->getClean("spf_intro_text") . '</p>
                        </div>
                        <div>
                            <div><h2>' . $i18n->getClean("dns_entry_header") . '</h2></div>
                                <div class="flat_area grid_16">
                                    <div id="dnsentry">&nbsp;</div>
                                </div>
                            <br>
                        </div>
                        <div>

                        <table>
                            <tr>
                                <td style="width: 270px;">' . $i18n->getClean("your_domain") . '</td>
                                <td><input type="text" name="domain" id="domain" class="tooltip right uniform" title="' . $i18n->getWrapped("your_domain_help") . '" onkeyup="getSPF();" value="'. $domainname . '" /></td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("allow_mx") . '</td>
                                <td>
                                    <select name="mx_allow" id="mx_allow" class="tooltip right" title="' . $i18n->getWrapped("allow_mx_help") . '" onchange="getSPF();" >
                                        <option value="0">' . $i18n->getClean("no") . '</option>
                                        <option value="1">' . $i18n->getClean("yes_recommended") . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("allow_current_ip") . '</td>
                                <td>
                                    <select name="ip_allow" id="ip_allow" class="tooltip right" title="' . $i18n->getWrapped("allow_current_ip_help") . '" onchange="getSPF();">
                                        <option value="0">' . $i18n->getClean("no") . '</option>
                                        <option value="1">' . $i18n->getClean("yes_recommended") . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("allow_hostname") . '</td>
                                <td>
                                    <select name="host_allow" id="host_allow" class="tooltip right" title="' . $i18n->getWrapped("allow_hostname_help") . '" onchange="getSPF();">
                                        <option value="0">' . $i18n->getClean("no_recommended") . '</option>
                                        <option value="1">' . $i18n->getClean("yes") . '</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("allow_ip_cidr") . '</td>
                                <td><input type="text" name="ip_additional" id="ip_additional" class="tooltip right uniform" title="' . $i18n->getWrapped("allow_ip_cidr_help") . '" onkeyup="getSPF();" /></td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("allow_other_hostname") . '</td>
                                <td><input type="text" name="host_additional" id="host_additional" class="tooltip right uniform" title="' . $i18n->getWrapped("allow_other_hostname_help") . '" onkeyup="getSPF();" /></td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("any_other_domains") . '</td>
                                <td><input type="text" name="domain_additional" id="domain_additional" class="tooltip right uniform" title="' . $i18n->getWrapped("any_other_domains_help") . '" onkeyup="getSPF();" /></td>
                            </tr>
                            <tr>
                                <td>' . $i18n->getClean("how_strict") . '</td>
                                <td>
                                    <select name="restrict" id="restrict" class="tooltip right" title="' . $i18n->getWrapped("how_strict_help") . '" onchange="getSPF();">
                                        <option value="0">' . $i18n->getClean("dash") . '</option>
                                        <option value="1">' . $i18n->getClean("fail_notcompliant") . '</option>
                                        <option value="2">' . $i18n->getClean("softfail") . '</option>
                                        <option value="3">' . $i18n->getClean("neutral_maybe") . '</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                </div>' . "\n";
            $block->addFormField(
                $factory->getRawHTML("SPF", $spf_quatsch),
                $factory->getLabel("SPF"),
                $defaultPage
            );
        }
        elseif ($TYPE == 'AAAA') {
            // A Record:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }

            // a_host_name:
            $a_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $a_host_name->setType("hostnamePlusWildcard");
            $a_host_name->setOptional(TRUE);
            $block->addFormField(
                $a_host_name,
                $factory->getLabel("a_host_name"), 
                $defaultPage
            );
            // a_domain_name:
            $a_domain_name = $factory->getDomainName('domainname', $domainname, 'rw');
            $a_domain_name->setOptional(FALSE);
            $block->addFormField(
                $a_domain_name,
                $factory->getLabel("a_domain_name"), 
                $defaultPage
            );
            // a_ip_address:
            $aaaa_ip_address = $factory->getTextField('ipaddr', $ipaddr, 'rw');
            $aaaa_ip_address->setOptional(FALSE);
            $aaaa_ip_address->setType("ipaddrIPv6");
            $block->addFormField(
                $aaaa_ip_address,
                $factory->getLabel("aaaa_ip_address"), 
                $defaultPage
            );
        }
        else {
            // A Record:
            if ($domauth != "") {
                $alias_domainname = $domauth;
                $domainname = $domauth;
            }

            // a_host_name:
            $a_host_name = $factory->getDomainName('hostname', $hostname, 'rw');
            $a_host_name->setType("hostnamePlusWildcard");
            $a_host_name->setOptional(TRUE);
            $block->addFormField(
                $a_host_name,
                $factory->getLabel("a_host_name"), 
                $defaultPage
            );
            // a_domain_name:
            $a_domain_name = $factory->getDomainName('domainname', $domainname, 'rw');
            $a_domain_name->setOptional(FALSE);
            $block->addFormField(
                $a_domain_name,
                $factory->getLabel("a_domain_name"), 
                $defaultPage
            );
            // a_ip_address:
            $a_ip_address = $factory->getIpAddress('ipaddr', $ipaddr, 'rw');
            $a_ip_address->setOptional(FALSE);
            $a_ip_address->setCurrentLabel($i18n->get("a_ip_address"));
            $a_ip_address->setDescription($i18n->getWrapped("a_ip_address_help"));
            $block->addFormField(
                $a_ip_address,
                $factory->getLabel("a_ip_address"), 
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

        if ($domauth != "") {
            $redirect_URL = $parent . "&domauth=" . urlencode($domauth);
        }
        else {
            $redirect_URL = $parent;
        }
        $block->addButton($factory->getCancelButton($redirect_URL));

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