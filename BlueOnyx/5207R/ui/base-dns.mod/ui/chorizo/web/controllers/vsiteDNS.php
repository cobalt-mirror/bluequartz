<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vsiteDNS extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /dns/vsiteDNS.
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

        // Lastly, check if we have 'siteDNS' caps:
        if (!$Capabilities->getAllowed('siteDNS')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#3");
        }

        //
        //-- Prepare data:
        //

        $nm_to_dec = array(
            "0.0.0.0"  => "0",
            "128.0.0.0" => "1", "255.128.0.0" => "9", "255.255.128.0" => "17", "255.255.255.128" => "25",
            "192.0.0.0" => "2", "255.192.0.0" => "10", "255.255.192.0" => "18", "255.255.255.192" => "26",
            "224.0.0.0" => "3", "255.224.0.0" => "11", "255.255.224.0" => "19", "255.255.255.224" => "27",
            "240.0.0.0" => "4", "255.240.0.0" => "12", "255.255.240.0" => "20", "255.255.255.240" => "28",
            "248.0.0.0" => "5", "255.248.0.0" => "13", "255.255.248.0" => "21", "255.255.255.248" => "29",
            "252.0.0.0" => "6", "255.252.0.0" => "14", "255.255.252.0" => "22", "255.255.255.252" => "30",
            "254.0.0.0" => "7", "255.254.0.0" => "15", "255.255.248.0" => "23", "255.255.255.254" => "31",
            "255.0.0.0" => "8", "255.255.0.0" => "16", "255.255.255.0" => "24", "255.255.255.255" => "32" ); 
            $dec_to_nm = array_flip($nm_to_dec);

        // Get data for the Vsite:
        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));

        // Get Vsite DNS data:
        $vsite_dns = $CI->cceClient->getObject('Vsite', array('name' => $group), "DNS");

        $default_domauth = $vsite['domain'];
        $default_netauth = "";

        if(isset($get_form_data['domauth'])) {
            $domauth = urldecode($get_form_data['domauth']);
            $netauth = '';
        }
        else {
            $domauth = $default_domauth;
        }
        if ($domauth == "") {
            $domauth = $default_domauth;
        }

        if(isset($get_form_data['netauth'])) {
            $netauth = urldecode($get_form_data['netauth']);
            $domauth = '';
        }
        else {
            $netauth = '';
        }

        $iam = "/dns/vsiteDNS?group=$group"; 
        $addmod = "/dns/vsite_dns_add?group=$group"; 
        $soamod = "/dns/vsite_dns_soa?group=$group";

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

        if ($CI->input->post(NULL, TRUE)) {
            // Make sure we have the $group set:
            if ((isset($group)) && (!isset($attributes['group']))) {
                $attributes['group'] = $group;
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
            // Update list of domains we're responsible for:
            if ((isset($attributes['dnsNames'])) && (isset($attributes['group']))) {
                $CI->cceClient->set($vsite['OID'], 'DNS', array("domains" => $attributes['dnsNames']));
            }
            else {
                $CI->cceClient->set($vsite['OID'], 'DNS', array("domains" => ""));
            }
            $errors = array_merge($errors, $CI->cceClient->errors());

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/dns/vsiteDNS?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-dns", $iam);
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $confirm_removal = $i18n->get('confirm_removal'); 
        $confirm_delall = $i18n->get('confirm_delall'); 
        $records_title_separator = ' - ';

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('base_dns_vsite');
        $page_module = 'base_sitemanage';
        $defaultPage = "defaultPage";

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page.  Site admins
        // can view it for informational purposes.
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = TRUE;
            $access = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access = 'rw';
            $is_site_admin = FALSE;
        }
        else {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#4");
        }

        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group)); 
        $vsite_dns = $CI->cceClient->getObject('Vsite', array('name' => $group), "DNS");

        // Find out which domains we can manage:
        $allAliases = array();
        if ($vsite_dns["domains"] != "") {
            $allAliases = $CI->cceClient->scalar_to_array($vsite_dns["domains"]);
        }
        else {
            $allAliases = array();
        }

        // Only 'systemAdministrator' and Resellers with 'siteDNS' can see this part:
        if (($Capabilities->getAllowed('adminUser')) || (($Capabilities->getAllowed('manageSite')) && ($Capabilities->getAllowed('siteDNS')))) {

            // how to get web aliases & email aliases? 
            $_settings = $factory->getPagedBlock("dnsNames_header", array($defaultPage));
            $_settings->setDefaultPage($defaultPage);

            $webAliases = $CI->cceClient->scalar_to_array($vsite["webAliases"]); 
            $mailAliases = $CI->cceClient->scalar_to_array($vsite["mailAliases"]); 
            $availableAliases = array_merge_alt($webAliases, $mailAliases);

            $key = array_search($vsite["domain"], $availableAliases);
            if ( $key === FALSE ) {
                array_push($availableAliases, $vsite["domain"]);
            }

            $picklist =& $factory->getSetSelector('dnsNames',
                                    $CI->cceClient->array_to_scalar($allAliases), 
                                    $CI->cceClient->array_to_scalar($availableAliases),
                                    'selected', 'notSelected',
                                    'rw', 
                                    $CI->cceClient->array_to_scalar(array_values($allAliases)),
                                    $CI->cceClient->array_to_scalar(array_values($availableAliases))
                                );

            $picklist->setOptional(true);

            $_settings->addFormField($picklist, 
                        $factory->getLabel('adminPowers'),
                        $defaultPage
                        );

            $_settings->addFormField(
                $factory->getRawHTML("group", '<INPUT TYPE="HIDDEN" NAME="group" VALUE="' . $group . '">'),
                $factory->getLabel("group"), 
                $defaultPage
            );

            $_settings->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        }

        //
        //--- Handle mass deletion and single record deletion:
        //

        $addUrl = "";
        if (isset($domauth)) {
            $addUrl = "&domauth=$domauth";
        }

        // Handle mass deletion:
        if (isset($get_form_data['_DELMANY'])) {
            if (preg_match('/^[1-9][0-9]{0,15}$/', $get_form_data['_DELMANY'])) {
                // Single OID to delete:
                $_DELMANY = array($get_form_data['_DELMANY']);
            }
            else {
                // Multiple OID's to delete:
                $_DELMANY = explode("x", $get_form_data['_DELMANY']);
            }
            // Check the input we have to make sure it is what we think it might be:
            foreach ($_DELMANY as $oid) {
                // Check if it is numeric:
                if (preg_match('/^[1-9][0-9]{0,15}$/', $oid)) {
                    // Verify if it's an DnsRecord Object:
                    $DnsRecord = $CI->cceClient->get($oid);

                    if ($DnsRecord['CLASS'] != "DnsRecord") { 
                        // This is not what we're looking for! Stop poking around!
                        // Nice people say goodbye, or CCEd waits forever:
                        $CI->cceClient->bye();
                        $CI->serverScriptHelper->destructor();
                        Log403Error("/gui/Forbidden403#MD1");
                    }
                    else {
                        // Handle the delete action if appropriate and not in demo-mode:
                        if ((isset($oid)) && (!is_file("/etc/DEMO"))) {
                            // Before we destroy the record, we check if it is really among
                            // the records that this domain can manage. If it is not, we 
                            // simply skip over it without throwing an error. Because we're
                            // in a good mood and take into account that the originating
                            // page might have been cached:
                            if (in_array($DnsRecord['domainname'], $allAliases)) {
                                $CI->cceClient->destroy($oid);
                                // CCE errors that might have happened during submit to CODB:
                                $CCEerrors = $CI->cceClient->errors();
                                foreach ($CCEerrors as $object => $objData) {
                                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                                    $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                                }
                            }
                        }
                    }
                }
                else {
                    // Non-numeric OID!
                    // This is not what we're looking for! Stop poking around!
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#MD2");
                }
            }
            // Also commit the changes to restart the DNS server:
            $update['commit'] = time();
            $CI->cceClient->set($system['OID'], "DNS",  $update);

            // No errors during submit? Redirect to previous page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("location: $iam$addUrl");
                exit;
            }
        }

        // Get the Object in question for the delete action:
        if (isset($get_form_data['_RTARGET'])) {
            $_RTARGET = $get_form_data['_RTARGET'];
            $DnsRecord = $CI->cceClient->get($_RTARGET);

            if (!in_array($DnsRecord['domainname'], $allAliases)) {
                // Is this domain amongst the ones that this domain can manage?
                // It is not? Silently return to the previous page. We could
                // throw an error, but this might be a cached page. So we forgive.
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("location: $iam$addUrl");
                exit;
            }
            elseif ($DnsRecord['CLASS'] != "DnsRecord") { 
                // Verify if it's an DnsRecord Object to begin with:
                // This is not what we're looking for! Stop poking around!
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403#MD3");
            }
            else {
                // Handle the delete action if appropriate:
                if (isset($_RTARGET)) {
                    if (!is_file("/etc/DEMO")) {
                        $CI->cceClient->destroy($_RTARGET);
                    }

                    // CCE errors that might have happened during submit to CODB:
                    $CCEerrors = $CI->cceClient->errors();
                    foreach ($CCEerrors as $object => $objData) {
                        // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                        $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                    }

                    // Also commit the changes to restart the DNS server:
                    $update['commit'] = time();
                    $CI->cceClient->setObject("System", $update, "DNS");

                    // No errors during submit? Redirect to previous page:
                    if (count($errors) == "0") {
                        $CI->cceClient->bye();
                        $CI->serverScriptHelper->destructor();
                        header("location: $iam$addUrl");
                        exit;
                    }
                }               
            }
        }


        // Grab system-DNS data
        $sys_oid = $CI->cceClient->find('System');
        $sys_dns = $CI->cceClient->get($sys_oid, 'DNS');

        // Abstract our authorities list
        // build a pull-down menu, select a default authority
        $oids = $CI->cceClient->find("DnsSOA");
        $rec_oids = array();
        $smallnet = array();
        $auth_dom_oids = array();
        $auth_net_oids = array();
        $authorities_dom_label = array();
        $authorities_net_label = array();
        $auth_oids = array();

        rsort($oids);
        if (count($oids)) { // Any current records?
            for ($i = 0; $i <= $oids[0]; $i++) {
                if (isset($oids[$i])) {
                    $rec = $CI->cceClient->get($oids[$i], "");
                    if (in_array($rec["domainname"], $allAliases)) {
                        $authorities_dom[$rec["domainname"]] = "$iam&domauth=".urlencode($rec["domainname"]);
                        $authorities_dom_label[$rec["domainname"]] = "$iam&domauth=".urlencode($rec["domainname"]);
                        $auth_oids[$rec['domainname']] = $oids[$i];
                        array_push($auth_dom_oids, $oids[$i]);
                    }
                }
            }
        }

        // Make sure that we have populated the pulldown with all domains that we have
        // under management for this Viste. If one or more is missing, add them here:
        foreach ($allAliases as $keydomain) {
            if (!in_array($keydomain, $authorities_dom_label)) {
                $authorities_dom_label[$keydomain] = "$iam&domauth=".urlencode($keydomain);
            }
        }

        $block =& $factory->getPagedBlock($i18n->get('dnsSetting') . " - " . $domauth, array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
//      $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        $ScrollList = $factory->getScrollList("dnsSetting", array("source", "direction", "resolution", "listAction"), array()); 
        $ScrollList->setAlignments(array("left", "center", "left", "center"));
        $ScrollList->setDefaultSortedIndex('0');
        $ScrollList->setSortOrder('ascending');
        $ScrollList->setSortDisabled(array('3', '4'));
        $ScrollList->setPaginateDisabled(FALSE);
        $ScrollList->setSearchDisabled(FALSE);
        $ScrollList->setSelectorDisabled(FALSE);
        $ScrollList->enableAutoWidth(FALSE);
        $ScrollList->setInfoDisabled(FALSE);
        $ScrollList->setColumnWidths(array("244", "150", "244", "100")); // Max: 739px

        // We only show the SOA and Delete buttons if we're actually 
        // looking at the records of a domain:
        $DNS_top_buttons = array();
        if ($domauth != '') {
            $domauth = urldecode($domauth);

            // Check if the domain is among the ones we are allowed to edit:
            if ((!in_array($domauth, $allAliases)) && (count($allAliases) > 0)) {
                // It is not? Reload page:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/dns/vsiteDNS?group=$group";
                header("location: $redirect_URL");
                exit;
            }

            $rec_oids = $CI->cceClient->find("DnsRecord", array('domainname' => $domauth));
            $auth_link = '&domauth=' . $domauth;
            if (isset($auth_oids[$domauth])) {
                $DNS_top_buttons[] = $factory->getButton("$soamod&_LOAD=" . $auth_oids[$domauth] . $auth_link,"edit_soa");
            }
            else {
                if (isset($auth_oids[$default_domauth])) {
                    $DNS_top_buttons[] = $factory->getButton("$soamod&_LOAD=" . $auth_oids[$default_domauth] . $auth_link,"edit_soa");
                }
            }
            $many_oids = join('x', $rec_oids);
            $DNS_top_buttons[] = $factory->getRawHTML("del_records", '<a class="lb" href="' . "$iam&_DELMANY=$many_oids". '"><button class="no_margin_bottom div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("del_records") . '"><div class="ui-icon ui-icon-trash"></div><span>' . $i18n->getHtml("del_records") . '</span></button></a>');

            $rblbuttonContainer = $factory->getButtonContainer("dnsSetting", $DNS_top_buttons);

            // If we have records, we show the button container:
            if (count($rec_oids) > 0) {
                $block->addFormField(
                    $rblbuttonContainer,
                    $factory->getLabel("dnsrecords"),
                    $defaultPage
                );
            }
        }

        if (!isset($auth_link)) {
            $auth_link = '&domauth=' . $domauth;
        }

        // Array of labels => actions for "add a record" menu
        $addRecordsList = array(
                    "a_record" => "$addmod&TYPE=A" . $auth_link,
                    "aaaa_record" => "$addmod&TYPE=AAAA" . $auth_link,
                    "mx_record" => "$addmod&TYPE=MX" . $auth_link,
                    "cname_record" => "$addmod&TYPE=CNAME" . $auth_link,
                    "txt_record" => "$addmod&TYPE=TXT" . $auth_link);

        if ((isset($domauth)) && (isset($netauth))) {
            if ($domauth != '') {
                $addRecordsList['subdom'] = "$addmod&TYPE=SUBDOM" . $auth_link;
            }
            elseif ($netauth != '') {
                $addRecordsList['subnet'] = "$addmod&TYPE=SUBNET" . $auth_link;
            }
        }

        // Display records:
        rsort($rec_oids);
        if(count($rec_oids)) { 
            for ($i = 0; $i < $rec_oids[0]; $i++) {
                if(isset($rec_oids[$i])) {
                    $oid = $rec_oids[$i];
                    $rec = $CI->cceClient->get($oid, "");

                    /*
                     * we could add a recordtype if structure to build the 
                     * scrollist entries aesthetically
                     * all records define 
                     * { $source, $direction, $resolution, $label }
                     */
                    $direction = $rec['type'];
                    $resolution = '';
                    $source = '';
                
                    if ($rec['type'] == 'A') {
                        if($rec['hostname']) { 
                            $source = $rec['hostname'] . ' . '; 
                        }
                        $source .= $rec['domainname'];
                        $direction = $i18n->get('a_dir');
                        $resolution = $rec['ipaddr'];
                        $label = $rec['hostname'] . '.' . $rec['domainname'];

                    }
                    elseif($rec['type'] == 'AAAA') {
                        if($rec['hostname']) { 
                            $source = $rec['hostname'] . ' . '; 
                        }
                        $source .= $rec['domainname'];
                        $direction = $i18n->get('aaaa_dir');
                        $resolution = $rec['ipaddr'];
                        $label = $rec['hostname'] . '.' . $rec['domainname'];
                    }
                    elseif($rec['type'] == 'PTR') {
                        $source = $rec['ipaddr'];
                        if ($domauth) {
                            $source .= '/' . $rec['netmask'];
                        }
                        if ($rec['hostname'] != '') { 
                            $resolution = $rec['hostname'] . ' . '; 
                        }
                        $direction = $i18n->get('ptr_dir');
                        $resolution .= $rec['domainname'];
                        $label = $rec['ipaddr'] . '/' . $rec['netmask'];

                    }
                    elseif ($rec['type'] == 'CNAME') {
                        if($rec['hostname'] != '') { 
                            $source = $rec['hostname'].' . '; 
                        } 
                        $source .= $rec['domainname'];
                        $direction = $i18n->get('cname_dir');
                        if ($rec['alias_hostname'] != '') {
                            $resolution = $rec['alias_hostname'] . ' . ';
                        }
                        $resolution .= $rec['alias_domainname'];
                        $label = $rec['alias_hostname'] . '.' . $rec['domainname'];

                    }
                    elseif ($rec['type'] == 'MX') {
                        if($rec['hostname']) { 
                            $source = $rec['hostname'] . ' . '; 
                        }
                        $source .= $rec['domainname'];
                        $resolution = $rec['mail_server_name'];
                        $direction = $i18n->get('mx_dir_' . 
                            $rec['mail_server_priority']);
                        $label = $rec['hostname'] . '.' . $rec['domainname'];

                    }
                    elseif ($rec['type'] == 'TXT') {
                        if($rec['hostname']) {
                            $source = $rec['hostname'] . ' . ';
                        }
                        $source .= $rec['domainname'];
                        $resolution = $rec['strings'];
                        $direction = $i18n->get('txt_dir');
                        $label = $rec['hostname'] . '.' . $rec['domainname'];

                    }
                    elseif ($rec['type'] == 'SN') {
                        if($rec['ipaddr']) { 
                            $rec['type'] = 'SUBNET';
                            $direction = $i18n->get('subnet_dir');

                            $smallnet = preg_split('/\//', $rec['network_delegate']);
                            $source = $smallnet[0] . '/' .
                                $dec_to_nm[$smallnet[1]];
                            $resolution = $rec['delegate_dns_servers'];
                            $label = $rec['ipaddr'] . '/' . $rec["netmask"];
                        }
                        else {
                            $rec['type'] = 'SUBDOM';
                            $direction = $i18n->get('subdom_dir');

                            $source = $rec['hostname'].' . '.$rec['domainname'];
                            $resolution = $rec['delegate_dns_servers'];
                            $label = $rec['hostname'].'.'.$rec['domainname'];
                        }
                        $resolution = preg_replace('/^&/', '', $resolution);
                        $resolution = preg_replace('/&$/', '', $resolution);
                        $resolution = preg_replace('/&/', ' ', $resolution);
                    }
                    else {
                        //next;
                        //echo "unkown type: ".$rec['type']."\n";
                    }

                    $modify_button = $factory->getModifyButton("$addmod&_BlockID=_".$rec['type']."&_TARGET=$oid&_LOAD=1&TYPE=".$rec['type'].$auth_link);
                    $modify_button->setImageOnly(TRUE);
                    $remove_button = $factory->getRemoveButton("$iam&_RTARGET=$oid$auth_link");
                    $remove_button->setImageOnly(TRUE);

                    $combined_buttons = $factory->getCompositeFormField(array($modify_button, $remove_button));

                    // Populate Scrollist
                    $ScrollList->addEntry(array(
                        $source,
                        $direction,
                        $resolution,
                        $combined_buttons,
                        ''
                    ));
                }
            }
        }

        // -- Add Pulldown for Domain-Selection:
        if(count($authorities_dom_label) > 0) {
            // select-an-authority button
            ksort($authorities_dom_label);
            $authorityDomButton = $factory->getMultiButton("select_dom", array_values($authorities_dom_label), array_keys($authorities_dom_label));

            $block->addFormField(
                $factory->getRawHTML("filler", "&nbsp;"),
                $factory->getLabel(" "),
                $defaultPage
            );
            $filler_present = "1";

            $block->addFormField(
                $authorityDomButton,
                $factory->getLabel(" "),
                $defaultPage
            );
        }

        $addButton = $factory->getMultiButton("add_record",
                                              array_values($addRecordsList),
                                              array_keys($addRecordsList)
                                            );

        // Add the "Add Record..." Pulldown:
        $block->addFormField(
            $addButton,
            $factory->getLabel(" "),
            $defaultPage
        );

        // Show the ScrollList of the DNS Records:
        $block->addFormField(
            $factory->getRawHTML("dnsrecords", $ScrollList->toHtml()),
            $factory->getLabel("dnsrecords"),
            $defaultPage
        );

        // Extra header for the "do you really want to delete" dialog:
        $BxPage->setExtraHeaders('
            <script type="text/javascript">
            $(document).ready(function () {

              $("#dialog").dialog({
                modal: true,
                bgiframe: true,
                width: 500,
                height: 200,
                autoOpen: false
              });

              $(".lb").click(function (e) {
                e.preventDefault();
                var hrefAttribute = $(this).attr("href");

                $("#dialog").dialog(\'option\', \'buttons\', {
                  "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
                    window.location.href = hrefAttribute;
                  },
                  "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
                    $(this).dialog("close");
                  }
                });

                $("#dialog").dialog("open");

              });
            });
            </script>');

        // Add hidden Modal for Delete-Confirmation
        $page_body[] = '
            <div class="display_none">
                        <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-dns.del_records]]") . '">
                            <div class="block">
                                    <div class="section">
                                            <h1>' . $i18n->getHtml("[[base-dns.del_records]]") . '</h1>
                                            <div class="dashed_line"></div>
                                            <p>' . $i18n->getHtml("[[base-dns.confirm_delall]]") . '</p>
                                    </div>
                            </div>
                        </div>
            </div>' . "\n";

        // Add the DNS picklist. Only 'systemAdministrator' and Resellers with 'siteDNS' can see this:
        if (($Capabilities->getAllowed('adminUser')) || (($Capabilities->getAllowed('manageSite')) && ($Capabilities->getAllowed('siteDNS')))) {
            $page_body[] = $_settings->toHtml();
            $page_body[] = "<br>\n";
        }

        // Only show the ScrollList and the shebang around it if we have DNS
        // records to manage for this Vsite and the authority for it:
        if (count($allAliases) > 0) {
            $page_body[] = $block->toHtml();
        }

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