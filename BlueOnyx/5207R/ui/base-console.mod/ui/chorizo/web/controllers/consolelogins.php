<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Consolelogins extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /console/consolelogins.
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

        $i18n = new I18n("base-console", $CI->BX_SESSION['loginUser']['localePreference']);
        $system = $CI->getSystem();
        $user = $CI->BX_SESSION['loginUser'];

        // Not 'serverConfig'? Bye, bye!
        if (!$CI->serverScriptHelper->getAllowed('serverConfig')) {
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403");
        }

        //
        //--- Get CODB-Object of interest updated: 
        //

        $ourOID = $CI->cceClient->find("SOL_Console");
        $CI->cceClient->set($ourOID[0], "", array('gui_list_lasttrigger' => time()));
        $errors = $CI->cceClient->errors();

        //
        //--- Get CODB-Object of interest loaded: 
        //

        $CODBDATA = $CI->cceClient->get($ourOID[0]);

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

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Check if we have everything:
        if (((isset($get_form_data['console'])) && ($get_form_data['console'] != "")) && 
            ((isset($get_form_data['username'])) && ($get_form_data['username'] != "")) && 
            ((isset($get_form_data['pid'])) && ($get_form_data['pid'] != ""))) { 

            $user_kill_action = array(
                "user_kill_console" => urldecode($get_form_data['console']),
                "user_kill_user" => $get_form_data['username'],
                "user_kill_pid" => $get_form_data['pid'],
                "user_kill_trigger" => time()
              );

            // Actual submit to CODB:
            $CI->cceClient->setObject("SOL_Console", $user_kill_action);        

            // CCE errors that might have happened during submit to CODB:
            $CCEerrors = $CI->cceClient->errors();
            foreach ($CCEerrors as $object => $objData) {
                // When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
                $errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
            }
            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                header("Location: /console/consolelogins");
                exit;
            }
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        //
        //-- Page Logic:
        //

        $iam = '/console/consolelogins';

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-console", "/console/consolelogins");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_security');
        $BxPage->setVerticalMenuChild('base_console_logins');
        $page_module = 'base_sysmanage';

        $defaultPage = "basic";

        $block =& $factory->getPagedBlock("vserver_loginlist", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
//      $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- Basic Tab
        //

        $ScrollList = $factory->getScrollList("vserver_loginlist", array(" ", "LUSER", "CONSOLE", "HOST", "START_DATE", "STIME", "ETIME", "DURATION", "UKILL"), array());
        $ScrollList->setAlignments(array("left", "left", "left", "left", "left", "left", "left", "left", "center"));
        $ScrollList->setDefaultSortedIndex('0');
        $ScrollList->setSortOrder('ascending');
        $ScrollList->setSortDisabled(array('8'));
        $ScrollList->setPaginateDisabled(FALSE);
        $ScrollList->setSearchDisabled(FALSE);
        $ScrollList->setSelectorDisabled(FALSE);
        $ScrollList->enableAutoWidth(TRUE);
        $ScrollList->setInfoDisabled(FALSE);
        $ScrollList->setColumnWidths(array("10", "20", "20", "100", "250", "50", "50", "100", "100")); // Max: 739px

        // Populate table rows with the data:

        // USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

        // Explode entire strings into separate lines:
        $pieces = explode("#DELI#", $CODBDATA['sol_logins']);

        // How many entries are in $pieces?
        $ps_lines = 0;
        $ps_lines = count($pieces);
        $ps_a = "0";
        $ps_b = "1";

        foreach ($pieces as $line) {
            if (($ps_a > 0) && (count_chars($line) > 3)) {

                $action = $factory->getCompositeFormField();

                // Split down each line into the bits and pieces we need:
                $login = rtrim(substr($line, "0", "9"));
                $console = rtrim(substr($line, "9", "13"));
                $host = rtrim(substr($line, "22", "17"));
                $startdate = rtrim(substr($line, "39", "11"));
                $starttime = rtrim(substr($line, "50", "5"));
                $endtime = rtrim(substr($line, "58", "5"));
                $duration = rtrim(substr($line, "64", "75"));

                if (($CI->serverScriptHelper->getAllowed('adminUser')) && ($endtime == "still")) {
                    if (preg_match("/ftpd/i", $console)) {
                        $killer = "ftpd";
                        $ftpd_pid = rtrim(substr($console, "4", "6"));
                    }
                else {
                        $killer = urlencode($console);
                        $ftpd_pid = "0";
                    }

                    $remove_button = $factory->getRemoveButton("$iam?console=" . urlencode($killer) . "&username=$login&pid=$ftpd_pid");
                    $remove_button->setImageOnly(TRUE);
                    $action->addFormField($remove_button);
                }

                if (preg_match("/wtmp begins/i", $line)) {
                    $header = rtrim(substr($line, "0", "42"));
                }
                elseif (!$login) {
                }
                else { 
                    // Populate Scrollist
                    $ScrollList->addEntry(array(
                                $ps_a,
                                $login,
                                $console,
                                $host,
                                $startdate,
                                $starttime,
                                $endtime,
                                $duration,
                                $action
                    ));
                    $ps_b++;
                }
            }
            $ps_a++;
        }

        $block->addFormField(
            $factory->getRawHTML("filler", "&nbsp;"),
            $factory->getLabel(" "),
            $defaultPage
        );

        $block->addFormField(
            $factory->getRawHTML("filler", "&nbsp;" . $header),
            $factory->getLabel(" "),
            $defaultPage
        );

        // Commit-Integer: We need at least one form field to be able to submit data.
        // So we use this hidden one:
        $block->addFormField(
            $factory->getTextField('commit', time(), ''),
            $factory->getLabel("commit"), 
            $defaultPage
        );  

        // Show the ScrollList of Logins:
        $block->addFormField(
            $factory->getRawHTML("vserver_loginlist", $ScrollList->toHtml()),
            $factory->getLabel("vserver_loginlist"),
            $defaultPage
        );

        // Add the buttons
        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/console/consolelogins"));

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