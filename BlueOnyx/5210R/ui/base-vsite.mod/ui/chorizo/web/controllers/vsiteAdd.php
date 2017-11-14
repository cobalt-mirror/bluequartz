<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class VsiteAdd extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/vsiteAdd.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $sessionId and $CI->BX_SESSION['loginName'] from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
        $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

        // Line up the ducks for CCE-Connection and store them for re-usability in $CI:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();

        $i18n = new I18n("base-vsite", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $CODBDATA = $CI->BX_SESSION['loginUser'];

        // Not 'manageSite'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('manageSite')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // -- Actual page logic start:
        $extra_headers = array();
        $access = 'rw';

        // We start without any active errors:
        $errors = array();
        $ci_errors = array();
        $my_errors = array();

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);
        $get_data = $CI->input->get(NULL, TRUE);

        // Form fields that are required to have input:
        $required_keys = array('hostName', 'domain', 'createdUser', 'volume', 'maxusers');

        // Check the status of IPv6 and if we show IPv6 related fields:
        if ($system['gateway_IPv6'] != "") {
            $access_ipv6 = 'rw';
            $show_IPv6 = TRUE;
        }
        else {
            $access_ipv6 = 'r';
            $show_IPv6 = FALSE;
        }

        // Check the status of IPv4 and if we shizld show IPv4 related fields:
        if ($system['gateway'] != "") {
            $show_IPv4 = TRUE;
        }
        else {
            $show_IPv4 = FALSE;
        }

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("BlueOnyx_Info_Text", "_serialized_errors");

        // Get $errors from ServerScriptHandler POST vars:
        if (isset($form_data['_serialized_errors'])) {
            $TMPerrors = array_merge($errors, unserialize($form_data['_serialized_errors']));
            foreach ($TMPerrors as $errNum => $errMsg) {
                if (!is_object($errMsg)) {
                    // Error message is not an Object. We urldecode() it and use it as is:
                    $errors[$errNum] = urldecode($errMsg);
                }
                else {
                    if ((isset($errMsg->key)) && (isset($errMsg->message))) {
                        // We already have an error Object. Use it:
                        $errors[$errNum] = ErrorMessage($i18n->get($errMsg->message, true, array('key' => $errMsg->key)) . '<br>&nbsp;');
                    }
                    elseif (isset($errMsg->message)) {
                        // Error message object without key:
                        $errors[$errNum] = ErrorMessage($i18n->get($errMsg->message, true) . '<br>&nbsp;');
                    }
                    else {
                        # Nothing to see.
                    }
                }
            }
            $attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
        }
        else {

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

                if ((isset($form_data['prefix'])) && (strlen($form_data['prefix']) > "0")) {
                    $attributes['userPrefixEnabled'] = "1";
                }
                else {
                    $attributes['userPrefixEnabled'] = "0";
                }
                if (!isset($attributes['ipAddr'])) {
                    $attributes['ipAddr'] = "";
                }
                if (!isset($attributes['ipaddrIPv6'])) {
                    $attributes['ipaddrIPv6'] = "";
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
                $vsiteOID = $CI->cceClient->create("Vsite", 
                             array(
                                'hostname' => $attributes['hostName'],
                                'domain' => $attributes['domain'],
                                'fqdn' => ($attributes['hostName'] . '.' . $attributes['domain']),
                                'ipaddr' => $attributes['ipAddr'],
                                'ipaddrIPv6' => $attributes['ipaddrIPv6'],
                                'createdUser' => $attributes['createdUser'], 
                                'webAliases' => $attributes['webAliases'],
                                'webAliasRedirects' => $attributes['webAliasRedirects'],
                                'emailDisabled' => $attributes['emailDisabled'],
                                'mailAliases' => $attributes['mailAliases'],
                                "mailCatchAll" => $attributes['mailCatchAll'],
                                'volume' => $attributes['volume'],
                                'maxusers' => $attributes['maxusers'],
                                'dns_auto' => $attributes['dns_auto'],
                                'prefix' => $attributes['prefix'],
                                "userPrefixEnabled" => $attributes['userPrefixEnabled'],
                                "userPrefixField" => $attributes['prefix'],
                                'site_preview' => $attributes['site_preview']
                             )
                            );

                // CCE errors that might have happened during submit to CODB:
                $CCEerrors = $CI->cceClient->errors();
                foreach ($CCEerrors as $object => $objData) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }

                // Setup Quota information:
                if ($vsiteOID) {
                    $attributes['quota'] = preg_replace('/\,/', '.', $attributes['quota']);
                    // Check if our quota has a unit:
                    $pattern = '/^(\d*[(\.)|(\,)]{0,1}\d+)(K|M|G|T)$/';
                    if (preg_match($pattern, $attributes['quota'], $matches, PREG_OFFSET_CAPTURE)) {
                        // Quota has a unit:
                        $quota = (unsimplify_number($attributes['quota'], "K")/1000);
                    }
                    else {
                        // Quota has no unit:
                        $quota = $attributes['quota'];
                    }

                    // If this is a reseller, check if the disk space changes would make him exceed his allowance:
                    if (!$CI->serverScriptHelper->getAllowed('systemAdministrator')) {
                        // Get a list of all sites he owns: 
                        $Userowned_Sites = $CI->cceClient->find('Vsite', array('createdUser' => $attributes['createdUser'])); 
                        $Quota_of_Userowned_Sites = $quota; // Set start quota to the value of the Quota the user wants this Vsite to have after the change. 
                        foreach ($Userowned_Sites as $oid) { 
                            $user_vsiteDisk = $CI->cceClient->get($oid, 'Disk'); 
                            $Quota_of_Userowned_Sites += $user_vsiteDisk['quota']; 
                        }
                        $Quota_of_Userowned_Sites = $Quota_of_Userowned_Sites*1000;

                        // Get the info about the 'manageSite' administrator:
                        @list($user_oid) = $CI->cceClient->find('User', array('name' => $attributes['createdUser'])); 

                        // Get the site allowance settings for this 'manageSite' user:
                        $AdminAllowances = $CI->cceClient->get($user_oid, 'Sites'); 
                        if ($Quota_of_Userowned_Sites > $AdminAllowances['quota']) {
                            // Reseller is trying to set more quota than he's allowed to:
                            $errors[] = ErrorMessage($i18n->get("[[base-vsite.quota]]") . '<br>&nbsp;');
                        }
                        else {
                            // Set the quota:
                            $ok = $CI->cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));                        
                        }
                    }
                    else {
                        // Not a reseller:

                        // Set the quota:
                        $ok = $CI->cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
                    }

                    $errors = array_merge($errors, $CI->cceClient->errors());

                    // If the WebApp Installer is present and RoundCube Autoinstall is enabled,
                    // then we might get a weird runtime issue with CCEd. So if the above SET
                    // for diskspace doesn't go through, we wait 5 seconds and try again.
                    // If THAT fails, too, then we do it yet again after 5 secs. If that STILL
                    // fails, we raise an error.
                    if ($ok === "0") {
                        // Sleep and do it again:
                        sleep(5);
                        $ok = $CI->cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
                        if ($ok === "0") {
                            // Sleep and do it again:
                            sleep(5);
                            $ok = $CI->cceClient->set($vsiteOID, 'Disk', array('quota' => $quota));
                            if ($ok === "0") {
                                $errors[] = new Error('[[base-vsite.cantReadVsite]]');
                            }
                        }
                    }
                    $errors = array_merge($errors, $CI->cceClient->errors());
                }

                /*
                 * Setup services only if the site was created successfully
                 * any errors after site creation above are non-fatal
                 */
                if ($vsiteOID) {
                    // Handle automatically detected services
                    list($servicesoid) = $CI->cceClient->find("VsiteServices");
                    $autoFeatures = new AutoFeatures($CI->serverScriptHelper, $attributes);
                    $af_errors = $autoFeatures->handle("create.Vsite", 
                                       array(
                                        "CCE_SERVICES_OID" => $servicesoid, 
                                        "CCE_OID" => $vsiteOID), $attributes);

                    $errors = array_merge($errors, $af_errors);
                }

                /*
                 * Defer the httpd reload/restart until 'force_update' is set:
                 */

                if ($vsiteOID) {
                    $ok = $CI->cceClient->set($vsiteOID, '', array('force_update' => time()));
                    $errors = array_merge($errors, $CI->cceClient->errors());
                }

                // Error check:
                if (count($errors) == "0") {
                    //
                    //--- We have no error. Redirect to Vsite-List:
                    //

                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    header("Location: /vsite/vsiteList");
                    exit;
                }
                else {
                    // We do have an error. And a partially create Vsite. So we destroy it:
                    if ((isset($vsiteOID)) && ($vsiteOID != "1") && ($vsiteOID != "0")) {
                        $ok = $CI->cceClient->destroy($vsiteOID);
                        if ($ok === FALSE) {
                            // If the first destroy() failed, we wait 5 secs and try again:
                            sleep(5);
                            $ok = $CI->cceClient->destroy($vsiteOID);
                            if ($ok === FALSE) {
                                // Try it one more time:
                                sleep(5);
                                $ok = $CI->cceClient->destroy($vsiteOID);
                                if ($ok === FALSE) {
                                    // Pointless. Give up and raise error:
                                    $errors[] = new Error('[[base-vsite.removeFailed]]');
                                }
                            }
                        }
                    }

                    // Remove duplicate Errors:
                    $errors = array_map("unserialize", array_unique(array_map("serialize", $errors)));
                    // Then we redirect back to this page by passing the errors and the post vars:
                    print $CI->serverScriptHelper->toHandlerHtml("/vsite/vsiteAdd", $errors, TRUE);
                }
            }
        }

        //
        //-- Generate page:
        //

        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/vsiteAdd");
        $BxPage = $factory->getPage();
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteList1');
        $page_module = 'base_sitemanageVSL';

        // Line up the ducks:
        /*
         *  DATA PRESERVATION
         *  One other possible use of the Errors array is to determine whether 
         *  any information needs to be read from CCE.  In the case of Vsite 
         *  addition, if there are errors present then there is no need to 
         *  get information from CCE for most things, because the data that was 
         *  in the fields, when the user clicked save, are available as
         *  global variables.  This should give a slight performance gain, but it isn't
         *  necessary for things to work correctly.
         */

        list($sysoid) = $CI->cceClient->find("System");
        if (count($errors) == 0) {
            // We have no errors. So we use the VsiteDefaults from CODB_
            $vsiteDefaults = $CI->cceClient->get($sysoid, "VsiteDefaults");
        }       
        else {
            // We have at least one error. Which means this is probably a
            // page reload after a post. Which means we have the post vars
            // and should use them instead of the VsiteDefaults. That way
            // we preserve the data that the user has entered before:
            $vsiteDefaults = $attributes;
            // Small correction due to naming inconsistencies:
            $vsiteDefaults['ipaddr'] = $attributes['ipAddr'];
        }

        $vsite = $CI->cceClient->get($sysoid, "Vsite"); 
        $vsiteoids = $CI->cceClient->find("Vsite"); 
        if ($vsite['maxVsite'] <= count($vsiteoids)) { 
            // The limit doesn't apply to systemAdministrators! 
            if (!$CI->serverScriptHelper->getAllowed('systemAdministrator')) {
                // But to everyone else:
                $errors[] = new Error('[[base-vsite.maxVsiteAlreadyMade]]');
            }
        } 

        $defaultPage = "basicSettingsTab";
        $secondPage = "otherServices";

        // Check vsite max for administrator 
        list($user_oid) = $CI->cceClient->find('User', array('name' => $CI->BX_SESSION['loginName'])); 
        $sites = $CI->cceClient->get($user_oid, 'Sites'); 
         
        $user_sites = $CI->cceClient->find('Vsite', array('createdUser' => $CI->BX_SESSION['loginName'])); 
        if ($sites['max'] > 0 && $sites['max'] <= count($user_sites)) { 
            $errors[] = ErrorMessage($i18n->getClean('[[base-vsite.maxVsiteAlreadyMade]]'), 'alert_red', 'alarm_bell', FALSE);

            $settings =& $factory->getPagedBlock("newVsiteSettings", array($defaultPage));

            // Hidden dummy text field. We need one or the error message won't show.
            $error_static = $factory->getTextField("dummy", $i18n->getClean("[[base-vsite.maxVsiteAlreadyMade]]"), '');
            $error_static->setLabelType("nolabel");
            $settings->addFormField(
                    $error_static,
                    $factory->getLabel("dummy"),
                    $defaultPage
                    );

            $settings->addButton($factory->getCancelButton("/vsite/vsiteList"));

            //
            //-- Error message handing:
            //
            $BXerrors = array();
            foreach ($errors as $object => $objData) {
                if (is_object($object)) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $BXerrors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }
                else {
                    $BXerrors[] = $objData;
                }
            }

            // Publish error messages:
            $BxPage->setErrors($BXerrors);

            //-- Generate page:
            $page_body[] = $settings->toHtml();

            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();

            // Out with the page:
            $BxPage->render($page_module, $page_body);

        }
        else { 

            $settings =& $factory->getPagedBlock("newVsiteSettings", array($defaultPage, $secondPage));
            $settings->setToggle("#");
            //$settings->setWindow("#");
            //$settings->setGrabber("#");
            $settings->setShowAllTabs("#");
            $settings->setSideTabs(FALSE);
            //$settings->setDefaultPage($secondPage);

            $net_opts = $CI->cceClient->get($sysoid, "Network");
            if ($net_opts["pooling"] == "1") {
                $range_strings = array();

                $oids = $CI->cceClient->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
                foreach ($oids as $oid) {
                    $range = $CI->cceClient->get($oid);
                    $adminArray = $CI->cceClient->scalar_to_array($range['admin']); 
                    if ($CI->BX_SESSION['loginName'] == 'admin' || in_array($CI->BX_SESSION['loginName'], $adminArray)) { 
                        if (filter_var($range['min'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $range_strings['v4'][] = $range['min'] . ' - ' . $range['max']; 
                        }
                        else {
                            $range_strings['v6'][] = $range['min'] . ' - ' . $range['max']; 
                        }
                    } 
                }

                $new_range_string = '';
                $nrs_num = "0";
                foreach ($range_strings['v4'] as $key => $value) {
                    if ($nrs_num > "0") {
                        $new_range_string .= "<br>";
                    }
                    $new_range_string .= $value;
                    $nrs_num++;
                }

                $ip_address = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
                $ip_address->setRange($new_range_string);
            }
            else {
                // IP Address, without ranges
                $ip_address = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
            }

            // IPv4 IP Address
            if ($show_IPv4) {
                $ip_address->setOptional(TRUE);
                $settings->addFormField(
                        $ip_address,
                        $factory->getLabel("ipAddr"),
                        $defaultPage
                        );
            }

            // IPv6:
            $new_range_string = '';
            $nrs_num = "0";
            if ($net_opts["pooling"] == "1") {
                // IPv6 IP Address, with ranges
                foreach ($range_strings['v6'] as $key => $value) {
                    if ($nrs_num > "0") {
                        $new_range_string .= "<br>";
                    }
                    $new_range_string .= $value;
                    $nrs_num++;
                }
                $ipv6_address = $factory->getIpAddress("ipaddrIPv6", $vsiteDefaults["ipaddrIPv6"], $access_ipv6);
                $ipv6_address->setRange($new_range_string);
            }
            else {
                // IPv6 IP Address, without ranges
                $ipv6_address = $factory->getIpAddress("ipaddrIPv6", $vsiteDefaults["ipaddrIPv6"], $access_ipv6);
            }

            // IPv6 IP Address, without ranges
            if ($show_IPv6) {
                $ipv6_address->setType("ipaddrIPv6");
                $ipv6_address->setOptional(TRUE);
                $settings->addFormField(
                        $ipv6_address,
                        $factory->getLabel("ipaddrIPv6"),
                        $defaultPage
                        );
            }

            // host and domain names
            if (isset($vsiteDefaults['hostname'])) {
                $server_hostname = $vsiteDefaults['hostname'];
            }
            else {
                $server_hostname = "";
            }       
            if (isset($vsiteDefaults['domain'])) {
                $server_domain = $vsiteDefaults['domain'];
            }
            else {
                $server_domain = "";
            }       

            $hostname_field = $factory->getDomainName("hostName", $server_hostname); 
            $hostname_field->setType("hostname");
            $hostname_field->setLabelType("label_top no_lines");

            $domainname_field = $factory->getDomainName("domain", $server_domain);
            $domainname_field->setType("domainname");
            $domainname_field->setLabelType("label_top no_lines");

            $fqdn =& $factory->getCompositeFormField(array($factory->getLabel("enterFqdn"), $hostname_field, $domainname_field), '');
            $fqdn->setColumnWidths(array('col_25', 'col_25', 'col_50'));

            $settings->addFormField(
                    $fqdn,
                    $factory->getLabel("enterFqdn"),
                    $defaultPage
                    );

            //-- Start Owner Management

            // Find all 'adminUsers' with the capability 'manageSite':
            $admins = $CI->cceClient->findx('User', 
                            array('systemAdministrator' => 0, 'capLevels' => 'manageSite'),
                            array());

            // Set up an array that - at least - has 'admin' in it:
            $adminNames = array('admin');
            foreach ($admins as $num => $oid) {
                $current = $CI->cceClient->get($oid);
                // Found a reseller, adding him to the array as well:
                $adminNames[] = $current['name'];
            }

            // Do we have form POST data with a 'createdUser'? If so, we use it:
            if (isset($form_data['createdUser'])) {
                $current_createdUser = $form_data['createdUser'];
            }
            else {
                // Else we need to set a current owner. Assume the current user:
                $current_createdUser = $CI->BX_SESSION['loginName'];
            }

            // If the current user has the cap 'serverManage', then he is allowed to change the owner:
            if ($CI->serverScriptHelper->getAllowed('serverManage')) { 
                // Sort the array values:
                asort($adminNames);
                // Build the MultiChoice selector:
                $current_createdUser_select = $factory->getMultiChoice("createdUser", array_values($adminNames));
                $current_createdUser_select->setSelected($current_createdUser, true);
                $settings->addFormField($current_createdUser_select, $factory->getLabel("createdUser"), $defaultPage);
            }
            else {
                // Current user doesn't have the cap 'serverManage'. So we just add a hidden TextField with the current owner in it:
                $settings->addFormField(
                        $factory->getTextField("createdUser", $CI->BX_SESSION['loginName'], "r"),
                        $factory->getLabel("createdUser"),
                        $defaultPage
                        );
            }

            //-- End Owner Management

            // Disk Volume:
            $settings->addFormField(
                    $factory->getTextField('volume', '/home', ''),
                    $factory->getLabel("volume"),
                    $defaultPage
                    );

            // Prefix:
            if (isset($vsiteDefaults['prefix'])) {
                $server_prefix = $vsiteDefaults['prefix'];
            }
            else {
                $server_prefix = "";
            }
            $vsite_prefix = $factory->getTextField("prefix", $server_prefix);
            $vsite_prefix->setOptional(TRUE);
            $vsite_prefix->setWidth(5);
            $vsite_prefix->setMaxLength(5);

            $settings->addFormField(
                $vsite_prefix,
                $factory->getLabel("prefix"),
                $defaultPage
                    );

            // web server aliases
            if (isset($vsiteDefaults['webAliases'])) {
                $webAliases_defaults = $vsiteDefaults['webAliases'];
            }
            else {
                $webAliases_defaults = "";
            }       
            $webAliasesField = $factory->getDomainNameList("webAliases", $webAliases_defaults);
            $webAliasesField->setOptional(TRUE);

            $settings->addFormField(
                    $webAliasesField,
                    $factory->getLabel("webAliases"),
                    $defaultPage
                    );

            # webAliasRedirect:
            $settings->addFormField(
                $factory->getBoolean('webAliasRedirects', $vsiteDefaults['webAliasRedirects'], 'rw'),
                $factory->getLabel('webAliasRedirects'), $defaultPage
                );      


            // enable & disable Email
            $settings->addFormField(
                    $factory->getBoolean("emailDisabled", $vsiteDefaults["emailDisabled"], "rw"),
                    $factory->getLabel("emailDisabled"),
                    $defaultPage
                    );

            // mail server aliases
            if (isset($vsiteDefaults['mailAliases'])) {
                $mailAliases_defaults = $vsiteDefaults['mailAliases'];
            }
            else {
                $mailAliases_defaults = "";
            }               
            $mailAliasesField = $factory->getDomainNameList("mailAliases", $mailAliases_defaults);
            $mailAliasesField->setOptional(TRUE);
            $settings->addFormField(
                    $mailAliasesField,
                    $factory->getLabel("mailAliases"),
                    $defaultPage
                    );

            // site email catch-all
            $mailCatchAllField = $factory->getEmailAddress("mailCatchAll", $vsiteDefaults["mailCatchAll"], 1);
            $mailCatchAllField->setOptional(TRUE);
            $settings->addFormField(
                $mailCatchAllField,
                $factory->getLabel("mailCatchAll"),
                $defaultPage
                );

            //
            //-- Resource Management:
            //

            $disk_dev = $CI->cceClient->find("Disk", array('isHomePartition' => 1, 'mounted' => 1));

            // Dirty hack not to use /home partition. Kicks in if we don't have a disk partition
            // after our last search or if the reported disk has no size information. Then we use
            // the size information from the / partition instead:
            if (count($disk_dev) == 0) { 
                    $disk_dev = $CI->cceClient->getObject('Disk', array('mountPoint' => '/'), ''); 
            }
            else {
                $disk_dev = $CI->cceClient->get($disk_dev[0]); 
            } 

            if (isset($disk_dev['total'])) {
                    $partitionMax = sprintf("%.0f", ($disk_dev['total'])); 
            } 

            if (isset($disk_dev['used'])) {
                    $partitionUsed = sprintf("%.0f", ($disk_dev['used'])); 
            } 

            // We now know how large the partition is and how much of it is used.
            $partitionMax = ($partitionMax-$partitionUsed);
            $VsiteTotalDiskSpace = $vsiteDefaults["quota"]*1000*1000;
            $VsiteUsedDiskSpace = "0";

            //
            //-- Start: Poll "server admin" resource settings and usage:
            //

            // If the site is not owned by 'admin', we need to gather information
            // about the allowances and usage info for this 'manageSite' administrator:
            $exact = array();
            $exact = array_merge($exact, array('createdUser' => $CI->BX_SESSION['loginName']));

            // Get the info about the 'manageSite' administrator:
            list($user_oid) = $CI->cceClient->find('User', array('name' => $CI->BX_SESSION['loginName'])); 

            // Get the site allowance settings for this 'manageSite' user:
            $AdminAllowances = $CI->cceClient->get($user_oid, 'Sites'); 
            
            // Get a list of all sites he owns: 
            $Userowned_Sites = $CI->cceClient->find('Vsite', array('createdUser' => $CI->BX_SESSION['loginName'])); 
            $Quota_of_Userowned_Sites = 0; 
            foreach ($Userowned_Sites as $oid) { 
                $user_vsiteDisk = $CI->cceClient->get($oid, 'Disk'); 
                $Quota_of_Userowned_Sites += $user_vsiteDisk['quota']; 
            }
            // Variable $Quota_of_Userowned_Sites includes the quota of the current Vsite. We need
            // to substract it from the total for now:
            $Quota_of_Userowned_Sites -= $disk_dev['quota'];
            // Multiply the quota to get it in bytes:
            $Quota_of_Userowned_Sites = $Quota_of_Userowned_Sites*1000*1000;
            $AdminAllowances['quota'] = $AdminAllowances['quota']*1000;

            // How many users accounts are allocated to Vsites this 'manageSite' administrator created?
            $AllocatedUserAccounts = 0;
            $CreatedUserAccountsAllSites = 0;
            $CreatedUserAccountsThisSite = 0;
            foreach ($Userowned_Sites as $oid) { 
                $user_vsite = $CI->cceClient->get($oid); 
                $AllocatedUserAccounts += $user_vsite['maxusers'];

                // How many accounts are set up on this Vsite?
                $useduser_oids = $CI->cceClient->find('User', array("site" => $user_vsite['name']));
                // Add them to the total:
                $CreatedUserAccountsAllSites += count($useduser_oids);
                // How many accounts does THIS site have at the moment?
                $CreatedUserAccountsThisSite = "0";
            }

            // Check if the amount of allocated accounts is greater than what the user is allowed to:
            if (($CI->serverScriptHelper->getAllowed('manageSite')) && ($CI->BX_SESSION['loginName'] == 'admin')) {
                $Can_Modify_Quantity_of_Users = "1";
            }
            elseif ($AllocatedUserAccounts > $AdminAllowances['user']) {
                $Can_Modify_Quantity_of_Users = "0";
            }
            else {
                $Can_Modify_Quantity_of_Users = "1";
            }

            //
            //-- End: Poll "server admin" resource settings and usage.
            //

            //
            //-- Site quota
            //

            if (!$CI->serverScriptHelper->getAllowed('systemAdministrator')) {
                $partitionMin = '1000000';
                $partitionMax = ($AdminAllowances['quota']-$Quota_of_Userowned_Sites);
            }
            else {
                $partitionMin = '1048576';
                $partitionMax = $partitionMax*1024;
            }

            // If the Disk Space is editable, we show it as editable:
            if ($access == 'rw') {
                $site_quota = $factory->getInteger('quota', simplify_number($VsiteTotalDiskSpace, "K", "2"), $partitionMin, $partitionMax, $access); 
                if ($CI->serverScriptHelper->getAllowed('systemAdministrator')) {
                    $site_quota->showBounds('diskquota');   // NOTE: This affects only the display of the range below the getInteger() field.
                }                                           // Quota for disk off the actual disk is stored with base 1024.
                else {
                    $site_quota->showBounds('dezi');        // NOTE: This affects only the display of the range below the getInteger() field.
                }                                           // Quota for Resellers is stored with base 1000.
                $site_quota->setType('memdisk');
                $settings->addFormField(
                        $site_quota,
                        $factory->getLabel('quota'),
                        $defaultPage
                        );
            }
            else {
                // Else we show it as shiny bargraph:
                $percent = round(100 * ($disk['used'] / $disk['quota']));
                $disk_bar = $factory->getBar("quota", floor($percent), "");
                $disk_bar->setBarText($i18n->getHtml("[[base-disk.userDiskPercentage_moreInfo]]", false, array("percentage" => $percent, "used" => simplify_number($VsiteUsedDiskSpace, "KB", "2"), "total" => simplify_number($VsiteTotalDiskSpace, "KB", "0"))));
                $disk_bar->setLabelType("quota");
                $disk_bar->setHelpTextPosition("bottom");   

                $settings->addFormField(
                        $disk_bar,
                        $factory->getLabel('quota'),
                        $defaultPage
                        );
            }

            //
            //-- Max user settings:
            //

            // Show an editable getInteger() if we have 'rw' access and can still modify the quantity of users:
            if (($access == 'rw') && ($Can_Modify_Quantity_of_Users == "1")) {
                $userMaxField = $factory->getInteger("maxusers", $vsiteDefaults["maxusers"], $CreatedUserAccountsThisSite, "50000", $access);
                $userMaxField->showBounds(FALSE);
                $settings->addFormField( 
                        $userMaxField, 
                        $factory->getLabel("maxUsers"),
                        $defaultPage
                        );
            }
            else {
                // This kicks on if the page visitor is 'siteAdmin', but is also used for anyone else
                // in case the number of users exceeds all limits for the 'manageSite' user in question:

                // We show it as shiny bargraph:
                if ((!isset($vsite['maxusers'])) || ($vsite['maxusers'] == "") || ($vsite['maxusers'] == "0")) {
                    // We sure don't want a division by zero in case 'maxusers' is not set or is "0":
                    $percent = "100";
                    $vsite['maxusers'] = "0";
                }
                else {
                    $percent = round(100 * ($CreatedUserAccountsThisSite / $vsite['maxusers']));
                }
                $user_bar = $factory->getBar("user_bar", floor($percent), "");
                $user_bar->setBarText($i18n->getHtml("[[base-disk.userDiskPercentage_moreInfo]]", false, array("percentage" => $percent, "used" => $CreatedUserAccountsThisSite, "total" => $vsite['maxusers'])));
                $user_bar->setLabelType("quota");
                $user_bar->setHelpTextPosition("bottom");   
                $settings->addFormField(
                        $user_bar,
                        $factory->getLabel('maxUsers'),
                        $defaultPage
                        );

                // Add hidden field with the current $vsite["maxusers"] value:
                $settings->addFormField(
                        $factory->getTextField("maxusers", $vsite["maxusers"], ''),
                        $factory->getLabel("maxUsers"),
                        $defaultPage
                        );
            }

            //---- END: Resource Management

            // auto dns option
            $settings->addFormField(
                    $factory->getBoolean("dns_auto", $vsiteDefaults["dns_auto"]),
                    $factory->getLabel("dns_auto"),
                    $defaultPage
                    );      

            // preview site option
            $settings->addFormField(
                    $factory->getBoolean("site_preview", $vsiteDefaults["site_preview"]),
                    $factory->getLabel("site_preview"),
                    $defaultPage
                    );

            //
            // --> AutoServices
            //
            // Add Divider:
            $settings->addFormField(
                    $factory->addBXDivider("otherServices", ""),
                    $factory->getLabel("otherServices", false),
                    $secondPage
                    );      
            // Figure out which services are available
            list($vsiteServices) = $CI->cceClient->find('VsiteServices');
            $autoFeatures = new AutoFeatures($CI->serverScriptHelper);

            // add all generic enabled/disabled type services detected above
            $autoFeatures->display($settings, 'create.Vsite', 
                    array(
                        'CCE_SERVICES_OID' => $vsiteServices,
                        'PAGED_BLOCK_DEFAULT_PAGE' => $secondPage,
                        'CAN_ADD_PAGE' => false
                        ));

            // Add the buttons
            $btn = $factory->getSaveButton($BxPage->getSubmitAction());

            $settings->addButton($btn);
            $settings->addButton($factory->getCancelButton("/vsite/vsiteList"));

            //
            //-- Error message handing:
            //
            $BXerrors = array();
            foreach ($errors as $object => $objData) {
                if (is_object($object)) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $BXerrors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }
                else {
                    $BXerrors[] = $objData;
                }
            }

            // Publish error messages:
            $BxPage->setErrors($BXerrors);

            //-- Generate page:
            $page_body[] = $settings->toHtml();

            // Out with the page:
            $BxPage->render($page_module, $page_body);
        }

    }
}
/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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