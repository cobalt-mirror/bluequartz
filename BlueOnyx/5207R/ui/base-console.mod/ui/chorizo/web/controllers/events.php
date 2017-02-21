<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Events extends MX_Controller {

    /**
     * Index Page for this controller.
     *
     * Past the login page this loads the page for /console/events.
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

        // -- Actual page logic start:

        //
        //--- Handle form validation:
        //

        // We start without any active errors:
        $errors = array();
        $extra_headers =array();
        $ci_errors = array();
        $my_errors = array();

        $get_form_data = $CI->input->get(NULL, TRUE);

        // Get query string:
        if (isset($get_form_data['q'])) {
            $query = $get_form_data['q'];
        }
        else {
            // This is not what we're looking for! Stop poking around!
            // Nice people say goodbye, or CCEd waits forever:
            $CI->cceClient->bye();
            $CI->serverScriptHelper->destructor();
            Log403Error("/gui/Forbidden403#FU");
        }

        //
        //--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
        //

        // Join the various error messages:
        $errors = array_merge($ci_errors, $my_errors);

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
        $BxPage->setVerticalMenu('pam_abl_status');
        $BxPage->setVerticalMenuChild('pam_abl_status');
        $page_module = 'base_sysmanage';

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

        $defaultPage = "blocked_hosts";
        $header_text = $i18n->get("pam_abl_blocked_users_and_hosts") . " (" . $query . ")";
        $block =& $factory->getPagedBlock($header_text, array($defaultPage));

        $block->setToggle("#");
        $block->setSideTabs(FALSE);
        $block->setShowAllTabs("#");
        $block->setDefaultPage($defaultPage);

        //
        //--- TAB: blocked_hosts
        //

        $scrollList = $factory->getScrollList("pam_abl_blocked_hosts", array("service", "user", "type", "date"), array()); 
        $scrollList->setAlignments(array("center", "center", "center", "center"));
        $scrollList->setDefaultSortedIndex('0');
        $scrollList->setSortOrder('ascending');
        $scrollList->setPaginateDisabled(FALSE);
        $scrollList->setSearchDisabled(FALSE);
        $scrollList->setSelectorDisabled(FALSE);
        $scrollList->enableAutoWidth(FALSE);
        $scrollList->setInfoDisabled(FALSE);
        $scrollList->setColumnWidths(array("184", "184", "184", "184")); // Max: 739px

        if (isset($RecordedHosts[$query])) {
            // Populate host table rows with the data:
            foreach ($RecordedHosts[$query]['event'] as $host => $data) {
                $scrollList->addEntry(array(
                    $data['event_service'],
                    $data['event_user'],
                    $data['event_type'],
                    $data['event_date']
                ));
            }
        }

        $block->addFormField(
            $factory->getRawHTML("pam_abl_blocked_hosts", $scrollList->toHtml()),
            $factory->getLabel("pam_abl_blocked_hosts"),
            "blocked_hosts"
        );

        // Page body:
        $page_body[] = $block->toHtml();

        // Out with the page:
        $BxPage->setOutOfStyle(TRUE);
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