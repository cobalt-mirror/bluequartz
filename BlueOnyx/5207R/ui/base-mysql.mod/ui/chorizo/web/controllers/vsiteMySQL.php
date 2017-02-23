<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vsiteMySQL extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /mysql/vsiteMySQL.
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
        $i18n = new I18n("base-mysql", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

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

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page.  Site admins
        // can view it for informational purposes.
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = TRUE;
            $access_basic = 'rw';
            $access_advanced = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access_basic = 'rw';
            $access_advanced = 'r';
            $is_site_admin = FALSE;
        }
        else {
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

        // Get the MySQL settings for this Vsite:
        $vsite_MySQL = $CI->cceClient->get($vsite['OID'], "MYSQL_Vsite");

        // Get PHPVsite for this Vsite:
        $PHPVsite = $CI->cceClient->get($vsite['OID'], "PHPVsite");

        // Get the existing MySQL data from CODB's "System" object:
        $SystemMYSQL = $CI->cceClient->get($system['OID'], "mysql");

        // Get the existing "MySQL" Object:
        $AbsMYSQL = $CI->cceClient->getObject("MySQL");

        // Get Array of extra MySQL databases:
        $mysql_databases_extra = $CI->cceClient->scalar_to_array($vsite_MySQL['DBmulti']);

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

            // Variable cleanup to remove what we don't want to update in CODB:
            if (isset($attributes['solmysql_username'])) {
                unset($attributes['solmysql_username']);
            }
            if (isset($attributes['solmysql_pass'])) {
                unset($attributes['solmysql_pass']);
            }
            if (isset($attributes['solmysql_host'])) {
                unset($attributes['solmysql_host']);
            }
            if (isset($attributes['solmysqlPort'])) {
                unset($attributes['solmysqlPort']);
            }
            if (isset($attributes['maxDBs'])) {
                unset($attributes['maxDBs']);
            }
            if (!isset($attributes['new_db_name'])) {
                // Set 'userPermsUpdate':
                $attributes['userPermsUpdate'] = time();
            }

            // Special case: siteAdmin has a SAVE button, but not rights to save anything but 'new_db_name'.
            if ($access_advanced == 'r') {
                if (isset($attributes['new_db_name'])) {
                    $new_db_name = $attributes['new_db_name'];
                }
                $attributes = array();
                if (isset($new_db_name)) {
                    $attributes['new_db_name'] = $new_db_name;
                }
            }
        }

        //
        //--- Remove existing DB:
        //
        if (isset($get_form_data['db_del'])) {
            // Check if Database exists:
            if (in_array($get_form_data['db_del'], $mysql_databases_extra)) {
                $CI->cceClient->set($vsite['OID'], 'MYSQL_Vsite', array("DBdel" => $get_form_data['db_del'], 'DBmultiDel' => time()));
                $my_errors = array_merge($errors, $CI->cceClient->errors());
                // Bye and redirect:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("Location: /mysql/vsiteMySQL?group=$group");
                exit;
            }
            else {
                $my_errors[] = ErrorMessage($i18n->get("[[base-mysql.db_not_found]]"));
            }
        }

        //
        //--- Reset User Permissions to Defaults:
        //
        if ((isset($get_form_data['reset'])) && ($access_advanced == 'rw')) {
            if ($get_form_data['reset'] == "defaults") {
                $CI->cceClient->set($vsite['OID'], 'MYSQL_Vsite', array("userPermsReset" => time()));
                $my_errors = array_merge($errors, $CI->cceClient->errors());
            }
            if (count($errors) == "0") {
                // Bye and redirect:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("Location: /mysql/vsiteMySQL?group=$group");
                exit;
            }
        }        

        //
        //--- Grant all Permissions:
        //
        if ((isset($get_form_data['perform'])) && ($access_advanced == 'rw')) {
            if ($get_form_data['perform'] == "all") {
                $CI->cceClient->set($vsite['OID'], 'MYSQL_Vsite', 
                    array(
                        'SELECT' => '1', 
                        'INSERT' => '1', 
                        'UPDATE' => '1', 
                        'DELETE' => '1', 
                        'FILE' => '1', 
                        'CREATE' => '1', 
                        'ALTER' => '1', 
                        'INDEX' => '1', 
                        'DROP' => '1', 
                        'TEMPORARY' => '1', 
                        'CREATE_VIEW' => '1', 
                        'SHOW_VIEW' => '1', 
                        'CREATE_ROUTINE' => '1', 
                        'ALTER_ROUTINE' => '1', 
                        'EXECUTE' => '1', 
                        'EVENT' => '1',
                        'TRIGGER' => '1',
                        'GRANT' => '0',
                        'LOCK_TABLES' => '1',
                        'REFERENCES' => '1',
                        'MAX_UPDATES_PER_HOUR' => '0', 
                        'MAX_QUERIES_PER_HOUR' => '0', 
                        'MAX_CONNECTIONS_PER_HOUR' => '0', 
                        "userPermsUpdate" => time())
                    );
                $my_errors = array_merge($errors, $CI->cceClient->errors());
            }
            if (count($errors) == "0") {
                // Bye and redirect:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                header("Location: /mysql/vsiteMySQL?group=$group");
                exit;
            }
        }  

        //
        //--- Raise Error if adding DB resulted in a name conflict:
        //
        if (isset($get_form_data['nameError'])) {
            $my_errors[] = ErrorMessage($i18n->get("[[base-mysql.db_name_already_in_use]]"));
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
            // Add new DB:
            if (isset($attributes['new_db_name'])) {

                if ((count($mysql_databases_extra) + 1) >= $vsite_MySQL['maxDBs']) {
                    // Someone tried to be *really* clever and tried to add more DBs than allowed:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#cheater");
                }

                if (in_array($attributes['new_db_name'], $mysql_databases_extra)) {
                    // Name conflict! DB already exists!
                    // Bye and redirect:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    header("Location: /mysql/vsiteMySQL?group=$group&addDB=true&nameError=true");
                    exit;
                }
                else {
                    // Check if DB already exists:
                    $err_level = error_reporting(0);  
                    $mysqli = new mysqli($AbsMYSQL['sql_host'], $AbsMYSQL['sql_root'], $AbsMYSQL['sql_rootpassword']);
                    error_reporting($err_level);
                    if (!$mysqli->connect_errno) {
                        // Check if the DB already exists in MySQL. And for this we use MySQLi because we *really*
                        // want to know if a DB with that name already exists. CODB could be wrong on this. And 
                        // polling CODB for DB names over all Vsites is *very* costly and (like said) might not 
                        // tell the whole truth. Imagine a dickhead user specifying 'mysql' as DB name and guess
                        // what kind of hillarity would ensue.
                        $sql = "SHOW DATABASES LIKE '" . $attributes['new_db_name'] . "'";
                        $result = $mysqli->query($sql);
                        if ($result->num_rows > 0) {
                            // A DB with that name already exists in MySQL:
                            $CI->cceClient->bye();
                            $CI->serverScriptHelper->destructor();
                            header("Location: /mysql/vsiteMySQL?group=$group&addDB=true&nameError=true");
                            exit;
                        }
                    }
                    $mysqli->close();

                    // Adding new DB:
                    $mysql_databases_extra[] = $attributes['new_db_name'];
                    $CI->cceClient->set($vsite['OID'], 'MYSQL_Vsite', array("DBmulti" => $CI->cceClient->array_to_scalar($mysql_databases_extra), 'DBmultiAdd' => time()));
                    $errors = array_merge($errors, $CI->cceClient->errors());
                    if (count($errors) == "0") {
                        // Bye and redirect:
                        $CI->cceClient->bye();
                        $CI->serverScriptHelper->destructor();
                        header("Location: /mysql/vsiteMySQL?group=$group");
                        exit;
                    }
                    else {
                        // DB with that name already exists for this Vsite:
                        $CI->cceClient->bye();
                        $CI->serverScriptHelper->destructor();
                        header("Location: /mysql/vsiteMySQL?group=$group&addDB=true&nameError=true");
                        exit;
                    }
                }
            }

            $CI->cceClient->set($vsite['OID'], 'MYSQL_Vsite', $attributes);
            $errors = array_merge($errors, $CI->cceClient->errors());

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                $redirect_URL = "/mysql/vsiteMySQL?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-mysql", "/mysql/vsiteMySQL?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('base_mysql_vsite');
        $page_module = 'base_sitemanage';

        $defaultPage = "VsiteDBtab";

        if (($vsite_MySQL["enabled"] == "0") || (isset($get_form_data['addDB']))) {
            $block =& $factory->getPagedBlock("mysql_vsite_head", array($defaultPage));
        }
        else {
            $block =& $factory->getPagedBlock("mysql_vsite_head", array($defaultPage, 'MySQLuserRights'));
        }
        $block->setLabel($factory->getLabel('mysql_vsite_head', false, array('vsite' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        //
        //-- Assemble info about databases that are present for this Vsite:
        //
        $dbList = array();
        $mysql_databases = array();
        $num_dbs = '0';
        if ($vsite_MySQL['DB'] != "") {
            $mysql_databases[] = $vsite_MySQL['DB'];

            $dbList[0][$num_dbs] = $vsite_MySQL['DB'];
            $dbList[1][$num_dbs] = '<a class="lbx" href="javascript:void(0)"><button class="red tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getWrapped("dbRemoveNotPoss") . '"><div class="ui-icon ui-icon-alert"></div></button></a><br>';
            $num_dbs++;

        }
        if ($vsite_MySQL['DBmulti'] != "") {
            if (is_array($mysql_databases_extra)) {
                foreach ($mysql_databases_extra as $key => $extra_db_name) {
                    $mysql_databases[] = $extra_db_name;
                    $dbList[0][$num_dbs] = $extra_db_name;
                    $dbList[1][$num_dbs] = '<a class="lb" href="/mysql/vsiteMySQL?group=' . $group . '&db_del=' . $extra_db_name . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getWrapped("dbRemove") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>';
                    $num_dbs++;
                }
            }
        }

        //
        //-- Assemble output depending on which transaction needs to be performed:
        //

        if ($vsite_MySQL["enabled"] == "0") {
            // Show error message box if MySQL is not enabled for this vsite:
            $mysqloff_statusbox = $factory->getTextField("MySQLVsiteNotEnabled", $i18n->get("MySQLVsiteNotEnabled"), 'r');
            $mysqloff_statusbox->setLabelType("nolabel");
            $block->addFormField(
                $mysqloff_statusbox,
                $factory->getLabel(" "),
                $defaultPage
                );
        }
        elseif (isset($get_form_data['addDB'])) {
            if ($get_form_data['addDB'] == "true") {
                if ($num_dbs < $vsite_MySQL['maxDBs']) {
                    $ndbField = $factory->getTextField("new_db_name", '', 'rw');
                    $ndbField->setMaxLength("16");
                    $block->addFormField(
                        $ndbField,
                        $factory->getLabel("new_db_name"),
                        $defaultPage
                    );
                }
                else {
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403");
                }
            }
            else {
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403");
            }
        }
        else {
            //
            //-- Show settings: Databases:
            //

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_Vsite_condetails", ""),
                    $factory->getLabel("DIVIDER_Vsite_condetails", false),
                    $defaultPage
                    );

            $block->addFormField(
                $factory->getTextField("solmysql_username", $vsite_MySQL["username"], 'r'),
                $factory->getLabel("solmysql_username"),
                $defaultPage
            );

            $block->addFormField(
                $factory->getTextField("solmysql_pass", $vsite_MySQL["pass"], 'r'),
                $factory->getLabel("solmysql_pass"),
                $defaultPage
            );

            $block->addFormField(
                $factory->getTextField("solmysql_host", $vsite_MySQL["host"], 'r'),
                $factory->getLabel("solmysql_host"),
                $defaultPage
            );

            $block->addFormField(
                $factory->getTextField("solmysqlPort", $vsite_MySQL["port"], 'r'),
                $factory->getLabel("solmysqlPort"),
                $defaultPage
            );

            $block->addFormField(
                $factory->getTextField("maxDBs", $vsite_MySQL["maxDBs"], 'r'),
                $factory->getLabel("maxDBs"),
                $defaultPage
            );

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_Vsite_DBlist", ""),
                    $factory->getLabel("DIVIDER_Vsite_DBlist", false),
                    $defaultPage
                    );

            // Button: Add Database:
            if ($num_dbs < $vsite_MySQL['maxDBs']) {
                $addDatabaseButton = $factory->getAddButton("/mysql/vsiteMySQL?group=$group&addDB=true", '[[base-mysql.DBaddbut_help]]');
                $buttonContainerAddDB = $factory->getButtonContainer("", array($addDatabaseButton));
                $block->addFormField(
                    $factory->getRawHTML("DBaddbut", $buttonContainerAddDB->toHtml()),
                    $factory->getLabel("DBaddbut"),
                    $defaultPage
                );
            }

            // Assemble ScrollList for MySQL database names:
            $scrollList = $factory->getScrollList("MySQLdbList", array("db_name", "action"), $dbList); 
            $scrollList->setAlignments(array("left", "center"));
            $scrollList->setDefaultSortedIndex('0');
            $scrollList->setSortOrder('ascending');
            $scrollList->setSortDisabled(array('1'));
            $scrollList->setPaginateDisabled(FALSE);
            $scrollList->setSearchDisabled(FALSE);
            $scrollList->setSelectorDisabled(FALSE);
            $scrollList->enableAutoWidth(FALSE);
            $scrollList->setInfoDisabled(FALSE);
            $scrollList->setColumnWidths(array("680", "55")); // Max: 739px

            // Push out the Scrollist:
            $block->addFormField(
                $factory->getRawHTML("MySQLdbList", $scrollList->toHtml()),
                $factory->getLabel("MySQLdbList"),
                $defaultPage
            );

            //
            //-- MySQL user rights:
            //

            // Button: Reset to defaults:
            if ($access_advanced == 'rw') {
                $reset_button = $factory->getButton("/mysql/vsiteMySQL?group=$group&reset=defaults", 'resetToDefaults');
                $grantAll_button = $factory->getButton("/mysql/vsiteMySQL?group=$group&perform=all", 'GrantAllPerms');
                $buttonContainer = $factory->getButtonContainer("", array($reset_button, $grantAll_button));
                $block->addFormField(
                    $buttonContainer,
                    $factory->getLabel(""),
                    'MySQLuserRights'
                );
            }

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_ONE", ""),
                    $factory->getLabel("DIVIDER_ONE", false),
                    'MySQLuserRights'
                    );

            $SELECT = $vsite_MySQL['SELECT'];
            $block->addFormField(
              $factory->getBoolean("SELECT", $SELECT, $access_advanced),
              $factory->getLabel("SELECT"),
              'MySQLuserRights'
            );
            $INSERT = $vsite_MySQL['INSERT'];
            $block->addFormField(
              $factory->getBoolean("INSERT", $INSERT, $access_advanced),
              $factory->getLabel("INSERT"),
              'MySQLuserRights'
            );
            $UPDATE = $vsite_MySQL['UPDATE'];
            $block->addFormField(
              $factory->getBoolean("UPDATE", $UPDATE, $access_advanced),
              $factory->getLabel("UPDATE"),
              'MySQLuserRights'
            );
            $DELETE = $vsite_MySQL['DELETE'];
            $block->addFormField(
              $factory->getBoolean("DELETE", $DELETE, $access_advanced),
              $factory->getLabel("DELETE"),
              'MySQLuserRights'
            );
            // File is a GLOBAL privilege and cannot be granted individually for a single DB:
            //$FILE = $vsite_MySQL['FILE'];
            //$block->addFormField(
            //  $factory->getBoolean("FILE", $FILE),
            //  $factory->getLabel("FILE"),
            //  'MySQLuserRights'
            //);

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_TWO", ""),
                    $factory->getLabel("DIVIDER_TWO", false),
                    'MySQLuserRights'
                    );

            $CREATE = $vsite_MySQL['CREATE'];
            $block->addFormField(
              $factory->getBoolean("CREATE", $CREATE, $access_advanced),
              $factory->getLabel("CREATE"),
              'MySQLuserRights'
            );
            $ALTER = $vsite_MySQL['ALTER'];
            $block->addFormField(
              $factory->getBoolean("ALTER", $ALTER, $access_advanced),
              $factory->getLabel("ALTER"),
              'MySQLuserRights'
            );
            $INDEX = $vsite_MySQL['INDEX'];
            $block->addFormField(
              $factory->getBoolean("INDEX", $INDEX, $access_advanced),
              $factory->getLabel("INDEX"),
              'MySQLuserRights'
            );
            $DROP = $vsite_MySQL['DROP'];
            $block->addFormField(
              $factory->getBoolean("DROP", $DROP, $access_advanced),
              $factory->getLabel("DROP"),
              'MySQLuserRights'
            );
            $TEMPORARY = $vsite_MySQL['TEMPORARY'];
            $block->addFormField(
              $factory->getBoolean("TEMPORARY", $TEMPORARY, $access_advanced),
              $factory->getLabel("TEMPORARY"),
              'MySQLuserRights'
            );

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_THREE", ""),
                    $factory->getLabel("DIVIDER_THREE", false),
                    'MySQLuserRights'
                    );

            $CREATE_VIEW = $vsite_MySQL['CREATE_VIEW'];
            $block->addFormField(
              $factory->getBoolean("CREATE_VIEW", $CREATE_VIEW, $access_advanced),
              $factory->getLabel("CREATE_VIEW"),
              'MySQLuserRights'
            );
            $SHOW_VIEW = $vsite_MySQL['SHOW_VIEW'];
            $block->addFormField(
              $factory->getBoolean("SHOW_VIEW", $SHOW_VIEW, $access_advanced),
              $factory->getLabel("SHOW_VIEW"),
              'MySQLuserRights'
            );
            $CREATE_ROUTINE = $vsite_MySQL['CREATE_ROUTINE'];
            $block->addFormField(
              $factory->getBoolean("CREATE_ROUTINE", $CREATE_ROUTINE, $access_advanced),
              $factory->getLabel("CREATE_ROUTINE"),
              'MySQLuserRights'
            );
            $ALTER_ROUTINE = $vsite_MySQL['ALTER_ROUTINE'];
            $block->addFormField(
              $factory->getBoolean("ALTER_ROUTINE", $ALTER_ROUTINE, $access_advanced),
              $factory->getLabel("ALTER_ROUTINE"),
              'MySQLuserRights'
            );
            $EXECUTE = $vsite_MySQL['EXECUTE'];
            $block->addFormField(
              $factory->getBoolean("EXECUTE", $EXECUTE, $access_advanced),
              $factory->getLabel("EXECUTE"),
              'MySQLuserRights'
            );

            // New additions:
            $EVENT = $vsite_MySQL['EVENT'];
            $block->addFormField(
              $factory->getBoolean("EVENT", $EVENT, $access_advanced),
              $factory->getLabel("EVENT"),
              'MySQLuserRights'
            );
            $TRIGGER = $vsite_MySQL['TRIGGER'];
            $block->addFormField(
              $factory->getBoolean("TRIGGER", $TRIGGER, $access_advanced),
              $factory->getLabel("TRIGGER"),
              'MySQLuserRights'
            );

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_ADM", ""),
                    $factory->getLabel("DIVIDER_ADM", false),
                    'MySQLuserRights'
                    );
            $GRANT = $vsite_MySQL['GRANT'];
            $block->addFormField(
              $factory->getBoolean("GRANT", $GRANT, 'r'),
              $factory->getLabel("GRANT"),
              'MySQLuserRights'
            );
            $LOCK_TABLES = $vsite_MySQL['LOCK_TABLES'];
            $block->addFormField(
              $factory->getBoolean("LOCK_TABLES", $LOCK_TABLES, $access_advanced),
              $factory->getLabel("LOCK_TABLES"),
              'MySQLuserRights'
            );
            $REFERENCES = $vsite_MySQL['REFERENCES'];
            $block->addFormField(
              $factory->getBoolean("REFERENCES", $REFERENCES, $access_advanced),
              $factory->getLabel("REFERENCES"),
              'MySQLuserRights'
            );

            // Add divider:
            $block->addFormField(
                    $factory->addBXDivider("DIVIDER_FOUR", ""),
                    $factory->getLabel("DIVIDER_FOUR", false),
                    'MySQLuserRights'
                    );

            $MAX_QUERIES_PER_HOUR =& $factory->getInteger("MAX_QUERIES_PER_HOUR", $vsite_MySQL['MAX_QUERIES_PER_HOUR'], 0, 50000000, $access_advanced);
            $MAX_QUERIES_PER_HOUR->showBounds(1);
            $MAX_QUERIES_PER_HOUR->setWidth(8);
            $block->addFormField(
                $MAX_QUERIES_PER_HOUR,
                $factory->getLabel('MAX_QUERIES_PER_HOUR'),
                'MySQLuserRights'
                );

            $MAX_CONNECTIONS_PER_HOUR =& $factory->getInteger("MAX_CONNECTIONS_PER_HOUR", $vsite_MySQL['MAX_CONNECTIONS_PER_HOUR'], 0, 50000000, $access_advanced);
            $MAX_CONNECTIONS_PER_HOUR->showBounds(1);
            $MAX_CONNECTIONS_PER_HOUR->setWidth(8);
            $block->addFormField(
                $MAX_CONNECTIONS_PER_HOUR,
                $factory->getLabel('MAX_CONNECTIONS_PER_HOUR'),
                'MySQLuserRights'
                );

            $MAX_UPDATES_PER_HOUR =& $factory->getInteger("MAX_UPDATES_PER_HOUR", $vsite_MySQL['MAX_UPDATES_PER_HOUR'], 0, 50000000, $access_advanced);
            $MAX_UPDATES_PER_HOUR->showBounds(1);
            $MAX_UPDATES_PER_HOUR->setWidth(8);
            $block->addFormField(
                $MAX_UPDATES_PER_HOUR,
                $factory->getLabel('MAX_UPDATES_PER_HOUR'),
                'MySQLuserRights'
                );
        }

        // Extra header for the "do you really want to delete" dialog:
        $BxPage->setExtraHeaders('
                <script type="text/javascript">
                $(document).ready(function () {

                  $("#dialog").dialog({
                    modal: true,
                    bgiframe: true,
                    width: 500,
                    height: 280,
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

        // Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
            <div class="display_none">
                        <div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-mysql.dbRemoveConfirmNeutral]]") . '">
                            <div class="block">
                                    <div class="section">
                                            <h1>' . $i18n->getHtml("[[base-mysql.dbRemoveConfirmNeutral]]") . '</h1>
                                            <div class="dashed_line"></div>
                                            <p>' . $i18n->getHtml("[[base-mysql.DBremoveConfirmInfo]]") . '</p>
                                    </div>
                            </div>
                        </div>
            </div>';

        // Add the buttons for those who can edit this page:
        if (((isset($get_form_data['addDB'])) && ($access_advanced == 'r') && ($vsite_MySQL["enabled"] != "0")) || (($access_advanced == 'rw') && ($vsite_MySQL["enabled"] != "0"))) {
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/mysql/vsiteMySQL?group=$group"));
        }

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

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