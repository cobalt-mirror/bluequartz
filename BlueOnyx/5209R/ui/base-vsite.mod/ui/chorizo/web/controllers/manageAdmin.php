<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ManageAdmin extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/manageAdmin.
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
        $User = $cceClient->getObject("User", array("name" => $loginName));
        $i18n = new I18n("base-vsite", $User['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        // Only "systemAdministrator" should be here. This is important. Boot anyone else:
        if (!$Capabilities->getAllowed('systemAdministrator')) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Set up possible capabilities:
        $possible_caps = array(
                    'serverShowActiveMonitor' => 1,
                    'serverInformation' => 1,
                    'serverHttpd' => 1,
                    'serverFTP' => 1,
                    'serverEmail' => 1,
                    'serverDNS' => 1,
                    'serverSNMP' => 1,
                    'serverShell' => 1,
                    'serveriStat' => 1,
                    'serverSSL' => 1,
                    'serverMemcache' => 1,
                    'serverNetwork' => 1,
                    'serverIpPooling' => 1,
                    'serverPower' => 1,
                    'serverTime' => 1,
                    'serverServerDesktop' => 1,
                    'menuServerServerStats' => 1,
                    'serverActiveMonitor' => 1,
                    'managePackage' => 1,
                    'menuServerSecurity' => 1,
                    'systemAdministrator' => 1
                                );

                    //'manageSite' => 1, <- Handled via Checkbox for now
                    //'siteDNS' => 1,    <- Handled via Checkbox for now

                    //'serverVsite' => 1, <- Removed for now. Stand alone it makes no sense.

        // Get 'reseller' CapabilityGroup and get the possible reseller Capabilities from within that:
        $reseller_caps = $cceClient->getObject("CapabilityGroup", array("name" => 'reseller'));
        $possible_reseller_caps = scalar_to_array($reseller_caps['capabilities']);

        // Build an associative array with Capabilities and their default states:
        $possible_reseller_caps_with_defaults = array();
        foreach ($possible_reseller_caps as $key => $value) {
            $thisCap = $cceClient->getObject('Capabilities');
            $thisCapValue = $cceClient->get($thisCap['OID'], $value);
            $possible_reseller_caps_with_defaults[$value] = $thisCapValue['capable'];
        }

        //
        //--- Get CODB-Object of interest: 
        //

        // We get our $get_form_data early, as this page handles both Add/Edit of admin-users.

        // Get Support-Settings:
        $Support = $cceClient->getObject("System", array(), "Support");

        $get_form_data = $CI->input->get(NULL, TRUE);
        $CODBDATA = array('fullName' => '', 'sortName' => '', 'name' => '', 'ui_enabled' => '', 'capLevels' => '');
        if ($get_form_data['_oid'] != '') {
            $_oid = $get_form_data['_oid'];
            $tempdata = $cceClient->get($_oid);
            if ($tempdata['CLASS'] != 'User') {
                // Object is not a User-Object!
                // Nice people say goodbye, or CCEd waits forever:
                $cceClient->bye();
                $serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403#right-notsofastthere");
            }
            $CurrCaps = scalar_to_array($tempdata['capLevels']);
            if (in_array('adminUser', $CurrCaps)) {
                $CODBDATA = $tempdata;
            }
            else {
                // Sneaky bastard. Trying to modify something you're not supposed to modify?
                // Nice people say goodbye, or CCEd waits forever:
                $cceClient->bye();
                $serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403#notsofastthere");
            }
        }

        // A 'systemAdministrator' tries to edit or delete himself. Can't have that!
        if (($User['systemAdministrator'] == "1") && ($CODBDATA['name'] == $loginName)) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#etutbrutus");
        }

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        //
        //--- Handle User Deletion:
        //

        if (isset($get_form_data['DELETE'])) {
            if ($get_form_data['DELETE'] == "1") {
                if (isset($_oid)) {
                    if (!is_file("/etc/DEMO")) {

                        // If this user has 'root' access or is 'systemAdministrator',
                        // then we take his elevated abilities away first:
                        $ok = $cceClient->set($_oid, 'RootAccess', array('enabled' => '0'));
                        $errors = array_merge($errors, $cceClient->errors());

                        $ok = $cceClient->set($_oid, '', array('systemAdministrator' => '0'));
                        $errors = array_merge($errors, $cceClient->errors());

                        $ok = $cceClient->set($_oid, 'Shell', array('enabled' => 0));
                        $errors = array_merge($errors, $cceClient->errors());

                        // Now with that out of the way we delete him:
                        $ok = $cceClient->destroy($_oid);
                        $my_errors = array_merge($my_errors, $cceClient->errors());
                    }
                }
                if (count($my_errors == "0")) {
                    // No errors. Redirect to adminList:
                    // Nice people say goodbye, or CCEd waits forever:
                    $cceClient->bye();
                    $serverScriptHelper->destructor();
                    header("location: /vsite/adminList");
                    exit;                   
                }
            }
        }

        //
        //--- Handle form validation:
        //

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // Form fields that are required to have input:
        $required_keys = array("");

        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array('_password_repeat');
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
            // For new users a password MUST be set:
            if (!isset($_oid)) {
                // Check Password match:
                $passwd = "";
                if (isset($form_data['password'])) {
                    $passwd = $form_data['password'];
                }
                $passwd_repeat = "";
                if (isset($form_data['_password_repeat'])) {
                    $passwd_repeat = $form_data['_password_repeat'];
                }
                if (bx_pw_check($i18n, $passwd, $passwd_repeat) != "") {
                    $my_errors = bx_pw_check($i18n, $passwd, $passwd_repeat);
                }

                // Support-Module has a reserved username for the support account:
                if ((isset($Support['support_account'])) && (!isset($_oid))) {
                    if ($Support['support_account'] == $attributes['userName']) {
                        $my_errors[] = ErrorMessage($i18n->get("[[base-support.Error_support_account_reserved]]"));
                    }
                }
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // Remove the special capabilities from the user's current ones:
            if (isset($attributes['adminPowers'])) {
                $current_caps = $cceClient->scalar_to_array($attributes['adminPowers']);
            }
            else {
                $attributes['adminPowers'] = "";
                $current_caps = array();
            }

            if (!in_array('adminUser', $current_caps)) {
                $current_caps[] = 'adminUser';
            }

            // Hack root access back out
            $rootAccess = 0;
            if (($key = array_search('rootAccess', $current_caps)) !== false) {
                unset($current_caps[$key]);
                $rootAccess = 1;
            }

            // Hack out systemAdministrator's except for user 'admin':
            $systemAdministrator = 0;
            if (($key = array_search('systemAdministrator', $current_caps)) !== false) {
                unset($current_caps[$key]);
                $systemAdministrator = 1;
                $rootAccess = 1;
            }

            // Add 'manageSite' to $current_caps if the checkbox was ticked:
            if (isset($attributes['manageSite'])) {
                if ($attributes['manageSite'] == '1') {
                    $current_caps[] = 'manageSite';
                }
            }

            // Add 'siteDNS' to $current_caps if the checkbox was ticked:
            if (isset($attributes['siteDNS'])) {
                if ($attributes['siteDNS'] == '1') {
                    $current_caps[] = 'siteDNS';
                }
            }

            // Handle create of user if necessary:
            if (!isset($_oid)) {
                $big_ok = $cceClient->create('User',
                                array(
                                    'fullName' => $attributes['fullName'],
                                    'sortName' => "",
                                    'name' => $attributes['userName'],
                                    'password' => $attributes['password'],
                                    'capLevels' => $cceClient->array_to_scalar($current_caps)
                                    ));
                // Get the OID of this transaction:
                if ($big_ok) {
                    $_oid = $big_ok;
                }
            }
            else {
                // It's an existing user and we update him:

                $ui_enabled = "0";
                if (isset($attributes['suspend'])) {
                    if ($attributes['suspend'] == "1") {
                        $ui_enabled = "0";
                    }
                    else {
                        $ui_enabled = "1";
                    }
                }

                $new_settings = array(
                                    'fullName' => $attributes['fullName'],
                                    'sortName' => "",
                                    'capLevels' => $cceClient->array_to_scalar($current_caps),
                                    'ui_enabled' => $ui_enabled
                                    );

                if (isset($attributes['password'])) {
                    if ($attributes['password'] != "") {
                        $new_settings['password'] = $attributes['password'];
                    }
                }

                $big_ok = $cceClient->set($_oid, '', $new_settings);

                // CCE errors that might have happened during submit to CODB:
                $errors = array_merge($errors, $cceClient->errors());

                if ((isset($Support['support_account'])) && (!isset($_oid))) {
                    if ($Support['support_account'] == $attributes['userName']) {
                        $my_errors[] = ErrorMessage($i18n->get("[[base-support.Error_support_account_reserved]]"));
                    }
                }

                // Handle expiry date changes:
                if ((isset($Support['support_account'])) && (isset($_oid))) {
                    // Run if SAExpiry is supplied, this is the defined support account *and* it has an expiry defined to begin with:
                    if ((isset($attributes['SAExpiry'])) && ($Support['support_account'] == $attributes['userName']) && ($Support['access_epoch'] != '0')) {
                        // Puzzle the date and time back together:
                        if (isset($attributes['_SAExpiry_day']) && isset($attributes['_SAExpiry_month']) && isset($attributes['_SAExpiry_year']) && isset($attributes['_SAExpiry_hour']) && isset($attributes['_SAExpiry_minute'])) {
                            $attributes['SAExpiry'] = mktime ($attributes['_SAExpiry_hour'], $attributes['_SAExpiry_minute'], '00', $attributes['_SAExpiry_month'], $attributes['_SAExpiry_day'], $attributes['_SAExpiry_year']);
                        }
                        // Update expiry date in CODB:
                        $sup_cfg = array('access_epoch' => $attributes['SAExpiry']);
                        $cceClient->setObject("System", $sup_cfg, "Support");
                        // CCE errors that might have happened during submit to CODB:
                        $errors = array_merge($errors, $cceClient->errors());
                    }
                }

            }

            // CCE errors that might have happened during submit to CODB:
            $errors = array_merge($errors, $cceClient->errors());

            // Set the disk quota:
            if ($big_ok) {
                $diskQuota = floor(unsimplify_number($attributes['diskQuota'], "KB")/1024);
                $cceClient->set($_oid, 'Disk', array('quota' => $diskQuota));
            }

            // CCE errors that might have happened during submit to CODB:
            $errors = array_merge($errors, $cceClient->errors());

            // Set the root access flag:
            if ($big_ok) {
                $ok = $cceClient->set($_oid, 'RootAccess', array('enabled' => $rootAccess));
                $errors = array_merge($errors, $cceClient->errors());
            }

            // Set the systemAdministrator flag:
            if ($big_ok) {
                $ok = $cceClient->set($_oid, '', array('systemAdministrator' => $systemAdministrator));
                $errors = array_merge($errors, $cceClient->errors());
            }

            // Handle Shell access:
            // Granted if Shell is ticked, OR user is systemAdministrator OR has rootAccess:
            if ($big_ok) {
                if (($attributes['shell'] == "1") || ($systemAdministrator == "1") || ($rootAccess == "1")) {
                    $ok = $cceClient->set($_oid, 'Shell', array('enabled' => 1));
                    $errors = array_merge($errors, $cceClient->errors());
                }
                else {
                    $ok = $cceClient->set($_oid, 'Shell', array('enabled' => 0));
                    $errors = array_merge($errors, $cceClient->errors());
                }
            }

            // Set Site Management information
            if ($big_ok) {
                $siteQuota = unsimplify_number($attributes['siteQuota'], "K");
                $cceClient->set($_oid, 'Sites',
                    array('quota' => ($siteQuota == '' ? '0' : $siteQuota),
                          'max' => ($attributes['siteMax'] == '' ? '0' : $attributes['siteMax']),
                          'user' => ($attributes['siteUser'] == '' ? '0' : $attributes['siteUser'])));
                $errors = array_merge($errors, $cceClient->errors());
            }

            // Handle 'resellerPowers' if the user has 'manageSite' Capability:
            if ((in_array('manageSite', $current_caps)) && (isset($attributes['resellerPowers']))) {
                // Get current User object:
                $tempResData = $cceClient->get($_oid);
                $tempCurrCaps = scalar_to_array($tempResData['capabilities']);
                foreach ($possible_reseller_caps as $key => $value) {
                    // Remove all reseller caps from currently used caps:
                    if (($key = array_search($value, $tempCurrCaps)) !== false) {
                        unset($tempCurrCaps[$key]);
                    }
                }
                $modified_settings = array(
                    'capabilities' => $cceClient->array_to_scalar(array_unique(array_merge($tempCurrCaps, $cceClient->scalar_to_array($attributes['resellerPowers']))))
                    );
                $big_ok = $cceClient->set($_oid, '', $modified_settings);
                $errors = array_merge($errors, $cceClient->errors());
            }
            else {
                $tmpresellerPowers = array();
                if (isset($_oid)) {
                    // Get current User object:
                    $tempResData = $cceClient->get($_oid);
                    $tempCurrCaps = scalar_to_array($tempResData['capabilities']);
                    foreach ($possible_reseller_caps as $key => $value) {
                        // Remove all reseller caps from currently used caps:
                        if (($key = array_search($value, $tempCurrCaps)) !== false) {
                            unset($tempCurrCaps[$key]);
                        }
                    }
                    $modified_settings = array(
                        'capabilities' => $cceClient->array_to_scalar(array_unique(array_merge($tempCurrCaps, $tmpresellerPowers)))
                        );
                    $big_ok = $cceClient->set($_oid, '', $modified_settings);
                    $errors = array_merge($errors, $cceClient->errors());
                }
            }

            // Out with the errors:
            foreach ($errors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                // Nice people say goodbye, or CCEd waits forever:
                $cceClient->bye();
                $serverScriptHelper->destructor();              
                header("Location: /vsite/adminList");
                exit;
            }

        }

        //
        //-- Generate page:
        //

        $iam = "/vsite/manageAdmin";
        if ($get_form_data['_oid'] != '') {
            $iam = "/vsite/manageAdmin?_oid=" . $get_form_data['_oid'];
        }

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", $iam);
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_controlpanel');
        $BxPage->setVerticalMenuChild('base_manageAdmin');
        $page_module = 'base_sysmanage';

        $defaultPage = "basicSettingsTab";
        $advancedPage = "advancedSettingsTab";

        $block =& $factory->getPagedBlock("manageAdmin", array($defaultPage, $advancedPage));

        // Modify getPagedBlock()'s lable based on if we add/modify a user:
        if (isset($_oid)) {
            $block->setCurrentLabel($i18n->get('manageAdmin', false, array('name' => $CODBDATA['name'])));
        }
        else {
            $block->setCurrentLabel($i18n->get('createAdminUser', false));
        }

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        // Add Divider:
        $block->addFormField(
                $factory->addBXDivider("userInformation", ""),
                $factory->getLabel("userInformation", false),
                $defaultPage
                );  

        // Full name field
        $block->addFormField(
            $factory->getFullName('fullName', bx_charsetsafe($CODBDATA['fullName'])),
            $factory->getLabel('fullName'),
            $defaultPage
            );

        // Add the sort name field if necessary. Not sure what this is, though.
        if ($i18n->getProperty('needSortName') == 'yes') {
            $sortName =& $factory->getFullName('sortNameField', $CODBDATA['sortName']);
            $sortName->setOptional('silent');
            $block->addFormField(
                $sortName,
                $factory->getLabel('sortNameField'),
                $defaultPage
            );
        }

        // If this is a create, add the username field
        if (!isset($_oid)) {
            $block->addFormField(
                $factory->getUserName('userName'),
                $factory->getLabel('userNameCreate'),
                $defaultPage
                );
        }
        else {
            $uname_field = $factory->getUserName('userName', $CODBDATA['name'], "r");
            $block->addFormField(
                $uname_field,
                $factory->getLabel('userName'),
                $defaultPage
            );
        }

        // Don't pass back data for password fields
        $pass_field =& $factory->getPassword('password');
        if (isset($_oid)) {
            $pass_field->setOptional(TRUE);
        }    

        $block->addFormField(
            $pass_field,
            $factory->getLabel('userPassword'),
            $defaultPage
            );

        if (isset($_oid)) {
            $disk = $cceClient->get($_oid, 'Disk');
            $displayed_quota = simplify_number($disk['quota']*1024*1024, "KB", "0");
        }
        else {
            $displayed_quota = "200M";
        }

        $disk_quota =& $factory->getTextField('diskQuota', $displayed_quota, 1);
        $disk_quota->setOptional(FALSE);
        $disk_quota->setType('memdisk');
        $block->addFormField(
            $disk_quota,
            $factory->getLabel('userDiskQuota'),
            $defaultPage
            );

        // Server Admin Shell
        if (isset($_oid)) {
            $userShell = $cceClient->get($_oid, 'Shell');
        }
        else {
            $userShell['enabled'] = '0';
        }
        $block->addFormField(
            $factory->getBoolean('shell', ($userShell['enabled'] ? 1 : 0)),
            $factory->getLabel('userShell'),
            $defaultPage);

        // Add suspend check box to be consistent
        $suspend_ui = "1";
        if ($CODBDATA['ui_enabled'] == "1") {
            $suspend_ui = "0";
        }
        if (isset($_oid)) {
            $block->addFormField(
                    $factory->getBoolean('suspend', $suspend_ui),
                    $factory->getLabel('suspendUser'),
                    $defaultPage
                    );
        }

        //
        //--- Reseller Controls
        //

        // Display site controls
        $block->addFormField(
                $factory->addBXDivider("adminSites_new", ""),
                $factory->getLabel("adminSites_new", false),
                $defaultPage
                );

        $Currcaps = $cceClient->scalar_to_array($CODBDATA['capLevels']);

        $resCAP['manageSite'] = '0';
        if (in_array('manageSite', $Currcaps)) {
            $resCAP['manageSite'] = '1';
        }
        $resCAP['siteDNS'] = '0';
        if (in_array('siteDNS', $Currcaps)) {
            $resCAP['siteDNS'] = '1';
        }

        // Checkbox for capLevel 'manageSite':
        $block->addFormField(
            $factory->getBoolean('manageSite', $resCAP['manageSite']),
            $factory->getLabel('CapManageSite'),
            $defaultPage);

        // Checkbox for capLevel 'siteDNS':
        $block->addFormField(
            $factory->getBoolean('siteDNS', $resCAP['siteDNS']),
            $factory->getLabel('CapSiteDNS'),
            $defaultPage);

        if (isset($_oid)) {
            $site = $cceClient->get($_oid, 'Sites');
            $sites_quota = ($site['quota'] == -1 ? '' : $site['quota']);
            $sites_quota = simplify_number($sites_quota*1000, "K", "0");
            $sites_max = ($site['max'] == -1 ? '' : $site['max']);
            $sites_user = ($site['user'] == -1 ? '' : $site['user']);
        }
        else {
            $sites_quota = "500M";
            $sites_max = 5;
            $sites_user = 100;
        }

        $site_quota =& $factory->getInteger('siteQuota', $sites_quota, 1);
        $site_quota->setOptional('silent');
        $site_quota->setType('memdisk');
        $block->addFormField(
            $site_quota,
            $factory->getLabel('userSitesQuota'),
            $defaultPage
            );

        $site_max =& $factory->getInteger('siteMax', $sites_max, 1);
        $site_max->setOptional('silent');
        $block->addFormField(
            $site_max,
            $factory->getLabel('userSitesMax'),
            $defaultPage
            );

        $site_user =& $factory->getInteger('siteUser', $sites_user, 1);
        $site_user->setOptional('silent');
        $block->addFormField(
            $site_user,
            $factory->getLabel('userSitesUser'),
            $defaultPage
            );

        //
        //--- 'manageSite' extraCaps:
        //

        // Get strings to use as labels
        list($caps_oid) = $cceClient->find('Capabilities');
        $possible_reseller_labels = array();
        foreach ($possible_reseller_caps_with_defaults as $cap => $junk) {
            $ns = $cceClient->get($caps_oid, $cap);
            $possible_reseller_labels[$cap] = $i18n->get($ns['nameTag']);
        }

        $reseller_allowed_caps = array();
        $reseller_allowed_labels = array();
        if (isset($_oid)) {
            if (count($CODBDATA['capabilities']) > "0") {
                $resCaps = $cceClient->scalar_to_array($CODBDATA['capabilities']);
            }
            else {
                $resCaps = array();
            }
        }
        else {
            $resCaps = array();
            foreach ($possible_reseller_caps_with_defaults as $key => $value) {
                if ($value == "1") {
                    $resCaps[] =  $key;
                }
            }
        }

        foreach ($resCaps as $capability) {
            if (isset($possible_reseller_caps_with_defaults[$capability])) {
                $reseller_allowed_caps[] = $capability;
                $reseller_allowed_labels[] = $possible_reseller_labels[$capability];
            }
        }

//--
        // If this account is the support-account, then we do not allow anyone to
        // modify the capabilities OR the extraCaps. You can delete the account,
        // but you cannot revoke capabilities. For that purpose we set the 
        // getSetSelector()'s to read-only, which is a bit of a hack. As the 
        // bloody getSetSelector() didn't really support it yet and I had to hack
        // that capability into the code. Which required a change of CCEClient's
        // scalar_to_array() as well <sigh>.
        $cap_access = 'rw';
        if ((isset($Support['support_account'])) && (isset($_oid))) {
            // This is a support-account AND we're editing it.
            if ($Support['support_account'] == $CODBDATA['name']) {
                // In that case we set it to read-only:
                $cap_access = 'r';
            }
        }
//--

        $select_reseller_caps =& $factory->getSetSelector('resellerPowers',
                                $cceClient->array_to_scalar($reseller_allowed_labels), 
                                $cceClient->array_to_scalar($possible_reseller_labels),
                                'allowedAbilities', 'disallowedAbilities',
                                $cap_access, 
                                $cceClient->array_to_scalar($reseller_allowed_caps),
                                $cceClient->array_to_scalar(array_keys($possible_reseller_caps_with_defaults))
                            );

        $select_reseller_caps->setOptional(true);

        $block->addFormField($select_reseller_caps, 
                    $factory->getLabel('resellerPowers'),
                    $defaultPage
                    );

        // Hmmm .... not ideal. Need to throw in a spacer or the getSetSelector() displays oddly:
        $block->addFormField(
            $factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="120" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
            $factory->getLabel("Spacer"),
            $defaultPage
        );

        // Add Divider:
        $block->addFormField(
                $factory->addBXDivider("adminOptions_new", ""),
                $factory->getLabel("adminOptions_new", false),
                $advancedPage
                );  

        // Show a text description of what this tab is for:
        $adminOptions_desc = $factory->getHtmlField("adminOptions_desc", "<br>" . $i18n->getHtml("[[base-vsite.adminOptions_desc]]"), 'r');
        $adminOptions_desc->setLabelType("nolabel");
        $block->addFormField(
                $adminOptions_desc,
                $factory->getLabel("adminOptions_desc"),
                $advancedPage
                );

        //
        //--- Get the capabilities and populate the getSetSelector():
        //

        // display admin controls
        if (isset($_oid)) {
            $root_access = $cceClient->get($_oid, 'RootAccess');
        }

        // Get strings to use as labels
        list($caps_oid) = $cceClient->find('Capabilities');
        $possible_labels = array();
        foreach ($possible_caps as $cap => $junk) {
            $ns = $cceClient->get($caps_oid, $cap);
            $possible_labels[$cap] = $i18n->get($ns['nameTag']);
        }

        $allowed_caps = array();
        $allowed_labels = array();
        if (count($CODBDATA['capLevels']) > "0") {
            $caps = $cceClient->scalar_to_array($CODBDATA['capLevels']);
        }
        else {
            $caps = array();
        }

        foreach ($caps as $capability) {
            if (isset($possible_caps[$capability])) {
                $allowed_caps[] = $capability;
                $allowed_labels[] = $possible_labels[$capability];
            }
        }

        if (isset($root_access['enabled'])) {
            if ($root_access['enabled'] == "1") {
                $allowed_labels[] = $i18n->get('[[base-vsite.rootAccess]]');
                $allowed_caps[] = 'rootAccess';
            }
        }

        $possible_labels['rootAccess'] = $i18n->get('[[base-vsite.rootAccess]]');
        $possible_caps['rootAccess'] = 1;

        // Manually add 'systemAdministrator' if the flag is set for this User:
        if (isset($CODBDATA['systemAdministrator'])) {
            if ($CODBDATA['systemAdministrator'] == "1") {
                $CODBDATA[] = $i18n->get('[[base-vsite.cap_systemAdministrator]]');
                $allowed_caps[] = 'systemAdministrator';
            }
        }

        // If this account is the support-account, then we do not allow anyone to
        // modify the capabilities OR the extraCaps. You can delete the account,
        // but you cannot revoke capabilities. For that purpose we set the 
        // getSetSelector()'s to read-only, which is a bit of a hack. As the 
        // bloody getSetSelector() didn't really support it yet and I had to hack
        // that capability into the code. Which required a change of CCEClient's
        // scalar_to_array() as well <sigh>.
        $cap_access = 'rw';
        if ((isset($Support['support_account'])) && (isset($_oid))) {
            // This is a support-account AND we're editing it.
            if ($Support['support_account'] == $CODBDATA['name']) {
                // In that case we set it to read-only:
                $cap_access = 'r';

                // Add explanation why the capabilities cannot be changed if this is the support-account:
                $sa_desc = $factory->getTextField("sa_desc", $i18n->get("[[base-support.sa_desc_manageAdmin]]"), 'r');
                $sa_desc->setLabelType("nolabel");
                $block->addFormField(
                        $sa_desc,
                        $factory->getLabel("sa_desc", false),
                        $defaultPage
                        );

                // Show expiry date of this account:
                if ($Support['access_epoch'] != "0") {
                    $sa_epoch = $factory->getTimeStamp("SAExpiry", $Support['access_epoch'], "r");
                    $sa_epoch->setFormat("datetime");
                    $block->addFormField(
                      $sa_epoch,
                      $factory->getLabel("SAExpiry"),
                      $defaultPage
                    );
                }
                else {
                    // If the account is set to never expire, we show that as getTextField() instead:
                    $sa_epoch = $factory->getTextField("SAExpiry", $i18n->get("[[base-support.never]]"), 'r');
                    $block->addFormField(
                            $sa_epoch,
                            $factory->getLabel("SAExpiry", false),
                            $defaultPage
                            );
                }
            }
        }

        $select_caps =& $factory->getSetSelector('adminPowers',
                                $cceClient->array_to_scalar($allowed_labels), 
                                $cceClient->array_to_scalar($possible_labels),
                                'allowedAbilities', 'disallowedAbilities',
                                $cap_access, 
                                $cceClient->array_to_scalar($allowed_caps),
                                $cceClient->array_to_scalar(array_keys($possible_caps))
                            );

        $select_caps->setOptional(true);

        $block->addFormField($select_caps, 
                    $factory->getLabel('adminPowers'),
                    $advancedPage
                    );


        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/vsite/adminList"));

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
