<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vsiteMod extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/vsiteMod.
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

        //
        //-- Prepare data:
        //

        // Get data for the Vsite:
        $vsite = $CI->cceClient->getObject('Vsite', array('name' => $group));

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
            // Suspended sites must NOT have site_preview enabled:
            if ($attributes['suspend'] == "1") { 
                $attributes['site_preview'] = "0"; 
            }

            if ($attributes['prefix'] != "") {
                $userPrefixEnabled = "1";
            }
            else {
                $userPrefixEnabled = "0";
            }

            if (!isset($attributes['createdUser'])) {
                $attributes['createdUser'] = "admin";
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
            // Actual submit to CODB:
            $vsiteOID = $CI->cceClient->find("Vsite", array("name" => $group));
            $CI->cceClient->set($vsiteOID[0], "", 
                  array(
                    "hostname" => $attributes['hostName'],
                    "domain" => $attributes['domain'],
                    "fqdn" => ($attributes['hostName'] . "." . $attributes['domain']),
                    "ipaddr" => $attributes['ipAddr'],
                    "maxusers" => $attributes['maxusers'],
                    "dns_auto" => $attributes['dns_auto'],
                    "site_preview" => $attributes['site_preview'],
                    "createdUser" => $attributes['createdUser'],
                    "prefix" => $attributes['prefix'],
                    "userPrefixEnabled" => $userPrefixEnabled, 
                    "userPrefixField" => $attributes['prefix'],         
                    "suspend" => $attributes['suspend']
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
                    $quota = floor((unsimplify_number($attributes['quota'], "K")/1000));
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
                        if ($oid != $vsiteOID[0]) { // Skipp polling the quota that this Vsite currently has set in CODB.
                            $user_vsiteDisk = $CI->cceClient->get($oid, 'Disk'); 
                            $Quota_of_Userowned_Sites += $user_vsiteDisk['quota']; 
                        }
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
                        $CI->cceClient->set($vsiteOID[0], 'Disk', array('quota' => $quota));                        
                    }
                }
                else {
                    // Not a reseller:

                    // Set the quota:
                    $CI->cceClient->set($vsiteOID[0], 'Disk', array('quota' => $quota));
                }

                // CCE errors that might have happened during submit to CODB:
                $CCEerrors = $CI->cceClient->errors();
                foreach ($CCEerrors as $object => $objData) {
                    // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                    $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
                }
            }

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/vsite/vsiteMod?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/vsiteMod?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_sitemanage');
        $BxPage->setVerticalMenuChild('base_vsite_general');
        $page_module = 'base_sitemanage';

        $defaultPage = "basicSettingsTab";
        $block =& $factory->getPagedBlock("modVsiteSettings", array($defaultPage));
        $block->setLabel($factory->getLabel('modVsiteSettings', false, array('site' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page.  Site admins
        // can view it for informational purposes.
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = false;
            $access = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access = 'r';
            $is_site_admin = true;
        }
        else {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        // With IP Pooling enabled, display the IP field with a 
        // range of possible choices
        $net_opts = $CI->cceClient->get($system['OID'], "Network");
        if (($net_opts["pooling"] == "1") && $Capabilities->getAllowed('manageSite')) {
            $range_strings = array();

            $oids = $CI->cceClient->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
            foreach ($oids as $oid) {
                $range = $CI->cceClient->get($oid);
                $adminArray = $CI->cceClient->scalar_to_array($range['admin']); 
                if ($CI->BX_SESSION['loginName'] == 'admin' || in_array($CI->BX_SESSION['loginName'], $adminArray)) { 
                        $range_strings[] = $range['min'] . ' - ' . $range['max']; 
                } 
            }
            $string = arrayToString($range_strings);

            $new_range_string = '';
            $nrs_num = "0";
            foreach ($range_strings as $key => $value) {
                if ($nrs_num > "0") {
                    $new_range_string .= "<br>";
                }
                $new_range_string .= $value;
                $nrs_num++;
            }

            $ip_address = $factory->getIpAddress("ipAddr", $vsite["ipaddr"], $access);
            $ip_address->setRange($new_range_string);
        }
        else {
            // IP Address, without ranges
            $ip_address = $factory->getIpAddress("ipAddr", $vsite["ipaddr"], $access);
        }

        // IP Address
        $ip_address->setOptional(FALSE);
        $block->addFormField(
                $ip_address,
                $factory->getLabel("ipAddr"),
                $defaultPage
                );

        // Host and domain names
        if (isset($vsite['hostname'])) {
            $server_hostname = $vsite['hostname'];
        }
        else {
            $server_hostname = "";
        }       
        if (isset($vsite['domain'])) {
            $server_domain = $vsite['domain'];
        }
        else {
            $server_domain = "";
        }       

        $hostname_field = $factory->getDomainName("hostName", $server_hostname, $access); 
        $hostname_field->setType("hostname");
        $hostname_field->setLabelType("label_top no_lines");

        $domainname_field = $factory->getDomainName("domain", $server_domain, $access);
        $domainname_field->setType("domainname");
        $domainname_field->setLabelType("label_top no_lines");

        $fqdn =& $factory->getCompositeFormField(array($factory->getLabel("enterFqdn"), $hostname_field, $domainname_field), '');
        $fqdn->setColumnWidths(array('col_25', 'col_25', 'col_50'));

        $block->addFormField(
                $fqdn,
                $factory->getLabel("enterFqdn"),
                $defaultPage
                );


        //-- Start Owner Management

        // Find all 'adminUsers' with the capability 'manageSite':
        $admins = $CI->cceClient->findx('User', 
                        array('systemAdministrator' => "0", 'capLevels' => 'manageSite'),
                        array());

        // Set up an array that - at least - has 'admin' in it:
        $adminNames = array('admin');
        foreach ($admins as $num => $oid) {
            $current = $CI->cceClient->get($oid);
            // Found a reseller, adding him to the array as well:
            $adminNames[] = $current['name'];
        }

        // If that Vsite has no owner yet, then 'admin' will own it:
        if ($vsite['createdUser'] == "") {
            $current_createdUser = "admin";
        }
        else {
            $current_createdUser = $vsite['createdUser'];
        }

        // If the current user has the cap 'serverManage', then he is allowed to change the owner:
        if ($Capabilities->getAllowed('serverManage')) { 
            // Sort the array values:
            asort($adminNames);
            // Build the MultiChoice selector:
            $current_createdUser_select = $factory->getMultiChoice("createdUser", array_values($adminNames));
            $current_createdUser_select->setSelected($current_createdUser, true);
            $block->addFormField($current_createdUser_select, $factory->getLabel("createdUser"), $defaultPage);
        }
        else {
            // Current user doesn't have the cap 'serverManage'. So we just add a hidden TextField with the current owner in it:
            $block->addFormField(
                    $factory->getTextField("createdUser", $vsite['createdUser'], ""),
                    $factory->getLabel("createdUser"),
                    $defaultPage
                    );
        }

        //-- End Owner Management

        // site prefix
        $vsite_prefix = $factory->getTextField("prefix", $vsite['prefix'], $access);
        $vsite_prefix->setOptional(true);
        $vsite_prefix->setWidth(5);
         
        $block->addFormField(
                $vsite_prefix,
                $factory->getLabel("prefix"),
                $defaultPage
                );

        // vsite disk info
        $disk = $CI->cceClient->get($vsite['OID'], 'Disk');

        $disk_dev = $CI->cceClient->getObject('Disk', array('mountPoint' => $vsite['volume']), '');

        // Dirty hack not to use /home partition. Kicks in if we don't have a disk partition
        // after our last search or if the reported disk has no size information. Then we use
        // the size information from the / partition instead:
        if ((count($disk_dev) == 0) || ($disk_dev['total'] == "")) { 
                $disk_dev = $CI->cceClient->getObject('Disk', array('mountPoint' => '/'), ''); 
        } 

        if ($disk_dev['total']) {
                $partitionMax = sprintf("%.0f", ($disk_dev['total'])); 
        } 

        if ($disk_dev['used']) {
                $partitionUsed = sprintf("%.0f", ($disk_dev['used'])); 
        } 

        // We now know how large the partition is and how much of it is used.
        $partitionMax = ($partitionMax-$partitionUsed);
        $VsiteTotalDiskSpace = $disk['quota']*1000*1000;
        $VsiteUsedDiskSpace = $disk['used']*1000*1000;

        //
        //-- Start: Poll "server admin" resource settings and usage:
        //

        // If the site is not owned by 'admin', we need to gather information
        // about the allowances and usage info for this 'manageSite' administrator:
        $exact = array();
        $exact = array_merge($exact, array('createdUser' => $CI->BX_SESSION['loginName']));

        // Get the info about the 'manageSite' administrator:
        @list($user_oid) = $CI->cceClient->find('User', array('name' => $vsite['createdUser'])); 

        // Get the site allowance settings for this 'manageSite' user:
        $AdminAllowances = $CI->cceClient->get($user_oid, 'Sites'); 

        if (!isset($AdminAllowances['user'])) {
            $AdminAllowances['user'] = 'admin';
            $AdminAllowances['quota'] = '500';
        }
        
        // Get a list of all sites he owns: 
        $Userowned_Sites = $CI->cceClient->find('Vsite', array('createdUser' => $vsite['createdUser'])); 
        $Quota_of_Userowned_Sites = 0; 
        foreach ($Userowned_Sites as $oid) { 
            if ($oid != $vsite['OID']) {
                $user_vsiteDisk = $CI->cceClient->get($oid, 'Disk'); 
                $Quota_of_Userowned_Sites += $user_vsiteDisk['quota']; 
            }
        }
        // Multiply the quota to get it in bytes:
        $Quota_of_Userowned_Sites = $Quota_of_Userowned_Sites*1000;

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
            if ($user_vsite['name'] == $group) {
                $CreatedUserAccountsThisSite = count($useduser_oids);
            }
        }

        // Check if the amount of allocated accounts is greater than what the user is allowed to:
        if (($Capabilities->getAllowed('manageSite')) && ($vsite['createdUser'] == "admin") && ($CI->BX_SESSION['loginName'] == 'admin')) {
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

        $admin_view = '0';
        if ($CI->serverScriptHelper->getAllowed('systemAdministrator')) {
            $partitionMin = '1048576';
            $partitionMax = $partitionMax*1024;
            $admin_view = '1';
        }

        if ($vsite['createdUser'] != 'admin') {
            $partitionMin = '1000000';
            $partitionMax = ($AdminAllowances['quota']-$Quota_of_Userowned_Sites)*1000;
            $admin_view = '0';
        }

        // If the Disk Space is editable, we show it as editable:
        if ($access == 'rw') {
            $site_quota = $factory->getInteger('quota', simplify_number($VsiteTotalDiskSpace, "K", "2"), $partitionMin, $partitionMax, $access); 
            if ($admin_view == '1') {
                $site_quota->showBounds('diskquota');   // NOTE: This affects only the display of the range below the getInteger() field.
            }                                           // Quota for disk off the actual disk is stored with base 1024.
            else {
                $site_quota->showBounds('dezi');        // NOTE: This affects only the display of the range below the getInteger() field.
            }                                           // Quota for Resellers is stored with base 1000.
            $site_quota->setType('memdisk');
            $block->addFormField(
                    $site_quota,
                    $factory->getLabel('quota'),
                    $defaultPage
                    );
        }
        else {
            // Else we show it as shiny bargraph:
            $percent = round(100 * ($disk['used'] / $disk['quota']));
            $disk_bar = $factory->getBar("quota", floor($percent), "");
            $disk_bar->setBarText($i18n->getHtml("[[base-disk.userDiskPercentage_moreInfo]]", false, array("percentage" => $percent, "used" => simplify_number($VsiteUsedDiskSpace, "K", "2"), "total" => simplify_number($VsiteTotalDiskSpace, "K", "0"))));
            $disk_bar->setLabelType("quota");
            $disk_bar->setHelpTextPosition("bottom");   

            $block->addFormField(
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
            if ($vsite['maxusers'] == "") {
                $vsite["maxusers"] = "0";
            }
            $userMaxField = $factory->getInteger("maxusers", $vsite["maxusers"], $CreatedUserAccountsThisSite, "50000", $access);
            $userMaxField->showBounds(FALSE);
            $block->addFormField( 
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
            $block->addFormField(
                    $user_bar,
                    $factory->getLabel('maxUsers'),
                    $defaultPage
                    );

            // Add hidden field with the current $vsite["maxusers"] value:
            $block->addFormField(
                    $factory->getTextField("maxusers", $vsite["maxusers"], ''),
                    $factory->getLabel("maxUsers"),
                    $defaultPage
                    );
        }

        // Auto dns option
        $block->addFormField(
                $factory->getBoolean("dns_auto", $vsite["dns_auto"], $access),
                $factory->getLabel("dns_auto"),
                $defaultPage
                );

        // Preview site option
        $block->addFormField(
                $factory->getBoolean("site_preview", $vsite["site_preview"], $access),
                $factory->getLabel("site_preview"),
                $defaultPage
                );

        if (!$is_site_admin) {
            // Suspend Vsite        
            $block->addFormField(
                $factory->getBoolean("suspend", $vsite["suspend"]),
                $factory->getLabel("suspend"),
                $defaultPage
            );
        }

        // Add the buttons for those who can edit this page:
        if ($access == 'rw') {
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/vsite/vsiteMod?group=$group"));
        }

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