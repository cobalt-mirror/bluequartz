<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ablstatus extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /console/ablstatus.
     *
     */

    public function index() {

        $CI =& get_instance();

        // We load the BlueOnyx helper library first of all, as we heavily depend on it:
        $this->load->helper('blueonyx');
        init_libraries();

        // Need to load 'BxPage' for page rendering:
        $this->load->library('BxPage');

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
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
        $ignore_attributes = array("BlueOnyx_Info_Text", "_");
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
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

        // If we have no errors and have POST data, we submit to CODB:
        if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

            // No errors. Reload the entire page to load it with the updated values:
            if ((count($errors) == "0")) {
                // Nice people say goodbye, or CCEd waits forever:
                $CI->cceClient->bye();
                $CI->serverScriptHelper->destructor();              
                header("Location: /console/ablstatus");
                exit;
            }
        }

        //
        //-- Own page logic:
        //

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Other (button initiated) action taking place:
        if (isset($get_form_data['action'])) {
            // Remove the pam_abl databases altogether:
            $runas = 'root';
            $ret = $CI->serverScriptHelper->shell("/bin/rm -f /var/lib/pam_abl/*", $nfk, $runas, $CI->BX_SESSION['sessionId']);
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            header("Location: /console/ablstatus");
            exit;
        }

        // Unblocking action taking place on a selected IP via ScrollList Unblock button:
        if (isset($get_form_data['host'])) {
            if (isset($get_form_data['host'])) {
                // Make sure that what we got is really an IP:
                if (filter_var($get_form_data['host'], FILTER_VALIDATE_IP)) {
                    $runas = 'root';
                    $e_ip = $get_form_data['host'];
                    $ret = $CI->serverScriptHelper->shell("/usr/bin/pam_abl -wH $e_ip", $nfk, $runas, $CI->BX_SESSION['sessionId']);
                }
            }
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            header("Location: /console/ablstatus");
            exit;
        }

        //
        //--- Get 'fail_hosts' information from CCEWrap:
        //

        $runas = 'root';
        $ret = $CI->serverScriptHelper->shell("/usr/bin/pam_abl -v", $nfk, $runas, $CI->BX_SESSION['sessionId']);
        $hostList = explode(PHP_EOL, $nfk);
        $clean_hostlist = array();
        foreach ($hostList as $key => $value) {
            $value = preg_replace('/\s+/', ';', $value);
            $stripper = array();
            $stripper = explode(';', $value);
            // Remove anything we're not interested in:
            if (($stripper[0] != "Reading") && ($stripper[0] != "No") && ($stripper[0] != "Failed") && ($value != "")) {
                $clean_hostlist[] = $value;
            }
        }

        $RecordedHosts = array();
        $auth_types = array('USER', 'HOST', 'BOTH', 'AUTH');
        foreach ($clean_hostlist as $key => $value) {
            $value = explode(';', $value);
            unset($value[0]);
            if (count($value) != "1") {
                if (count($value) == "2") {
                    // We may have the IP. But check if it is an IP:
                    if (filter_var($value[1], FILTER_VALIDATE_IP)) {
                        $event_IP = $value[1];
                    }
                    else {
                        // $value[1] is not an IP. Could be a hostname? Check it:
                        $resolved_event_ip = @gethostbyname($value[1]);
                        if (filter_var($resolved_event_ip, FILTER_VALIDATE_IP)) {
                            // If we now have an IP, then we use it:
                            $event_IP = $resolved_event_ip;
                        }
                        else {
                            // Still don't have an IP? We give up.
                            $event_IP = "n/a";
                        }
                    }
                    $event_count = $value[2];
                    $RecordedHosts[$event_IP]['failcnt'] = $event_count;
                    $event_num = "1";
                }
                else {
                    $event_service = $value[1];
                    if (in_array($value[2], $auth_types)) {
                        $event_user = "n/a";
                        $event_type = $value[2];
                        unset($value[1]);
                        unset($value[2]);
                    }
                    else {
                        $event_user = $value[2];
                        $event_type = $value[3];
                        unset($value[1]);
                        unset($value[2]);
                        unset($value[3]);
                    }
                    $event_date = implode(" ", $value);
                    $RecordedHosts[$event_IP]['event'][$event_num] = array('event_service' => $event_service, 'event_user' => $event_user, 'event_type' => $event_type, 'event_date' => $event_date);
                    $event_num++;
                }
            }
        }

        //
        //-- Generate page:
        //

        // Prepare Page:
        $factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-console", "/console/ablstatus");
        $BxPage = $factory->getPage();
        $BxPage->setErrors($errors);
        $i18n = $factory->getI18n();

        $product = new Product($CI->cceClient);

        // Set Menu items:
        $BxPage->setVerticalMenu('base_security');
        $BxPage->setVerticalMenuChild('pam_abl_status');
        $page_module = 'base_sysmanage';

        $defaultPage = "blocked_hosts";

        $block =& $factory->getPagedBlock("pam_abl_blocked_users_and_hosts", array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- TAB: blocked_hosts
        //

        $scrollList = $factory->getScrollList("pam_abl_blocked_hosts", array("host_ip", "host_fqdn", "events", "whois", "failcnt", "access", "Action"), array()); 
        $scrollList->setAlignments(array("center", "center", "center", "center", "center", "center", "center"));
        $scrollList->setDefaultSortedIndex('4');
        $scrollList->setSortOrder('descending');
        $scrollList->setSortDisabled(array('2', '3', '5', '6'));
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("180", "180", "75", "75", "75", "75", "75")); // Max: 739px

        // host_rule:
        $CODBDATA = $CI->cceClient->getObject("pam_abl_settings");
        $host_rule_raw = $CODBDATA['host_rule'];
        $hr_diss = explode(':', $host_rule_raw);
        if (!isset($hr_diss[1])) {
            // 'host_rule' in CODB is fubar. Set it to default:
            $attributes['host_rule'] = "*:30/1h";
            $CI->cceClient->setObject("pam_abl_settings", $attributes);

            // Now try it again:
            $CODBDATA = $CI->cceClient->getObject("pam_abl_settings");
            $host_rule_raw = $CODBDATA['host_rule'];
            $hr_diss = explode(':', $host_rule_raw);
        }
        $host_rule = $hr_diss[1];
        $hr_per_hr = explode('/', $host_rule);
        $host_rule_per_hour = $hr_per_hr[0];

        // Populate host table rows with the data:
        foreach ($RecordedHosts as $host => $data) {

            // Events button:
            $events_button = $factory->getFancyButton("/console/events?q=" . $host, "events");
            $events_button->setImageOnly(TRUE);

            // Whois button:
            $whois_button = $factory->getFancyButton("/console/whois?q=" . $host, "whois");
            $whois_button->setImageOnly(TRUE);

            // Remove button:
            $remove_button = $factory->getRemoveButton("/console/ablstatus?host=". $host);
            $remove_button->setImageOnly(TRUE);

            // Access icon:
            $failcnt = str_replace('(', '', $RecordedHosts[$host]['failcnt']);
            $failcnt = str_replace(')', '', $failcnt);
            if ($failcnt >= $host_rule_per_hour) {
                $status = '                 <button class="red tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.Yes]]") . '"><span>' . $i18n->getHtml("[[palette.Yes]]") . '</span></button>';
            }
            else {
                $status = '                 <button class="light tiny text_only has_text tooltip hover" title="' . $i18n->getHtml("[[palette.No]]") . '"><span>' . $i18n->getHtml("[[palette.No]]") . '</span></button>';
            }

            // fqdn:
            $event_fqdn = @gethostbyaddr($host);
            if ($event_fqdn == "") {
                $event_fqdn = "n/a";
            }

            $scrollList->addEntry(array(
                $host,
                "<a href='javascript:void(0)' class='tooltip hover' title=\"$event_fqdn\">". stringshortener($event_fqdn, '35') . "</a>",
                $events_button,
                $whois_button,
                $failcnt,
                $status,
                $remove_button
            ));

        }
        
        // Page selector:
        $ps = "d";

        // Purge buttons:
        $reset_hosts_button = $factory->getButton("/console/ablstatus?action=reset_hosts&ps=$ps", 'reset_hosts_button');
    
        $buttonContainer = $factory->getButtonContainer("pam_abl_blocked_hosts", array($reset_hosts_button));

        $block->addFormField(
            $buttonContainer,
            $factory->getLabel("pam_abl_blocked_hosts"),
            "blocked_hosts"
        );

        $block->addFormField(
            $factory->getRawHTML("pam_abl_blocked_hosts", $scrollList->toHtml()),
            $factory->getLabel("pam_abl_blocked_hosts"),
            "blocked_hosts"
        );

        //
        //--- Add the buttons
        //

        $block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
        $block->addButton($factory->getCancelButton("/console/ablstatus"));

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