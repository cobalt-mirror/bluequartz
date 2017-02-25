<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WarAdd extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /java/warAdd.
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

        // Adds settings to avoid changing php.ini
        ini_set('memory_limit', '256M');
        ini_set('post_max_size ', '200M');
        ini_set('upload_max_filesize', '200M');
        ini_set('max_execution_time', '0');
        ini_set('max_input_time', '0');

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-java", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // -- Actual page logic start:

        // Get URL params:
        $get_form_data = $CI->input->get(NULL, TRUE);
        $post_form_data = $CI->input->post(NULL, TRUE);

        // Get the backUrl:
        if (isset($get_form_data['backUrl'])) {
            // URL string:
            $backUrl = $get_form_data['backUrl'];
        }
        elseif (isset($post_form_data['backUrl'])) {
            // Alternatively POST value:
            $backUrl = $post_form_data['backUrl'];
        }
        else {
            // Nothing? Then it's empty:
            $backUrl = "";
        }

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
        $vsiteJava = $CI->cceClient->getObject('Vsite', array('name' => $group), "Java");
        $dirName = $vsite['basedir'] . "/web";

        if ($vsiteJava['enabled'] != "1") {
            // Java is not enabled! We're going home!
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();

            // Back to the main page:
            header("Location: /java/warList?group=" . $group);
            exit;
        }

        // Declare some constants:
        $prepare_cmd = "/usr/sausalito/sbin/java_load_war.pl";
        $packageDir = "/home/tmp";
        $pageUrl = "/java/warAdd.php?group=$group&backUrl=$backUrl";

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
        $required_keys = array("hostname", "rootpath", 'webdir');

        // Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

        // Empty array for key => values we want to submit to CCE:
        $attributes = array();
        // Items we do NOT want to submit to CCE:
        $ignore_attributes = array("");
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
            // Make sure $backUrl isn't empty or someone messed with it:
            if (!preg_match('/^\/java\//', $backUrl)) {
                // Don't play games with us!
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403#0");
            }

            $runas = ($Capabilities->getAllowed('adminUser') ? 'root' : $CI->BX_SESSION['loginName']);

            // Filter bash manipulative characters
            $targetField = preg_replace('/[\n\r\;\"]/', "", $form_data['targetField']);

            // TargetField can not start with tilde:
            if(ord($targetField) == 0x7e) {
                $my_errors[] = ErrorMessage($i18n->get("[[base-java.notToUserHome]]") . '<br>&nbsp;');
            }

            // Check which install method was selected:
            if (($form_data['locationField'] == 'urlField') && (count($my_errors) == "0")) {

                //
                //-- Install from URL:
                //

                // Check if URL appears to be valid:
                if (substr($form_data['urlField'], 0, 8) != "https://" && substr($form_data['urlField'], 0, 7) != "http://" && substr($form_data['urlField'], 0, 6) != "ftp://") {
                    $my_errors[] = ErrorMessage($i18n->get("[[base-java.invalidUrl]]") . '<br>&nbsp;');
                }
                else {
                    // We seem to have a valid URL. Package name is the last piece of the URL:
                    $names = explode("/", $form_data['urlField']);
                    $nameField = $names[count($names)-1];

                    // Install:
                    $urlField = $form_data['urlField'];

                    // Check if we have a valid URL. Because someone could call this with ...
                    // http://www.smd.net/1.pkg";touch "/tmp/yougot0wned;chmod 755 /tmp/yougot0wned;/bin/sh /tmp/yougot0wned
                    // ... and we'd execute that right on the shell as 'admserv'. Sure, that's like user 'admin'
                    // rooting the box that he has already 'root' access for. But no excuses here. Better safe
                    // than sorry. Note to self: This check requires PHP-5.2 or better.
                    $ret = -1;
                    if (filter_var($urlField, FILTER_VALIDATE_URL)) {
                        $ret = $CI->serverScriptHelper->shell("$prepare_cmd -n $CI->BX_SESSION['loginName'] -g $group -u \"$urlField\" -t \"$targetField\" -c", $output, $runas, $CI->BX_SESSION['sessionId']);
                    }

                    if ($ret != 0) {
                        // Deal with errors:
                        $ci_errors[] = new CceError('huh', 0, 'urlField', "[[base-java.badPackage]]");
                    }
                    else {
                        // If the 'prepare_cmd' was sucessful, we now have the *.war listed in CODB:
                        $WarCry = $CI->cceClient->getObject("JavaWar", array('group' => $group, 'name' => $targetField), "");
                        if (!isset($WarCry['name'])) {
                            // Install failed. Roll up error message and let the user try again:
                            $my_errors[] = ErrorMessage($i18n->get("[[base-java.installFailure]]") . '<br>&nbsp;');
                        }
                        else {
                            // Install went fine. We're going home.
                            // Nice people say goodbye, or CCEd waits forever:
                            $CI->cceClient->bye();
                            $CI->serverScriptHelper->destructor();

                            // Back to the main page:
                            header("Location: /java/warList?group=" . $group);
                            exit;
                        }
                    }
                }
            }
            elseif ($form_data['locationField'] == 'fileField') {

                //
                //-- Install from uploaded file:
                //

                $config['upload_path'] = '/tmp/';
                $config['allowed_types'] = '*'; // Can't set this to 'war', as we have no MIME-type for it!
                $config['encrypt_name'] = TRUE;
                $config['remove_spaces'] = TRUE;
                $this->load->library('upload', $config);
                $this->upload->do_upload("fileField");

                // Get the full path and encrypted/randomized file name:
                $data = $this->upload->data();
                $nameField = $data['client_name'];

                if (!is_file($data['full_path'])) {
                    // file opening problems
                    $ci_errors[] = new CceError('huh', 0, 'cert', "[[base-java.unknownFileFormat]]");
                }
                else {
                    $tmp_pkg = $data['full_path'];

                    // Install uploaded WAR:
                    $ret = $CI->serverScriptHelper->shell("$prepare_cmd -n $CI->BX_SESSION['loginName'] -g $group -f $tmp_pkg -t \"$targetField\" -c", $output, $runas, $CI->BX_SESSION['sessionId']);

                    if ($ret != 0) {
                        // Deal with errors:
                        $ci_errors[] = new CceError('huh', 0, 'urlField', "[[base-java.badPackage]]");
                        if (is_file($tmp_pkg)) {
                            unlink($tmp_pkg);
                        }
                    }
                    else {
                        // If the 'prepare_cmd' was sucessful, we now have the *.war listed in CODB:
                        $WarCry = $CI->cceClient->getObject("JavaWar", array('group' => $group, 'name' => $targetField), "");
                        if (!isset($WarCry['name'])) {
                            // Install failed. Roll up error message and let the user try again:
                            $my_errors[] = ErrorMessage($i18n->get("[[base-java.installFailure]]") . '<br>&nbsp;');
                        }
                        else {
                            // Install went fine. We're going home.
                            // Nice people say goodbye, or CCEd waits forever:
                            $CI->cceClient->bye();
                            $CI->serverScriptHelper->destructor();

                            // Back to the main page:
                            header("Location: /java/warList?group=" . $group);
                            exit;
                        }
                    }
                }
            }
            elseif ($form_data['locationField'] == 'loaded') {

                //
                //-- Install from /web of that Vsite:
                //

                $WarPath = $vsite['basedir'].'/web/'.$form_data['loaded'];

                if (!is_file($WarPath)) {
                    // Don't play games with us!
                    // Nice people say goodbye, or CCEd waits forever:
                    $CI->cceClient->bye();
                    $CI->serverScriptHelper->destructor();
                    Log403Error("/gui/Forbidden403#0a");
                }

                // Install uploaded WAR:
                $ret = $CI->serverScriptHelper->shell("$prepare_cmd -n $CI->BX_SESSION['loginName'] -g $group -f $WarPath -t \"$targetField\" -c", $output, $runas, $CI->BX_SESSION['sessionId']);
                if ($ret != 0) {
                    // Deal with errors:
                    $ci_errors[] = new CceError('huh', 0, 'urlField', "[[base-java.badPackage]]");
                    if (is_file($tmp_pkg)) {
                        unlink($tmp_pkg);
                    }
                }
                else {
                    // If the 'prepare_cmd' was sucessful, we now have the *.war listed in CODB:
                    $WarCry = $CI->cceClient->getObject("JavaWar", array('group' => $group, 'name' => $targetField), "");
                    if (!isset($WarCry['name'])) {
                        // Install failed. Roll up error message and let the user try again:
                        $my_errors[] = ErrorMessage($i18n->get("[[base-java.installFailure]]") . '<br>&nbsp;');
                    }
                    else {
                        // Install went fine. We're going home.
                        // Nice people say goodbye, or CCEd waits forever:
                        $CI->cceClient->bye();
                        $CI->serverScriptHelper->destructor();

                        // Back to the main page:
                        header("Location: /java/warList?group=" . $group);
                        exit;
                    }
                }
            }
            else {
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();

                // Wow. No method selected. Reload page and try that again:
                header("Location: /swupdate/manualInstall?backUrl=$backUrl");
                exit;
            }
        }

        //
        //-- Generate page:
        //

        // Determine current user's access rights to view or edit information
        // here.  Only 'manageSite' can modify things on this page. 
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
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#3");
        }

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-java", "/java/warAdd?group=$group");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_siteadmin');
        $BxPage->setVerticalMenuChild('base_java_apps');
        $page_module = 'base_sitemanage';

        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("addWar", array($defaultPage));
        $block->setLabel($factory->getLabel('addWar', false, array('item' => $vsite['fqdn'])));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        // Set up MultiChoice:
        $location = $factory->getMultiChoice("locationFieldEnter");

        // Add URL option:
        $url = $factory->getOption("url", true);
        $urlFieldx = $factory->getTextField("urlField");
        $urlFieldx->setOptional(TRUE);
        $urlFieldx->setType("");
        $url->addFormField($urlFieldx);
        $location->addOption($url);

        // Add Upload option:
        $upload = $factory->getOption("upload");
        $upload->addFormField($factory->getFileUpload("fileField", ""), $defaultPage);
        $location->addOption($upload);

        // add .war's as an option if they exist
        $magic_cmd = "/usr/bin/file";
        $wars = array();
        if(is_dir($dirName)) {
            $dir = opendir($dirName);
            while($file = readdir($dir)) {
                if ($file[0] == '.') {
                    continue;
                }
                $output = `$magic_cmd $dirName/$file 2>&1`;
                if (preg_match("/Zip archive data/", $output)) {
                        $wars[] = $file;
                }
            }
            closedir($dir);
        }
        if(count($wars) > 0) {
          $loaded = $factory->getOption("loaded");
          $loaded->addFormField($factory->getMultiChoice("loaded", $wars));
          $location->addOption($loaded);
        }

        // Push out the MultiChoice:
        $block->addFormField(
            $location,
            $factory->getLabel("locationFieldEnter"),
            $defaultPage
        );

        // Add composite form field for where we want to deploy:
        $fqdn_field = $factory->getTextField("targetName", 'http://'.$vsite['fqdn'].'/', 'r'); 
        $fqdn_field->setType("");
        $fqdn_field->setLabelType("nolabel no_lines");

        $wardeploy_field = $factory->getTextField("targetField", "", 'rw');
        $wardeploy_field->setType("alphanum_plus");
        $wardeploy_field->setLabelType("nolabel no_lines");

        $target =& $factory->getCompositeFormField(array($factory->getLabel("targetName"), $fqdn_field, $wardeploy_field), '');
        $target->setColumnWidths(array('col_25', 'col_25', 'col_50'));

        $block->addFormField(
                $target,
                $factory->getLabel("targetName"),
                $defaultPage
                );

        // Submit backUrl as well:
        $block->addFormField(
            $factory->getTextField("backUrl", "/java/warList?group=$group", ""), 
            $defaultPage
        );

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/java/warList?group=$group"));

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