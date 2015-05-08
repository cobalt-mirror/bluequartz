<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vsitePHP extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /vsite/vsitePHP.
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
            $cceClient->bye();
            $serverScriptHelper->destructor();
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
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        //
        //-- Prepare data:
        //

        // Known PHP versions:
        $known_php_versions = array(
                                'PHP53' => '5.3',
                                'PHP54' => '5.4',
                                'PHP55' => '5.5',
                                'PHP56' => '5.6'
                                );

        // Get data for the Vsite:
        $vsite = $cceClient->getObject('Vsite', array('name' => $group));

        // Get the PHP settings for this Vsite:
        $vsite_php = $cceClient->getObject('Vsite', array('name' => $group), "PHP");

        // Get PHPVsite for this Vsite:
        $systemObj = $cceClient->getObject('Vsite', array('name' => $group), "PHPVsite");

        // Find out which PHP version the server uses:
        $system_php = $cceClient->getObject('PHP');
        $platform = $system_php["PHP_version"];

        // Get all known PHP versions together:
        $all_php_versions = array('PHPOS' => $system_php['PHP_version_os']);
        $all_php_versions_reverse = array($system_php['PHP_version_os'] => 'PHPOS');

        foreach ($known_php_versions as $NSkey => $NSvalue) {
            $extraPHPs[$NSkey] = $cceClient->get($system_php["OID"], $NSkey);
            if ($extraPHPs[$NSkey]['present'] != "1") {
                unset($extraPHPs[$NSkey]);
            }
        }

        $all_selectable_php_versions['PHPOS'] = $system_php['PHP_version_os'];
        foreach ($extraPHPs as $NSkey => $NSvalue) {
            if ($NSvalue['present'] == '1') {
                $all_php_versions[$NSvalue['NAMESPACE']] = $NSvalue['version'];
                $all_php_versions_reverse[$NSvalue['version']] = $NSvalue['NAMESPACE'];
                if ($NSvalue['enabled'] == '1') {
                    $all_selectable_php_versions[$NSvalue['NAMESPACE']] = $NSvalue['version'];
                }
            }
        }

        // Find out which PHP version the Vsite is supposed to use:
        if ($vsite_php['version'] == "") {
            $usedPHPversion = $all_selectable_php_versions['PHPOS'];
        }
        else {
            $usedPHPversion = $all_selectable_php_versions[$vsite_php['version']];
        }

        if (($usedPHPversion >= "5.3") && ($system_php["show_safemode"] == "0")) {
            // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
            $pageID53 = "Hidden";
        }
        else {
            $pageID53 = "defaultPage";
        }

        if (($usedPHPversion >= "5.4") && ($system_php["show_safemode"] == "0")) {
            // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
            $pageID54 = "Hidden";
        }
        else {
            $pageID54 = "defaultPage";
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

            //
            //-- Handle 'open_basedir':
            //

            // Clean up 'open_basedir' user additions - and just the user additions:
            $open_basedir_cleaned = str_replace(array("\r\n", "\r", "\n"), ':', $attributes['open_basedir']);

            // Turn it into an array:
            $open_basedir_temp = explode(":", $open_basedir_cleaned);

            // Walk through the array to filter out anything that doesn't look like a valid path:
            $open_basedir_nocrap = array();
            foreach ($open_basedir_temp as $entry) {
                // Valid paths must start with a slash and end with a slash and certainly not with two slashes at the beginning:
                if (preg_match("/^\/(.*)\/?$/", $entry, $regs) && (!preg_match("/^\/\/(.*)$/", $entry, $regs))) {
                    // Only push if the user added addition isn't already covered in the mandatory section:
                    if (!in_array($entry, explode(":", $attributes['open_basedir_mandatory_hidden']))) {
                        // And make sure the bloody directory exists to begin with:
                        if (is_dir($entry)) {
                            array_push($open_basedir_nocrap, $entry);
                        }
                    }
                }
            }

            // No longer need this beyond this point:
            unset($attributes['open_basedir_mandatory_hidden']);

            // Remove duplicates:
            $open_basedir_unique = array_unique($open_basedir_nocrap);

            // Assemble the results into a workable format:
            array_multisort($open_basedir_unique, SORT_ASC);
            $attributes['open_basedir'] = implode(":", $open_basedir_unique);

            // Make sure our 'safe_mode_allowed_env_vars' has the bare metal minimums in it:
            $safe_mode_allowed_env_vars_minimal = array('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
            if (isset($attributes['safe_mode_allowed_env_vars'])) {
                $safe_mode_allowed_env_vars_pieces = explode (',', $attributes['safe_mode_allowed_env_vars']);
                if ($safe_mode_allowed_env_vars_pieces[0] == "") {
                    $safe_mode_allowed_env_vars_pieces = array();
                }
                $new_safe_mode_allowed_env_vars = array_unique($safe_mode_allowed_env_vars_merged);
                $attributes['safe_mode_allowed_env_vars'] = implode(',', $new_safe_mode_allowed_env_vars);
            }

            if ($usedPHPversion >= "5.3") {
                // We reset these to our safe defaults just to make sure:
                $attributes['safe_mode_allowed_env_vars'] = implode(',', $safe_mode_allowed_env_vars_minimal);
                $attributes['safe_mode_protected_env_vars'] = 'LD_LIBRARY_PATH';
            }

            // We need to *really* make sure 'register_globals' is set to "Off" on
            // PHP versions of PHP-5.4 and above, or we get nasty error messages:
            if ($usedPHPversion >= "5.4") {
                $attributes['register_globals'] = "Off";
            }

        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            if ($platform >= "5.3") {
                // We need to skip updating some legacy PHP settings that no longer work in PHP-5.3 or better:
                $cceClient->set($vsite['OID'], 'PHPVsite',
                    array(
                        "force_update" => time(),
                        "register_globals" => $attributes['register_globals'],
                        "open_basedir" => $attributes['open_basedir'],
                        "upload_max_filesize" => $attributes['upload_max_filesize'],
                        "post_max_size" => $attributes['post_max_size'],
                        "allow_url_fopen" => $attributes['allow_url_fopen'],
                        "allow_url_include" => $attributes['allow_url_include'],
                        "max_execution_time" => $attributes['max_execution_time'],
                        "max_input_time" => $attributes['max_input_time'],
                        "max_input_vars" => $attributes['max_input_vars'],
                        "memory_limit" => $attributes['memory_limit'],
                ));

            }
            else {
                // Update all settings for PHP older than 5.3:
                $cceClient->set($vsite['OID'], 'PHPVsite',
                    array(
                        "force_update" => time(),
                        "register_globals" => $attributes['register_globals'],
                        "safe_mode" => $attributes['safe_mode'],
                        "safe_mode_gid" => $attributes['safe_mode_gid'],
                        "safe_mode_include_dir" => $attributes['safe_mode_include_dir'],
                        "safe_mode_exec_dir" => $attributes['safe_mode_exec_dir'],
                        "safe_mode_allowed_env_vars" => $attributes['safe_mode_allowed_env_vars'],
                        "safe_mode_protected_env_vars" => $attributes['safe_mode_protected_env_vars'],
                        "open_basedir" => $attributes['open_basedir'],
                        "upload_max_filesize" => $attributes['upload_max_filesize'],
                        "post_max_size" => $attributes['post_max_size'],
                        "allow_url_fopen" => $attributes['allow_url_fopen'],
                        "allow_url_include" => $attributes['allow_url_include'],
                        "max_execution_time" => $attributes['max_execution_time'],
                        "max_input_time" => $attributes['max_input_time'],
                        "max_input_vars" => $attributes['max_input_vars'],
                        "memory_limit" => $attributes['memory_limit']
                ));
            }
            $errors = array_merge($errors, $cceClient->errors());

            // No errors during submit? Reload page:
            if (count($errors) == "0") {
                $cceClient->bye();
                $serverScriptHelper->destructor();
                $redirect_URL = "/vsite/vsitePHP?group=$group";
                header("location: $redirect_URL");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/vsite/vsitePHP?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteservices');
        $BxPage->setVerticalMenuChild('base_vsite_php');
        $page_module = 'base_sitemanage';

        $defaultPage = "defaultPage";
        $block =& $factory->getPagedBlock("php_vsite_head", array($defaultPage));
        $block->setLabel($factory->getLabel('php_vsite_head', false, array('vsite' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page.  Site admins
        // can view it for informational purposes.
        if ($Capabilities->getAllowed('manageSite')) {
            $is_site_admin = TRUE;
            $access = 'rw';
        }
        elseif (($Capabilities->getAllowed('siteAdmin')) && ($group == $Capabilities->loginUser['site'])) {
            $access = 'r';
            $is_site_admin = FALSE;
        }
        else {
            // Nice people say goodbye, or CCEd waits forever:
            $cceClient->bye();
            $serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#2");
        }

        if ($vsite_php["enabled"] == "0") {
            // Show error message box if PHP is not enabled for this vsite:
            $phpoff_statusbox = $factory->getTextField("phpVsiteNotEnabled", $i18n->get("phpVsiteNotEnabled"), 'r');
            $phpoff_statusbox->setLabelType("nolabel");
            $block->addFormField(
                $phpoff_statusbox,
                $factory->getLabel(" "),
                $defaultPage
                );
        }
        else {
            // Show settings:

            // Force Update of CODB:
            mt_srand((double)microtime() * 1000000);
            $zufall = mt_rand();
            $force_update_Field = $factory->getTextField("force_update", $zufall);
            $force_update_Field->setOptional ('silent');
            $block->addFormField(
                $force_update_Field,
                $factory->getLabel("force_update"),
                "hidden"
            );

            // PHP_version being used by this Vsite:
            $PHP_version_Field = $factory->getTextField("PHP_version", $usedPHPversion, "r");
            $PHP_version_Field->setOptional ('silent');
            $block->addFormField(
                $PHP_version_Field,
                $factory->getLabel("PHP_version_Field"),
                $defaultPage
            );

            // Register Globals:
            if ($systemObj["register_globals"] == 'Off') {
                $register_globals_choices=array("register_globals_no" => "Off", "register_globals_yes" => "On");
            }
            else {
                //Strict, but safe default:
                $register_globals_choices=array("register_globals_yes" => "On", "register_globals_no" => "Off");
            }

            // Register Globals Input:

            $register_globals_select = $factory->getMultiChoice("register_globals",array_values($register_globals_choices));
            $register_globals_select->setSelected($systemObj['register_globals'], true);
            $block->addFormField($register_globals_select,$factory->getLabel("register_globals"), $pageID54);


            // Safe Mode:
            if ($systemObj["safe_mode"] == 'Off') {
                    $safe_mode_choices=array("safe_mode_no" => "Off", "safe_mode_yes" => "On");
            }
            else {
                //Strict, but safe default:
                $safe_mode_choices=array("safe_mode_yes" => "On", "safe_mode_no" => "Off");
            }

            // Safe Mode Input:
            $safe_mode_select = $factory->getMultiChoice("safe_mode",array_values($safe_mode_choices));
            $safe_mode_select->setSelected($systemObj["safe_mode"], true);
            $block->addFormField($safe_mode_select,$factory->getLabel("safe_mode"), $pageID53);

            // safe_mode_gid = Off
            if ($systemObj["safe_mode_gid"] == 'On') {
                $safe_mode_gid_choices=array("safe_mode_gid_yes" => "On", "safe_mode_gid_no" => "Off");
            }
            else {
            //Safe default:
                $safe_mode_gid_choices=array("safe_mode_gid_no" => "Off", "safe_mode_gid_yes" => "On");
            }

            // Safe Mode GID Input:
            $safe_mode_gid_select = $factory->getMultiChoice("safe_mode_gid",array_values($safe_mode_gid_choices));
            $safe_mode_gid_select->setSelected($systemObj["safe_mode_gid"], true);
            $block->addFormField($safe_mode_gid_select,$factory->getLabel("safe_mode_gid"), $pageID53);

            // safe_mode_include_dir
            $safe_mode_include_dir = '&' . preg_replace('/,/', '&', $systemObj['safe_mode_include_dir']) . '&';
            $safe_mode_include_dir_Field = $factory->getTextBlock("safe_mode_include_dir", scalar_to_string($safe_mode_include_dir), $access);
            $safe_mode_include_dir_Field->setOptional ('silent');
            $block->addFormField(
                $safe_mode_include_dir_Field,
                $factory->getLabel("safe_mode_include_dir"),
                $pageID53
            );

            // safe_mode_exec_dir =
            $safe_mode_exec_dir = '&' . preg_replace('/,/', '&', $systemObj['safe_mode_exec_dir']) . '&';
            $safe_mode_exec_dir_Field = $factory->getTextBlock("safe_mode_exec_dir", scalar_to_string($safe_mode_exec_dir), $access);
            $safe_mode_exec_dir_Field->setOptional ('silent');
            $block->addFormField(
                $safe_mode_exec_dir_Field,
                $factory->getLabel("safe_mode_exec_dir"),
                $pageID53
            );

            // safe_mode_allowed_env_vars = PHP_
            $safe_mode_allowed_env_vars = '&' . preg_replace('/,/', '&', $systemObj['safe_mode_allowed_env_vars']) . '&';
            $safe_mode_allowed_env_vars_Field = $factory->getTextBlock("safe_mode_allowed_env_vars", scalar_to_string($safe_mode_allowed_env_vars), $access);
            $safe_mode_allowed_env_vars_Field->setOptional ('silent');
            $block->addFormField(
                $safe_mode_allowed_env_vars_Field,
                $factory->getLabel("safe_mode_allowed_env_vars"),
                $pageID53
            );

            // safe_mode_protected_env_vars = LD_LIBRARY_PATH
            $safe_mode_protected_env_vars = '&' . preg_replace('/,/', '&', $systemObj['safe_mode_protected_env_vars']) . '&';
            $safe_mode_protected_env_vars_Field = $factory->getTextBlock("safe_mode_protected_env_vars", scalar_to_string($safe_mode_protected_env_vars), $access);
            $safe_mode_protected_env_vars_Field->setOptional ('silent');
            $block->addFormField(
                $safe_mode_protected_env_vars_Field,
                $factory->getLabel("safe_mode_protected_env_vars"),
                $pageID53
            );

            // open_basedir
            /*

                OK, this gets a little complicated looking, so some explanations may be in order:

                The new handling for 'open_basedir' splits the form field into two separate text blocks. One is read only
                and contains the server wide PHP settings defined for 'open_basedir' plus the Vsite's root directory
                added to it. That is the read only one.

                The second one is editable and contains optional paths which a serverAdmin or siteAdmin may have chosen
                to add manually.

                To achieve this we read in the current server wide PHP settings to get our mandatory 'open_basedir' paths.
                We add the Vsite basedir to that and store this in one array. 

                THEN we read in the Vsite's 'open_basedir' settings and remove anything from it that starts with 
                '/home/.sites/' just to be really damn sure that no path to the wrong Vsite's root directory has been added.
                Anything that is not yet covered in the mandatory section is then pushed into a second array.

                Lastly we use array_diff() to compare both arrays and to extract anything that's different. This may be 
                redundant, but let us really be sure here. The differences are then stored in a third array, which we 
                present in the second editable 'open_basedir' text block.

                But take note here: This is just for showing off. In fact the only thing that vsite_phpHandler.php will really
                care about are the bits and pices from the user editeable form. The mandatory bits and pices and the Vsite
                root will be added by the underlying constructor. That way we remain compatible with CMU.

            */

            // Make sure our 'open_basedir' has the bare metal minimums in it as defined for this server:
            // We add the Vsite basedir to the 'open_basedir' settings defined for the server:
            $open_basedir_mandatory_pieces = explode (':', $system_php['open_basedir'] . ":" . $vsite['basedir']);

            // Now we walk through this Vsite's 'open_basedir' settings and remove all the bits and pieces
            // that we already have covered, so that only user added additions remain:
            $this_vsite_open_basedir = preg_split ("/:/", $systemObj['open_basedir']);
            $this_vsite_open_basedir_new = array();
            foreach ($this_vsite_open_basedir as $entry) {
                // Only push pieces if they're not already covered by mandatory entries and also don't push if path starts with '/home/.sites/':
                if ((!in_array($entry, $open_basedir_mandatory_pieces)) && (!preg_match("/\/home\/.sites\//i", $entry, $regs))) {
                array_push($this_vsite_open_basedir_new, $entry);
                }
            }

            // Now remove anything that we have already covered in the mandatory section. That leaves us with the user additions:
            $result = array_diff($this_vsite_open_basedir_new, $open_basedir_mandatory_pieces);
            $open_basedir_additions = $result;

            // Sort the arrays before we implode them later on:
            array_multisort($open_basedir_mandatory_pieces, SORT_ASC);
            array_multisort($open_basedir_additions, SORT_ASC);

            // Print out the block with the mandatory 'open_basedir' stuff. Please note: This is for display only. The contends here cannot
            // be processed via form handlers:
            $open_basedir_mandatory_Field = $factory->getTextBlock("open_basedir_mandatory", implode("\n",$open_basedir_mandatory_pieces), 'r');
            $open_basedir_mandatory_Field->setOptional ('silent');
            $block->addFormField(
                $open_basedir_mandatory_Field,
                $factory->getLabel("open_basedir_mandatory"),
                $defaultPage
            );

            // Print out a hidden block with the same mandatory 'open_basedir' stuff in it, but hide it from view. This can be processed
            // as form data:
            $open_basedir_mandatory_hidden_Field = $factory->getTextField("open_basedir_mandatory_hidden", implode(":",$open_basedir_mandatory_pieces), '');
            $open_basedir_mandatory_hidden_Field->setOptional ('silent');
            $block->addFormField(
                $open_basedir_mandatory_hidden_Field,
                $factory->getLabel("open_basedir_mandatory_hidden"),
                $defaultPage
            );

            // Print out the block with the custom additions for 'open_basedir':
            $open_basedir_Field = $factory->getTextBlock("open_basedir", implode("\n",$open_basedir_additions), $access);
            $open_basedir_Field->setOptional ('silent');
            $block->addFormField(
                $open_basedir_Field,
                $factory->getLabel("open_basedir"),
                $defaultPage
            );

            // allow_url_fopen:
            if ($systemObj["allow_url_fopen"] == 'On') {
                $allow_url_fopen_choices=array("allow_url_fopen_yes" => "On", "allow_url_fopen_no" => "Off");
            }
            else {
                    //Strict, but safe default:
                $allow_url_fopen_choices=array("allow_url_fopen_no" => "Off", "allow_url_fopen_yes" => "On");
            }

            // allow_url_fopen Input:
            $allow_url_fopen_select = $factory->getMultiChoice("allow_url_fopen",array_values($allow_url_fopen_choices));
            $allow_url_fopen_select->setSelected($systemObj['allow_url_fopen'], true);
            $block->addFormField($allow_url_fopen_select,$factory->getLabel("allow_url_fopen"), $defaultPage);

            // allow_url_include:
            if ($systemObj["allow_url_include"] == 'On') {
                $allow_url_include_choices=array("allow_url_include_yes" => "On", "allow_url_include_no" => "Off");
            }
            else {
                    //Strict, but safe default:
                $allow_url_include_choices=array("allow_url_include_no" => "Off", "allow_url_include_yes" => "On");
            }

            // allow_url_include Input:
            $allow_url_include_select = $factory->getMultiChoice("allow_url_include",array_values($allow_url_include_choices));
            $allow_url_include_select->setSelected($systemObj['allow_url_include'], true);
            $block->addFormField($allow_url_include_select,$factory->getLabel("allow_url_include"), $defaultPage);

            // upload_max_filesize:
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

            if ($systemObj['upload_max_filesize']) {
                // If we're currently using something that's not in that array, we add it to it:
                if (!in_array($systemObj['upload_max_filesize'], $upload_max_filesize_choices)) {
                        $upload_max_filesize_choices[] = $systemObj['upload_max_filesize'];
                }
                sort($upload_max_filesize_choices, SORT_NUMERIC);
            }

            // upload_max_filesize Input:
            $upload_max_filesize_choices_select = $factory->getMultiChoice("upload_max_filesize",array_values($upload_max_filesize_choices));
            $upload_max_filesize_choices_select->setSelected($systemObj['upload_max_filesize'], true);
            $block->addFormField($upload_max_filesize_choices_select,$factory->getLabel("upload_max_filesize"), $defaultPage);

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

            if ($systemObj['post_max_size']) {
                // If we're currently using something that's not in that array, we add it to it:
                if (!in_array($systemObj['post_max_size'], $post_max_size_choices)) {
                        $post_max_size_choices[] = $systemObj['post_max_size'];
                }
                sort($post_max_size_choices, SORT_NUMERIC);
            }

            // post_max_size Input:
            $post_max_size_choices_select = $factory->getMultiChoice("post_max_size",array_values($post_max_size_choices));
            $post_max_size_choices_select->setSelected($systemObj['post_max_size'], true);
            $block->addFormField($post_max_size_choices_select,$factory->getLabel("post_max_size"), $defaultPage);

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

            if ($systemObj['max_execution_time']) {
                // If we're currently using something that's not in that array, we add it to it:
                if (!in_array($systemObj['max_execution_time'], $max_execution_time_choices)) {
                        $max_execution_time_choices[] = $systemObj['max_execution_time'];
                }
                sort($max_execution_time_choices, SORT_NUMERIC);
            }

            // max_execution_time Input:
            $max_execution_time_choices_select = $factory->getMultiChoice("max_execution_time",array_values($max_execution_time_choices));
            $max_execution_time_choices_select->setSelected($systemObj['max_execution_time'], true);
            $block->addFormField($max_execution_time_choices_select,$factory->getLabel("max_execution_time"), $defaultPage);

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

            if ($systemObj['max_input_time']) {
                // If we're currently using something that's not in that array, we add it to it:
                if (!in_array($systemObj['max_input_time'], $max_input_time_choices)) {
                        $max_input_time_choices[] = $systemObj['max_input_time'];
                }
                sort($max_input_time_choices, SORT_NUMERIC);
            }

            // max_input_time Input:
            $max_input_time_choices_select = $factory->getMultiChoice("max_input_time",array_values($max_input_time_choices));
            $max_input_time_choices_select->setSelected($systemObj['max_input_time'], true);
            $block->addFormField($max_input_time_choices_select,$factory->getLabel("max_input_time"), $defaultPage);

            // max_input_vars:
            if (!isset($systemObj["max_input_vars"])) { 
                $systemObj["max_input_vars"] = '1000'; 
            }
            $max_input_vars_Field = $factory->getInteger("max_input_vars", $systemObj["max_input_vars"], "-1", "65535");
            $max_input_vars_Field->setWidth(5);
            $max_input_vars_Field->showBounds(1);
            $block->addFormField(
                $max_input_vars_Field,
                $factory->getLabel("max_input_vars"),
                $defaultPage
            );

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

            if ($systemObj['memory_limit']) {
                // If we're currently using something that's not in that array, we add it to it:
                if (!in_array($systemObj['memory_limit'], $memory_limit_choices)) {
                        $memory_limit_choices[] = $systemObj['memory_limit'];
                }
                sort($memory_limit_choices, SORT_NUMERIC);
            }

            // memory_limit Input:
            $memory_limit_choices_select = $factory->getMultiChoice("memory_limit",array_values($memory_limit_choices));
            $memory_limit_choices_select->setSelected($systemObj['memory_limit'], true);
            $block->addFormField($memory_limit_choices_select,$factory->getLabel("memory_limit"), $defaultPage);

        }

        // Add the buttons for those who can edit this page:
        if (($access == 'rw') && ($vsite_php["enabled"] != "0")) {
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/vsite/vsitePHP?group=$group"));
        }

        // Nice people say goodbye, or CCEd waits forever:
        $cceClient->bye();
        $serverScriptHelper->destructor();

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