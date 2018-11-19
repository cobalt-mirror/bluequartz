<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class DockerCompose extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for the docker compose.
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

        $cookie = array('name' => 'docker', 'path' => '/', 'value' => $CI->BX_SESSION['sessionId'], 'expire' => '0');
        $this->input->set_cookie($cookie);

        // Line up the ducks for CCE-Connection:
        include_once('ServerScriptHelper.php');
        $CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
        $CI->cceClient = $CI->serverScriptHelper->getCceClient();
        $user = $CI->BX_SESSION['loginUser'];
        $i18n = new I18n("base-docker", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $systemDocker = $CI->cceClient->get($system['OID'], "Docker");

        // Initialize Capabilities so that we can poll the access rights as well:
        $Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        // No 'serverNetwork' access? Bye, bye!
        if (!$Capabilities->getAllowed('serverNetwork')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        // Get URL strings:
        $get_form_data = $CI->input->get(NULL, TRUE);

        // Get a list of 'DockerCompose' OID's:
        $ComposerListOIDs = $CI->cceClient->findx('DockerCompose', array(), array(), "", "");
        $ComposerList = array();
        $NameList = array();
        foreach ($ComposerListOIDs as $OID) {
            $entry = $CI->cceClient->get($OID);
            if ((isset($entry['name'])) && (isset($entry['file'])) && (isset($entry['dir']))) {
                $ComposerList[$entry['name']] = array('name' => $entry['name'] , 'file' => $entry['file'], 'dir' => $entry['dir']);
                $NameList[] = $entry['name'];
            }
        }

        $action = '';
        $TARGET = '';
        if (isset($get_form_data['action'])) {
            $action = urldecode($get_form_data['action']);
            $TARGET = urldecode($get_form_data['name']);
            if ((!in_array($action, array('0' => 'build', '1' => 'up', '2' => 'down', '3' => 'restart', '4' => 'delCTs', '5' => 'remove'))) || (!in_array($TARGET, $NameList))) {
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();
                Log403Error("/gui/Forbidden403");
            }
        }

        if (isset($get_form_data['add'])) {
            $action = 'add';
        }

        $my_errors = array();
        if (isset($get_form_data['error'])) {
            $my_errors[] = ErrorMessage($i18n->get("[[base-docker.ErrorImageDelete]]"));
        }

        // Required array setup:
        $extra_headers = array();

        //
        //--- DockerLibs integration:
        //

        include_once('/usr/sausalito/ui/chorizo/ci/application/modules/base/docker/controllers/DockerLibs.php');
        $DockerLibs = new DockerLibs($CI->serverScriptHelper, $CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $ci_errors = array();

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
            $attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes);
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

            // Check if Name already exists:
            $cpOID = $CI->cceClient->find('DockerCompose', array('name' => $attributes['CNAME'],));
            if (isset($cpOID[0])) {
                // Already got that name in CODB:
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.DockerComposeExists]]"));
            }

            // Check if directory is on /home:
            $checkDir = $attributes['DIR'];
            if (!preg_match('|^/home/(.*)/$|', $checkDir)) {
                // Directory path doesn't start with /home and doesn't end with a slash:
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.Docker_CPdir_notHome]]"));
            }

            // Check if directory exists:
            $is_there = '';
            $ret = $CI->serverScriptHelper->shell("/bin/ls --directory $checkDir", $is_there, 'root', $CI->BX_SESSION['sessionId']);
            if (preg_match("|^$checkDir$|", $is_there)) {
                # exists
            }
            else {
                // Directory isn't there:
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.Docker_CPdir_notThere]]"));
            }

            // Check if dir is part of composefile-path:
            if (!preg_match("|^$checkDir(.*)|", $attributes['FILE'])) {
                // Path of directory is not part of the dir of the yml file:
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.Docker_CPdir_notPartOfFilePath]]"));
            }

            // Check if the bloody file exists:
            $checkFile = $attributes['FILE'];
            $is_there = '';
            $ret = $CI->serverScriptHelper->shell("/bin/ls --directory $checkFile", $is_there, 'root', $CI->BX_SESSION['sessionId']);
            if (preg_match("|^$checkFile$|", $is_there)) {
                # exists
            }
            else {
                // yml file doesn't exist
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.Docker_CP_file_notThere]]"));
            }

            if (!preg_match('|^/home/(.*)\.yml$|', $checkFile)) {
                // yml file doesn't have yml extension:
                $my_errors[] = ErrorMessage($i18n->get("[[base-docker.Docker_CP_file_not_yml_extension]]"));
            }

        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {
            $systemDocker = $CI->cceClient->get($system['OID'], "Docker");
                $newcpOID = $CI->cceClient->create("DockerCompose", 
                                                    array(
                                                        'name' => $attributes['CNAME'],
                                                        'dir' => $attributes['DIR'],
                                                        'file' => $attributes['FILE'],
                                                        'action' => time()
                                                    )
                                                );

        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-docker", "/docker/dockerCompose");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        // Set Menu items:
        $BxPage->setVerticalMenu('base_sysmanage');
        $page_module = 'base_sysmanage';
        if ($system['productBuild'] == "6109R") {
            $BxPage->setVerticalMenu('base_sitemanageVSL');
            $page_module = 'base_sitemanageVSL';
        }
        $BxPage->setVerticalMenuChild('docker_Compose');
        $defaultPage = "basicSettingsTab";

        $block =& $factory->getPagedBlock("DockerCompose", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setDefaultPage($defaultPage);

        if ($systemDocker['enabled'] == "0") {
                $disabled_TEXT = "<div class='flat_area grid_16'><br>" . $i18n->getClean("[[base-docker.enabledField_help]]") . "</div>";
                $disabledtext = $factory->getHtmlField("admin_text", $disabled_TEXT, 'r');
                $disabledtext->setLabelType("nolabel");
                $block->addFormField(
                  $disabledtext,
                  $factory->getLabel(" ", false),
                  $defaultPage
                );
        }

        if ($action == '') {

            //
            //--- Show list:
            //

            $scrollList = $factory->getScrollList("DockerCompose", array("CNAME", "FILE", "Action"), array()); 

            $scrollList->setAlignments(array("left", "left", "center"));
            $scrollList->setDefaultSortedIndex('0');
            $scrollList->setSortOrder('ascending');
            $scrollList->setSortDisabled(array('2'));
            $scrollList->setPaginateDisabled(FALSE);
            $scrollList->setSearchDisabled(FALSE);
            $scrollList->setSelectorDisabled(FALSE);
            $scrollList->enableAutoWidth(FALSE);
            $scrollList->setInfoDisabled(FALSE);
            $scrollList->setColumnWidths(array("200", "494", "45")); // Max: 739px

            $v = '0';
            foreach ($ComposerList as $ctline => $value) {

                // Add Buttons:
                $buttons = '';
                $buttons = ' <button title="' . $i18n->getWrapped("composeBUILD") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=build&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-wrench"></div></button>';
                $buttons .= ' <button title="' . $i18n->getWrapped("composeUP") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=up&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-arrowthick-1-n"></div></button>';
                $buttons .= ' <button title="' . $i18n->getWrapped("composeDOWN") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=down&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-arrowthick-1-s"></div></button>';
                $buttons .= ' <button title="' . $i18n->getWrapped("composeRESTART") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=restart&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-arrowrefresh-1-e"></div></button>';
                $buttons .= ' <button title="' . $i18n->getWrapped("composeDELCTS") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=delCTs&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-closethick"></div></button>';
                $buttons .= ' <button title="' . $i18n->getWrapped("composeDELALL") . '" class="tiny icon_only div_icon tooltip hover right link_button" data-link="/docker/dockerCompose?action=remove&name=' . urlencode($ComposerList[$ctline]['name']) . '" target="_self" formtarget="_self"><div class="ui-icon ui-icon-trash"></div></button>';

                // Assemble the ScrollList-Entries:
                $scrollList->addEntry(
                        array(
                                $ComposerList[$ctline]['name'],
                                $ComposerList[$ctline]['file'],
                                $buttons
                        ),
                        '', false, $v);
                $v++;
            }

            // Generate +Add button:
            $addbutton = $factory->getAddButton("/docker/dockerCompose?add=true");
            $buttonContainer = $factory->getButtonContainer("DockerCompose", $addbutton);
            $block->addFormField(
                $buttonContainer,
                $factory->getLabel("dockerComposeAdd"),
                $defaultPage
            );

            // Push out the Scrollist:
            $block->addFormField(
                $factory->getRawHTML("virtualServerList", $scrollList->toHtml()),
                $factory->getLabel("virtualServerList"),
                $defaultPage
            );

        }
        elseif (($action == 'build') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' build';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $action = 'done';
        }
        elseif (($action == 'up') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' up -d';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $action = 'done';
        }
        elseif (($action == 'down') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' down';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $action = 'done';
        }
        elseif (($action == 'restart') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' restart';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $action = 'done';
        }
        elseif (($action == 'delCTs') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' kill';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' stop';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $cmd = '/usr/local/bin/docker-compose-wrapper --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' rm';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $action = 'done';
        }
        elseif (($action == 'remove') && (preg_match('|^/home/(.*)$|', $ComposerList[$TARGET]['dir'])) && (preg_match('|^/home/(.*)\.yml$|', $ComposerList[$TARGET]['file']))) {
            $Composer_raw = '';
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' kill';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $cmd = '/usr/local/bin/docker-compose --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' stop';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $cmd = '/usr/local/bin/docker-compose-wrapper --file ' . $ComposerList[$TARGET]['file'] . ' --project-directory ' . $ComposerList[$TARGET]['dir'] . ' rm';
            $ret = $this->serverScriptHelper->shell("$cmd", $Composer_raw, 'root', $CI->BX_SESSION['sessionId']);
            $cpOID = $CI->cceClient->find('DockerCompose', array('name' => $TARGET, 'file' => $ComposerList[$TARGET]['file']));
            if (isset($cpOID[0])) {
                $ok = $CI->cceClient->destroy($cpOID[0]);
            }
            $action = 'done';
        }
        else {

            //
            //--- Add
            //

            // Name:
            $NAME = $factory->getTextField("CNAME", "", "rw");
            $NAME->setType('alphanum_plus');
            $NAME->setOptional(false);
            $block->addFormField(
                    $NAME,
                    $factory->getLabel('CNAME'),
                    $defaultPage
                    );

            // Dir:
            $DIR = $factory->getTextField("DIR", "", "rw");
            $DIR->setType('');
            $DIR->setOptional(false);
            $block->addFormField(
                    $DIR,
                    $factory->getLabel('DIR'),
                    $defaultPage
                    );

            // File:
            $FILE = $factory->getTextField("FILE", "", "rw");
            $FILE->setType('');
            $FILE->setOptional(false);
            $block->addFormField(
                    $FILE,
                    $factory->getLabel('FILE'),
                    $defaultPage
                    );

            // Add the buttons
            $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
            $block->addButton($factory->getCancelButton("/docker/dockerCompose"));
        }

        if ($action == 'done') {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            header("Location: /docker/dockerCompose");
            exit;
        }

        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->render($page_module, $page_body);

    }       
}
/*
Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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