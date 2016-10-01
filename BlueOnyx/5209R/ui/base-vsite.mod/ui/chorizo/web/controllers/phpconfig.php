<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Phpconfig extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/phpconfig.
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
        $i18n = new I18n("base-vsite", $user['localePreference']);
        $system = $cceClient->getObject("System");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

        // -- Actual page logic start:

        // Not serverConfig? Bye, bye!
        if (!$Capabilities->getAllowed('serverConfig')) {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Get CODB-Object of interest: 
        //

        $CODBDATA = $cceClient->getObject("PHP");
        $platform = $CODBDATA["PHP_version"];

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        // Known PHP versions:
        $known_php_versions = array(
                                'PHP53' => '5.3',
                                'PHP54' => '5.4',
                                'PHP55' => '5.5',
                                'PHP56' => '5.6',
                                'PHP70' => '7.0',
                                'PHP71' => '7.1',
                                'PHP72' => '7.2',
                                'PHP73' => '7.3',
                                'PHP74' => '7.4',
                                'PHP75' => '7.5',
                                'PHP76' => '7.6',
                                'PHP77' => '7.7',
                                'PHP78' => '7.8',
                                'PHP79' => '7.9'
                                );

        // Start clean:
        $enabledExtraPHPversions = array();

        // Shove submitted input into $form_data after passing it through the XSS filter:
        $form_data = $CI->input->post(NULL, TRUE);

        // Form fields that are required to have input:
        $required_keys = array();
        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("BlueOnyx_Info_Text", "_", "PHP_version");
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
            $attributes['force_update'] = time();

            // Clean up 'open_basedir' user additions - and just the user additions:
            $open_basedir_cleaned = str_replace(array("\r\n", "\r", "\n"), ':', $attributes['open_basedir']);

            // Bare metal minimals for 'open_basedir':
            $open_basedir_minimal = array('/tmp/', '/var/lib/php/session/', '/usr/sausalito/configs/php/');

            // Turn it into an array:
            $open_basedir_temp = explode(":", $open_basedir_cleaned);

            // Walk through the array to filter out anything that doesn't look like a valid path:
            foreach ($open_basedir_temp as $entry) {
                // Valid paths must start with a slash and end with a slash and certainly not with two slashes at the beginning:
                if ((preg_match("/^\/(.*)\/?$/", $entry, $regs)) && (!preg_match("/^\/\/(.*)$/", $entry, $regs))) {
                    if ((is_dir($entry)) || (is_readable($entry))) {
                        // That is_dir() or is_readable() ought to weed out anything that's invalid. Poor man's regular expression. :p
                        array_push($open_basedir_minimal, $entry);
                    }
                    else {
                        print_rp("Removing $entry");
                    }
                }
            }

            // Remove duplicates:
            $open_basedir_unique = array_unique($open_basedir_minimal);

            // Assemble the results into a workable format:
            array_multisort($open_basedir_unique, SORT_ASC);
            $attributes['open_basedir'] = implode(":", $open_basedir_unique);

            // Clean up 'disable_functions':
            $attributes['disable_functions'] = str_replace(array("\r\n", "\r", "\n"), ',', $attributes['disable_functions']);

            // Make sure that 'register_globals' is off on PHP versions of 5.4 or greater:
            if ($platform >= "5.4") {
                $attributes['register_globals'] = "Off";
            }

            // Handle default PHP version change for Apache:
            if (isset($attributes['setDefaultPHPVersion'])) {
                $attributes['PHP_version'] = $attributes['setDefaultPHPVersion'];
                unset($attributes['setDefaultPHPVersion']);
            }

            // Handle Extra PHP versions:
            if (isset($attributes['extraPHPversions'])) {
                $enabledExtraPHPversions = scalar_to_array($attributes['extraPHPversions']);
                unset($attributes['extraPHPversions']);
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // Enable / Disable PHP Extra versions:
            foreach ($known_php_versions as $NSkey => $NSvalue) {
                if (in_array($NSkey, $enabledExtraPHPversions)) {
                    $cceClient->setObject("PHP", array('enabled' => '1'), "$NSkey" );
                }
                else {
                    $cceClient->setObject("PHP", array('enabled' => '0'), "$NSkey" );
                }
            }

            // Actual submit to CODB:
            $cceClient->setObject("PHP", $attributes);

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                header("Location: /vsite/phpconfig");
                exit;
            }
            else {
                $CODBDATA = $attributes;
            }
        }

        //
        //-- Own page logic:
        //

        $CODBDATA = $cceClient->getObject("PHP");
        $platform = $CODBDATA["PHP_version"];

        //
        //-- Generate page:
        //


        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/phpconfig");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_security');
        $BxPage->setVerticalMenuChild('base_php_server');
        $page_module = 'base_sysmanage';

        $defaultPage = "php_ini_security_settings";

        $block =& $factory->getPagedBlock("php_server_head", array($defaultPage, 'php_ini_expert_mode'));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- php_ini_security_settings
        //

        // PHP_version being used by Apache:
        $PHP_version_Field = $factory->getTextField("PHP_version", $CODBDATA['PHP_version'], "r");
        $PHP_version_Field->setOptional ('silent');
        $block->addFormField(
            $PHP_version_Field,
            $factory->getLabel("PHP_version"),
            "php_ini_security_settings"
        );

        if ($CODBDATA['PHP_version_os'] != $CODBDATA['PHP_version']) {
            // PHP_version of the OS:
            $PHP_version_os_Field = $factory->getTextField("PHP_version_os", $CODBDATA['PHP_version_os'], "r");
            $PHP_version_os_Field->setOptional ('silent');
            $block->addFormField(
                $PHP_version_os_Field,
                $factory->getLabel("PHP_version_os"),
                "php_ini_security_settings"
            );
        }

        foreach ($known_php_versions as $NSkey => $NSvalue) {
            $extraPHPs[$NSkey] = $cceClient->get($CODBDATA['OID'], $NSkey);
            if ($extraPHPs[$NSkey]['present'] != "1") {
                unset($extraPHPs[$NSkey]);
            }
        }

        $all_php_versions = array('PHPOS' => $CODBDATA['PHP_version_os']);
        $all_php_versions_reverse = array($CODBDATA['PHP_version_os'] => 'PHPOS');

        // Assemble data for getSetSelector() for enabling/disabling PHP versions:
        if (count($extraPHPs) > "0") {

            $all_available_php_versions = array();
            $all_available_php_versions_labels = array();
            $permitted_php_versions = array();
            $permitted_php_versions_labels = array();
            $all_selectable_php_versions = array();

            foreach ($extraPHPs as $NSkey => $NSvalue) {
                if ($NSvalue['present'] == '1') {
                    $all_available_php_versions[] = $NSvalue['NAMESPACE'];
                    $all_available_php_versions_labels[] = $NSvalue['version'];
                    $all_php_versions[$NSvalue['NAMESPACE']] = $NSvalue['version'];
                    $all_php_versions_reverse[$NSvalue['version']] = $NSvalue['NAMESPACE'];
                    if ($NSvalue['enabled'] == '1') {
                        $permitted_php_versions[$NSvalue['NAMESPACE']] = '1';
                        $permitted_php_versions_labels[$NSvalue['NAMESPACE']] = $NSvalue['version'];
                        $all_selectable_php_versions[] = $NSvalue['NAMESPACE'];
                    }
                    else {
                        $permitted_php_versions[$NSvalue['NAMESPACE']] = '0';
                    }
                }
            }

            $extraPHPversions =& $factory->getSetSelector('extraPHPversions',
                    $cceClient->array_to_scalar($permitted_php_versions_labels), 
                    $cceClient->array_to_scalar($all_available_php_versions_labels),
                    'allowedPHPversions', 'disallowedPHPversions',
                    "rw", 
                    $cceClient->array_to_scalar($all_selectable_php_versions),
                    $cceClient->array_to_scalar(array_keys($permitted_php_versions))
                );

            $extraPHPversions->setOptional(true);
        }

        if (count($all_php_versions) > '1') {
            // Add a pulldown that allows to change the default PHP version of Apache to
            // one of the extra PHP versions:
            $setDefaultPHPVersion_select = $factory->getMultiChoice("setDefaultPHPVersion", array_values($all_php_versions));
            $setDefaultPHPVersion_select->setSelected($CODBDATA['PHP_version'], true);
            $block->addFormField($setDefaultPHPVersion_select, $factory->getLabel("setDefaultPHPVersion"), "php_ini_security_settings");
        }
        else {
            // Add a hidden textField instead (no need to confuse people):
            $setDefaultPHPVersion = $factory->getTextField("setDefaultPHPVersion", $CODBDATA['PHP_version_os'], "");
            $setDefaultPHPVersion->setOptional ('silent');
            $block->addFormField(
                $setDefaultPHPVersion,
                $factory->getLabel("setDefaultPHPVersion"),
                "php_ini_security_settings"
            );
        }

        // Display the getSetSelector():
        if (count($extraPHPs) > "0") {
            $block->addFormField($extraPHPversions, 
                    $factory->getLabel('extraPHPversions'),
                    "php_ini_security_settings"
                );

            // Hmmm .... not ideal. Need to throw in a spacer or the getSetSelector() displays oddly:
            $block->addFormField(
                $factory->getRawHTML("Spacer", '<IMG BORDER="0" WIDTH="120" HEIGHT="0" SRC="/libImage/spaceHolder.gif">'),
                $factory->getLabel("Spacer"),
                "php_ini_security_settings"
            );

            // If a PHP-5.3 is present, we show the checkbox that allows to sets its 'register_globals_exception' to on:
            if (isset($extraPHPs['PHP53'])) {
                if ($permitted_php_versions['PHP53'] == '1') {
                    $block->addFormField(
                        $factory->getBoolean('register_globals_exception', $CODBDATA['register_globals_exception'], "rw"),
                        $factory->getLabel('register_globals_exception'), 
                        "php_ini_security_settings"
                        );
                }
            }
        }

        // php.ini location:
        $php_ini_location_Field = $factory->getTextField("php_ini_location", $CODBDATA['php_ini_location'], "r");
        $php_ini_location_Field->setOptional ('silent');
        $block->addFormField(
            $php_ini_location_Field,
            $factory->getLabel("php_ini_location"),
            "php_ini_security_settings"
        );

        if ($platform < "5.4") {
            // Register Globals (only shown if PHP version is smaller than 5.4):
            if ($CODBDATA["register_globals"] == 'Off') {
                    $register_globals_choices=array("Off" => "Off", "On" => "On");
            }
            else {
                //Strict, but safe default:
                    $register_globals_choices=array("On" => "On", "Off" => "Off");
                    $CODBDATA["register_globals"] = 'Off';
            }

            // Register Globals Input:
            $register_globals_select = $factory->getMultiChoice("register_globals",array_values($register_globals_choices));
            $register_globals_select->setSelected($register_globals_choices[$CODBDATA["register_globals"]], true);
            $block->addFormField($register_globals_select,$factory->getLabel("register_globals"), "php_ini_security_settings");
        }

        //------ open_basedir:

        // Make sure our 'open_basedir' has all the mandatory stuff in it:
        $open_basedir_mandatory_pieces = array("/tmp/", "/var/lib/php/session/", "/usr/sausalito/configs/php/");

        // Now we walk through $CODBDATA['open_basedir'] and make sure nobody added '/home/.sites/' here:
        $this_open_basedir = preg_split ("/:/", $CODBDATA['open_basedir']);
        // Pre-populate our new output array with the mandatory fields (we remove duplicates later on):
        $this_open_basedir_new = $open_basedir_mandatory_pieces;
        foreach ($this_open_basedir as $entry) {
            // Only push pieces if '/home/.sites/' or '/home/sites/' has not been added:
            if ((!preg_match("/^\/home\/.sites\//i", $entry, $regs)) && (!preg_match("/^\/home\/sites\//i", $entry, $regs))) {
                array_push($this_open_basedir_new, $entry);
            }
        }

        // Remove duplicates:
        $open_basedir_cleaned = array_unique($this_open_basedir_new);

        // Sort the array before we implode them later on:
        array_multisort($open_basedir_cleaned, SORT_ASC);

        // Print out the block with the mandatory 'open_basedir' stuff. Please note: This is for display only. The contends here cannot
        // be processed via form handlers:
        $open_basedir_mandatory_Field = $factory->getTextBlock("open_basedir", implode("\n",$open_basedir_cleaned));
        $open_basedir_mandatory_Field->setOptional('silent');
        $block->addFormField(
            $open_basedir_mandatory_Field,
            $factory->getLabel("open_basedir_mandatory"),
            "php_ini_security_settings"
        ); 

        // disable_functions
        $this_disable_functions = preg_split ("/,/", $CODBDATA['disable_functions']);
        $disable_functions_Field = $factory->getTextBlock("disable_functions", implode("\n",$this_disable_functions));
        $disable_functions_Field->setOptional('silent');
        $disable_functions_Field->setType('alphanum_plus_multiline');
        $block->addFormField(
            $disable_functions_Field,
            $factory->getLabel("disable_functions"),
            "php_ini_security_settings"
        );

        // disable_classes
        $this_disable_classes = preg_split ("/,/", $CODBDATA['disable_classes']);
        $disable_classes_Field = $factory->getTextBlock("disable_classes", implode("\n",$this_disable_classes));
        $disable_classes_Field->setOptional('silent');
        $disable_classes_Field->setType('alphanum_plus_multiline');
        $block->addFormField(
            $disable_classes_Field,
            $factory->getLabel("disable_classes"),
            "php_ini_security_settings"
        );

        // allow_url_fopen:
        if ($CODBDATA["allow_url_fopen"] == 'On') {
            $allow_url_fopen_choices=array("On" => "On", "Off" => "Off");
        }
        else {
            //Strict, but safe default:
            $allow_url_fopen_choices=array("Off" => "Off", "On" => "On");
            $CODBDATA["allow_url_fopen"] = "Off";
        }

        // allow_url_fopen Input:
        $allow_url_fopen_select = $factory->getMultiChoice("allow_url_fopen", array_values($allow_url_fopen_choices));
        $allow_url_fopen_select->setSelected($allow_url_fopen_choices[$CODBDATA["allow_url_fopen"]], true);
        $block->addFormField($allow_url_fopen_select,$factory->getLabel("allow_url_fopen"), "php_ini_security_settings");

        // allow_url_include:
        if ($CODBDATA["allow_url_include"] == 'On') {
                $allow_url_include_choices=array("On" => "On", "Off" => "Off");
        }
        else {
            //Strict, but safe default:
            $allow_url_include_choices=array("Off" => "Off", "On" => "On");
            $CODBDATA["allow_url_include"] = 'Off';
        }

        // allow_url_include Input:
        $allow_url_include_select = $factory->getMultiChoice("allow_url_include",array_values($allow_url_include_choices));
        $allow_url_include_select->setSelected($allow_url_include_choices[$CODBDATA["allow_url_include"]], true);
        $block->addFormField($allow_url_include_select,$factory->getLabel("allow_url_include"), "php_ini_security_settings");

        // upload_max_filesize:
        if ($CODBDATA['upload_max_filesize']) {
            $upload_max_filesize_choices = array (
                '2M',
                '4M',
                '8M',
                '16M',
                '24M',
                '32M',
                '40M',
                '48M',
                '56M',
                '64M',
                '72M',
                '80M',   
                '88M',
                '96M',
                '104M',
                '112M',
                '120M',
                '128M',
                '132M',
                '140M',
                '148M',
                '156M',
                '164M',
                '172M',
                '180M',
                '256M',
                '512M',
                '750M',
                '1024M'
            );

            // If we're currently using something that's not in that array, we add it to it:
            if (!in_array($CODBDATA['upload_max_filesize'], $upload_max_filesize_choices)) {
                    $upload_max_filesize_choices[] = $CODBDATA['upload_max_filesize'];
            }
            sort($upload_max_filesize_choices, SORT_NUMERIC);
        }

        // upload_max_filesize Input:
        $upload_max_filesize_choices_select = $factory->getMultiChoice("upload_max_filesize",array_values($upload_max_filesize_choices));
        $upload_max_filesize_choices_select->setSelected($CODBDATA['upload_max_filesize'], true);
        $block->addFormField($upload_max_filesize_choices_select,$factory->getLabel("upload_max_filesize"), "php_ini_security_settings");

        // post_max_size:
        $post_max_size_choices = array (
            '2M',  
            '4M',  
            '8M',  
            '16M', 
            '24M', 
            '32M', 
            '40M', 
            '48M', 
            '56M', 
            '64M', 
            '72M',
            '80M',
            '88M',
            '96M',
            '104M',
            '112M',
            '120M',
            '128M',
            '132M',
            '140M',
            '148M',
            '156M',
            '164M',
            '172M',
            '180M',
            '256M',
            '512M',
            '750M',
            '1024M'
        );

        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($CODBDATA['post_max_size'], $post_max_size_choices)) {
                $post_max_size_choices[] = $CODBDATA['post_max_size'];
        }
        sort($post_max_size_choices, SORT_NUMERIC);

        // post_max_size Input:
        $post_max_size_choices_select = $factory->getMultiChoice("post_max_size",array_values($post_max_size_choices));
        $post_max_size_choices_select->setSelected($CODBDATA['post_max_size'], true);
        $block->addFormField($post_max_size_choices_select,$factory->getLabel("post_max_size"), "php_ini_security_settings");

        // max_execution_time:
        $max_execution_time_choices = array (
            '30',  
            '60',  
            '90',  
            '120', 
            '150', 
            '180', 
            '210', 
            '240', 
            '270', 
            '300', 
            '500',
            '600',
            '900'
        );
        
        // If we're currently using something that's not in that array, we add it to it:
        if ((!in_array($CODBDATA['max_execution_time'], $max_execution_time_choices)) && ($CODBDATA['max_execution_time'] != "")) {
                $max_execution_time_choices[] = $CODBDATA['max_execution_time'];
        }
        sort($max_execution_time_choices, SORT_NUMERIC);

        // max_execution_time Input:
        $max_execution_time_choices_select = $factory->getMultiChoice("max_execution_time",array_values($max_execution_time_choices));
        $max_execution_time_choices_select->setSelected($CODBDATA['max_execution_time'], true);
        $block->addFormField($max_execution_time_choices_select,$factory->getLabel("max_execution_time"), "php_ini_security_settings");

        // max_input_time:
        $max_input_time_choices = array (
            '30',  
            '60',  
            '90',  
            '120', 
            '150', 
            '180', 
            '210', 
            '240', 
            '270',
            '300',
            '500',
            '600',
            '900'
        );
        
        // If we're currently using something that's not in that array, we add it to it:
        if ((!in_array($CODBDATA['max_input_time'], $max_input_time_choices)) && ($CODBDATA['max_input_time'] != "")) {
                $max_input_time_choices[] = $CODBDATA['max_input_time'];
        }
        sort($max_input_time_choices, SORT_NUMERIC);

        // max_input_time Input:
        $max_input_time_choices_select = $factory->getMultiChoice("max_input_time",array_values($max_input_time_choices));
        $max_input_time_choices_select->setSelected($CODBDATA['max_input_time'], true);
        $block->addFormField($max_input_time_choices_select,$factory->getLabel("max_input_time"), "php_ini_security_settings");

        // memory_limit:
        $memory_limit_choices = array (
            '16M', 
            '24M', 
            '32M', 
            '40M', 
            '48M', 
            '56M',
            '64M',
            '72M',
            '80M',
            '88M',
            '96M',
            '104M',
            '112M',
            '120M',
            '128M',
            '132M',
            '140M',
            '148M',
            '156M',
            '164M',
            '172M',
            '180M',
            '256M',
            '512M',
            '750M',
            '1024M'
        );

        // If we're currently using something that's not in that array, we add it to it:
        if ((!in_array($CODBDATA['memory_limit'], $memory_limit_choices)) && ($CODBDATA['memory_limit'] != "")) {
            $memory_limit_choices[] = $CODBDATA['memory_limit'];
        }
        sort($memory_limit_choices, SORT_NUMERIC);

        // max_input_vars:
        if (!isset($CODBDATA["max_input_vars"])) { 
            $CODBDATA["max_input_vars"] = '1000'; 
        }
        $max_input_vars_Field = $factory->getInteger("max_input_vars", $CODBDATA["max_input_vars"], "-1", "65535");
        $max_input_vars_Field->setWidth(5);
        $max_input_vars_Field->showBounds(1);
        $block->addFormField(
            $max_input_vars_Field,
            $factory->getLabel("max_input_vars"),
            "php_ini_security_settings"
        );

        // memory_limit Input:
        $memory_limit_choices_select = $factory->getMultiChoice("memory_limit",array_values($memory_limit_choices));
        $memory_limit_choices_select->setSelected($CODBDATA['memory_limit'], true);
        $block->addFormField($memory_limit_choices_select,$factory->getLabel("memory_limit"), "php_ini_security_settings");

        // fpm_max_children:
        if (!isset($CODBDATA["fpm_max_children"])) { 
            $CODBDATA["fpm_max_children"] = '15'; 
        }
        $fpm_max_children_Field = $factory->getInteger("fpm_max_children", $CODBDATA["fpm_max_children"], "1", "255");
        $fpm_max_children_Field->setWidth(3);
        $fpm_max_children_Field->showBounds(1);
        $block->addFormField(
            $fpm_max_children_Field,
            $factory->getLabel("fpm_max_children"),
            "php_ini_security_settings"
        );

        //
        //--- php_ini_expert_mode
        //

        // Note: As of now we don't allow editing php.ini through the GUI. We already didn't allow it before, so this
        // is not just a quirk of this new GUI. The reason here is that it's one big hassle. We can't do relieable
        // error checks and there is just too big a chance for a fuck-up. So we just show the php.ini in the safest
        // possible way. getTextArea() isn't relieable here due to the special characters in php.ini which throw
        // the new jQuery stuff into a hissy-fit. Hence we use getRawHTML(), pass php.ini through formspecialchars()
        // to make it safe for viewing and encapsulate the result into <pre></pre> tags:

        $file_php_ini = $CODBDATA['php_ini_location'];
        $ret = $serverScriptHelper->shell("/bin/cat $file_php_ini", $the_file_data, 'root', $sessionId);
        $ini_presenter = $factory->getRawHTML("php_ini", "<pre>" . formspecialchars($the_file_data) . "</pre>", "r");
        $block->addFormField(
          $ini_presenter,
          $factory->getLabel("php_ini"),
          "php_ini_expert_mode"
        );

        //
        //--- Add the buttons
        //

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/vsite/phpconfig"));

        // Nice people say goodbye, or CCEd waits forever:
        $cceClient->bye();
        $serverScriptHelper->destructor();

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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